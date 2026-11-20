<?php

use App\Http\Controllers\Api\AppController;
use App\Http\Controllers\Api\CommerceController;
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

// Application mobile : comptes par téléphone + code, données synchronisées.
Route::prefix('app')->group(function () {
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/register', [AppController::class, 'register']);
        Route::post('/login', [AppController::class, 'login']);
    });

    Route::middleware('app.account')->group(function () {
        Route::get('/me', [AppController::class, 'me']);
        Route::post('/sync', [AppController::class, 'sync']);
        Route::post('/foods/{uid}/image', [AppController::class, 'uploadImage']);
        Route::get('/foods/{uid}/image', [AppController::class, 'image']);
        Route::post('/code', [AppController::class, 'changeCode'])->middleware('throttle:10,1');
        Route::post('/logout', [AppController::class, 'logout']);

        // Espace client et suivi des clients par le commerçant.
        Route::get('/commerce', [CommerceController::class, 'state']);
        Route::get('/shops', [CommerceController::class, 'search']);
        Route::post('/shops/{shop}/follow', [CommerceController::class, 'follow'])->whereNumber('shop');
        Route::delete('/shops/{shop}/follow', [CommerceController::class, 'unfollow'])->whereNumber('shop');
        Route::post('/orders', [CommerceController::class, 'order']);
        Route::post('/orders/{uid}/cancel', [CommerceController::class, 'cancel']);
        Route::post('/orders/{order}/status', [CommerceController::class, 'status'])->whereNumber('order');
        Route::post('/messages', [CommerceController::class, 'message']);
        Route::post('/messages/read', [CommerceController::class, 'read']);
    });

    Route::get('/shops/{shop}/foods/{uid}/image', [CommerceController::class, 'image'])->whereNumber('shop');
});
