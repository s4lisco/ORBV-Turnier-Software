<?php

use App\Http\Controllers\Admin\AnmeldungController as AdminAnmeldungController;
use App\Http\Controllers\ErgebnisseController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EinstellungController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\KategorieController;
use App\Http\Controllers\Admin\RanglisteController;
use App\Http\Controllers\Admin\SetupController;
use App\Http\Controllers\Admin\TurnierController as AdminTurnierController;
use App\Http\Controllers\Admin\VereinController;
use App\Http\Controllers\Verein\AnmeldungController as VereinAnmeldungController;
use App\Http\Controllers\Verein\AuthController as VereinAuthController;
use App\Http\Controllers\Verein\DashboardController as VereinDashboardController;
use App\Http\Controllers\Verein\TurnierController as VereinTurnierController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('home'))->name('home');

// --- Öffentliche Ergebnisseite (kein Login nötig, siehe Plan_Feedback_Umsetzung.md 9.3) ---
Route::get('ergebnisse', [ErgebnisseController::class, 'index'])->name('ergebnisse.index');
Route::get('ergebnisse/{turnier}', [ErgebnisseController::class, 'show'])->name('ergebnisse.show');

// --- Admin: Ersteinrichtung & Login (kein auth:admin nötig) ---
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('setup', [SetupController::class, 'show'])->name('setup');
    Route::post('setup', [SetupController::class, 'store'])->name('setup.store');
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.attempt');
});

// --- Admin: geschützter Bereich ---
Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function () {
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
    Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('einstellungen', [EinstellungController::class, 'edit'])->name('einstellungen.edit');
    Route::put('einstellungen', [EinstellungController::class, 'update'])->name('einstellungen.update');

    Route::resource('vereine', VereinController::class)->except(['show'])->parameters(['vereine' => 'verein']);
    Route::resource('kategorien', KategorieController::class)->only(['index', 'edit', 'update'])->parameters(['kategorien' => 'kategorie']);
    Route::resource('turniere', AdminTurnierController::class)->parameters(['turniere' => 'turnier']);

    Route::get('turniere/{turnier}/export', [ExportController::class, 'download'])->name('turniere.export');
    Route::post('turniere/{turnier}/rangliste', [RanglisteController::class, 'upload'])->name('turniere.rangliste.upload');

    Route::prefix('turniere/{turnier}/kategorien/{kategorie}')->name('turniere.anmeldungen.')->group(function () {
        Route::post('solo', [AdminAnmeldungController::class, 'storeSolo'])->name('solo.store');
        Route::post('paar', [AdminAnmeldungController::class, 'storePaar'])->name('paar.store');
        Route::post('formation', [AdminAnmeldungController::class, 'storeFormation'])->name('formation.store');
    });

    Route::prefix('turniere/{turnier}')->name('turniere.')->group(function () {
        Route::delete('solo/{anmeldungSolo}', [AdminAnmeldungController::class, 'destroySolo'])->name('anmeldungen.solo.destroy');
        Route::put('solo/{anmeldungSolo}', [AdminAnmeldungController::class, 'updateSolo'])->name('anmeldungen.solo.update');
        Route::delete('paar/{anmeldungPaar}', [AdminAnmeldungController::class, 'destroyPaar'])->name('anmeldungen.paar.destroy');
        Route::put('paar/{anmeldungPaar}', [AdminAnmeldungController::class, 'updatePaar'])->name('anmeldungen.paar.update');
        Route::delete('formation/{anmeldungFormation}', [AdminAnmeldungController::class, 'destroyFormation'])->name('anmeldungen.formation.destroy');
        Route::put('formation/{anmeldungFormation}', [AdminAnmeldungController::class, 'updateFormation'])->name('anmeldungen.formation.update');
    });
});

// --- Verein: Login (kein auth:verein nötig) ---
Route::prefix('verein')->name('verein.')->group(function () {
    Route::get('login', [VereinAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [VereinAuthController::class, 'login'])->name('login.attempt');
});

// --- Verein: geschützter Bereich ---
Route::prefix('verein')->name('verein.')->middleware('auth:verein')->group(function () {
    Route::post('logout', [VereinAuthController::class, 'logout'])->name('logout');
    Route::get('dashboard', [VereinDashboardController::class, 'index'])->name('dashboard');

    Route::get('turniere/{turnier}', [VereinTurnierController::class, 'show'])->name('turniere.show');

    Route::prefix('turniere/{turnier}/kategorien/{kategorie}')->name('anmeldungen.')->group(function () {
        Route::get('solo/create', [VereinAnmeldungController::class, 'createSolo'])->name('solo.create');
        Route::post('solo', [VereinAnmeldungController::class, 'storeSolo'])->name('solo.store');
        Route::get('solo/{anmeldungSolo}/edit', [VereinAnmeldungController::class, 'editSolo'])->name('solo.edit');
        Route::put('solo/{anmeldungSolo}', [VereinAnmeldungController::class, 'updateSolo'])->name('solo.update');
        Route::delete('solo/{anmeldungSolo}', [VereinAnmeldungController::class, 'destroySolo'])->name('solo.destroy');

        Route::get('paar/create', [VereinAnmeldungController::class, 'createPaar'])->name('paar.create');
        Route::post('paar', [VereinAnmeldungController::class, 'storePaar'])->name('paar.store');
        Route::get('paar/{anmeldungPaar}/edit', [VereinAnmeldungController::class, 'editPaar'])->name('paar.edit');
        Route::put('paar/{anmeldungPaar}', [VereinAnmeldungController::class, 'updatePaar'])->name('paar.update');
        Route::delete('paar/{anmeldungPaar}', [VereinAnmeldungController::class, 'destroyPaar'])->name('paar.destroy');

        Route::get('formation/create', [VereinAnmeldungController::class, 'createFormation'])->name('formation.create');
        Route::post('formation', [VereinAnmeldungController::class, 'storeFormation'])->name('formation.store');
        Route::get('formation/{anmeldungFormation}/edit', [VereinAnmeldungController::class, 'editFormation'])->name('formation.edit');
        Route::put('formation/{anmeldungFormation}', [VereinAnmeldungController::class, 'updateFormation'])->name('formation.update');
        Route::delete('formation/{anmeldungFormation}', [VereinAnmeldungController::class, 'destroyFormation'])->name('formation.destroy');
    });
});
