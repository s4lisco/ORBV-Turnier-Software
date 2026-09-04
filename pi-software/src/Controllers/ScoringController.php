<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Services\RoundService;
use App\View;

/**
 * Wertungsrichter-Workflow (Plan_Feedback_Umsetzung.md Phase 11): kein
 * Auswahlbildschirm mehr – die Ansicht zeigt direkt die aktuelle Aufgabe des
 * Richters (bewerten / warten / leerlauf), ermittelt über
 * RoundService::aktuelleAufgabeFuerRichter().
 */
final class ScoringController
{
    public function index(): void
    {
        $judgeId = Auth::requireJudge();
        $this->renderAufgabe((new RoundService())->aktuelleAufgabeFuerRichter($judgeId));
    }

    /** JSON-Endpoint fürs Polling der Warte-/Leerlaufseite (alle 2s, siehe 11.3). */
    public function status(): void
    {
        $judgeId = Auth::requireJudge();
        $aufgabe = (new RoundService())->aktuelleAufgabeFuerRichter($judgeId);

        header('Content-Type: application/json');
        echo json_encode(['status' => $aufgabe['status']]);
    }

    public function submit(string $rundeId): void
    {
        $judgeId = Auth::requireJudge();
        $roundService = new RoundService();

        $aufgabe = $roundService->aktuelleAufgabeFuerRichter($judgeId);

        // Stand hat sich seit dem Laden des Formulars geändert (z.B. bereits über
        // einen anderen Tab abgesendet) – einfach neu ermitteln lassen statt
        // versehentlich doppelt/falsch zu speichern.
        if ($aufgabe['status'] !== 'bewerten' || (int) $aufgabe['runde_id'] !== (int) $rundeId) {
            header('Location: /kampfrichter/bewertung');
            exit;
        }

        $pdo = Database::connection();
        $erwarteteTeilnehmerIds = array_map(fn ($t) => (int) $t['id'], $aufgabe['teilnehmer']);

        $stmt = $pdo->prepare('SELECT kategorie_id FROM runden WHERE id = :id');
        $stmt->execute(['id' => $rundeId]);
        $kategorieId = $stmt->fetch()['kategorie_id'];

        $stmt = $pdo->prepare('SELECT id FROM kriterien WHERE kategorie_id = :kategorie_id');
        $stmt->execute(['kategorie_id' => $kategorieId]);
        $kriterienIds = array_column($stmt->fetchAll(), 'id');

        $punkteInput = $_POST['punkte'] ?? [];
        $nichtGetanztInput = $_POST['nicht_getanzt'] ?? [];

        $zuSpeichern = [];
        foreach ($erwarteteTeilnehmerIds as $teilnehmerId) {
            $istDnf = ($nichtGetanztInput[$teilnehmerId] ?? '0') === '1';

            if ($istDnf) {
                $zuSpeichern[$teilnehmerId] = array_fill_keys($kriterienIds, 0.0);
                continue;
            }

            $werte = [];
            foreach ($kriterienIds as $kriteriumId) {
                $wert = $punkteInput[$teilnehmerId][$kriteriumId] ?? null;
                if ($wert === null || $wert === '' || !is_numeric($wert) || (float) $wert < 0 || (float) $wert > 10) {
                    $this->renderAufgabe($aufgabe, 'Bitte für jedes Kriterium eine Punktzahl zwischen 0 und 10 eingeben (oder "Nicht getanzt" markieren).');
                    return;
                }
                $werte[$kriteriumId] = (float) $wert;
            }
            $zuSpeichern[$teilnehmerId] = $werte;
        }

        $insert = $pdo->prepare(
            'INSERT INTO bewertungen (runde_id, teilnehmer_id, wertungsrichter_id, kriterium_id, punkte)
             VALUES (:runde_id, :teilnehmer_id, :wertungsrichter_id, :kriterium_id, :punkte)'
        );
        $updateStatus = $pdo->prepare("UPDATE teilnehmer SET status = 'dnf' WHERE id = :id");

        $pdo->beginTransaction();
        foreach ($zuSpeichern as $teilnehmerId => $werte) {
            foreach ($werte as $kriteriumId => $punkte) {
                $insert->execute([
                    'runde_id' => $rundeId,
                    'teilnehmer_id' => $teilnehmerId,
                    'wertungsrichter_id' => $judgeId,
                    'kriterium_id' => $kriteriumId,
                    'punkte' => $punkte,
                ]);
            }
            if (($nichtGetanztInput[$teilnehmerId] ?? '0') === '1') {
                $updateStatus->execute(['id' => $teilnehmerId]);
            }
        }
        $pdo->commit();

        $roundService->pruefeUndFreigebenNaechste((int) $rundeId);

        header('Location: /kampfrichter/bewertung');
        exit;
    }

    /** @param array{status:string, runde_id?:int, teilnehmer?:array, paarweise?:bool} $aufgabe */
    private function renderAufgabe(array $aufgabe, ?string $error = null): void
    {
        match ($aufgabe['status']) {
            'bewerten' => $this->renderBewertungsformular($aufgabe, $error),
            'warten' => View::render('judge/warten', [], 'Warten auf die anderen Richter – ORBV Turnier'),
            default => View::render('judge/leerlauf', [], 'Warte auf nächste Runde – ORBV Turnier'),
        };
    }

    /** @param array{status:string, runde_id?:int, teilnehmer?:array, paarweise?:bool} $aufgabe */
    private function renderBewertungsformular(array $aufgabe, ?string $error): void
    {
        $pdo = Database::connection();
        $rundeId = $aufgabe['runde_id'];

        $stmt = $pdo->prepare(
            'SELECT k.id AS kategorie_id, k.name AS kategorie_name
             FROM runden r JOIN kategorien k ON k.id = r.kategorie_id
             WHERE r.id = :id'
        );
        $stmt->execute(['id' => $rundeId]);
        $kategorie = $stmt->fetch();

        $stmt = $pdo->prepare('SELECT id, name FROM kriterien WHERE kategorie_id = :kategorie_id ORDER BY id');
        $stmt->execute(['kategorie_id' => $kategorie['kategorie_id']]);
        $kriterien = $stmt->fetchAll();

        View::render('judge/scoring_form', [
            'rundeId' => $rundeId,
            'kategorieName' => $kategorie['kategorie_name'],
            'teilnehmer' => $aufgabe['teilnehmer'],
            'paarweise' => $aufgabe['paarweise'],
            'kriterien' => $kriterien,
            'error' => $error,
        ], 'Bewertung – ORBV Turnier');
    }
}
