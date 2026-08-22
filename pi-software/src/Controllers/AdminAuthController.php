<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\View;

final class AdminAuthController
{
    public function showLogin(): void
    {
        if (!SetupController::adminExists()) {
            header('Location: /admin/setup');
            exit;
        }

        View::render('admin/login', ['error' => null], 'Admin-Login – ORBV Turnier');
    }

    public function login(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        $stmt = Database::connection()->prepare('SELECT * FROM admin WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            View::render('admin/login', ['error' => 'Benutzername oder Passwort falsch.'], 'Admin-Login – ORBV Turnier');
            return;
        }

        Auth::loginAdmin((int) $admin['id']);
        header('Location: /organisatoren');
        exit;
    }

    public function logout(): void
    {
        Auth::logoutAdmin();
        header('Location: /');
        exit;
    }
}
