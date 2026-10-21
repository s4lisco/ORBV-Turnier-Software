<?php

declare(strict_types=1);

use App\Database;
use App\Services\CsvExportService;
use App\Services\RoundService;

test('Rangliste-Export für Upload: nutzt remote_id, überspringt Kategorien ohne remote_id, DNF nicht enthalten', function () {
    $pdo = Database::connection();

    // Kategorie mit remote_id (wie nach einem CSV-Import von turnier.orbv.ch, siehe 8.1b)
    $pdo->prepare('INSERT INTO kategorien (remote_id, name, typ) VALUES (:remote_id, :name, :typ)')
        ->execute(['remote_id' => 42, 'name' => 'Solo Einsteiger', 'typ' => 'solo']);
    $kategorieId = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO kriterien (kategorie_id, name) VALUES (:kategorie_id, :name)')
        ->execute(['kategorie_id' => $kategorieId, 'name' => 'Tanz']);

    // Kategorie ohne remote_id (lokal angelegt, kein Import) -> muss übersprungen werden
    $pdo->prepare('INSERT INTO kategorien (name, typ) VALUES (:name, :typ)')
        ->execute(['name' => 'Lokale Testkategorie', 'typ' => 'solo']);
    $ohneRemoteId = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO kriterien (kategorie_id, name) VALUES (:kategorie_id, :name)')
        ->execute(['kategorie_id' => $ohneRemoteId, 'name' => 'Tanz']);

    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 5);
    orbv_seed_teilnehmer($ohneRemoteId, 5);
    $richterIds = orbv_seed_richter();

    $roundService = new RoundService();
    $rundeId = $roundService->starteErsteRunde($kategorieId);
    $roundService->freigeben($rundeId, $teilnehmerIds);

    $dnfId = $teilnehmerIds[0];
    orbv_bewerten($rundeId, $dnfId, $kategorieId, $richterIds, 0.0);
    $pdo->prepare("UPDATE teilnehmer SET status = 'dnf' WHERE id = :id")->execute(['id' => $dnfId]);

    $punkte = 10.0;
    foreach (array_slice($teilnehmerIds, 1) as $teilnehmerId) {
        orbv_bewerten($rundeId, $teilnehmerId, $kategorieId, $richterIds, $punkte);
        $punkte -= 0.1;
    }
    $roundService->rundeAbschliessen($rundeId);

    $final2Id = orbv_runde_id($kategorieId, 'final2');
    $roundService->freigeben($final2Id, $teilnehmerIds);
    orbv_bewerten($final2Id, $dnfId, $kategorieId, $richterIds, 0.0);
    $punkte = 10.0;
    foreach (array_slice($teilnehmerIds, 1) as $teilnehmerId) {
        orbv_bewerten($final2Id, $teilnehmerId, $kategorieId, $richterIds, $punkte);
        $punkte -= 0.1;
    }
    $roundService->rundeAbschliessen($final2Id);

    $path = (new CsvExportService())->exportRanglisteFuerUpload();
    assertTrue(is_file($path), 'Export-Datei sollte existieren');

    $rows = array_map(fn (string $line) => str_getcsv($line, escape: '\\'), file($path));
    $header = array_shift($rows);
    assertEquals(['kategorie_id', 'kategorie_name', 'rang', 'name', 'verein_name'], $header);

    // Nur die Kategorie mit remote_id liefert Zeilen; DNF erscheint nicht auf
    // der Rangliste (Feedback-Runde 2), also nur die 4 aktiv bewerteten
    // Teilnehmer:innen.
    assertEquals(4, count($rows));

    foreach ($rows as $row) {
        assertEquals('42', $row[0]);
        assertEquals('Solo Einsteiger', $row[1]);
    }

    $raenge = array_column($rows, 2);
    assertTrue(!in_array('DNF', $raenge, true), 'DNF-Zeile sollte nicht vorhanden sein');
    assertTrue(in_array('1', $raenge, true), 'Ein Rang 1 sollte vergeben sein');
});
