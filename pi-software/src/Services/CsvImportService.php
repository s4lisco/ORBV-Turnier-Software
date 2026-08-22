<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;
use PDO;
use RuntimeException;

/**
 * Importiert die von turnier.orbv.ch heruntergeladene Teilnehmer-CSV.
 *
 * Erwartete Spalten (Kopfzeile, kommagetrennt):
 * kategorie_name,kategorie_typ,kriterium_name,teilnehmer_typ,teilnehmer_name,
 * geburtsdatum,name_p2,geburtsdatum_p2,anzahl_taenzerinnen,verein_name
 *
 * - kategorie_typ: solo|paar|formation
 * - kriterium_name: mit ";" getrennte Liste der Kriterien dieser Kategorie
 *   (wird nur beim ersten Vorkommen der Kategorie ausgewertet)
 * - teilnehmer_typ: solo|paar|formation
 */
final class CsvImportService
{
    private const REQUIRED_COLUMNS = [
        'kategorie_name', 'kategorie_typ', 'kriterium_name', 'teilnehmer_typ',
        'teilnehmer_name', 'geburtsdatum', 'name_p2', 'geburtsdatum_p2',
        'anzahl_taenzerinnen', 'verein_name',
    ];

    /** @return array{teilnehmer: int, kategorien: int} */
    public function import(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new RuntimeException('CSV-Datei konnte nicht geöffnet werden.');
        }

        $header = fgetcsv($handle, escape: '\\');
        if ($header === false) {
            fclose($handle);
            throw new RuntimeException('CSV-Datei ist leer.');
        }

        $header = array_map('trim', $header);
        $missing = array_diff(self::REQUIRED_COLUMNS, $header);
        if ($missing !== []) {
            fclose($handle);
            throw new RuntimeException('CSV fehlt Spalten: ' . implode(', ', $missing));
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        $kategorieIds = [];
        $importedKategorien = 0;
        $importedTeilnehmer = 0;

        try {
            while (($row = fgetcsv($handle, escape: '\\')) !== false) {
                if (count(array_filter($row, fn ($v) => $v !== null && $v !== '')) === 0) {
                    continue;
                }

                $data = array_combine($header, $row);
                if ($data === false) {
                    continue;
                }

                $kategorieKey = $data['kategorie_name'] . '|' . $data['kategorie_typ'];

                if (!isset($kategorieIds[$kategorieKey])) {
                    $kategorieId = $this->findOrCreateKategorie(
                        $pdo,
                        $data['kategorie_name'],
                        $data['kategorie_typ']
                    );
                    $kategorieIds[$kategorieKey] = $kategorieId;
                    $importedKategorien++;

                    foreach (array_filter(array_map('trim', explode(';', $data['kriterium_name']))) as $kriteriumName) {
                        $this->findOrCreateKriterium($pdo, $kategorieId, $kriteriumName);
                    }
                }

                $kategorieId = $kategorieIds[$kategorieKey];

                $stmt = $pdo->prepare(
                    'INSERT INTO teilnehmer
                        (kategorie_id, typ, name, geburtsdatum, name_p2, geburtsdatum_p2, anzahl_taenzerinnen, verein_name, status)
                     VALUES
                        (:kategorie_id, :typ, :name, :geburtsdatum, :name_p2, :geburtsdatum_p2, :anzahl_taenzerinnen, :verein_name, "aktiv")'
                );
                $stmt->execute([
                    'kategorie_id' => $kategorieId,
                    'typ' => $data['teilnehmer_typ'],
                    'name' => $data['teilnehmer_name'],
                    'geburtsdatum' => $data['geburtsdatum'] !== '' ? $data['geburtsdatum'] : null,
                    'name_p2' => $data['name_p2'] !== '' ? $data['name_p2'] : null,
                    'geburtsdatum_p2' => $data['geburtsdatum_p2'] !== '' ? $data['geburtsdatum_p2'] : null,
                    'anzahl_taenzerinnen' => $data['anzahl_taenzerinnen'] !== '' ? (int) $data['anzahl_taenzerinnen'] : null,
                    'verein_name' => $data['verein_name'] !== '' ? $data['verein_name'] : null,
                ]);
                $importedTeilnehmer++;
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            fclose($handle);
            throw $e;
        }

        fclose($handle);

        return ['teilnehmer' => $importedTeilnehmer, 'kategorien' => $importedKategorien];
    }

    private function findOrCreateKategorie(PDO $pdo, string $name, string $typ): int
    {
        $stmt = $pdo->prepare('SELECT id FROM kategorien WHERE name = :name AND typ = :typ');
        $stmt->execute(['name' => $name, 'typ' => $typ]);
        $existing = $stmt->fetch();

        if ($existing) {
            return (int) $existing['id'];
        }

        $stmt = $pdo->prepare('INSERT INTO kategorien (name, typ) VALUES (:name, :typ)');
        $stmt->execute(['name' => $name, 'typ' => $typ]);

        return (int) $pdo->lastInsertId();
    }

    private function findOrCreateKriterium(PDO $pdo, int $kategorieId, string $name): void
    {
        $stmt = $pdo->prepare(
            'INSERT OR IGNORE INTO kriterien (kategorie_id, name) VALUES (:kategorie_id, :name)'
        );
        $stmt->execute(['kategorie_id' => $kategorieId, 'name' => $name]);
    }
}
