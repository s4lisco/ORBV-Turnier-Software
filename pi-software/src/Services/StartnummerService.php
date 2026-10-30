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
     * wiederholter Aufruf keine bestehenden Nummern verändert. Nummern auf der
     * Sperrliste (`startnummern_sperrliste`, Feedback-Runde 2) werden dabei
     * übersprungen. Bereits vergebene Nummern werden hier NICHT angetastet,
     * auch wenn sie inzwischen gesperrt wurden – das übernimmt erst
     * `ersetzeGesperrteNummernFuerKategorie()` beim "Neu mischen" vor dem
     * Rundenstart.
     */
    public function vergeben(): int
    {
        $pdo = Database::connection();

        $stmt = $pdo->query('SELECT MAX(startnummer) AS max FROM teilnehmer');
        $next = ((int) ($stmt->fetch()['max'] ?? 0)) + 1;

        $gesperrt = array_flip(array_map(
            'intval',
            array_column($pdo->query('SELECT nummer FROM startnummern_sperrliste')->fetchAll(), 'nummer')
        ));

        $stmt = $pdo->query('SELECT id FROM teilnehmer WHERE startnummer IS NULL ORDER BY id ASC');
        $teilnehmerIds = array_column($stmt->fetchAll(), 'id');

        $update = $pdo->prepare('UPDATE teilnehmer SET startnummer = :startnummer WHERE id = :id');

        $vergeben = 0;
        foreach ($teilnehmerIds as $id) {
            while (isset($gesperrt[$next])) {
                $next++;
            }

            $update->execute(['startnummer' => $next, 'id' => $id]);
            $next++;
            $vergeben++;
        }

        return $vergeben;
    }

    /**
     * Entzieht Teilnehmer:innen einer Kategorie ihre Startnummer, falls diese
     * inzwischen auf der Sperrliste steht, und vergibt direkt eine neue, nicht
     * gesperrte Nummer (Feedback-Runde 2). Wird von
     * `RoundService::mischeKategorieStartreihenfolge()` aufgerufen, bevor die
     * Startreihenfolge gemischt wird – also immer vor dem Rundenstart, nie an
     * bereits laufenden/abgeschlossenen Runden.
     */
    public function ersetzeGesperrteNummernFuerKategorie(int $kategorieId): void
    {
        $pdo = Database::connection();

        $gesperrtNummern = array_map(
            'intval',
            array_column($pdo->query('SELECT nummer FROM startnummern_sperrliste')->fetchAll(), 'nummer')
        );
        if ($gesperrtNummern === []) {
            return;
        }

        $gesperrt = array_flip($gesperrtNummern);
        $platzhalter = implode(',', array_fill(0, count($gesperrtNummern), '?'));
        $stmt = $pdo->prepare(
            "SELECT id FROM teilnehmer WHERE kategorie_id = ? AND startnummer IN ($platzhalter)"
        );
        $stmt->execute([$kategorieId, ...$gesperrtNummern]);
        $betroffeneIds = array_map('intval', array_column($stmt->fetchAll(), 'id'));

        if ($betroffeneIds === []) {
            return;
        }

        $idPlatzhalter = implode(',', array_fill(0, count($betroffeneIds), '?'));
        $pdo->prepare("UPDATE teilnehmer SET startnummer = NULL WHERE id IN ($idPlatzhalter)")
            ->execute($betroffeneIds);

        $stmt = $pdo->query('SELECT MAX(startnummer) AS max FROM teilnehmer');
        $next = ((int) ($stmt->fetch()['max'] ?? 0)) + 1;

        $update = $pdo->prepare('UPDATE teilnehmer SET startnummer = :startnummer WHERE id = :id');
        foreach ($betroffeneIds as $id) {
            while (isset($gesperrt[$next])) {
                $next++;
            }

            $update->execute(['startnummer' => $next, 'id' => $id]);
            $next++;
        }
    }
}
