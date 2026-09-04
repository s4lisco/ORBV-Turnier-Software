<?php

declare(strict_types=1);

use App\Database;
use App\Services\RankingService;
use App\Services\RoundService;

test('Endrangliste: Hoffnungsrunden-Ausgeschiedene zuunterst, sortiert nach Hoffnungsrunden-Punktzahl (Plan_Feedback_Umsetzung.md 12.4)', function () {
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 9);
    $richterIds = orbv_seed_richter();
    $roundService = new RoundService();

    $vorrundeId = $roundService->starteErsteRunde($kategorieId);
    $roundService->freigeben($vorrundeId, $teilnehmerIds);
    $punkte = 10.0;
    foreach ($teilnehmerIds as $teilnehmerId) {
        orbv_bewerten($vorrundeId, $teilnehmerId, $kategorieId, $richterIds, $punkte);
        $punkte -= 0.1;
    }
    $roundService->rundeAbschliessen($vorrundeId);

    // direktAnzahl = ceil(9/3) = 3 direkt in den Final, 6 in die Hoffnungsrunde,
    // davon qualifyAnzahl = ceil(6/2) = 3 zusätzlich in den Final, 3 ausgeschieden.
    $hoffnungsrundeId = orbv_runde_id($kategorieId, 'hoffnungsrunde');
    $stmt = Database::connection()->prepare('SELECT teilnehmer_id FROM runde_teilnehmer WHERE runde_id = :id');
    $stmt->execute(['id' => $hoffnungsrundeId]);
    $hoffnungsrundeTeilnehmer = array_map('intval', array_column($stmt->fetchAll(), 'teilnehmer_id'));
    assertEquals(6, count($hoffnungsrundeTeilnehmer));

    $roundService->freigeben($hoffnungsrundeId, $hoffnungsrundeTeilnehmer);

    // Bewusst NICHT in Reihenfolge der Teilnehmerliste bewerten, um sicherzustellen,
    // dass die Endrangliste wirklich nach Punktzahl sortiert und nicht nur die
    // Bewertungsreihenfolge übernimmt.
    $manuellePunkte = [10.0, 7.5, 9.0, 6.0, 8.5, 5.5];
    foreach ($hoffnungsrundeTeilnehmer as $i => $teilnehmerId) {
        orbv_bewerten($hoffnungsrundeId, $teilnehmerId, $kategorieId, $richterIds, $manuellePunkte[$i]);
    }
    $roundService->rundeAbschliessen($hoffnungsrundeId);

    // Erwartete Hoffnungsrunden-Rangfolge (bestes zuerst): Index 0 (10), 2 (9), 4 (8.5) qualifizieren;
    // Index 1 (7.5), 3 (6), 5 (5.5) scheiden aus, in dieser Reihenfolge (bestes zuerst).
    $erwarteteAusgeschiedeneReihenfolge = [
        $hoffnungsrundeTeilnehmer[1],
        $hoffnungsrundeTeilnehmer[3],
        $hoffnungsrundeTeilnehmer[5],
    ];

    $finalId = orbv_runde_id($kategorieId, 'final');
    $roundService->freigeben($finalId, array_column(
        (new RoundService())->alleTeilnehmerFuerRunde($finalId),
        'id'
    ));
    $punkte = 10.0;
    $stmt = Database::connection()->prepare('SELECT teilnehmer_id FROM runde_teilnehmer WHERE runde_id = :id ORDER BY reihenfolge');
    $stmt->execute(['id' => $finalId]);
    foreach (array_map('intval', array_column($stmt->fetchAll(), 'teilnehmer_id')) as $teilnehmerId) {
        orbv_bewerten($finalId, $teilnehmerId, $kategorieId, $richterIds, $punkte);
        $punkte -= 0.1;
    }
    $roundService->rundeAbschliessen($finalId);

    $rangliste = (new RankingService())->endranglisteFuerKategorie($kategorieId);
    assertEquals(9, count($rangliste));

    $ausgeschiedeneEintraege = array_slice($rangliste, 6);
    assertEquals($erwarteteAusgeschiedeneReihenfolge, array_column($ausgeschiedeneEintraege, 'teilnehmer_id'));
    assertEquals([7, 8, 9], array_column($ausgeschiedeneEintraege, 'rang'));
});
