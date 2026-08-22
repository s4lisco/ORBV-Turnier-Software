<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Turnier;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV-Export im exakten Format, das
 * pi-software/src/Services/CsvImportService.php erwartet:
 * kategorie_name,kategorie_typ,kriterium_name,teilnehmer_typ,teilnehmer_name,
 * geburtsdatum,name_p2,geburtsdatum_p2,anzahl_taenzerinnen,verein_name
 */
class ExportController extends Controller
{
    public function download(Turnier $turnier): StreamedResponse
    {
        $turnier->load([
            'turnierKategorien.kategorie.bewertungen',
            'turnierKategorien.anmeldungenSolo.verein',
            'turnierKategorien.anmeldungenPaar.verein',
            'turnierKategorien.anmeldungenFormation.verein',
        ]);

        $filename = 'turnier_' . $turnier->id . '_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($turnier) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'kategorie_name', 'kategorie_typ', 'kriterium_name', 'teilnehmer_typ', 'teilnehmer_name',
                'geburtsdatum', 'name_p2', 'geburtsdatum_p2', 'anzahl_taenzerinnen', 'verein_name',
            ], escape: '\\');

            foreach ($turnier->turnierKategorien as $turnierKategorie) {
                $kategorie = $turnierKategorie->kategorie;
                $kriteriumName = $kategorie->kriteriumNamen();

                foreach ($turnierKategorie->anmeldungenSolo as $a) {
                    fputcsv($handle, [
                        $kategorie->name, $kategorie->typ, $kriteriumName, 'solo', $a->name,
                        $a->geburtsdatum->format('Y-m-d'), '', '', '', $a->verein->name,
                    ], escape: '\\');
                }

                foreach ($turnierKategorie->anmeldungenPaar as $a) {
                    fputcsv($handle, [
                        $kategorie->name, $kategorie->typ, $kriteriumName, 'paar', $a->name_p1,
                        $a->geburtsdatum_p1->format('Y-m-d'), $a->name_p2, $a->geburtsdatum_p2->format('Y-m-d'), '', $a->verein->name,
                    ], escape: '\\');
                }

                foreach ($turnierKategorie->anmeldungenFormation as $a) {
                    fputcsv($handle, [
                        $kategorie->name, $kategorie->typ, $kriteriumName, 'formation', $a->name,
                        '', '', '', $a->anzahl_taenzerinnen, $a->verein->name,
                    ], escape: '\\');
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
