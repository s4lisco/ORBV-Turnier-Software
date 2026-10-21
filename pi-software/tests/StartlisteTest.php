<?php

declare(strict_types=1);

use App\Services\RoundService;

test('tanztPaarweise: Solo/Paartanz Einsteiger paarweise, andere Kategorien nicht', function () {
    $roundService = new RoundService();

    assertTrue($roundService->tanztPaarweise('solo', 'Solo Dance Einsteiger'));
    assertTrue($roundService->tanztPaarweise('paar', 'Paartanz Einsteiger'));
    // Groß-/Kleinschreibung und leicht abweichender Name (Admin-Umbenennung) spielen keine Rolle.
    assertTrue($roundService->tanztPaarweise('solo', 'solo dance einsteiger (halle 2)'));

    assertEquals(false, $roundService->tanztPaarweise('solo', 'Solo Dance Mittel'));
    assertEquals(false, $roundService->tanztPaarweise('paar', 'Paartanz Pro'));
    assertEquals(false, $roundService->tanztPaarweise('formation', 'Formation Einsteiger'));
});

test('alleTeilnehmerFuerRunde: liefert vollständige Startliste, unabhängig vom Freigabe-Status', function () {
    $kategorieId = orbv_seed_kategorie('Solo Dance Einsteiger', 'solo', ['Tanz']);
    $teilnehmerIds = orbv_seed_teilnehmer($kategorieId, 4);

    $roundService = new RoundService();
    $rundeId = $roundService->starteErsteRunde($kategorieId);

    // Nur die Hälfte freigeben: alleTeilnehmerFuerRunde muss trotzdem alle 4 liefern.
    $roundService->freigeben($rundeId, [$teilnehmerIds[0]]);

    $startliste = $roundService->alleTeilnehmerFuerRunde($rundeId);
    assertEquals(4, count($startliste));
    // Reihenfolge ist seit Feedback-Runde 2 immer zufällig, daher nur Mengen-
    // gleichheit prüfen statt exakter Reihenfolge.
    $sortiert = array_column($startliste, 'id');
    sort($sortiert);
    $erwartet = $teilnehmerIds;
    sort($erwartet);
    assertEquals($erwartet, $sortiert);

    $gruppen = array_chunk($startliste, 2);
    assertEquals(2, count($gruppen));
});
