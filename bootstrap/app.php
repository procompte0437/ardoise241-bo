<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/gestion/connexion');
        // Railway termine le HTTPS devant l'application : on fait confiance à son proxy
        // pour que les liens et formulaires soient générés en https (sinon Chrome
        // affiche « informations non sécurisées » à l'envoi).
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'lessy.unlocked' => \App\Http\Middleware\EnsureLessyUnlocked::class,
            'lessy.admin' => \App\Http\Middleware\EnsureLessyAdmin::class,
            'app.account' => \App\Http\Middleware\EnsureAccountToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
