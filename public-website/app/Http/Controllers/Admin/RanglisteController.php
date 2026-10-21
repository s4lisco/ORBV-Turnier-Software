<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RanglisteEintrag;
use App\Models\Turnier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Rangliste-Upload (Plan_Feedback_Umsetzung.md 9.3): zusätzlicher Mechanismus
 * zum bestehenden Offline-Workflow auf dem Pi. Der Admin lädt hier die vom
 * Pi generierte CSV (siehe pi-software CsvExportService::exportRanglisteFuerUpload,
 * Spalten: kategorie_id,kategorie_name,rang,name) hoch; die Ergebnisse werden
 * danach auf der öffentlichen Ergebnisseite angezeigt. Die Spalte
 * verein_name ist optional, damit auch ältere CSV-Exporte ohne diese Spalte
 * noch funktionieren.
 */
class RanglisteController extends Controller
{
    private const REQUIRED_COLUMNS = ['kategorie_id', 'kategorie_name', 'rang', 'name'];

    public function upload(Request $request, Turnier $turnier): RedirectResponse
    {
        $request->validate([
            'rangliste_csv' => ['required', 'file'],
        ]);

        $handle = fopen($request->file('rangliste_csv')->getRealPath(), 'r');
        $header = fgetcsv($handle, escape: '\\');

        if ($header === false) {
            fclose($handle);
            return back()->withErrors('CSV-Datei ist leer.');
        }

        $header = array_map('trim', $header);
        $missing = array_diff(self::REQUIRED_COLUMNS, $header);
        if ($missing !== []) {
            fclose($handle);
            return back()->withErrors('CSV fehlt Spalten: ' . implode(', ', $missing));
        }

        $turnierKategorien = $turnier->turnierKategorien()->get()->keyBy('kategorie_id');

        $eintraegeProTurnierKategorie = [];
        $uebersprungeneKategorien = [];

        while (($row = fgetcsv($handle, escape: '\\')) !== false) {
            if (count(array_filter($row, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }

            $data = array_combine($header, $row);
            if ($data === false) {
                continue;
            }

            $kategorieId = (int) $data['kategorie_id'];
            $turnierKategorie = $turnierKategorien->get($kategorieId);

            if (!$turnierKategorie) {
                $uebersprungeneKategorien[$data['kategorie_name']] = true;
                continue;
            }

            $eintraegeProTurnierKategorie[$turnierKategorie->id][] = [
                'turnier_kategorie_id' => $turnierKategorie->id,
                'rang' => $data['rang'],
                'name' => $data['name'],
                'verein_name' => ($data['verein_name'] ?? '') !== '' ? $data['verein_name'] : null,
            ];
        }

        fclose($handle);

        DB::transaction(function () use ($eintraegeProTurnierKategorie) {
            foreach ($eintraegeProTurnierKategorie as $turnierKategorieId => $eintraege) {
                RanglisteEintrag::where('turnier_kategorie_id', $turnierKategorieId)->delete();
                RanglisteEintrag::insert($eintraege);
            }
        });

        $status = count($eintraegeProTurnierKategorie) . ' Kategorie(n) veröffentlicht.';
        if ($uebersprungeneKategorien !== []) {
            $status .= ' Übersprungen (nicht Teil dieses Turniers): ' . implode(', ', array_keys($uebersprungeneKategorien)) . '.';
        }

        return redirect()->route('admin.turniere.show', $turnier)->with('status', $status);
    }
}
