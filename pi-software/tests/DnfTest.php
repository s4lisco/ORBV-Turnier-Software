<?php

declare(strict_types=1);

use App\Database;
use App\Services\RankingService;
use App\Services\RoundService;

test('DNF: Teilnehmer:in mit status=dnf erscheint gar nicht auf der Rangliste, andere werden normal gerankt (Feedback-Runde 2)', function () {
    $kategorieId = orbv_seed_kategorie('Solo Einsteiger', 'solo', ['Tanz', 'Ausdruck', 'Technik']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 5);
    $richterIds = orbv_seed_richter();

    $roundService = new RoundService();
    $rundeId = $roundService->starteErsteRunde($kategorieId);

    $roundService->freigeben($rundeId, $teilnehmerIds);

    // Erste Person tanzt nicht: alle 5 Richter drücken "Nicht getanzt" (0 Punkte je Kriterium),
    // die Teilnahme wird intern als dnf markiert (siehe ScoringController::submit).
    $dnfTeilnehmerId = $teilnehmerIds[0];
    orbv_bewerten($rundeId, $dnfTeilnehmerId, $kategorieId, $richterIds, 0.0);
    $pdo = Database::connection();
    $pdo->prepare("UPDATE teilnehmer SET status = 'dnf' WHERE id = :id")->execute(['id' => $dnfTeilnehmerId]);

    $punkte = 10.0;
    foreach (array_slice($teilnehmerIds, 1) as $teilnehmerId) {
        orbv_bewerten($rundeId, $teilnehmerId, $kategorieId, $richterIds, $punkte);
        $punkte -= 0.1;
    }

    assertTrue($roundService->istRundeVollstaendigBewertet($rundeId), 'Runde sollte trotz DNF-Teilnehmer:in vollständig bewertet sein.');

    $roundService->rundeAbschliessen($rundeId);
    $final2Id = orbv_runde_id($kategorieId, 'final2');
    $roundService->freigeben($final2Id, $teilnehmerIds);
    orbv_bewerten($final2Id, $dnfTeilnehmerId, $kategorieId, $richterIds, 0.0);
    $punkte = 10.0;
    foreach (array_slice($teilnehmerIds, 1) as $teilnehmerId) {
        orbv_bewerten($final2Id, $teilnehmerId, $kategorieId, $richterIds, $punkte);
        $punkte -= 0.1;
    }
    $roundService->rundeAbschliessen($final2Id);

    $rangliste = (new RankingService())->endranglisteFuerKategorie($kategorieId);
    assertEquals(4, count($rangliste), 'DNF-Teilnehmer:in erscheint nicht auf der Rangliste.');

    foreach ($rangliste as $eintrag) {
        assertTrue($eintrag['teilnehmer_id'] !== $dnfTeilnehmerId, 'DNF-Teilnehmer:in sollte nicht in der Rangliste vorkommen.');
    }

    assertEquals(1, $rangliste[0]['rang']);
});
