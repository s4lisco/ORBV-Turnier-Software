<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategorie;
use App\Models\Turnier;
use App\Models\Verein;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'anzahlVereine' => Verein::count(),
            'anzahlKategorien' => Kategorie::count(),
            'turniere' => Turnier::with('verein')->orderByDesc('datum')->get(),
        ]);
    }
}
