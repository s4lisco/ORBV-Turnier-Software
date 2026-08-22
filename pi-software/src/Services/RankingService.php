<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;

/**
 * Rangberechnung inkl. Gleichstand-Handling (docs/turnier-plan.md 5.8):
 * Gleichstand ist erlaubt, der nächste Platz wird trotzdem korrekt
 * weitergezählt (nach zwei 1. Plätzen kommt direkt Platz 3).
 */
final class RankingService
{
    /**
     * @param array<int, float|null> $ergebnisse teilnehmer_id => Gesamtpunktzahl (null = nicht angetreten/-99)
     * @return array<int, array{teilnehmer_id: int, gesamt: ?float, rang: ?int}>
     */
    public function rangliste(array $ergebnisse): array
    {
        $aktive = array_filter($ergebnisse, fn ($v) => $v !== null);
        arsort($aktive);

        $ranked = [];
        $position = 0;
        $vorherigerWert = null;
        $rang = 0;

        foreach ($aktive as $teilnehmerId => $wert) {
            $position++;
            if ($vorherigerWert === null || $wert !== $vorherigerWert) {
                $rang = $position;
            }
            $ranked[] = ['teilnehmer_id' => $teilnehmerId, 'gesamt' => $wert, 'rang' => $rang];
            $vorherigerWert = $wert;
        }

        foreach ($ergebnisse as $teilnehmerId => $wert) {
            if ($wert === null) {
                $ranked[] = ['teilnehmer_id' => $teilnehmerId, 'gesamt' => null, 'rang' => null];
            }
        }

        return $ranked;
    }

    /**
     * Endrangliste für eine Kategorie: ≤6-Fall = Ø(Final1, Final2), ≥7-Fall = nur Finalrunde.
     *
     * @return array<int, array{teilnehmer_id: int, gesamt: ?float, rang: ?int}>
     */
    public function endranglisteFuerKategorie(int $kategorieId): array
    {
        $pdo = Database::connection();
        $roundService = new RoundService();

        // Nur abgeschlossene Finalrunden liefern eine Endrangliste – solange
        // eine Runde noch läuft (nicht alle 5 Richter fertig), gibt es
        // bewusst noch kein Ergebnis (Export überspringt die Kategorie dann).
        $stmt = $pdo->prepare(
            "SELECT id, typ FROM runden WHERE kategorie_id = :kategorie_id AND typ IN ('final1','final2','final') AND status = 'abgeschlossen'"
        );
        $stmt->execute(['kategorie_id' => $kategorieId]);
        $finalRunden = $stmt->fetchAll();

        $byTyp = [];
        foreach ($finalRunden as $r) {
            $byTyp[$r['typ']] = (int) $r['id'];
        }

        if (isset($byTyp['final1']) && isset($byTyp['final2'])) {
            $erg1 = $roundService->ergebnisseFuerRunde($byTyp['final1']);
            $erg2 = $roundService->ergebnisseFuerRunde($byTyp['final2']);

            $kombiniert = [];
            $alleIds = array_unique(array_merge(array_keys($erg1), array_keys($erg2)));
            foreach ($alleIds as $teilnehmerId) {
                $g1 = $erg1[$teilnehmerId] ?? null;
                $g2 = $erg2[$teilnehmerId] ?? null;
                $kombiniert[$teilnehmerId] = ($g1 === null || $g2 === null)
                    ? null
                    : round(($g1 + $g2) / 2, 1);
            }

            return $this->rangliste($kombiniert);
        }

        if (isset($byTyp['final'])) {
            return $this->rangliste($roundService->ergebnisseFuerRunde($byTyp['final']));
        }

        return [];
    }
}
