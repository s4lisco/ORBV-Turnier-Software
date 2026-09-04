<?php

namespace App\Http\Controllers;

use App\Models\Turnier;
use Illuminate\View\View;

/**
 * Öffentliche Ergebnisseite (Plan_Feedback_Umsetzung.md 9.3): zeigt die vom
 * Admin hochgeladenen Ranglisten (Name + Rang) an, ohne Login nötig.
 */
class ErgebnisseController extends Controller
{
    public function index(): View
    {
        $turniere = Turnier::query()
            ->whereHas('turnierKategorien.ranglisteEintraege')
            ->orderByDesc('datum')
            ->get();

        return view('ergebnisse.index', ['turniere' => $turniere]);
    }

    public function show(Turnier $turnier): View
    {
        $turnier->load([
            'turnierKategorien' => fn ($query) => $query->whereHas('ranglisteEintraege'),
            'turnierKategorien.kategorie',
            'turnierKategorien.ranglisteEintraege',
        ]);

        return view('ergebnisse.show', ['turnier' => $turnier]);
    }
}
