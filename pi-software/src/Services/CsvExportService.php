<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;

/**
 * Exportiert die Endergebnisse aller Kategorien mit abgeschlossener
 * Endrangliste als flache CSV (eine Zeile pro Teilnehmer:in), zum Upload
 * auf turnier.orbv.ch.
 */
final class CsvExportService
{
    public function exportAlleKategorien(): string
    {
        $pdo = Database::connection();
        $rankingService = new RankingService();

        $storageDir = dirname(__DIR__, 2) . '/storage/exports';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }

        $path = $storageDir . '/ergebnisse_' . date('Y-m-d_His') . '.csv';
        $handle = fopen($path, 'w');

        fputcsv($handle, [
            'kategorie_name', 'startnummer', 'teilnehmer_name', 'name_p2', 'verein_name', 'rang', 'gesamt',
        ], escape: '\\');

        $kategorien = $pdo->query('SELECT id, name FROM kategorien ORDER BY name')->fetchAll();

        foreach ($kategorien as $kategorie) {
            $rangliste = $rankingService->endranglisteFuerKategorie((int) $kategorie['id']);
            if ($rangliste === []) {
                continue;
            }

            $ids = array_map(fn ($r) => (int) $r['teilnehmer_id'], $rangliste);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT id, startnummer, name, name_p2, verein_name FROM teilnehmer WHERE id IN ($placeholders)");
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

                fputcsv($handle, [
                    $kategorie['name'],
                    $t['startnummer'],
                    $t['name'],
                    $t['name_p2'] ?? '',
                    $t['verein_name'] ?? '',
                    $eintrag['rang'] ?? '',
                    $eintrag['gesamt'] ?? '',
                ], escape: '\\');
            }
        }

        fclose($handle);

        return $path;
    }
}
