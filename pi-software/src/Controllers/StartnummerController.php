<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Services\StartnummerService;
use App\View;

final class StartnummerController
{
    public function vergeben(): void
    {
        Auth::requireAdmin();

        (new StartnummerService())->vergeben();

        header('Location: /organisatoren');
        exit;
    }

    /**
     * Sperrliste für Startnummern (Feedback-Runde 2): Nummern, die hier
     * eingetragen sind, werden bei "Startnummern vergeben" übersprungen (z.B.
     * weil eine physische Nummernkarte fehlt oder beschädigt ist).
     */
    public function sperrliste(): void
    {
        Auth::requireAdmin();

        $gesperrt = Database::connection()
            ->query('SELECT nummer FROM startnummern_sperrliste ORDER BY nummer ASC')
            ->fetchAll();

        View::render('admin/startnummern_sperrliste', [
            'gesperrt' => $gesperrt,
        ], 'Startnummern-Sperrliste – ORBV Turnier');
    }

    public function sperrlisteHinzufuegen(): void
    {
        Auth::requireAdmin();

        $nummer = (int) ($_POST['nummer'] ?? 0);
        if ($nummer > 0) {
            Database::connection()
                ->prepare('INSERT OR IGNORE INTO startnummern_sperrliste (nummer) VALUES (:nummer)')
                ->execute(['nummer' => $nummer]);
        }

        header('Location: /admin/startnummern/sperrliste');
        exit;
    }

    public function sperrlisteEntfernen(string $nummer): void
    {
        Auth::requireAdmin();

        Database::connection()
            ->prepare('DELETE FROM startnummern_sperrliste WHERE nummer = :nummer')
            ->execute(['nummer' => (int) $nummer]);

        header('Location: /admin/startnummern/sperrliste');
        exit;
    }
}
