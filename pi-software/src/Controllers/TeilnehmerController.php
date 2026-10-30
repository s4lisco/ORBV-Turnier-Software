<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\View;

/**
 * Verwaltung einzelner Teilnehmer:innen je Kategorie (Feedback-Runde 2): bisher
 * gab es nur den Komplett-Reset oder den CSV-Bulk-Import, keine Möglichkeit,
 * eine einzelne Person zu entfernen (z.B. nach einer Abmeldung/Fehlanmeldung).
 */
final class TeilnehmerController
{
    public function liste(string $kategorieId): void
    {
        Auth::requireAdmin();
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT id, name FROM kategorien WHERE id = :id');
        $stmt->execute(['id' => $kategorieId]);
        $kategorie = $stmt->fetch();

        if (!$kategorie) {
            http_response_code(404);
            echo 'Kategorie nicht gefunden.';
            return;
        }

        $stmt = $pdo->prepare(
            'SELECT id, startnummer, name, name_p2, verein_name, status FROM teilnehmer
             WHERE kategorie_id = :kategorie_id ORDER BY name'
        );
        $stmt->execute(['kategorie_id' => $kategorieId]);
        $teilnehmer = $stmt->fetchAll();

        View::render('admin/teilnehmer_liste', [
            'kategorie' => $kategorie,
            'teilnehmer' => $teilnehmer,
        ], 'Teilnehmer:innen: ' . $kategorie['name'] . ' – ORBV Turnier');
    }

    public function delete(string $teilnehmerId): void
    {
        Auth::requireAdmin();
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT kategorie_id FROM teilnehmer WHERE id = :id');
        $stmt->execute(['id' => $teilnehmerId]);
        $teilnehmer = $stmt->fetch();

        if (!$teilnehmer) {
            http_response_code(404);
            echo 'Teilnehmer:in nicht gefunden.';
            return;
        }

        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM fehler_eintraege WHERE teilnehmer_id = :id')->execute(['id' => $teilnehmerId]);
        $pdo->prepare('DELETE FROM bewertungen WHERE teilnehmer_id = :id')->execute(['id' => $teilnehmerId]);
        $pdo->prepare('DELETE FROM praesenz_status WHERE teilnehmer_id = :id')->execute(['id' => $teilnehmerId]);
        $pdo->prepare('DELETE FROM runde_teilnehmer WHERE teilnehmer_id = :id')->execute(['id' => $teilnehmerId]);
        $pdo->prepare('DELETE FROM teilnehmer WHERE id = :id')->execute(['id' => $teilnehmerId]);
        $pdo->commit();

        header('Location: /admin/kategorien/' . (int) $teilnehmer['kategorie_id'] . '/teilnehmer');
        exit;
    }
}
