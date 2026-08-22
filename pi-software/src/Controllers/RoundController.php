<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Services\RoundService;
use App\View;

final class RoundController
{
    public function index(): void
    {
        Auth::requireAdmin();

        $kategorien = Database::connection()->query(
            "SELECT k.id, k.name, k.typ,
                    (SELECT COUNT(*) FROM teilnehmer t WHERE t.kategorie_id = k.id) AS anzahl_teilnehmer,
                    (SELECT COUNT(*) FROM runden r WHERE r.kategorie_id = k.id) AS anzahl_runden,
                    (SELECT r.id FROM runden r WHERE r.kategorie_id = k.id AND r.status = 'laufend' LIMIT 1) AS laufende_runde_id
             FROM kategorien k ORDER BY k.name"
        )->fetchAll();

        View::render('admin/round_overview', ['kategorien' => $kategorien], 'Runden – ORBV Turnier');
    }

    public function starten(string $kategorieId): void
    {
        Auth::requireAdmin();

        try {
            (new RoundService())->starteErsteRunde((int) $kategorieId);
        } catch (\Throwable $e) {
            http_response_code(422);
            echo htmlspecialchars($e->getMessage());
            return;
        }

        header('Location: /admin/runden');
        exit;
    }

    public function show(string $rundeId): void
    {
        Auth::requireAdmin();
        $roundService = new RoundService();

        $stmt = Database::connection()->prepare(
            'SELECT r.*, k.name AS kategorie_name FROM runden r JOIN kategorien k ON k.id = r.kategorie_id WHERE r.id = :id'
        );
        $stmt->execute(['id' => $rundeId]);
        $runde = $stmt->fetch();

        if (!$runde) {
            http_response_code(404);
            echo 'Runde nicht gefunden.';
            return;
        }

        View::render('admin/round_control', [
            'runde' => $runde,
            'offene' => $roundService->offeneTeilnehmerFuerRunde((int) $rundeId),
            'live' => $roundService->liveStatusFuerRunde((int) $rundeId),
            'kannAbschliessen' => $roundService->istRundeVollstaendigBewertet((int) $rundeId),
        ], 'Rundensteuerung – ORBV Turnier');
    }

    /** JSON-Endpoint fürs Hintergrund-Polling der Rundensteuerung (Live-Status + Abschliessen-Button). */
    public function status(string $rundeId): void
    {
        Auth::requireAdmin();
        $roundService = new RoundService();

        header('Content-Type: application/json');
        echo json_encode([
            'live' => array_values($roundService->liveStatusFuerRunde((int) $rundeId)),
            'kannAbschliessen' => $roundService->istRundeVollstaendigBewertet((int) $rundeId),
        ]);
    }

    public function freigeben(string $rundeId): void
    {
        Auth::requireAdmin();

        $teilnehmerIds = array_map('intval', $_POST['teilnehmer_ids'] ?? []);
        if ($teilnehmerIds !== []) {
            (new RoundService())->freigeben((int) $rundeId, $teilnehmerIds);
        }

        header('Location: /admin/runden/' . $rundeId);
        exit;
    }

    public function abschliessen(string $rundeId): void
    {
        Auth::requireAdmin();

        try {
            (new RoundService())->rundeAbschliessen((int) $rundeId);
        } catch (\Throwable $e) {
            http_response_code(422);
            echo htmlspecialchars($e->getMessage());
            return;
        }

        header('Location: /admin/runden');
        exit;
    }
}
