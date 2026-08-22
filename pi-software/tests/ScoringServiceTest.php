<?php

declare(strict_types=1);

use App\Services\ScoringService;

test('Kriteriums-Durchschnitt streicht bestes/schlechtestes und rundet auf 0.1', function () {
    $service = new ScoringService();
    assertEqualsFloat(6.7, $service->berechneKriteriumsDurchschnitt([6, 4, 8, 10, 6]));
    assertEqualsFloat(7.3, $service->berechneKriteriumsDurchschnitt([7, 7, 8, 9, 6]));
    assertEqualsFloat(6.3, $service->berechneKriteriumsDurchschnitt([3, 5, 7, 7, 8]));
});

test('Rechenbeispiel aus docs/turnier-plan.md 5.6 ergibt Gesamt 20.3', function () {
    $service = new ScoringService();

    $tanz = $service->berechneKriteriumsDurchschnitt([6, 4, 8, 10, 6]);
    $ausdruck = $service->berechneKriteriumsDurchschnitt([7, 7, 8, 9, 6]);
    $technik = $service->berechneKriteriumsDurchschnitt([3, 5, 7, 7, 8]);

    $gesamt = $service->berechneRundenGesamt([$tanz, $ausdruck, $technik]);

    assertEqualsFloat(20.3, $gesamt);
});

test('Bei identischen Werten wird trotzdem je ein Wert gestrichen', function () {
    $service = new ScoringService();
    // sortiert [5,5,5,5,5] -> nach Streichen von Position 0 und 4: [5,5,5] -> Ø 5.0
    assertEqualsFloat(5.0, $service->berechneKriteriumsDurchschnitt([5, 5, 5, 5, 5]));
});
