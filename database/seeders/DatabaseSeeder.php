<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\LessyStore;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(LessyStore::class)->seed();

        User::query()->updateOrCreate(
            ['email' => 'admin@calcpro.test'],
            [
                'name' => 'Admin',
                'password' => 'Admin1234',
                'email_verified_at' => now(),
            ],
        );
    }
}
