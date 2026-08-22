<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\View;

final class ScoringController
{
    public function index(): void
    {
        $judgeId = Auth::requireJudge();
        $offene = $this->offeneFuerRichter($judgeId);

        View::render('judge/scoring', ['offene' => $offene], 'Bewertung – ORBV Turnier');
    }

    /** JSON-Endpoint fürs Hintergrund-Polling der Startseite (kein Seiten-Neuladen nötig). */
    public function status(): void
    {
        $judgeId = Auth::requireJudge();
        $offene = $this->offeneFuerRichter($judgeId);

        header('Content-Type: application/json');
        echo json_encode(array_values($offene));
    }

    /** @return array<int, array<string, mixed>> */
    private function offeneFuerRichter(int $judgeId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT p.runde_id, p.teilnehmer_id, t.startnummer, t.name, t.name_p2, k.name AS kategorie_name, r.typ AS runde_typ,
                    (SELECT COUNT(DISTINCT kr.id) FROM kriterien kr WHERE kr.kategorie_id = k.id) AS anzahl_kriterien,
                    (SELECT COUNT(*) FROM bewertungen b
                        WHERE b.runde_id = p.runde_id AND b.teilnehmer_id = p.teilnehmer_id
                          AND b.wertungsrichter_id = :judge_id) AS bereits_bewertet
             FROM praesenz_status p
             JOIN teilnehmer t ON t.id = p.teilnehmer_id
             JOIN runden r ON r.id = p.runde_id
             JOIN kategorien k ON k.id = t.kategorie_id
             WHERE r.status = "laufend"
             ORDER BY k.name, t.startnummer'
        );
        $stmt->execute(['judge_id' => $judgeId]);

        return $stmt->fetchAll();
    }

    public function showForm(string $rundeId, string $teilnehmerId): void
    {
        $judgeId = Auth::requireJudge();
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT t.*, k.name AS kategorie_name FROM teilnehmer t
             JOIN kategorien k ON k.id = t.kategorie_id
             WHERE t.id = :teilnehmer_id'
        );
        $stmt->execute(['teilnehmer_id' => $teilnehmerId]);
        $teilnehmer = $stmt->fetch();

        if (!$teilnehmer) {
            http_response_code(404);
            echo 'Teilnehmer:in nicht gefunden.';
            return;
        }

        $stmt = $pdo->prepare('SELECT id, name FROM kriterien WHERE kategorie_id = :kategorie_id ORDER BY id');
        $stmt->execute(['kategorie_id' => $teilnehmer['kategorie_id']]);
        $kriterien = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS c FROM bewertungen WHERE runde_id = :runde_id AND teilnehmer_id = :teilnehmer_id AND wertungsrichter_id = :judge_id'
        );
        $stmt->execute(['runde_id' => $rundeId, 'teilnehmer_id' => $teilnehmerId, 'judge_id' => $judgeId]);
        $bereitsBewertet = (int) $stmt->fetch()['c'] > 0;

        View::render('judge/scoring_form', [
            'teilnehmer' => $teilnehmer,
            'kriterien' => $kriterien,
            'rundeId' => $rundeId,
            'bereitsBewertet' => $bereitsBewertet,
            'error' => null,
        ], 'Bewertung – ORBV Turnier');
    }

    public function submit(string $rundeId, string $teilnehmerId): void
    {
        $judgeId = Auth::requireJudge();
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS c FROM bewertungen WHERE runde_id = :runde_id AND teilnehmer_id = :teilnehmer_id AND wertungsrichter_id = :judge_id'
        );
        $stmt->execute(['runde_id' => $rundeId, 'teilnehmer_id' => $teilnehmerId, 'judge_id' => $judgeId]);
        if ((int) $stmt->fetch()['c'] > 0) {
            header('Location: /kampfrichter/bewertung');
            exit;
        }

        $stmt = $pdo->prepare('SELECT kategorie_id FROM teilnehmer WHERE id = :id');
        $stmt->execute(['id' => $teilnehmerId]);
        $kategorieId = $stmt->fetch()['kategorie_id'];

        $stmt = $pdo->prepare('SELECT id FROM kriterien WHERE kategorie_id = :kategorie_id');
        $stmt->execute(['kategorie_id' => $kategorieId]);
        $kriterienIds = array_column($stmt->fetchAll(), 'id');

        $punkte = $_POST['punkte'] ?? [];
        foreach ($kriterienIds as $kriteriumId) {
            $wert = $punkte[$kriteriumId] ?? null;
            if ($wert === null || $wert === '' || !is_numeric($wert) || (float) $wert < 0 || (float) $wert > 10) {
                $this->showFormWithError($rundeId, $teilnehmerId, 'Bitte für jedes Kriterium eine Punktzahl zwischen 0 und 10 eingeben.');
                return;
            }
        }

        $insert = $pdo->prepare(
            'INSERT INTO bewertungen (runde_id, teilnehmer_id, wertungsrichter_id, kriterium_id, punkte)
             VALUES (:runde_id, :teilnehmer_id, :wertungsrichter_id, :kriterium_id, :punkte)'
        );

        $pdo->beginTransaction();
        foreach ($kriterienIds as $kriteriumId) {
            $insert->execute([
                'runde_id' => $rundeId,
                'teilnehmer_id' => $teilnehmerId,
                'wertungsrichter_id' => $judgeId,
                'kriterium_id' => $kriteriumId,
                'punkte' => (float) $punkte[$kriteriumId],
            ]);
        }
        $pdo->commit();

        header('Location: /kampfrichter/bewertung');
        exit;
    }

    private function showFormWithError(string $rundeId, string $teilnehmerId, string $error): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT t.*, k.name AS kategorie_name FROM teilnehmer t
             JOIN kategorien k ON k.id = t.kategorie_id
             WHERE t.id = :teilnehmer_id'
        );
        $stmt->execute(['teilnehmer_id' => $teilnehmerId]);
        $teilnehmer = $stmt->fetch();

        $stmt = $pdo->prepare('SELECT id, name FROM kriterien WHERE kategorie_id = :kategorie_id ORDER BY id');
        $stmt->execute(['kategorie_id' => $teilnehmer['kategorie_id']]);
        $kriterien = $stmt->fetchAll();

        View::render('judge/scoring_form', [
            'teilnehmer' => $teilnehmer,
            'kriterien' => $kriterien,
            'rundeId' => $rundeId,
            'bereitsBewertet' => false,
            'error' => $error,
        ], 'Bewertung – ORBV Turnier');
    }
}
