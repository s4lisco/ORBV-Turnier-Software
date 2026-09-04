<?php

declare(strict_types=1);

use App\Database;
use App\Services\RoundService;

test('Runde starten schlägt fehl, solange nicht alle aktiven Teilnehmer:innen eine Startnummer haben', function () {
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz']);
    $pdo = Database::connection();
    $stmt = $pdo->prepare('INSERT INTO teilnehmer (kategorie_id, typ, name, startnummer, status) VALUES (:kategorie_id, "solo", "Ohne Startnummer", NULL, "aktiv")');
    $stmt->execute(['kategorie_id' => $kategorieId]);

    $fehler = null;
    try {
        (new RoundService())->starteErsteRunde($kategorieId);
    } catch (\RuntimeException $e) {
        $fehler = $e->getMessage();
    }

    assertTrue($fehler !== null, 'Runde starten sollte ohne Startnummer eine Exception werfen');
    assertTrue(str_contains($fehler, 'Startnummern vergeben'));
});

test('<=6 Teilnehmer:innen: erste Runde ist final1, final2 existiert bereits', function () {
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz', 'Ausdruck', 'Technik']);
    orbv_seed_teilnehmer($kategorieId, 5);

    $rundeId = (new RoundService())->starteErsteRunde($kategorieId);

    $stmt = Database::connection()->prepare('SELECT typ, status FROM runden WHERE id = :id');
    $stmt->execute(['id' => $rundeId]);
    $runde = $stmt->fetch();

    assertEquals('final1', $runde['typ']);
    assertEquals('laufend', $runde['status']);
    assertEquals(5, orbv_runde_teilnehmer_anzahl($rundeId));

    $final2Id = orbv_runde_id($kategorieId, 'final2');
    assertTrue($final2Id !== null, 'final2 sollte bereits existieren');
    assertEquals(5, orbv_runde_teilnehmer_anzahl($final2Id));
});

test('<=6 Teilnehmer:innen: Reihenfolge in Final 2 ist gegenüber Final 1 umgekehrt (Plan_Feedback_Umsetzung.md 12.1)', function () {
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz']);
    orbv_seed_teilnehmer($kategorieId, 5);

    $final1Id = (new RoundService())->starteErsteRunde($kategorieId);
    $final2Id = orbv_runde_id($kategorieId, 'final2');

    $pdo = Database::connection();
    $stmt = $pdo->prepare('SELECT teilnehmer_id FROM runde_teilnehmer WHERE runde_id = :id ORDER BY reihenfolge ASC');

    $stmt->execute(['id' => $final1Id]);
    $reihenfolgeFinal1 = array_map('intval', array_column($stmt->fetchAll(), 'teilnehmer_id'));

    $stmt->execute(['id' => $final2Id]);
    $reihenfolgeFinal2 = array_map('intval', array_column($stmt->fetchAll(), 'teilnehmer_id'));

    assertEquals(array_reverse($reihenfolgeFinal1), $reihenfolgeFinal2);
});

test('>=7 Teilnehmer:innen: erste Runde ist Vorrunde', function () {
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz', 'Ausdruck', 'Technik']);
    orbv_seed_teilnehmer($kategorieId, 7);

    $rundeId = (new RoundService())->starteErsteRunde($kategorieId);

    $stmt = Database::connection()->prepare('SELECT typ FROM runden WHERE id = :id');
    $stmt->execute(['id' => $rundeId]);
    assertEquals('vorrunde', $stmt->fetch()['typ']);
    assertEquals(7, orbv_runde_teilnehmer_anzahl($rundeId));
});

function orbv_run_vorrunde_bis_final(int $anzahlTeilnehmer): array
{
    $roundService = new RoundService();
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, $anzahlTeilnehmer);
    $richterIds = orbv_seed_richter();

    $vorrundeId = $roundService->starteErsteRunde($kategorieId);
    $roundService->freigeben($vorrundeId, $teilnehmerIds);

    // Absteigende, eindeutige Punktzahlen -> klare Rangfolge ohne Gleichstand.
    $punkte = 10.0;
    foreach ($teilnehmerIds as $teilnehmerId) {
        orbv_bewerten($vorrundeId, $teilnehmerId, $kategorieId, $richterIds, $punkte);
        $punkte -= 0.1;
    }

    $roundService->rundeAbschliessen($vorrundeId);

    return ['kategorieId' => $kategorieId, 'roundService' => $roundService, 'teilnehmerIds' => $teilnehmerIds, 'richterIds' => $richterIds];
}

