<?php

declare(strict_types=1);

use App\Database;
use App\Services\RankingService;
use App\Services\RoundService;

/** Bewertet eine/n Teilnehmer:in mit frei wählbaren, pro Kriterium unterschiedlichen Punktzahlen (uniform über alle 5 Richter). */
function orbv_bewerten_je_kriterium(int $rundeId, int $teilnehmerId, int $kategorieId, array $richterIds, array $punkteProKriteriumsname): void
{
    $pdo = Database::connection();
    $kStmt = $pdo->prepare('SELECT id, name FROM kriterien WHERE kategorie_id = :kategorie_id');
    $kStmt->execute(['kategorie_id' => $kategorieId]);

    $insert = $pdo->prepare(
        'INSERT INTO bewertungen (runde_id, teilnehmer_id, wertungsrichter_id, kriterium_id, punkte)
         VALUES (:runde_id, :teilnehmer_id, :wertungsrichter_id, :kriterium_id, :punkte)'
    );

    foreach ($kStmt->fetchAll() as $kriterium) {
        $punkte = $punkteProKriteriumsname[$kriterium['name']];
        foreach ($richterIds as $richterId) {
            $insert->execute([
                'runde_id' => $rundeId,
                'teilnehmer_id' => $teilnehmerId,
                'wertungsrichter_id' => $richterId,
                'kriterium_id' => $kriterium['id'],
                'punkte' => $punkte,
            ]);
        }
    }
}

/** Fügt einem Richter mehrere Fehler-Einträge für eine/n Teilnehmer:in hinzu. */
function orbv_fehler_hinzufuegen(int $rundeId, int $teilnehmerId, int $richterId, array $werte): void
{
    $insert = Database::connection()->prepare(
        'INSERT INTO fehler_eintraege (runde_id, teilnehmer_id, wertungsrichter_id, wert)
         VALUES (:runde_id, :teilnehmer_id, :wertungsrichter_id, :wert)'
    );
    foreach ($werte as $wert) {
        $insert->execute([
            'runde_id' => $rundeId,
            'teilnehmer_id' => $teilnehmerId,
            'wertungsrichter_id' => $richterId,
            'wert' => $wert,
        ]);
    }
}

test('Fehler-Feld: Minuspunkte werden wie ein Kriterium verrechnet (bester/schlechtester Richterwert gestrichen)', function () {
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 2);
    $richterIds = orbv_seed_richter();
    $roundService = new RoundService();

    $final1Id = $roundService->starteErsteRunde($kategorieId);
    orbv_bewerten($final1Id, $teilnehmerIds[0], $kategorieId, $richterIds, 10.0);
    orbv_bewerten($final1Id, $teilnehmerIds[1], $kategorieId, $richterIds, 10.0);

    // Richterwerte -1,-1,-1,-5,-10 -> sortiert [-10,-5,-1,-1,-1], bester (-1)
    // und schlechtester (-10) gestrichen, Rest [-5,-1,-1] -> Ø -2.3.
    $fehlerWerte = [-1.0, -1.0, -1.0, -5.0, -10.0];
    foreach ($richterIds as $i => $richterId) {
        orbv_fehler_hinzufuegen($final1Id, $teilnehmerIds[0], $richterId, [$fehlerWerte[$i]]);
    }

    $kriterien = $roundService->kriteriumsErgebnisseFuerRunde($final1Id);
    assertEqualsFloat(-2.3, $kriterien[$teilnehmerIds[0]]['Fehler']);
    assertEqualsFloat(0.0, $kriterien[$teilnehmerIds[1]]['Fehler'], 'Ohne Fehler-Einträge ist der Abzug 0.');

    $ergebnisse = $roundService->ergebnisseFuerRunde($final1Id);
    assertEqualsFloat(7.7, $ergebnisse[$teilnehmerIds[0]], 'Gesamt = 10.0 Kriterium + (-2.3) Fehler-Durchschnitt.');
    assertEqualsFloat(10.0, $ergebnisse[$teilnehmerIds[1]]);
});

test('Fehler-Feld: mehrere Einträge desselben Richters werden zuerst summiert', function () {
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 1);
    $richterIds = orbv_seed_richter();
    $roundService = new RoundService();

    $final1Id = $roundService->starteErsteRunde($kategorieId);
    orbv_bewerten($final1Id, $teilnehmerIds[0], $kategorieId, $richterIds, 10.0);

    // Richter 1 gibt zwei Einträge (-1 und -5 -> Summe -6), alle anderen 0.
    orbv_fehler_hinzufuegen($final1Id, $teilnehmerIds[0], $richterIds[0], [-1.0, -5.0]);

    // Summen pro Richter: [-6, 0, 0, 0, 0] -> sortiert [-6,0,0,0,0], bester (0)
    // und schlechtester (-6) gestrichen, Rest [0,0,0] -> Ø 0.0.
    $kriterien = $roundService->kriteriumsErgebnisseFuerRunde($final1Id);
    assertEqualsFloat(0.0, $kriterien[$teilnehmerIds[0]]['Fehler']);
});

