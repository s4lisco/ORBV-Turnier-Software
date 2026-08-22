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

$pdo->exec($schema);

echo "Migration abgeschlossen: " . Database::databasePath() . PHP_EOL;
