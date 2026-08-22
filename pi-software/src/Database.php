<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $path = self::databasePath();
            $pdo = new PDO('sqlite:' . $path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA foreign_keys = ON');
            self::$connection = $pdo;
        }

        return self::$connection;
    }

    public static function databasePath(): string
    {
        return getenv('ORBV_DB_PATH') ?: dirname(__DIR__) . '/database/turnier.sqlite';
    }

    /** Nur für Tests: erzwingt eine neue Verbindung (z.B. nach Wechsel von ORBV_DB_PATH). */
    public static function reset(): void
    {
        self::$connection = null;
    }
}
