<?php

declare(strict_types=1);

use App\Database;
use App\Services\RankingService;
use App\Services\RoundService;
use App\Services\StartnummerService;

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
    $roundService->freigeben($vorrundeId, $teilnehmerIds);

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
    $roundService->freigeben($vorrundeId, $teilnehmerIds);
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
    $roundService->freigeben($hoffnungsrundeId, $hoffnungsrundeTeilnehmer);

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
    $roundService->freigeben($final1Id, $teilnehmerIds);

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

test('Startnummern-Sperrliste: gesperrte Nummern werden bei der Vergabe übersprungen', function () {
    $pdo = Database::connection();
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 4);

    $pdo->exec('INSERT INTO startnummern_sperrliste (nummer) VALUES (2)');
    $pdo->exec('INSERT INTO startnummern_sperrliste (nummer) VALUES (3)');

    (new StartnummerService())->vergeben();

    $stmt = $pdo->prepare('SELECT startnummer FROM teilnehmer WHERE id IN (' . implode(',', $teilnehmerIds) . ') ORDER BY id ASC');
    $stmt->execute();
    $vergebeneNummern = array_map('intval', array_column($stmt->fetchAll(), 'startnummer'));

    // 1, 2, 3 gesperrt -> übersprungen -> 1, 4, 5, 6 werden vergeben.
    assertEquals([1, 4, 5, 6], $vergebeneNummern);
    foreach ([2, 3] as $gesperrteNummer) {
        assertTrue(!in_array($gesperrteNummer, $vergebeneNummern, true), "Nummer $gesperrteNummer ist gesperrt und darf nicht vergeben werden");
    }
});

test('Startnummern-Sperrliste: entfernte Nummer kann danach wieder vergeben werden', function () {
    $pdo = Database::connection();
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 2);

    $pdo->exec('INSERT INTO startnummern_sperrliste (nummer) VALUES (1)');
    $pdo->prepare('DELETE FROM startnummern_sperrliste WHERE nummer = :nummer')->execute(['nummer' => 1]);

    (new StartnummerService())->vergeben();

    $stmt = $pdo->prepare('SELECT startnummer FROM teilnehmer WHERE id IN (' . implode(',', $teilnehmerIds) . ') ORDER BY id ASC');
    $stmt->execute();
    $vergebeneNummern = array_map('intval', array_column($stmt->fetchAll(), 'startnummer'));

    assertEquals([1, 2], $vergebeneNummern);
});

test('Startnummern-Sperrliste: vergeben() lässt bereits vergebene (auch nachträglich gesperrte) Nummern unangetastet', function () {
    $pdo = Database::connection();
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 3);
    $startnummerService = new StartnummerService();

    $startnummerService->vergeben();

    // Nachträglich gesperrt – vergeben() selbst darf daran nichts ändern,
    // das übernimmt erst "Neu mischen" (ersetzeGesperrteNummernFuerKategorie()).
    $pdo->exec('INSERT INTO startnummern_sperrliste (nummer) VALUES (2)');
    $startnummerService->vergeben();

    $stmt = $pdo->prepare('SELECT startnummer FROM teilnehmer WHERE id IN (' . implode(',', $teilnehmerIds) . ') ORDER BY id ASC');
    $stmt->execute();
    $nummern = array_map('intval', array_column($stmt->fetchAll(), 'startnummer'));

    assertEquals([1, 2, 3], $nummern);
});

test('"Neu mischen" entzieht eine inzwischen gesperrte Startnummer und ersetzt sie', function () {
    $pdo = Database::connection();
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 3);
    $roundService = new RoundService();

    (new StartnummerService())->vergeben();
    $stmt = $pdo->prepare('SELECT id, startnummer FROM teilnehmer WHERE id IN (' . implode(',', $teilnehmerIds) . ')');
    $stmt->execute();
    $vorher = [];
    foreach ($stmt->fetchAll() as $t) {
        $vorher[(int) $t['startnummer']] = (int) $t['id'];
    }
    assertEquals([1, 2, 3], array_keys($vorher));
    $teilnehmerMitNummer2 = $vorher[2];

    // Nummer 2 ist bereits vergeben, wird aber trotzdem gesperrt (Sperren ist
    // immer möglich) - erst beim nächsten "Neu mischen" wird sie frei.
    $pdo->exec('INSERT INTO startnummern_sperrliste (nummer) VALUES (2)');
    $roundService->mischeKategorieStartreihenfolge($kategorieId);

    $stmt = $pdo->prepare('SELECT startnummer FROM teilnehmer WHERE id = :id');
    $stmt->execute(['id' => $teilnehmerMitNummer2]);
    $neueNummer = (int) $stmt->fetch()['startnummer'];

    assertTrue($neueNummer !== 2, 'Nummer 2 darf nach dem Sperren nicht mehr gehalten werden');
    assertEquals(4, $neueNummer, 'wird mit der nächsten freien, nicht gesperrten Nummer neu versehen');

    // Nummer 2 bleibt unbenutzt, da weiterhin gesperrt.
    $stmt = $pdo->query('SELECT COUNT(*) AS c FROM teilnehmer WHERE startnummer = 2');
    assertEquals(0, (int) $stmt->fetch()['c']);

    // Die anderen beiden behalten ihre Nummer unverändert.
    $stmt = $pdo->prepare('SELECT startnummer FROM teilnehmer WHERE id IN (' . implode(',', array_diff($teilnehmerIds, [$teilnehmerMitNummer2])) . ') ORDER BY id ASC');
    $stmt->execute();
    assertEquals([1, 3], array_map('intval', array_column($stmt->fetchAll(), 'startnummer')));
});

test('"Neu mischen" bei einer bereits angelegten, noch nicht gestarteten Folgerunde (final2) mischt die Reihenfolge', function () {
    $pdo = Database::connection();
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 4);
    $roundService = new RoundService();

    $roundService->starteErsteRunde($kategorieId);
    $final2Id = orbv_runde_id($kategorieId, 'final2');

    $roundService->mischeStartliste($final2Id);

    $stmt = $pdo->prepare('SELECT teilnehmer_id FROM runde_teilnehmer WHERE runde_id = :runde_id ORDER BY reihenfolge ASC');
    $stmt->execute(['runde_id' => $final2Id]);
    $nachher = array_map('intval', array_column($stmt->fetchAll(), 'teilnehmer_id'));

    // Gleiche Teilnehmer:innen wie vorher, nur als Menge verglichen (die
    // konkrete Reihenfolge ist bei jedem Lauf zufällig).
    sort($nachher);
    $erwartet = $teilnehmerIds;
    sort($erwartet);
    assertEquals($erwartet, $nachher);
});

test('"Neu mischen" ist nicht mehr möglich, sobald die Runde gestartet wurde', function () {
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz']);
    orbv_seed_teilnehmer($kategorieId, 4);
    $roundService = new RoundService();

    $final1Id = $roundService->starteErsteRunde($kategorieId);

    $fehler = null;
    try {
        $roundService->mischeStartliste($final1Id);
    } catch (\RuntimeException $e) {
        $fehler = $e->getMessage();
    }

    assertTrue($fehler !== null, 'Mischen einer laufenden Runde sollte eine Exception werfen');
    assertTrue(str_contains($fehler, 'vor dem Start'));
});
