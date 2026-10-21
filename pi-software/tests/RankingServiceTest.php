<?php

declare(strict_types=1);

use App\Services\RankingService;

test('Gleichstand: zwei 1. Plätze, danach direkt Platz 3', function () {
    $service = new RankingService();

    $ergebnisse = [
        1 => 20.0,
        2 => 20.0,
        3 => 18.0,
        4 => 15.0,
    ];

    $rangliste = $service->rangliste($ergebnisse);

    $rangByTeilnehmer = [];
    foreach ($rangliste as $eintrag) {
        $rangByTeilnehmer[$eintrag['teilnehmer_id']] = $eintrag['rang'];
    }

    assertEquals(1, $rangByTeilnehmer[1]);
    assertEquals(1, $rangByTeilnehmer[2]);
    assertEquals(3, $rangByTeilnehmer[3]);
    assertEquals(4, $rangByTeilnehmer[4]);
});

test('DNF-Teilnehmer:innen (null) erscheinen gar nicht auf der Rangliste (Feedback-Runde 2)', function () {
    $service = new RankingService();

    $ergebnisse = [
        1 => 20.0,
        2 => null,
        3 => 18.0,
    ];

    $rangliste = $service->rangliste($ergebnisse);

    assertEquals(2, count($rangliste));
    assertEquals(1, $rangliste[0]['teilnehmer_id']);
    assertEquals(1, $rangliste[0]['rang']);
    assertEquals(3, $rangliste[1]['teilnehmer_id']);
    assertEquals(2, $rangliste[1]['rang']);
});
