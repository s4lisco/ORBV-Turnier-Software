<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/autoload.php';

use App\Database;

$GLOBALS['__tests_passed'] = 0;
$GLOBALS['__tests_failed'] = 0;

function orbv_fresh_database(): void
{
    $path = sys_get_temp_dir() . '/orbv_test_' . uniqid() . '.sqlite';
    putenv('ORBV_DB_PATH=' . $path);
    Database::reset();

    $schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
    Database::connection()->exec($schema);
}

function test(string $name, callable $fn): void
{
    orbv_fresh_database();

    try {
        $fn();
        $GLOBALS['__tests_passed']++;
        echo "  ✓ $name\n";
    } catch (\Throwable $e) {
        $GLOBALS['__tests_failed']++;
        echo "  ✗ $name\n";
        echo '    ' . get_class($e) . ': ' . $e->getMessage() . "\n";
        echo '    ' . $e->getFile() . ':' . $e->getLine() . "\n";
    }
}

function assertEqualsFloat(float $expected, float $actual, string $message = '', float $epsilon = 0.001): void
{
    if (abs($expected - $actual) > $epsilon) {
        throw new \RuntimeException(($message !== '' ? "$message: " : '') . "erwartet $expected, erhalten $actual");
    }
}

function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new \RuntimeException(
            ($message !== '' ? "$message: " : '')
            . 'erwartet ' . var_export($expected, true) . ', erhalten ' . var_export($actual, true)
        );
    }
}

function assertTrue(bool $condition, string $message = ''): void
{
    if (!$condition) {
        throw new \RuntimeException($message !== '' ? $message : 'Erwartete true, war false.');
    }
}

function orbv_runde_id(int $kategorieId, string $typ): ?int
{
    $stmt = Database::connection()->prepare('SELECT id FROM runden WHERE kategorie_id = :kategorie_id AND typ = :typ');
    $stmt->execute(['kategorie_id' => $kategorieId, 'typ' => $typ]);
    $row = $stmt->fetch();

    return $row ? (int) $row['id'] : null;
}

function orbv_runde_teilnehmer_anzahl(int $rundeId): int
{
    $stmt = Database::connection()->prepare('SELECT COUNT(*) AS c FROM runde_teilnehmer WHERE runde_id = :runde_id');
    $stmt->execute(['runde_id' => $rundeId]);

    return (int) $stmt->fetch()['c'];
}

/** Legt eine Kategorie mit Kriterien an und gibt die kategorie_id zurück. */
function orbv_seed_kategorie(string $name, string $typ, array $kriterien): int
{
    $pdo = Database::connection();
    $stmt = $pdo->prepare('INSERT INTO kategorien (name, typ) VALUES (:name, :typ)');
    $stmt->execute(['name' => $name, 'typ' => $typ]);
    $kategorieId = (int) $pdo->lastInsertId();

    $insert = $pdo->prepare('INSERT INTO kriterien (kategorie_id, name) VALUES (:kategorie_id, :name)');
    foreach ($kriterien as $kriteriumName) {
        $insert->execute(['kategorie_id' => $kategorieId, 'name' => $kriteriumName]);
    }

    return $kategorieId;
}

/** Legt $anzahl aktive Solo-Teilnehmer:innen in einer Kategorie an, gibt die teilnehmer_ids zurück. */
function orbv_seed_teilnehmer(int $kategorieId, int $anzahl): array
{
    $pdo = Database::connection();
    $insert = $pdo->prepare(
        'INSERT INTO teilnehmer (kategorie_id, typ, name, startnummer, status) VALUES (:kategorie_id, "solo", :name, :startnummer, "aktiv")'
    );

    $ids = [];
    for ($i = 1; $i <= $anzahl; $i++) {
        $insert->execute(['kategorie_id' => $kategorieId, 'name' => "Teilnehmer $i", 'startnummer' => $i]);
        $ids[] = (int) $pdo->lastInsertId();
    }

    return $ids;
}

/** Legt 5 Wertungsrichter an, gibt deren ids zurück. */
function orbv_seed_richter(): array
{
    $pdo = Database::connection();
    $insert = $pdo->prepare('INSERT INTO wertungsrichter (turnier_code) VALUES (:code)');

    $ids = [];
    for ($i = 1; $i <= 5; $i++) {
        $insert->execute(['code' => str_pad((string) $i, 4, '0', STR_PAD_LEFT)]);
        $ids[] = (int) $pdo->lastInsertId();
    }

    return $ids;
}

/**
 * Bewertet einen Teilnehmer in einer Runde mit allen 5 Richtern; gleicher
 * Punktewert für alle Kriterien (ausreichend für Struktur-/Grenzfalltests).
 */
function orbv_bewerten(int $rundeId, int $teilnehmerId, int $kategorieId, array $richterIds, float $punkteProKriterium): void
{
    $pdo = Database::connection();
    $kStmt = $pdo->prepare('SELECT id FROM kriterien WHERE kategorie_id = :kategorie_id');
    $kStmt->execute(['kategorie_id' => $kategorieId]);
    $kriterienIds = array_column($kStmt->fetchAll(), 'id');

    $insert = $pdo->prepare(
        'INSERT INTO bewertungen (runde_id, teilnehmer_id, wertungsrichter_id, kriterium_id, punkte)
         VALUES (:runde_id, :teilnehmer_id, :wertungsrichter_id, :kriterium_id, :punkte)'
    );

    foreach ($richterIds as $richterId) {
        foreach ($kriterienIds as $kriteriumId) {
            $insert->execute([
                'runde_id' => $rundeId,
                'teilnehmer_id' => $teilnehmerId,
                'wertungsrichter_id' => $richterId,
                'kriterium_id' => $kriteriumId,
                'punkte' => $punkteProKriterium,
            ]);
        }
    }
}
