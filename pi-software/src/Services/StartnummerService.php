<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;

final class StartnummerService
{
    /**
     * Vergibt fortlaufende Startnummern pro Kategorie (nur für Teilnehmer:innen
     * ohne Startnummer, damit ein wiederholter Aufruf keine neuen Nummern vergibt).
     */
    public function vergebenFuerKategorie(int $kategorieId): int
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT MAX(startnummer) AS max FROM teilnehmer WHERE kategorie_id = :kategorie_id');
        $stmt->execute(['kategorie_id' => $kategorieId]);
        $next = ((int) ($stmt->fetch()['max'] ?? 0)) + 1;

        $stmt = $pdo->prepare(
            'SELECT id FROM teilnehmer WHERE kategorie_id = :kategorie_id AND startnummer IS NULL ORDER BY id ASC'
        );
        $stmt->execute(['kategorie_id' => $kategorieId]);
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

    public function vergebenFuerAlleKategorien(): int
    {
        $pdo = Database::connection();
        $kategorieIds = array_column($pdo->query('SELECT id FROM kategorien')->fetchAll(), 'id');

        $gesamt = 0;
        foreach ($kategorieIds as $kategorieId) {
            $gesamt += $this->vergebenFuerKategorie((int) $kategorieId);
        }

        return $gesamt;
    }
}
