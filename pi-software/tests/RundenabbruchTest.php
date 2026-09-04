<?php

declare(strict_types=1);

use App\Database;
use App\Services\RoundService;

test('Rundenabbruch: <=6 Faelle loeschen final1 UND final2, Kategorie kann neu gestartet werden', function () {
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz']);
    orbv_seed_teilnehmer($kategorieId, 5);
    $roundService = new RoundService();

    $final1Id = $roundService->starteErsteRunde($kategorieId);
    assertTrue($roundService->kannRundeAbbrechen($final1Id));

    $roundService->rundeAbbrechen($final1Id);

    $pdo = Database::connection();
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM runden WHERE kategorie_id = :id');
    $stmt->execute(['id' => $kategorieId]);
    assertEquals(0, (int) $stmt->fetch()['c']);

    // Kategorie ist wieder im Ausgangszustand -> Runde kann neu gestartet werden.
    $neueRundeId = $roundService->starteErsteRunde($kategorieId);
    assertTrue($neueRundeId > 0);
});

test('Rundenabbruch: nicht mehr moeglich, sobald eine Bewertung existiert', function () {
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 5);
    $richterIds = orbv_seed_richter();
    $roundService = new RoundService();

    $rundeId = $roundService->starteErsteRunde($kategorieId);
    orbv_bewerten($rundeId, $teilnehmerIds[0], $kategorieId, [$richterIds[0]], 8.0);

    assertEquals(false, $roundService->kannRundeAbbrechen($rundeId));

    $fehler = null;
    try {
        $roundService->rundeAbbrechen($rundeId);
    } catch (\RuntimeException $e) {
        $fehler = $e->getMessage();
    }

    assertTrue($fehler !== null, 'Abbruch sollte eine Exception werfen');
    assertTrue(str_contains($fehler, 'Bewertung abgegeben'), 'Fehlermeldung sollte den Grund nennen');

    // Runde besteht unverändert weiter.
    $stmt = Database::connection()->prepare('SELECT COUNT(*) AS c FROM runden WHERE id = :id');
    $stmt->execute(['id' => $rundeId]);
    assertEquals(1, (int) $stmt->fetch()['c']);
});

test('Rundenabbruch: nicht moeglich fuer Folgerunden nach bereits abgeschlossener Vorgaenger-Runde', function () {
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 5);
    $richterIds = orbv_seed_richter();
    $roundService = new RoundService();

    $final1Id = $roundService->starteErsteRunde($kategorieId);
    $roundService->freigeben($final1Id, $teilnehmerIds);
    $punkte = 10.0;
    foreach ($teilnehmerIds as $teilnehmerId) {
        orbv_bewerten($final1Id, $teilnehmerId, $kategorieId, $richterIds, $punkte);
        $punkte -= 0.1;
    }
    $roundService->rundeAbschliessen($final1Id);

    $final2Id = orbv_runde_id($kategorieId, 'final2');
    assertEquals(false, $roundService->kannRundeAbbrechen($final2Id));

    $fehler = null;
    try {
        $roundService->rundeAbbrechen($final2Id);
    } catch (\RuntimeException $e) {
        $fehler = $e->getMessage();
    }

    assertTrue($fehler !== null);
    assertTrue(str_contains($fehler, 'bereits eine vorherige Runde abgeschlossen'));
});
