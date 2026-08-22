<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Services\StartnummerService;

final class StartnummerController
{
    public function vergeben(): void
    {
        Auth::requireAdmin();

        (new StartnummerService())->vergebenFuerAlleKategorien();

        header('Location: /organisatoren');
        exit;
    }
}
