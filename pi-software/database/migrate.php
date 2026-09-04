<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/autoload.php';

use App\Database;

$pdo = Database::connection();
$schema = file_get_contents(__DIR__ . '/schema.sql');

if ($schema === false) {
    fwrite(STDERR, "Konnte schema.sql nicht lesen.\n");
    exit(1);
}

// schema.sql legt nur fehlende Tabellen an (CREATE TABLE IF NOT EXISTS) – bei
// bereits existierenden Tabellen aus älteren Installationen müssen
// nachträglich hinzugekommene Spalten/Constraints separat migriert werden.
$pdo->exec($schema);

migriereKategorienRemoteId($pdo);
migriereTeilnehmerDnfStatus($pdo);

echo "Migration abgeschlossen: " . Database::databasePath() . PHP_EOL;

function spalteExistiert(PDO $pdo, string $tabelle, string $spalte): bool
{
    $stmt = $pdo->query("PRAGMA table_info($tabelle)");
    foreach ($stmt->fetchAll() as $row) {
        if ($row['name'] === $spalte) {
            return true;
        }
    }

    return false;
}

/** Fügt kategorien.remote_id nachträglich hinzu (siehe Plan_Feedback_Umsetzung.md 8.1b). */
function migriereKategorienRemoteId(PDO $pdo): void
{
    if (spalteExistiert($pdo, 'kategorien', 'remote_id')) {
        return;
    }

    echo "Migriere: kategorien.remote_id hinzufügen…\n";
    // ALTER TABLE ... ADD COLUMN erlaubt in SQLite kein UNIQUE direkt am
    // Spaltenausdruck, daher separater Unique-Index mit gleicher Wirkung.
    $pdo->exec('ALTER TABLE kategorien ADD COLUMN remote_id INTEGER');
    $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_kategorien_remote_id ON kategorien(remote_id)');
}

/** Baut teilnehmer neu auf, damit status CHECK 'dnf' statt '-99' erlaubt (siehe Plan_Feedback_Umsetzung.md 8.4). */
function migriereTeilnehmerDnfStatus(PDO $pdo): void
{
    $sql = $pdo->query("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'teilnehmer'")->fetch();

    if ($sql === false || str_contains($sql['sql'], "'dnf'")) {
        return;
    }

    echo "Migriere: teilnehmer.status CHECK-Constraint auf 'dnf' umstellen…\n";

    $pdo->exec('PRAGMA foreign_keys = OFF');
    $pdo->beginTransaction();

    $pdo->exec(
        "CREATE TABLE teilnehmer_neu (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            startnummer INTEGER,
            kategorie_id INTEGER NOT NULL REFERENCES kategorien(id),
            typ TEXT NOT NULL CHECK (typ IN ('solo', 'paar', 'formation')),
            name TEXT NOT NULL,
            geburtsdatum TEXT,
            name_p2 TEXT,
            geburtsdatum_p2 TEXT,
            anzahl_taenzerinnen INTEGER,
            verein_name TEXT,
            status TEXT NOT NULL DEFAULT 'aktiv' CHECK (status IN ('aktiv', 'dnf'))
        )"
    );

    $pdo->exec(
        "INSERT INTO teilnehmer_neu
         SELECT id, startnummer, kategorie_id, typ, name, geburtsdatum, name_p2, geburtsdatum_p2,
                anzahl_taenzerinnen, verein_name,
                CASE WHEN status = '-99' THEN 'dnf' ELSE status END
         FROM teilnehmer"
    );

    $pdo->exec('DROP TABLE teilnehmer');
    $pdo->exec('ALTER TABLE teilnehmer_neu RENAME TO teilnehmer');

    $pdo->commit();
    $pdo->exec('PRAGMA foreign_keys = ON');
}
