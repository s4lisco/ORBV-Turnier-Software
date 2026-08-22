<?php

declare(strict_types=1);

namespace App;

final class View
{
    public static function render(string $view, array $data = [], string $title = 'ORBV Turnier'): void
    {
        extract($data);

        ob_start();
        require dirname(__DIR__) . '/src/Views/' . $view . '.php';
        $content = ob_get_clean();

        require dirname(__DIR__) . '/src/Views/layout.php';
    }
}
