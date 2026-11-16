<?php

use App\Http\Controllers\Bo\DeskController;
use Illuminate\Support\Facades\Route;

Route::get('/verrou', [DeskController::class, 'verrouForm'])->name('verrou');
Route::post('/verrou', [DeskController::class, 'verrou'])->name('verrou.store');

Route::middleware('lessy.unlocked')->group(function () {
    Route::get('/connexion', [DeskController::class, 'connexionForm'])->name('connexion');
    Route::post('/connexion', [DeskController::class, 'connexion'])->name('connexion.store');

    Route::middleware('auth')->group(function () {
    Route::get('/', [DeskController::class, 'home'])->name('home');
    Route::get('/noms', [DeskController::class, 'noms'])->name('noms');
    Route::post('/deconnexion', [DeskController::class, 'deconnexion'])->name('deconnexion');
    Route::post('/admin/entrer', [DeskController::class, 'enter'])->name('admin.enter');
    Route::post('/admin/sortir', [DeskController::class, 'leave'])->name('admin.leave');
    Route::post('/noms', [DeskController::class, 'addNames'])->name('noms.store');
    Route::post('/noms/{person}/renommer', [DeskController::class, 'rename'])->name('noms.rename');
    Route::get('/bilan/{person}', [DeskController::class, 'bilan'])->name('bilan');
    Route::post('/bilan/{person}/effacer', [DeskController::class, 'erase'])->name('bilan.erase');
    Route::post('/bilan/{person}/paiement', [DeskController::class, 'pay'])->name('bilan.pay');
    Route::get('/session/{person}', [DeskController::class, 'session'])->name('session');
    Route::post('/session/{person}', [DeskController::class, 'validateSession'])->name('session.validate');
    Route::get('/aliments', [DeskController::class, 'aliments'])->name('aliments');
    Route::post('/aliments', [DeskController::class, 'addFood'])->name('aliments.store');
    Route::post('/aliments/photos', [DeskController::class, 'addPhotos'])->name('aliments.photos');
    Route::post('/aliments/{food}', [DeskController::class, 'updateFood'])->name('aliments.update');
    Route::post('/aliments/{food}/supprimer', [DeskController::class, 'deleteFood'])->name('aliments.delete');
    Route::post('/reglages/code', [DeskController::class, 'changeCode'])->name('reglages.code');
    });
});
