<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategorie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KategorieController extends Controller
{
    public function index(): View
    {
        return view('admin.kategorien.index', [
            'kategorien' => Kategorie::with('bewertungen')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.kategorien.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'typ' => ['required', 'in:solo,paar,formation'],
            'kriterien' => ['required', 'string'],
        ]);

        $kategorie = Kategorie::create([
            'name' => $data['name'],
            'typ' => $data['typ'],
        ]);

        $this->syncKriterien($kategorie, $data['kriterien']);

        return redirect()->route('admin.kategorien.index')->with('status', 'Kategorie angelegt.');
    }

    public function edit(Kategorie $kategorie): View
    {
        $kategorie->load('bewertungen');

        return view('admin.kategorien.edit', ['kategorie' => $kategorie]);
    }

    public function update(Request $request, Kategorie $kategorie): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'typ' => ['required', 'in:solo,paar,formation'],
            'kriterien' => ['required', 'string'],
        ]);

        $kategorie->update([
            'name' => $data['name'],
            'typ' => $data['typ'],
        ]);

        $this->syncKriterien($kategorie, $data['kriterien']);

        return redirect()->route('admin.kategorien.index')->with('status', 'Kategorie aktualisiert.');
    }

    public function destroy(Kategorie $kategorie): RedirectResponse
    {
        $kategorie->delete();

        return redirect()->route('admin.kategorien.index')->with('status', 'Kategorie gelöscht.');
    }

    /** Kriterien als kommagetrennte Liste im Formular, z.B. "Tanz, Ausdruck, Technik". */
    private function syncKriterien(Kategorie $kategorie, string $kriterienListe): void
    {
        $namen = array_filter(array_map('trim', explode(',', $kriterienListe)));

        $kategorie->bewertungen()->delete();
        foreach ($namen as $name) {
            $kategorie->bewertungen()->create(['name' => $name]);
        }
    }
}
