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
                    (SELECT r.id FROM runden r WHERE r.kategorie_id = k.id AND r.status = 'laufend' LIMIT 1) AS laufende_runde_id,
                    (SELECT COUNT(*) FROM teilnehmer t WHERE t.kategorie_id = k.id AND t.status = 'aktiv' AND t.startnummer IS NULL) AS anzahl_ohne_startnummer
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
            'live' => $roundService->liveStatusFuerRunde((int) $rundeId),
            'kannAbschliessen' => $roundService->istRundeVollstaendigBewertet((int) $rundeId),
            'kannAbbrechen' => $roundService->kannRundeAbbrechen((int) $rundeId),
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
            'kannAbbrechen' => $roundService->kannRundeAbbrechen((int) $rundeId),
        ]);
    }

    /** Druckbare Startliste einer Runde, separat pro Kategorie (siehe Plan_Feedback_Umsetzung.md 10.1/10.2). */
    public function startliste(string $rundeId): void
    {
        Auth::requireAdmin();
        $roundService = new RoundService();

        $stmt = Database::connection()->prepare(
            'SELECT r.*, k.name AS kategorie_name, k.typ AS kategorie_typ FROM runden r JOIN kategorien k ON k.id = r.kategorie_id WHERE r.id = :id'
        );
        $stmt->execute(['id' => $rundeId]);
        $runde = $stmt->fetch();

        if (!$runde) {
            http_response_code(404);
            echo 'Runde nicht gefunden.';
            return;
        }

        $teilnehmer = $roundService->alleTeilnehmerFuerRunde((int) $rundeId);
        $paarweise = $roundService->tanztPaarweise($runde['kategorie_typ'], $runde['kategorie_name']);

        View::render('admin/startliste', [
            'runde' => $runde,
            'teilnehmer' => $teilnehmer,
            'paarweise' => $paarweise,
            'gruppen' => array_chunk($teilnehmer, $paarweise ? 2 : 1),
        ], 'Startliste: ' . $runde['kategorie_name'] . ' – ORBV Turnier');
    }

    /** Druckbare Startliste vor dem Start der ersten Runde einer Kategorie (Vorschau anhand Startnummern-Reihenfolge). */
    public function startlisteVorschau(string $kategorieId): void
    {
        Auth::requireAdmin();
        $roundService = new RoundService();

        $stmt = Database::connection()->prepare('SELECT id, name, typ FROM kategorien WHERE id = :id');
        $stmt->execute(['id' => $kategorieId]);
        $kategorie = $stmt->fetch();

        if (!$kategorie) {
            http_response_code(404);
            echo 'Kategorie nicht gefunden.';
            return;
        }

        $stmt = Database::connection()->prepare(
            "SELECT id, startnummer, name, name_p2 FROM teilnehmer
             WHERE kategorie_id = :kategorie_id AND status = 'aktiv'
             ORDER BY startnummer ASC, id ASC"
        );
        $stmt->execute(['kategorie_id' => $kategorieId]);
        $teilnehmer = $stmt->fetchAll();

        $paarweise = $roundService->tanztPaarweise($kategorie['typ'], $kategorie['name']);

        View::render('admin/startliste', [
            'runde' => ['id' => null, 'kategorie_name' => $kategorie['name'], 'typ' => 'Vorschau vor Rundenstart'],
            'backUrl' => '/admin/runden',
            'teilnehmer' => $teilnehmer,
            'paarweise' => $paarweise,
            'gruppen' => array_chunk($teilnehmer, $paarweise ? 2 : 1),
        ], 'Startliste: ' . $kategorie['name'] . ' – ORBV Turnier');
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

    /** Bricht die erste Runde einer Kategorie ab (siehe Plan_Feedback_Umsetzung.md 12.3). */
    public function abbrechen(string $rundeId): void
    {
        Auth::requireAdmin();

        try {
            (new RoundService())->rundeAbbrechen((int) $rundeId);
        } catch (\Throwable $e) {
            http_response_code(422);
            echo htmlspecialchars($e->getMessage());
            return;
        }

        header('Location: /admin/runden');
        exit;
    }
}
