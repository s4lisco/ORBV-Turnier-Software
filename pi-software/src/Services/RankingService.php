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
     * @param array<int, float|null> $ergebnisse teilnehmer_id => Gesamtpunktzahl (null = DNF, "nicht getanzt")
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
            $kriterien1 = $roundService->kriteriumsErgebnisseFuerRunde($byTyp['final1']);
            $kriterien2 = $roundService->kriteriumsErgebnisseFuerRunde($byTyp['final2']);

            $kombiniert = [];
            $alleIds = array_unique(array_merge(array_keys($erg1), array_keys($erg2)));
            foreach ($alleIds as $teilnehmerId) {
                $g1 = $erg1[$teilnehmerId] ?? null;
                $g2 = $erg2[$teilnehmerId] ?? null;
                $kombiniert[$teilnehmerId] = ($g1 === null || $g2 === null)
                    ? null
                    : round(($g1 + $g2) / 2, 1);
            }

            $rangliste = $this->rangliste($kombiniert);
            foreach ($rangliste as &$eintrag) {
                $eintrag['kriterien'] = $this->kombiniereKriterien(
                    $kriterien1[$eintrag['teilnehmer_id']] ?? [],
                    $kriterien2[$eintrag['teilnehmer_id']] ?? []
                );
            }
            unset($eintrag);

            return $rangliste;
        }

        if (isset($byTyp['final'])) {
            $kriterienFinal = $roundService->kriteriumsErgebnisseFuerRunde($byTyp['final']);
            $finalRangliste = $this->rangliste($roundService->ergebnisseFuerRunde($byTyp['final']));
            foreach ($finalRangliste as &$eintrag) {
                $eintrag['kriterien'] = $kriterienFinal[$eintrag['teilnehmer_id']] ?? [];
            }
            unset($eintrag);

            $ausgeschiedene = $this->hoffnungsrundeAusgeschiedene($kategorieId, $byTyp['final'], $roundService);

            // Hoffnungsrunden-Ausgeschiedene stehen zuunterst, mit fortlaufenden
            // Rangnummern nach der Finalrangliste (Plan_Feedback_Umsetzung.md 12.4).
            $offset = count($finalRangliste);
            foreach ($ausgeschiedene as &$eintrag) {
                if ($eintrag['rang'] !== null) {
                    $eintrag['rang'] += $offset;
                }
            }
            unset($eintrag);

            return array_merge($finalRangliste, $ausgeschiedene);
        }

        return [];
    }

    /**
     * Teilnehmer:innen, die in der Hoffnungsrunde nicht für den Final
     * qualifiziert haben, sortiert nach ihrer Hoffnungsrunden-Punktzahl
     * (bestes Ergebnis zuerst) – siehe Plan_Feedback_Umsetzung.md 12.4.
     *
     * @return array<int, array{teilnehmer_id: int, gesamt: ?float, rang: ?int}>
     */
    private function hoffnungsrundeAusgeschiedene(int $kategorieId, int $finalId, RoundService $roundService): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            "SELECT id FROM runden WHERE kategorie_id = :kategorie_id AND typ = 'hoffnungsrunde' AND status = 'abgeschlossen'"
        );
        $stmt->execute(['kategorie_id' => $kategorieId]);
        $hoffnungsrunde = $stmt->fetch();

        if (!$hoffnungsrunde) {
            return [];
        }

        $finalTeilnehmerIds = array_map(
            fn ($t) => (int) $t['id'],
            $roundService->alleTeilnehmerFuerRunde($finalId)
        );

        $ergebnisseHoffnungsrunde = $roundService->ergebnisseFuerRunde((int) $hoffnungsrunde['id']);
        $kriterienHoffnungsrunde = $roundService->kriteriumsErgebnisseFuerRunde((int) $hoffnungsrunde['id']);
        $ausgeschiedeneErgebnisse = array_filter(
            $ergebnisseHoffnungsrunde,
            fn ($wert, $teilnehmerId) => !in_array($teilnehmerId, $finalTeilnehmerIds, true),
            ARRAY_FILTER_USE_BOTH
        );

        $rangliste = $this->rangliste($ausgeschiedeneErgebnisse);
        foreach ($rangliste as &$eintrag) {
            $eintrag['kriterien'] = $kriterienHoffnungsrunde[$eintrag['teilnehmer_id']] ?? [];
        }
        unset($eintrag);

        return $rangliste;
    }

    /**
     * Kombiniert die Kriteriums-Durchschnitte aus zwei Runden (Final1/Final2)
     * zum Mittelwert je Kriterium, analog zur Gesamtpunktzahl-Kombination.
     *
     * @param array<string, ?float> $k1
     * @param array<string, ?float> $k2
     * @return array<string, ?float>
     */
    private function kombiniereKriterien(array $k1, array $k2): array
    {
        $kombiniert = [];
        foreach (array_unique(array_merge(array_keys($k1), array_keys($k2))) as $name) {
            $w1 = $k1[$name] ?? null;
            $w2 = $k2[$name] ?? null;
            $kombiniert[$name] = ($w1 === null || $w2 === null) ? null : round(($w1 + $w2) / 2, 1);
        }

        return $kombiniert;
    }
}
