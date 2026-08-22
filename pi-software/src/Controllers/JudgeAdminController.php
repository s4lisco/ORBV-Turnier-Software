<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\View;

final class JudgeAdminController
{
    private const ANZAHL_RICHTER = 5;

    public function index(): void
    {
        Auth::requireAdmin();

        $richter = Database::connection()
            ->query('SELECT id, turnier_code FROM wertungsrichter ORDER BY id')
            ->fetchAll();

        View::render('admin/judges', [
            'richter' => $richter,
            'maxRichter' => self::ANZAHL_RICHTER,
        ], 'Kampfrichter – ORBV Turnier');
    }

    public function generate(): void
    {
        Auth::requireAdmin();

        $pdo = Database::connection();
        $vorhanden = (int) $pdo->query('SELECT COUNT(*) AS c FROM wertungsrichter')->fetch()['c'];

        $insert = $pdo->prepare('INSERT INTO wertungsrichter (turnier_code) VALUES (:code)');

        for ($i = $vorhanden; $i < self::ANZAHL_RICHTER; $i++) {
            $code = $this->generateUniqueCode($pdo);
            $insert->execute(['code' => $code]);
        }

        header('Location: /admin/judges');
        exit;
    }

    private function generateUniqueCode(\PDO $pdo): string
    {
        $check = $pdo->prepare('SELECT COUNT(*) AS c FROM wertungsrichter WHERE turnier_code = :code');

        do {
            $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $check->execute(['code' => $code]);
            $exists = (int) $check->fetch()['c'] > 0;
        } while ($exists);

        return $code;
    }
}
