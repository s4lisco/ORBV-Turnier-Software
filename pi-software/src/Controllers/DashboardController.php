<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\View;

final class DashboardController
{
    public function index(): void
    {
        if (!SetupController::adminExists()) {
            header('Location: /admin/setup');
            exit;
        }

        if (!Auth::isAdmin()) {
            header('Location: /admin/login');
            exit;
        }

        $pdo = Database::connection();

        $kategorien = $pdo->query(
            'SELECT k.id, k.name, k.typ,
                    COUNT(t.id) AS anzahl_teilnehmer,
                    SUM(CASE WHEN t.startnummer IS NOT NULL THEN 1 ELSE 0 END) AS anzahl_mit_startnummer
             FROM kategorien k
             LEFT JOIN teilnehmer t ON t.kategorie_id = k.id
             GROUP BY k.id, k.name, k.typ
             ORDER BY k.name'
        )->fetchAll();

        $richterAnzahl = (int) $pdo->query('SELECT COUNT(*) AS c FROM wertungsrichter')->fetch()['c'];

        View::render('admin/dashboard', [
            'kategorien' => $kategorien,
            'richterAnzahl' => $richterAnzahl,
        ], 'Wertungsbüro-Dashboard – ORBV Turnier');
    }
}
