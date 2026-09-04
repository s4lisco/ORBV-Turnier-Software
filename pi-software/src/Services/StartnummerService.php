<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;

final class StartnummerService
{
    /**
     * Vergibt fortlaufende Startnummern über das gesamte Turnier hinweg,
     * kategorieunabhängig (siehe Plan_Feedback_Umsetzung.md 8.2). Nur
     * Teilnehmer:innen ohne Startnummer erhalten eine neue, damit ein
     * wiederholter Aufruf keine bestehenden Nummern verändert.
     */
    public function vergeben(): int
    {
        $pdo = Database::connection();

        $stmt = $pdo->query('SELECT MAX(startnummer) AS max FROM teilnehmer');
        $next = ((int) ($stmt->fetch()['max'] ?? 0)) + 1;

        $stmt = $pdo->query('SELECT id FROM teilnehmer WHERE startnummer IS NULL ORDER BY id ASC');
        $teilnehmerIds = array_column($stmt->fetchAll(), 'id');

        $update = $pdo->prepare('UPDATE teilnehmer SET startnummer = :startnummer WHERE id = :id');

        $vergeben = 0;
        foreach ($teilnehmerIds as $id) {
            $update->execute(['startnummer' => $next, 'id' => $id]);
            $next++;
            $vergeben++;
        }

        return $vergeben;
    }
}
