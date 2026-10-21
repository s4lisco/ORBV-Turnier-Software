<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Services\TurnierResetService;
use App\View;

/**
 * Kompletter Turnier-Reset: löscht alle Turnierdaten (Kategorien, Teilnehmer:innen,
 * Runden, Bewertungen, Wertungsrichter-Codes) für ein neues Turnier. Der
 * Admin-Login bleibt bestehen, die Ersteinrichtung wird daher nicht erneut
 * ausgelöst (Feedback-Runde 2). Gedacht für den Fall, dass ein Turnier
 * fälschlicherweise gestartet oder abgebrochen wurde und komplett neu
 * aufgesetzt werden muss.
 */
final class ResetController
{
    private const BESTAETIGUNGSTEXT = 'ZURÜCKSETZEN';

    public function show(): void
    {
        Auth::requireAdmin();

        View::render('admin/reset', [
            'error' => null,
            'bestaetigungstext' => self::BESTAETIGUNGSTEXT,
        ], 'Turnier zurücksetzen – ORBV Turnier');
    }

    public function submit(): void
    {
        Auth::requireAdmin();

        $pdo = Database::connection();
        $adminId = (int) ($_SESSION['admin_id'] ?? 0);

        $stmt = $pdo->prepare('SELECT * FROM admin WHERE id = :id');
        $stmt->execute(['id' => $adminId]);
        $admin = $stmt->fetch();

        $password = (string) ($_POST['password'] ?? '');
        $bestaetigung = trim($_POST['bestaetigung'] ?? '');

        $error = $this->validate($admin, $password, $bestaetigung);

        if ($error !== null) {
            View::render('admin/reset', [
                'error' => $error,
                'bestaetigungstext' => self::BESTAETIGUNGSTEXT,
            ], 'Turnier zurücksetzen – ORBV Turnier');
            return;
        }

        (new TurnierResetService())->alleDatenLoeschen();
        Auth::logoutAdmin();

        header('Location: /admin/login');
        exit;
    }

    /** @param array<string, mixed>|false $admin */
    private function validate(array|false $admin, string $password, string $bestaetigung): ?string
    {
        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            return 'Passwort falsch.';
        }

        if ($bestaetigung !== self::BESTAETIGUNGSTEXT) {
            return 'Bitte "' . self::BESTAETIGUNGSTEXT . '" zur Bestätigung eingeben.';
        }

        return null;
    }
}
