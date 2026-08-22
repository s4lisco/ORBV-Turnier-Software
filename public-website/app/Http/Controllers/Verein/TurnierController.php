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
        ]);

        return view('verein.turniere.show', ['turnier' => $turnier]);
    }
}