test('Tie-Break Vorrunde: Punktgleichheit an der Aufstiegsgrenze lässt alle Gleichplatzierten aufsteigen', function () {
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 7);
    $richterIds = orbv_seed_richter();
    $roundService = new RoundService();

    $vorrundeId = $roundService->starteErsteRunde($kategorieId);

    // direktAnzahl = ceil(7/3) = 3 (Positionen 0-2), aber Position 3 ist mit
    // Position 2 punktgleich (8.0) -> steigt zusätzlich auf.
    $punkte = [10.0, 9.0, 8.0, 8.0, 7.0, 6.0, 5.0];
    foreach ($teilnehmerIds as $i => $teilnehmerId) {
        orbv_bewerten($vorrundeId, $teilnehmerId, $kategorieId, $richterIds, $punkte[$i]);
    }
    $roundService->rundeAbschliessen($vorrundeId);

    $finalId = orbv_runde_id($kategorieId, 'final');
    $hoffnungsrundeId = orbv_runde_id($kategorieId, 'hoffnungsrunde');

    assertEquals(4, orbv_runde_teilnehmer_anzahl($finalId), 'Vier statt drei direkt im Final wegen Punktgleichheit.');
    assertEquals(3, orbv_runde_teilnehmer_anzahl($hoffnungsrundeId));

    $pdo = Database::connection();
    $stmt = $pdo->prepare('SELECT teilnehmer_id FROM runde_teilnehmer WHERE runde_id = :id');
    $stmt->execute(['id' => $finalId]);
    $finalIds = array_map('intval', array_column($stmt->fetchAll(), 'teilnehmer_id'));
    sort($finalIds);
    $erwartet = array_slice($teilnehmerIds, 0, 4);
    sort($erwartet);
    assertEquals($erwartet, $finalIds);
});

test('Tie-Break Hoffnungsrunde: Punktgleichheit an der Aufstiegsgrenze lässt alle Gleichplatzierten zusätzlich aufsteigen', function () {
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 7);
    $richterIds = orbv_seed_richter();
    $roundService = new RoundService();

    $vorrundeId = $roundService->starteErsteRunde($kategorieId);
    // Keine Punktgleichheit in der Vorrunde: direktAnzahl = ceil(7/3) = 3
    // direkt in den Final (Index 0-2), 4 in die Hoffnungsrunde (Index 3-6).
    $punkte = [10.0, 9.0, 8.0, 7.0, 6.0, 5.0, 4.0];
    foreach ($teilnehmerIds as $i => $teilnehmerId) {
        orbv_bewerten($vorrundeId, $teilnehmerId, $kategorieId, $richterIds, $punkte[$i]);
    }
    $roundService->rundeAbschliessen($vorrundeId);

    $finalId = orbv_runde_id($kategorieId, 'final');
    $hoffnungsrundeId = orbv_runde_id($kategorieId, 'hoffnungsrunde');
    assertEquals(3, orbv_runde_teilnehmer_anzahl($finalId));
    assertEquals(4, orbv_runde_teilnehmer_anzahl($hoffnungsrundeId));

    $pdo = Database::connection();
    $stmt = $pdo->prepare('SELECT teilnehmer_id FROM runde_teilnehmer WHERE runde_id = :id');
    $stmt->execute(['id' => $hoffnungsrundeId]);
    $hoffnungsrundeTeilnehmer = array_map('intval', array_column($stmt->fetchAll(), 'teilnehmer_id'));

    // qualifyAnzahl = ceil(4/2) = 2, aber Positionen 2 und 3 sind ebenfalls mit
    // Position 1 punktgleich (8.0) -> alle 4 steigen zusätzlich auf.
    $hoffnungsrundenPunkte = [10.0, 8.0, 8.0, 8.0];
    foreach ($hoffnungsrundeTeilnehmer as $i => $teilnehmerId) {
        orbv_bewerten($hoffnungsrundeId, $teilnehmerId, $kategorieId, $richterIds, $hoffnungsrundenPunkte[$i]);
    }
    $roundService->rundeAbschliessen($hoffnungsrundeId);

    // 3 ursprünglich direkt Qualifizierte + alle 4 aus der Hoffnungsrunde.
    assertEquals(7, orbv_runde_teilnehmer_anzahl($finalId));
});

test('Endrangliste Final1+Final2: Gesamt entspricht der Summe der angezeigten Kriteriumswerte (kein Rundungsdrift durch doppelte Rundung)', function () {
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz', 'Ausdruck', 'Technik']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 2);
    $richterIds = orbv_seed_richter();
    $roundService = new RoundService();

    $final1Id = $roundService->starteErsteRunde($kategorieId);
    $final2Id = orbv_runde_id($kategorieId, 'final2');

    // Bewusst Werte, bei denen die paarweisen Kriteriums-Durchschnitte auf
    // X.X5 landen (Rundung nötig) -> klassischer Fall für Rundungsdrift
    // zwischen "Summe runden" und "Summanden runden, dann summieren".
    orbv_bewerten_je_kriterium($final1Id, $teilnehmerIds[0], $kategorieId, $richterIds, [
        'Tanz' => 5.1, 'Ausdruck' => 5.8, 'Technik' => 6.8,
    ]);
    orbv_bewerten_je_kriterium($final1Id, $teilnehmerIds[1], $kategorieId, $richterIds, [
        'Tanz' => 4.0, 'Ausdruck' => 4.0, 'Technik' => 4.0,
    ]);
    $roundService->rundeAbschliessen($final1Id);

    $roundService->freigeben($final2Id, $teilnehmerIds);
    orbv_bewerten_je_kriterium($final2Id, $teilnehmerIds[0], $kategorieId, $richterIds, [
        'Tanz' => 5.4, 'Ausdruck' => 6.1, 'Technik' => 7.1,
    ]);
    orbv_bewerten_je_kriterium($final2Id, $teilnehmerIds[1], $kategorieId, $richterIds, [
        'Tanz' => 4.0, 'Ausdruck' => 4.0, 'Technik' => 4.0,
    ]);
    $roundService->rundeAbschliessen($final2Id);

    $rangliste = (new RankingService())->endranglisteFuerKategorie($kategorieId);
    foreach ($rangliste as $eintrag) {
        assertEqualsFloat(
            round(array_sum($eintrag['kriterien']), 1),
            $eintrag['gesamt'],
            'Summe der angezeigten Kriteriumswerte muss dem angezeigten Gesamt entsprechen'
        );
    }
});
