<?php

declare(strict_types=1);

use App\Services\CsvExportService;
use App\Services\RoundService;

test('CSV-Export bricht nicht ab, wenn eine Runde noch läuft (nicht alle Richter fertig)', function () {
    $kategorieId = orbv_seed_kategorie('Solo Test', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 5);
    $richterIds = orbv_seed_richter();

    $roundService = new RoundService();
    $final1Id = $roundService->starteErsteRunde($kategorieId);
    $roundService->freigeben($final1Id, $teilnehmerIds);

    // Nur 3 von 5 Richtern bewerten Teilnehmer:in 1 -> Runde ist NICHT
    // abgeschlossen, ein Export muss trotzdem funktionieren (Kategorie wird
    // einfach noch nicht in der CSV aufgeführt statt die App abstürzen zu
    // lassen).
    $unvollstaendigeRichter = array_slice($richterIds, 0, 3);
    orbv_bewerten($final1Id, $teilnehmerIds[0], $kategorieId, $unvollstaendigeRichter, 8.0);

    $csvPath = (new CsvExportService())->exportAlleKategorien();
    assertTrue(is_file($csvPath), 'Export-Datei sollte trotz laufender Runde erzeugt werden');

    $lines = file($csvPath);
    // Nur die Kopfzeile, da die einzige Kategorie noch keine abgeschlossene Endrangliste hat.
    assertEquals(1, count($lines));
});
