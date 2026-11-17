<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Crée (ou met à jour) un compte du back-office de gestion : php artisan ardoise:admin admin@exemple.com
Artisan::command('ardoise:admin {email}', function (string $email) {
    $password = $this->secret('Mot de passe (8 caractères minimum)');
    if (strlen((string) $password) < 8) {
        $this->error('Mot de passe trop court.');

        return 1;
    }

    \App\Models\User::query()->updateOrCreate(
        ['email' => $email],
        ['name' => 'Gestion', 'password' => \Illuminate\Support\Facades\Hash::make($password)],
    );
    $this->info("Compte de gestion prêt : $email");
})->purpose('Créer un accès au back-office de gestion');
