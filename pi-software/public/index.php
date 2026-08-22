<?php

declare(strict_types=1);

// Wird als Router-Skript für den PHP-Built-in-Server genutzt (siehe
// docker-compose.yml). Statische Dateien (z.B. /css/app.css) werden direkt
// vom Server ausgeliefert, alles andere läuft über den eigenen Router.
$requestedFile = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (php_sapi_name() === 'cli-server' && $requestedFile !== __DIR__ . '/' && is_file($requestedFile)) {
    return false;
}

require dirname(__DIR__) . '/src/autoload.php';

use App\Auth;
use App\Router;
use App\Controllers\AdminAuthController;
use App\Controllers\DashboardController;
use App\Controllers\ExportController;
use App\Controllers\HomeController;
use App\Controllers\ImportController;
use App\Controllers\JudgeAdminController;
use App\Controllers\JudgeAuthController;
use App\Controllers\ResultsController;
use App\Controllers\RoundController;
use App\Controllers\ScoringController;
use App\Controllers\SetupController;
use App\Controllers\StartnummerController;

Auth::startSession();

$router = new Router();

$router->get('/', [HomeController::class, 'index']);

// Ersteinrichtung
$router->get('/admin/setup', [SetupController::class, 'show']);
$router->post('/admin/setup', [SetupController::class, 'submit']);

// Admin-Login
$router->get('/admin/login', [AdminAuthController::class, 'showLogin']);
$router->post('/admin/login', [AdminAuthController::class, 'login']);
$router->post('/admin/logout', [AdminAuthController::class, 'logout']);

// Organisator-Dashboard
$router->get('/organisatoren', [DashboardController::class, 'index']);

// Import & Startnummern
$router->get('/admin/import', [ImportController::class, 'show']);
$router->post('/admin/import', [ImportController::class, 'upload']);
$router->post('/admin/startnummern', [StartnummerController::class, 'vergeben']);

// Kampfrichter-Verwaltung (Admin-Seite)
$router->get('/admin/judges', [JudgeAdminController::class, 'index']);
$router->post('/admin/judges/generate', [JudgeAdminController::class, 'generate']);

// Rundensteuerung
$router->get('/admin/runden', [RoundController::class, 'index']);
$router->post('/admin/runden/{kategorieId}/starten', [RoundController::class, 'starten']);
$router->get('/admin/runden/{rundeId}', [RoundController::class, 'show']);
$router->post('/admin/runden/{rundeId}/freigeben', [RoundController::class, 'freigeben']);
$router->post('/admin/runden/{rundeId}/abschliessen', [RoundController::class, 'abschliessen']);

// Ergebnisse & Export
$router->get('/admin/ergebnisse', [ResultsController::class, 'index']);
$router->get('/admin/ergebnisse/{kategorieId}', [ResultsController::class, 'show']);
$router->get('/admin/export', [ExportController::class, 'show']);
$router->get('/admin/export/download', [ExportController::class, 'download']);

// Kampfrichter-Flow
$router->get('/kampfrichter', [JudgeAuthController::class, 'showCodeEntry']);
$router->post('/kampfrichter', [JudgeAuthController::class, 'submitCode']);
$router->post('/kampfrichter/logout', [JudgeAuthController::class, 'logout']);
$router->get('/kampfrichter/bewertung', [ScoringController::class, 'index']);
$router->get('/kampfrichter/bewertung/{rundeId}/{teilnehmerId}', [ScoringController::class, 'showForm']);
$router->post('/kampfrichter/bewertung/{rundeId}/{teilnehmerId}', [ScoringController::class, 'submit']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
