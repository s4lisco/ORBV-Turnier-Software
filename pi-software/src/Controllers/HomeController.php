<?php

declare(strict_types=1);

namespace App\Controllers;

use App\View;

final class HomeController
{
    public function index(): void
    {
        View::render('home', [], 'ORBV Turnier-Software');
    }
}
