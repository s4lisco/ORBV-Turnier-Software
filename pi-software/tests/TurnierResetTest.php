<?php

declare(strict_types=1);

use App\Database;
use App\Services\RoundService;
use App\Services\TurnierResetService;

test('Turnier-Reset: löscht alle Turnierdaten, Admin-Login bleibt erhalten (Feedback-Runde 2)', function () {
    $pdo = Database::connection();

    $pdo->exec("INSERT INTO admin (username, password_hash) VALUES ('org', 'hash')");

    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 5);
    $richterIds = orbv_seed_richter();
    $roundService = new RoundService();

    $rundeId = $roundService->starteErsteRunde($kategorieId);
    orbv_bewerten($rundeId, $teilnehmerIds[0], $kategorieId, $richterIds, 8.0);

    (new TurnierResetService())->alleDatenLoeschen();

    $tabellen = ['kategorien', 'kriterien', 'teilnehmer', 'runden', 'runde_teilnehmer', 'wertungsrichter', 'bewertungen', 'praesenz_status', 'fehler_eintraege'];
    foreach ($tabellen as $tabelle) {
        $anzahl = (int) $pdo->query("SELECT COUNT(*) AS c FROM $tabelle")->fetch()['c'];
        assertEquals(0, $anzahl, "Tabelle $tabelle sollte nach Reset leer sein");
    }

    // Admin-Login bleibt bestehen, damit die Ersteinrichtung nicht erneut
    // durchlaufen werden muss.
    $adminAnzahl = (int) $pdo->query('SELECT COUNT(*) AS c FROM admin')->fetch()['c'];
    assertEquals(1, $adminAnzahl, 'Admin-Login sollte nach Reset weiterhin bestehen');

    // Neue Turnierdaten erhalten trotzdem frische IDs ab 1.
    $neueKategorieId = orbv_seed_kategorie('Solo Test 2', 'solo', ['Tanz']);
    assertEquals(1, $neueKategorieId);
});