test('8 Teilnehmer:innen: Drittel/Hälfte-Aufrundung ergibt 6 Finalist:innen', function () {
    $ctx = orbv_run_vorrunde_bis_final(8);
    $kategorieId = $ctx['kategorieId'];

    // direktAnzahl = ceil(8/3) = 3, Rest = 5 -> Hoffnungsrunde
    $hoffnungsrundeId = orbv_runde_id($kategorieId, 'hoffnungsrunde');
    assertTrue($hoffnungsrundeId !== null);
    assertEquals(5, orbv_runde_teilnehmer_anzahl($hoffnungsrundeId));

    $finalId = orbv_runde_id($kategorieId, 'final');
    assertEquals(3, orbv_runde_teilnehmer_anzahl($finalId));

    // Hoffnungsrunde bewerten und abschliessen: ceil(5/2) = 3 qualifizieren zusätzlich.
    $roundService = $ctx['roundService'];
    $stmt = Database::connection()->prepare('SELECT teilnehmer_id FROM runde_teilnehmer WHERE runde_id = :id');
    $stmt->execute(['id' => $hoffnungsrundeId]);
    $hoffnungsrundeTeilnehmer = array_map('intval', array_column($stmt->fetchAll(), 'teilnehmer_id'));

    $roundService->freigeben($hoffnungsrundeId, $hoffnungsrundeTeilnehmer);
    $punkte = 10.0;
    foreach ($hoffnungsrundeTeilnehmer as $teilnehmerId) {
        orbv_bewerten($hoffnungsrundeId, $teilnehmerId, $kategorieId, $ctx['richterIds'], $punkte);
        $punkte -= 0.1;
    }
    $roundService->rundeAbschliessen($hoffnungsrundeId);

    assertEquals(6, orbv_runde_teilnehmer_anzahl($finalId));
});

test('>=7 Teilnehmer:innen: Hoffnungsrunden-Reihenfolge ist zufällig, nicht identisch zur Vorrunden-Rangfolge (Plan_Feedback_Umsetzung.md 12.2)', function () {
    $abweichungGefunden = false;

    for ($i = 0; $i < 30 && !$abweichungGefunden; $i++) {
        $ctx = orbv_run_vorrunde_bis_final(8);
        $kategorieId = $ctx['kategorieId'];
        $hoffnungsrundeId = orbv_runde_id($kategorieId, 'hoffnungsrunde');

        $stmt = Database::connection()->prepare('SELECT teilnehmer_id FROM runde_teilnehmer WHERE runde_id = :id ORDER BY reihenfolge ASC');
        $stmt->execute(['id' => $hoffnungsrundeId]);
        $reihenfolge = array_map('intval', array_column($stmt->fetchAll(), 'teilnehmer_id'));

        // teilnehmerIds wurden mit absteigenden Punktzahlen in Erfassungsreihenfolge
        // bewertet -> die "unrandomisierte" Rang-Reihenfolge der Hoffnungsrunde wäre
        // exakt array_slice($teilnehmerIds, 3) (die ersten 3 gehen direkt in den Final).
        $unrandomisierteReihenfolge = array_slice($ctx['teilnehmerIds'], 3);

        if ($reihenfolge !== $unrandomisierteReihenfolge) {
            $abweichungGefunden = true;
        }
    }

    assertTrue($abweichungGefunden, 'Nach 30 Versuchen keine einzige zufällige Abweichung von der Rangfolge gefunden.');
});

test('12 Teilnehmer:innen: 4 direkt, 8 in Hoffnungsrunde, 4 qualifizieren nach', function () {
    $ctx = orbv_run_vorrunde_bis_final(12);
    $kategorieId = $ctx['kategorieId'];

    $hoffnungsrundeId = orbv_runde_id($kategorieId, 'hoffnungsrunde');
    assertEquals(8, orbv_runde_teilnehmer_anzahl($hoffnungsrundeId));

    $finalId = orbv_runde_id($kategorieId, 'final');
    assertEquals(4, orbv_runde_teilnehmer_anzahl($finalId));
});
