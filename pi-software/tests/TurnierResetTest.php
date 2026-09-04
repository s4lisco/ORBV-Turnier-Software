<?php

declare(strict_types=1);

use App\Database;
use App\Services\RoundService;
use App\Services\TurnierResetService;

test('Turnier-Reset: löscht alle Daten aus allen Tabellen inkl. Admin-Login', function () {
    $pdo = Database::connection();

    $pdo->exec("INSERT INTO admin (username, password_hash) VALUES ('org', 'hash')");

    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 5);
    $richterIds = orbv_seed_richter();
    $roundService = new RoundService();

    $rundeId = $roundService->starteErsteRunde($kategorieId);
    orbv_bewerten($rundeId, $teilnehmerIds[0], $kategorieId, $richterIds, 8.0);

    (new TurnierResetService())->alleDatenLoeschen();

    $tabellen = ['admin', 'kategorien', 'kriterien', 'teilnehmer', 'runden', 'runde_teilnehmer', 'wertungsrichter', 'bewertungen', 'praesenz_status'];
    foreach ($tabellen as $tabelle) {
        $anzahl = (int) $pdo->query("SELECT COUNT(*) AS c FROM $tabelle")->fetch()['c'];
        assertEquals(0, $anzahl, "Tabelle $tabelle sollte nach Reset leer sein");
    }

    // Nach dem Reset kann ein neues Turnier von vorne aufgesetzt werden (frische IDs ab 1).
    $pdo->exec("INSERT INTO admin (username, password_hash) VALUES ('org2', 'hash2')");
    $neueId = (int) $pdo->lastInsertId();
    assertEquals(1, $neueId);
});
