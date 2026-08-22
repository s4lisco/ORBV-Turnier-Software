<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Services\RankingService;
use App\View;

final class ResultsController
{
    public function index(): void
    {
        Auth::requireAdmin();

        $kategorien = Database::connection()->query('SELECT id, name, typ FROM kategorien ORDER BY name')->fetchAll();

        View::render('admin/results_overview', ['kategorien' => $kategorien], 'Ergebnisse – ORBV Turnier');
    }

    public function show(string $kategorieId): void
    {
        Auth::requireAdmin();

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, name FROM kategorien WHERE id = :id');
        $stmt->execute(['id' => $kategorieId]);
        $kategorie = $stmt->fetch();

        if (!$kategorie) {
            http_response_code(404);
            echo 'Kategorie nicht gefunden.';
            return;
        }

        $rangliste = (new RankingService())->endranglisteFuerKategorie((int) $kategorieId);

        $teilnehmerNamen = [];
        if ($rangliste !== []) {
            $ids = array_map(fn ($r) => (int) $r['teilnehmer_id'], $rangliste);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT id, startnummer, name, name_p2 FROM teilnehmer WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $t) {
                $teilnehmerNamen[(int) $t['id']] = $t;
            }
        }

        View::render('admin/results', [
            'kategorie' => $kategorie,
            'rangliste' => $rangliste,
            'teilnehmerNamen' => $teilnehmerNamen,
        ], 'Ergebnisse – ORBV Turnier');
    }
}
