<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Einstellung;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EinstellungController extends Controller
{
    public function edit(): View
    {
        return view('admin.einstellungen.edit', ['einstellung' => Einstellung::aktuell()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'info_text' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $einstellung = Einstellung::aktuell();

        if ($request->hasFile('logo')) {
            if ($einstellung->logo_pfad) {
                Storage::disk('public')->delete($einstellung->logo_pfad);
            }
            $einstellung->logo_pfad = $request->file('logo')->store('logo', 'public');
        }

        $einstellung->info_text = $data['info_text'] ?? null;
        $einstellung->save();

        return redirect()->route('admin.einstellungen.edit')->with('status', 'Header-Einstellungen gespeichert.');
    }
}
