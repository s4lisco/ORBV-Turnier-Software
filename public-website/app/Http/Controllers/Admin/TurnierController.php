<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategorie;
use App\Models\Turnier;
use App\Models\Verein;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TurnierController extends Controller
{
    public function index(): View
    {
        return view('admin.turniere.index', [
            'turniere' => Turnier::with('verein')->orderByDesc('datum')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.turniere.create', [
            'vereine' => Verein::orderBy('name')->get(),
            'kategorien' => Kategorie::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $turnier = Turnier::create([
            'name' => $data['name'],
            'beschreibung' => $data['beschreibung'] ?? null,
            'adresse' => $data['adresse'] ?? null,
            'datum' => $data['datum'],
            'verein_id' => $data['verein_id'],
        ]);

        $turnier->kategorien()->sync($data['kategorie_ids'] ?? []);

        return redirect()->route('admin.turniere.show', $turnier)->with('status', 'Turnier angelegt.');
    }

    public function show(Turnier $turnier): View
    {
        $turnier->load([
            'verein',
            'turnierKategorien.kategorie',
            'turnierKategorien.anmeldungenSolo.verein',
            'turnierKategorien.anmeldungenPaar.verein',
            'turnierKategorien.anmeldungenFormation.verein',
            'turnierKategorien.ranglisteEintraege',
        ]);

        return view('admin.turniere.show', ['turnier' => $turnier]);
    }

    public function edit(Turnier $turnier): View
    {
        $turnier->load('kategorien');

        return view('admin.turniere.edit', [
            'turnier' => $turnier,
            'vereine' => Verein::orderBy('name')->get(),
            'kategorien' => Kategorie::orderBy('name')->get(),
            'zugewieseneKategorieIds' => $turnier->kategorien->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, Turnier $turnier): RedirectResponse
    {
        $data = $this->validateData($request);

        $turnier->update([
            'name' => $data['name'],
            'beschreibung' => $data['beschreibung'] ?? null,
            'adresse' => $data['adresse'] ?? null,
            'datum' => $data['datum'],
            'verein_id' => $data['verein_id'],
        ]);

        $turnier->kategorien()->sync($data['kategorie_ids'] ?? []);

        return redirect()->route('admin.turniere.show', $turnier)->with('status', 'Turnier aktualisiert.');
    }

    public function destroy(Turnier $turnier): RedirectResponse
    {
        $turnier->delete();

        return redirect()->route('admin.turniere.index')->with('status', 'Turnier gelöscht.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'beschreibung' => ['nullable', 'string'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'datum' => ['required', 'date'],
            'verein_id' => ['required', 'exists:vereine,id'],
            'kategorie_ids' => ['nullable', 'array'],
            'kategorie_ids.*' => ['exists:kategorien,id'],
        ]);
    }
}
