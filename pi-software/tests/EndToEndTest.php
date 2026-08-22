<?php

declare(strict_types=1);

use App\Database;
use App\Services\CsvExportService;
use App\Services\CsvImportService;
use App\Services\RankingService;
use App\Services\RoundService;
use App\Services\StartnummerService;

test('Ende-zu-Ende: Import -> Startnummern -> Runden -> Endrangliste -> Export', function () {
    $pdo = Database::connection();

    // 1. Import
    $result = (new CsvImportService())->import(__DIR__ . '/fixtures/beispiel_turnier.csv');
    assertEquals(14, $result['teilnehmer']);
    assertEquals(2, $result['kategorien']);

    // 2. Startnummern
    (new StartnummerService())->vergebenFuerAlleKategorien();
    $ohneStartnummer = (int) $pdo->query('SELECT COUNT(*) AS c FROM teilnehmer WHERE startnummer IS NULL')->fetch()['c'];
    assertEquals(0, $ohneStartnummer);

    $richterIds = orbv_seed_richter();
    $roundService = new RoundService();

    $kategorien = $pdo->query('SELECT id, name FROM kategorien ORDER BY name')->fetchAll();
    $einsteigerId = null;
    $fortgeschrittenId = null;
    foreach ($kategorien as $k) {
        if ($k['name'] === 'Solo Einsteiger') {
            $einsteigerId = (int) $k['id'];
        } else {
            $fortgeschrittenId = (int) $k['id'];
        }
    }

    // 3a. Solo Einsteiger (5 Teilnehmer:innen) -> final1 + final2
    $final1Id = $roundService->starteErsteRunde($einsteigerId);
    orbv_bewerte_ganze_runde($roundService, $final1Id, $einsteigerId, $richterIds);
    $roundService->rundeAbschliessen($final1Id);

    $final2Id = orbv_runde_id($einsteigerId, 'final2');
    orbv_bewerte_ganze_runde($roundService, $final2Id, $einsteigerId, $richterIds);
    $roundService->rundeAbschliessen($final2Id);

    $ranglisteEinsteiger = (new RankingService())->endranglisteFuerKategorie($einsteigerId);
    assertEquals(5, count($ranglisteEinsteiger));
    assertEquals(1, $ranglisteEinsteiger[0]['rang']);

    // 3b. Solo Fortgeschritten (9 Teilnehmer:innen) -> Vorrunde -> Hoffnungsrunde -> Final
    $vorrundeId = $roundService->starteErsteRunde($fortgeschrittenId);
    orbv_bewerte_ganze_runde($roundService, $vorrundeId, $fortgeschrittenId, $richterIds);
    $roundService->rundeAbschliessen($vorrundeId);

    $hoffnungsrundeId = orbv_runde_id($fortgeschrittenId, 'hoffnungsrunde');
    assertTrue($hoffnungsrundeId !== null);
    assertEquals(6, orbv_runde_teilnehmer_anzahl($hoffnungsrundeId));

    $finalId = orbv_runde_id($fortgeschrittenId, 'final');
    assertEquals(3, orbv_runde_teilnehmer_anzahl($finalId));

    orbv_bewerte_ganze_runde($roundService, $hoffnungsrundeId, $fortgeschrittenId, $richterIds);
    $roundService->rundeAbschliessen($hoffnungsrundeId);
    assertEquals(6, orbv_runde_teilnehmer_anzahl($finalId));

    orbv_bewerte_ganze_runde($roundService, $finalId, $fortgeschrittenId, $richterIds);
    $roundService->rundeAbschliessen($finalId);

    $ranglisteFortgeschritten = (new RankingService())->endranglisteFuerKategorie($fortgeschrittenId);
    assertEquals(6, count($ranglisteFortgeschritten));
    assertEquals(1, $ranglisteFortgeschritten[0]['rang']);

    // 4. Export
    $csvPath = (new CsvExportService())->exportAlleKategorien();
    assertTrue(is_file($csvPath), 'Export-Datei sollte existieren');

    $lines = file($csvPath);
    // Kopfzeile + 5 (Einsteiger) + 6 (Fortgeschritten Finalist:innen)
    assertEquals(1 + 5 + 6, count($lines));
});

/** Gibt alle Teilnehmer:innen einer Runde frei und bewertet sie mit absteigenden, eindeutigen Punktzahlen. */
function orbv_bewerte_ganze_runde(RoundService $roundService, int $rundeId, int $kategorieId, array $richterIds): void
{
    $pdo = Database::connection();
    $stmt = $pdo->prepare('SELECT teilnehmer_id FROM runde_teilnehmer WHERE runde_id = :runde_id ORDER BY reihenfolge');
    $stmt->execute(['runde_id' => $rundeId]);
    $teilnehmerIds = array_map('intval', array_column($stmt->fetchAll(), 'teilnehmer_id'));

    $roundService->freigeben($rundeId, $teilnehmerIds);

    $punkte = 10.0;
    foreach ($teilnehmerIds as $teilnehmerId) {
        orbv_bewerten($rundeId, $teilnehmerId, $kategorieId, $richterIds, $punkte);
        $punkte -= 0.1;
    }
}
