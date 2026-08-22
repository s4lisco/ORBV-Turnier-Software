<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnmeldungFormation;
use App\Models\AnmeldungPaar;
use App\Models\AnmeldungSolo;
use App\Models\Kategorie;
use App\Models\Turnier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Admin darf Anmeldungen jederzeit anlegen/ändern/löschen, unabhängig vom
 * Anmeldeschluss der Vereine (docs/turnier-plan.md Abschnitt 3/5.1).
 */
class AnmeldungController extends Controller
{
    public function storeSolo(Request $request, Turnier $turnier, Kategorie $kategorie): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'geburtsdatum' => ['required', 'date'],
            'verein_id' => ['required', 'exists:vereine,id'],
        ]);

        AnmeldungSolo::create([
            'turnier_kategorie_id' => $this->turnierKategorieId($turnier, $kategorie),
            ...$data,
        ]);

        return redirect()->route('admin.turniere.show', $turnier)->with('status', 'Anmeldung hinzugefügt.');
    }

    public function updateSolo(Request $request, Turnier $turnier, AnmeldungSolo $anmeldungSolo): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'geburtsdatum' => ['required', 'date'],
        ]);

        $anmeldungSolo->update($data);

        return redirect()->route('admin.turniere.show', $turnier)->with('status', 'Anmeldung aktualisiert.');
    }

    public function destroySolo(Turnier $turnier, AnmeldungSolo $anmeldungSolo): RedirectResponse
    {
        $anmeldungSolo->delete();

        return redirect()->route('admin.turniere.show', $turnier)->with('status', 'Anmeldung gelöscht.');
    }

    public function storePaar(Request $request, Turnier $turnier, Kategorie $kategorie): RedirectResponse
    {
        $data = $request->validate([
            'name_p1' => ['required', 'string', 'max:255'],
            'geburtsdatum_p1' => ['required', 'date'],
            'name_p2' => ['required', 'string', 'max:255'],
            'geburtsdatum_p2' => ['required', 'date'],
            'verein_id' => ['required', 'exists:vereine,id'],
        ]);

        AnmeldungPaar::create([
            'turnier_kategorie_id' => $this->turnierKategorieId($turnier, $kategorie),
            ...$data,
        ]);

        return redirect()->route('admin.turniere.show', $turnier)->with('status', 'Anmeldung hinzugefügt.');
    }

    public function updatePaar(Request $request, Turnier $turnier, AnmeldungPaar $anmeldungPaar): RedirectResponse
    {
        $data = $request->validate([
            'name_p1' => ['required', 'string', 'max:255'],
            'geburtsdatum_p1' => ['required', 'date'],
            'name_p2' => ['required', 'string', 'max:255'],
            'geburtsdatum_p2' => ['required', 'date'],
        ]);

        $anmeldungPaar->update($data);

        return redirect()->route('admin.turniere.show', $turnier)->with('status', 'Anmeldung aktualisiert.');
    }

    public function destroyPaar(Turnier $turnier, AnmeldungPaar $anmeldungPaar): RedirectResponse
    {
        $anmeldungPaar->delete();

        return redirect()->route('admin.turniere.show', $turnier)->with('status', 'Anmeldung gelöscht.');
    }

    public function storeFormation(Request $request, Turnier $turnier, Kategorie $kategorie): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'anzahl_taenzerinnen' => ['required', 'integer', 'min:1'],
            'verein_id' => ['required', 'exists:vereine,id'],
        ]);

        AnmeldungFormation::create([
            'turnier_kategorie_id' => $this->turnierKategorieId($turnier, $kategorie),
            ...$data,
        ]);

        return redirect()->route('admin.turniere.show', $turnier)->with('status', 'Anmeldung hinzugefügt.');
    }

    public function updateFormation(Request $request, Turnier $turnier, AnmeldungFormation $anmeldungFormation): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'anzahl_taenzerinnen' => ['required', 'integer', 'min:1'],
        ]);

        $anmeldungFormation->update($data);

        return redirect()->route('admin.turniere.show', $turnier)->with('status', 'Anmeldung aktualisiert.');
    }

    public function destroyFormation(Turnier $turnier, AnmeldungFormation $anmeldungFormation): RedirectResponse
    {
        $anmeldungFormation->delete();

        return redirect()->route('admin.turniere.show', $turnier)->with('status', 'Anmeldung gelöscht.');
    }

    private function turnierKategorieId(Turnier $turnier, Kategorie $kategorie): int
    {
        return $turnier->turnierKategorien()
            ->where('kategorie_id', $kategorie->id)
            ->firstOrFail()
            ->id;
    }
}
