<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Services\CsvImportService;
use App\View;

final class ImportController
{
    public function show(): void
    {
        Auth::requireAdmin();
        View::render('admin/import', ['result' => null, 'error' => null], 'Teilnehmer-Import – ORBV Turnier');
    }

    public function upload(): void
    {
        Auth::requireAdmin();

        if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
            View::render('admin/import', ['result' => null, 'error' => 'Bitte eine CSV-Datei auswählen.'], 'Teilnehmer-Import – ORBV Turnier');
            return;
        }

        $storageDir = dirname(__DIR__, 2) . '/storage/imports';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }

        $destination = $storageDir . '/' . date('Y-m-d_His') . '_' . basename($_FILES['csv']['name']);
        move_uploaded_file($_FILES['csv']['tmp_name'], $destination);

        try {
            $result = (new CsvImportService())->import($destination);
        } catch (\Throwable $e) {
            View::render('admin/import', ['result' => null, 'error' => $e->getMessage()], 'Teilnehmer-Import – ORBV Turnier');
            return;
        }

        View::render('admin/import', ['result' => $result, 'error' => null], 'Teilnehmer-Import – ORBV Turnier');
    }
}
