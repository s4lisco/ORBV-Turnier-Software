<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/**
 * Reine Berechnungslogik gemäss docs/turnier-plan.md Abschnitt 5.6:
 * pro Kriterium bester/schlechtester Wert streichen, Ø der übrigen drei,
 * auf 0.1 runden. Kriteriums-Durchschnitte pro Runde aufsummieren.
 */
final class ScoringService
{
    /** @param float[] $fuenfWerte genau 5 Richterwerte für ein Kriterium */
    public function berechneKriteriumsDurchschnitt(array $fuenfWerte): float
    {
        if (count($fuenfWerte) !== 5) {
            throw new InvalidArgumentException('Es müssen genau 5 Richterwerte vorliegen.');
        }

        $sortiert = $fuenfWerte;
        sort($sortiert);

        $mittlere = array_slice($sortiert, 1, 3);

        return round(array_sum($mittlere) / 3, 1);
    }

    /** @param float[] $kriteriumsDurchschnitte bereits gerundete Kriteriums-Durchschnitte */
    public function berechneRundenGesamt(array $kriteriumsDurchschnitte): float
    {
        return round(array_sum($kriteriumsDurchschnitte), 1);
    }
}
