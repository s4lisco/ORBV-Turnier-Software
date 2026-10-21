<?php

namespace App\Http\Controllers\Verein;

use App\Http\Controllers\Controller;
use App\Models\Turnier;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TurnierController extends Controller
{
    public function show(Turnier $turnier): View
    {
        $vereinId = Auth::guard('verein')->id();

        $turnier->load([
            'turnierKategorien.kategorie',
            'turnierKategorien.anmeldungenSolo' => fn ($q) => $q->where('verein_id', $vereinId),
            'turnierKategorien.anmeldungenPaar' => fn ($q) => $q->where('verein_id', $vereinId),
            'turnierKategorien.anmeldungenFormation' => fn ($q) => $q->where('verein_id', $vereinId),
            // Für die Rang-Anzeige (Feedback-Runde 2): Rangliste wird nur über
            // den Namen mit der eigenen Anmeldung abgeglichen, da Pi und
            // Public-Website keine gemeinsame Teilnehmer-ID haben.
            'turnierKategorien.ranglisteEintraege',
        ]);

        return view('verein.turniere.show', ['turnier' => $turnier]);
    }
}
