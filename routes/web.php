<?php

use App\Http\Controllers\Gestion\ConsoleController;
use Illuminate\Support\Facades\Route;

// L'ancien bureau à cahier unique (/noms, /aliments…) est remplacé par /gestion.
Route::redirect('/', '/gestion');
Route::redirect('/connexion', '/gestion/connexion');
Route::get('/{ancien}', fn () => redirect('/gestion'))
    ->where('ancien', '(noms|aliments|verrou|bilan|session)(/.*)?');

// Back-office de gestion des comptes de l'application mobile.
Route::prefix('gestion')->name('gestion.')->group(function () {
    Route::get('/connexion', [ConsoleController::class, 'loginForm'])->name('login');
    Route::post('/connexion', [ConsoleController::class, 'login'])->middleware('throttle:10,1')->name('login.store');

    Route::middleware('auth')->group(function () {
        Route::get('/', [ConsoleController::class, 'home'])->name('home');
        Route::post('/deconnexion', [ConsoleController::class, 'logout'])->name('logout');
        Route::get('/comptes', [ConsoleController::class, 'index'])->name('comptes');
        Route::get('/comptes/{account}', [ConsoleController::class, 'show'])->name('compte');
        Route::get('/comptes/{account}/images/{uid}', [ConsoleController::class, 'image'])->name('compte.image');
        Route::post('/comptes/{account}/essai', [ConsoleController::class, 'trial'])->name('compte.essai');
        Route::post('/comptes/{account}/renouveler', [ConsoleController::class, 'renew'])->name('compte.renouveler');
        Route::post('/comptes/{account}/terminer', [ConsoleController::class, 'stop'])->name('compte.terminer');
        Route::post('/comptes/{account}/suspendre', [ConsoleController::class, 'block'])->name('compte.suspendre');
        Route::post('/comptes/{account}/code', [ConsoleController::class, 'resetCode'])->name('compte.code');
        Route::post('/comptes/{account}/appareils', [ConsoleController::class, 'devices'])->name('compte.appareils');
        Route::post('/comptes/{account}/fiche', [ConsoleController::class, 'note'])->name('compte.fiche');
        Route::get('/reglages', [ConsoleController::class, 'settings'])->name('reglages');
        Route::post('/reglages', [ConsoleController::class, 'saveSettings'])->name('reglages.store');
    });
});
