<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\View;

final class SetupController
{
    public static function adminExists(): bool
    {
        $count = Database::connection()
            ->query('SELECT COUNT(*) AS c FROM admin')
            ->fetch()['c'];

        return (int) $count > 0;
    }

    public function show(): void
    {
        if (self::adminExists()) {
            header('Location: /admin/login');
            exit;
        }

        View::render('setup', ['error' => null], 'Ersteinrichtung – ORBV Turnier');
    }

    public function submit(): void
    {
        if (self::adminExists()) {
            header('Location: /admin/login');
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

        $error = $this->validate($username, $password, $passwordConfirm);

        if ($error !== null) {
            View::render('setup', ['error' => $error], 'Ersteinrichtung – ORBV Turnier');
            return;
        }

        $stmt = Database::connection()->prepare(
            'INSERT INTO admin (username, password_hash) VALUES (:username, :password_hash)'
        );
        $stmt->execute([
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        Auth::loginAdmin((int) Database::connection()->lastInsertId());

        header('Location: /organisatoren');
        exit;
    }

    private function validate(string $username, string $password, string $passwordConfirm): ?string
    {
        if ($username === '') {
            return 'Benutzername darf nicht leer sein.';
        }

        if (strlen($password) < 12) {
            return 'Passwort muss mindestens 12 Zeichen lang sein.';
        }

        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
            return 'Passwort muss Buchstaben, Zahlen und Sonderzeichen enthalten.';
        }

        if ($password !== $passwordConfirm) {
            return 'Passwörter stimmen nicht überein.';
        }

        return null;
    }
}
