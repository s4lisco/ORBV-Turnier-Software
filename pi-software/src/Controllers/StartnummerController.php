<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Services\StartnummerService;
use App\View;

final class StartnummerController
{
    public function vergeben(): void
    {
        Auth::requireAdmin();

        (new StartnummerService())->vergeben();

        header('Location: /organisatoren');
        exit;
    }

    /**
     * Liste der Teilnehmer:innen ohne zugewiesene Startnummer, für die manuelle
     * Ausgabe der Startnummern-Karten am Check-in (Feedback-Runde 2).
     */
    public function fehlende(): void
    {
        Auth::requireAdmin();

        $teilnehmer = Database::connection()->query(
            "SELECT t.id, t.name, t.name_p2, t.verein_name, k.name AS kategorie_name
             FROM teilnehmer t JOIN kategorien k ON k.id = t.kategorie_id
             WHERE t.startnummer IS NULL
             ORDER BY k.name, t.name"
        )->fetchAll();

        View::render('admin/startnummern_fehlend', [
            'teilnehmer' => $teilnehmer,
        ], 'Fehlende Startnummern – ORBV Turnier');
    }
}
