<?php

namespace App\Http\Controllers\Verein;

use App\Http\Controllers\Controller;
use App\Models\AnmeldungFormation;
use App\Models\AnmeldungPaar;
use App\Models\AnmeldungSolo;
use App\Models\Kategorie;
use App\Models\Turnier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Vereine dürfen eigene Anmeldungen nur bis 2 Wochen vor dem Turnierdatum
 * selbst an-/abmelden (docs/turnier-plan.md Abschnitt 5.1, Klärung mit dem
 * User: sofort sichtbar, Frist endet bei $turnier->anmeldeschluss).
 */
class AnmeldungController extends Controller
{
    // --- Solo ---

    public function createSolo(Turnier $turnier, Kategorie $kategorie): View
    {
        $this->authorizeOffen($turnier);

        return view('verein.anmeldungen.solo.create', compact('turnier', 'kategorie'));
    }

    public function storeSolo(Request $request, Turnier $turnier, Kategorie $kategorie): RedirectResponse
    {
        $this->authorizeOffen($turnier);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'geburtsdatum' => ['required', 'date'],
        ]);

        AnmeldungSolo::create([
            'turnier_kategorie_id' => $this->turnierKategorieId($turnier, $kategorie),
            'verein_id' => Auth::guard('verein')->id(),
            ...$data,
        ]);

        return redirect()->route('verein.turniere.show', $turnier)->with('status', 'Anmeldung gespeichert.');
    }

    public function editSolo(Turnier $turnier, Kategorie $kategorie, AnmeldungSolo $anmeldungSolo): View
    {
        $this->authorizeOffenUndEigentuemer($turnier, $anmeldungSolo->verein_id);

        return view('verein.anmeldungen.solo.edit', compact('turnier', 'kategorie', 'anmeldungSolo'));
    }

    public function updateSolo(Request $request, Turnier $turnier, Kategorie $kategorie, AnmeldungSolo $anmeldungSolo): RedirectResponse
    {
        $this->authorizeOffenUndEigentuemer($turnier, $anmeldungSolo->verein_id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'geburtsdatum' => ['required', 'date'],
        ]);

        $anmeldungSolo->update($data);

        return redirect()->route('verein.turniere.show', $turnier)->with('status', 'Anmeldung aktualisiert.');
    }

    public function destroySolo(Turnier $turnier, Kategorie $kategorie, AnmeldungSolo $anmeldungSolo): RedirectResponse
    {
        $this->authorizeOffenUndEigentuemer($turnier, $anmeldungSolo->verein_id);
        $anmeldungSolo->delete();

        return redirect()->route('verein.turniere.show', $turnier)->with('status', 'Anmeldung entfernt.');
    }

    // --- Paar ---

    public function createPaar(Turnier $turnier, Kategorie $kategorie): View
    {
        $this->authorizeOffen($turnier);

        return view('verein.anmeldungen.paar.create', compact('turnier', 'kategorie'));
    }

    public function storePaar(Request $request, Turnier $turnier, Kategorie $kategorie): RedirectResponse
    {
        $this->authorizeOffen($turnier);

        $data = $request->validate([
            'name_p1' => ['required', 'string', 'max:255'],
            'geburtsdatum_p1' => ['required', 'date'],
            'name_p2' => ['required', 'string', 'max:255'],
            'geburtsdatum_p2' => ['required', 'date'],
        ]);

        AnmeldungPaar::create([
            'turnier_kategorie_id' => $this->turnierKategorieId($turnier, $kategorie),
            'verein_id' => Auth::guard('verein')->id(),
            ...$data,
        ]);

        return redirect()->route('verein.turniere.show', $turnier)->with('status', 'Anmeldung gespeichert.');
    }

    public function editPaar(Turnier $turnier, Kategorie $kategorie, AnmeldungPaar $anmeldungPaar): View
    {
        $this->authorizeOffenUndEigentuemer($turnier, $anmeldungPaar->verein_id);

        return view('verein.anmeldungen.paar.edit', compact('turnier', 'kategorie', 'anmeldungPaar'));
    }

    public function updatePaar(Request $request, Turnier $turnier, Kategorie $kategorie, AnmeldungPaar $anmeldungPaar): RedirectResponse
    {
        $this->authorizeOffenUndEigentuemer($turnier, $anmeldungPaar->verein_id);

        $data = $request->validate([
            'name_p1' => ['required', 'string', 'max:255'],
            'geburtsdatum_p1' => ['required', 'date'],
            'name_p2' => ['required', 'string', 'max:255'],
            'geburtsdatum_p2' => ['required', 'date'],
        ]);

        $anmeldungPaar->update($data);

        return redirect()->route('verein.turniere.show', $turnier)->with('status', 'Anmeldung aktualisiert.');
    }

    public function destroyPaar(Turnier $turnier, Kategorie $kategorie, AnmeldungPaar $anmeldungPaar): RedirectResponse
    {
        $this->authorizeOffenUndEigentuemer($turnier, $anmeldungPaar->verein_id);
        $anmeldungPaar->delete();

        return redirect()->route('verein.turniere.show', $turnier)->with('status', 'Anmeldung entfernt.');
    }

    // --- Formation ---

    public function createFormation(Turnier $turnier, Kategorie $kategorie): View
    {
        $this->authorizeOffen($turnier);

        return view('verein.anmeldungen.formation.create', compact('turnier', 'kategorie'));
    }

    public function storeFormation(Request $request, Turnier $turnier, Kategorie $kategorie): RedirectResponse
    {
        $this->authorizeOffen($turnier);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'anzahl_taenzerinnen' => ['required', 'integer', 'min:1'],
        ]);

        AnmeldungFormation::create([
            'turnier_kategorie_id' => $this->turnierKategorieId($turnier, $kategorie),
            'verein_id' => Auth::guard('verein')->id(),
            ...$data,
        ]);

        return redirect()->route('verein.turniere.show', $turnier)->with('status', 'Anmeldung gespeichert.');
    }

    public function editFormation(Turnier $turnier, Kategorie $kategorie, AnmeldungFormation $anmeldungFormation): View
    {
        $this->authorizeOffenUndEigentuemer($turnier, $anmeldungFormation->verein_id);

        return view('verein.anmeldungen.formation.edit', compact('turnier', 'kategorie', 'anmeldungFormation'));
    }

    public function updateFormation(Request $request, Turnier $turnier, Kategorie $kategorie, AnmeldungFormation $anmeldungFormation): RedirectResponse
    {
        $this->authorizeOffenUndEigentuemer($turnier, $anmeldungFormation->verein_id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'anzahl_taenzerinnen' => ['required', 'integer', 'min:1'],
        ]);

        $anmeldungFormation->update($data);

        return redirect()->route('verein.turniere.show', $turnier)->with('status', 'Anmeldung aktualisiert.');
    }

    public function destroyFormation(Turnier $turnier, Kategorie $kategorie, AnmeldungFormation $anmeldungFormation): RedirectResponse
    {
        $this->authorizeOffenUndEigentuemer($turnier, $anmeldungFormation->verein_id);
        $anmeldungFormation->delete();

        return redirect()->route('verein.turniere.show', $turnier)->with('status', 'Anmeldung entfernt.');
    }

    private function authorizeOffen(Turnier $turnier): void
    {
        abort_unless($turnier->istAnmeldungOffenFuerVerein(), 403, 'Die Anmeldefrist für dieses Turnier ist abgelaufen.');
    }

    private function authorizeOffenUndEigentuemer(Turnier $turnier, int $anmeldungVereinId): void
    {
        $this->authorizeOffen($turnier);
        abort_unless($anmeldungVereinId === Auth::guard('verein')->id(), 403);
    }

    private function turnierKategorieId(Turnier $turnier, Kategorie $kategorie): int
    {
        return $turnier->turnierKategorien()
            ->where('kategorie_id', $kategorie->id)
            ->firstOrFail()
            ->id;
    }
}
