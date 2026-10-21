<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;

/**
 * Exportiert die Endrangliste als flache CSV zum Upload auf turnier.orbv.ch.
 */
final class CsvExportService
{
    /**
     * Export für den Rangliste-Upload auf turnier.orbv.ch (siehe
     * Plan_Feedback_Umsetzung.md 9.3).
     *
     * Format (eine Datei für alle Kategorien): kategorie_id,kategorie_name,rang,name,verein_name
     * kategorie_id ist die remote_id aus dem Import (siehe CsvImportService),
     * damit turnier.orbv.ch die Kategorie eindeutig der eigenen Kategorie-ID
     * zuordnen kann. Kategorien ohne remote_id (z.B. lokal ohne Import
     * angelegt) werden übersprungen, da keine eindeutige Zuordnung möglich ist.
     * DNF-Teilnehmer:innen erscheinen nicht in der Rangliste (Feedback-Runde 2)
     * und werden daher hier auch nicht exportiert.
     */
    public function exportRanglisteFuerUpload(): string
    {
        $pdo = Database::connection();
        $rankingService = new RankingService();

        $storageDir = dirname(__DIR__, 2) . '/storage/exports';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }

        $path = $storageDir . '/rangliste_' . date('Y-m-d_His') . '.csv';
        $handle = fopen($path, 'w');

        fputcsv($handle, ['kategorie_id', 'kategorie_name', 'rang', 'name', 'verein_name'], escape: '\\');

        $kategorien = $pdo->query('SELECT id, remote_id, name FROM kategorien ORDER BY name')->fetchAll();

        foreach ($kategorien as $kategorie) {
            if ($kategorie['remote_id'] === null) {
                continue;
            }

            $rangliste = $rankingService->endranglisteFuerKategorie((int) $kategorie['id']);
            if ($rangliste === []) {
                continue;
            }

            $ids = array_map(fn ($r) => (int) $r['teilnehmer_id'], $rangliste);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT id, name, name_p2, verein_name FROM teilnehmer WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $teilnehmer = [];
            foreach ($stmt->fetchAll() as $t) {
                $teilnehmer[(int) $t['id']] = $t;
            }

            foreach ($rangliste as $eintrag) {
                $t = $teilnehmer[$eintrag['teilnehmer_id']] ?? null;
                if (!$t) {
                    continue;
                }

                $name = $t['name'] . ($t['name_p2'] ? ' & ' . $t['name_p2'] : '');

                fputcsv($handle, [
                    $kategorie['remote_id'],
                    $kategorie['name'],
                    $eintrag['rang'],
                    $name,
                    $t['verein_name'] ?? '',
                ], escape: '\\');
            }
        }

        fclose($handle);

        return $path;
    }
}
