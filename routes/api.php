<?php

use App\Http\Controllers\Api\LessyController;
use Illuminate\Support\Facades\Route;

Route::get('/state', [LessyController::class, 'state']);
Route::post('/unlock', [LessyController::class, 'unlock']);
Route::post('/admin/login', [LessyController::class, 'login']);
Route::post('/admin/logout', [LessyController::class, 'logout']);

Route::middleware('lessy.unlocked')->group(function () {
    Route::get('/people/{person}', [LessyController::class, 'person']);
    Route::post('/people/{person}/sessions', [LessyController::class, 'session']);
    Route::post('/people/{person}/erase', [LessyController::class, 'erase']);

    Route::middleware('lessy.admin')->group(function () {
        Route::post('/people/{person}/payments', [LessyController::class, 'pay']);
        Route::post('/names', [LessyController::class, 'addNames']);
        Route::patch('/names/{person}', [LessyController::class, 'rename']);
        Route::post('/foods', [LessyController::class, 'addFood']);
        Route::post('/foods/photos', [LessyController::class, 'photos']);
        Route::patch('/foods/{food}', [LessyController::class, 'updateFood']);
        Route::post('/foods/{food}/image', [LessyController::class, 'image']);
        Route::delete('/foods/{food}', [LessyController::class, 'deleteFood']);
        Route::post('/settings/code', [LessyController::class, 'changeCode']);
    });
});
