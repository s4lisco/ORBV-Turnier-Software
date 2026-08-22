<?php

declare(strict_types=1);

namespace App;

final class Auth
{
    public static function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function loginAdmin(int $adminId): void
    {
        self::startSession();
        $_SESSION['admin_id'] = $adminId;
    }

    public static function logoutAdmin(): void
    {
        self::startSession();
        unset($_SESSION['admin_id']);
    }

    public static function isAdmin(): bool
    {
        self::startSession();
        return isset($_SESSION['admin_id']);
    }

    public static function requireAdmin(): void
    {
        if (!self::isAdmin()) {
            header('Location: /admin/login');
            exit;
        }
    }

    public static function loginJudge(int $judgeId): void
    {
        self::startSession();
        $_SESSION['judge_id'] = $judgeId;
    }

    public static function logoutJudge(): void
    {
        self::startSession();
        unset($_SESSION['judge_id']);
    }

    public static function judgeId(): ?int
    {
        self::startSession();
        return isset($_SESSION['judge_id']) ? (int) $_SESSION['judge_id'] : null;
    }

    public static function requireJudge(): int
    {
        $id = self::judgeId();
        if ($id === null) {
            header('Location: /kampfrichter');
            exit;
        }

        return $id;
    }
}
