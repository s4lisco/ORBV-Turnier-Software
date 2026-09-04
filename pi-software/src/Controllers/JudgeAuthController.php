<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\View;

final class JudgeAuthController
{
    public function showCodeEntry(): void
    {
        if (Auth::judgeId() !== null) {
            header('Location: /kampfrichter/bewertung');
            exit;
        }

        View::render('judge/code_entry', ['error' => null], 'Wertungsrichter-Login – ORBV Turnier');
    }

    public function submitCode(): void
    {
        $code = trim($_POST['code'] ?? '');

        $stmt = Database::connection()->prepare('SELECT id FROM wertungsrichter WHERE turnier_code = :code');
        $stmt->execute(['code' => $code]);
        $richter = $stmt->fetch();

        if (!$richter) {
            View::render('judge/code_entry', ['error' => 'Code ungültig.'], 'Wertungsrichter-Login – ORBV Turnier');
            return;
        }

        Auth::loginJudge((int) $richter['id']);
        header('Location: /kampfrichter/bewertung');
        exit;
    }

    public function logout(): void
    {
        Auth::logoutJudge();
        header('Location: /');
        exit;
    }
}
