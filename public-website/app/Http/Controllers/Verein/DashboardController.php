<?php

namespace App\Http\Controllers\Verein;

use App\Http\Controllers\Controller;
use App\Models\Turnier;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('verein.dashboard', [
            'turniere' => Turnier::with('verein')->orderBy('datum')->get(),
        ]);
    }
}
