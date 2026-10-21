<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;

/**
 * Kompletter Turnier-Reset: löscht alle Turnierdaten (Kategorien, Teilnehmer:innen,
 * Runden, Bewertungen, Wertungsrichter-Codes) für ein neues Turnier. Der
 * Admin-Login bleibt dabei bestehen, damit die Ersteinrichtung nach einem
 * Reset nicht erneut durchlaufen werden muss (Feedback-Runde 2).
 */
final class TurnierResetService
{
    public function alleDatenLoeschen(): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        // Reihenfolge respektiert Fremdschlüssel-Abhängigkeiten (siehe schema.sql).
        $pdo->exec('DELETE FROM fehler_eintraege');
        $pdo->exec('DELETE FROM bewertungen');
        $pdo->exec('DELETE FROM praesenz_status');
        $pdo->exec('DELETE FROM runde_teilnehmer');
        $pdo->exec('DELETE FROM runden');
        $pdo->exec('DELETE FROM teilnehmer');
        $pdo->exec('DELETE FROM kriterien');
        $pdo->exec('DELETE FROM kategorien');
        $pdo->exec('DELETE FROM wertungsrichter');
        $pdo->exec(
            "DELETE FROM sqlite_sequence WHERE name != 'admin'"
        );

        $pdo->commit();
    }
}
