<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Services\CsvExportService;
use App\View;

/**
 * Export für den Rangliste-Upload auf turnier.orbv.ch (Plan_Feedback_Umsetzung.md 9.3).
 */
final class RanglisteExportController
{
    public function show(): void
    {
        Auth::requireAdmin();
        View::render('admin/rangliste_export', [], 'Rangliste für turnier.orbv.ch – ORBV Turnier');
    }

    public function download(): void
    {
        Auth::requireAdmin();

        $path = (new CsvExportService())->exportRanglisteFuerUpload();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
