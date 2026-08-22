<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Services\CsvExportService;
use App\View;

final class ExportController
{
    public function show(): void
    {
        Auth::requireAdmin();
        View::render('admin/export', [], 'Export – ORBV Turnier');
    }

    public function download(): void
    {
        Auth::requireAdmin();

        $path = (new CsvExportService())->exportAlleKategorien();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
