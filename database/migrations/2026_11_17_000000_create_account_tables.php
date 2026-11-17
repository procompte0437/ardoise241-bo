<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comptes utilisateurs de l'application mobile (un commerçant = un compte).
 *
 * Le téléphone est l'identifiant, le code d'accès sert de mot de passe.
 * Les données du cahier sont copiées depuis le téléphone par la synchronisation :
 * chaque ligne garde son identifiant client (uid) et un numéro de révision (rev)
 * qui permet au téléphone de ne récupérer que ce qui a changé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique();
            $table->string('name', 60)->nullable();
            $table->string('code_hash');
            $table->string('local_salt', 32);
            $table->string('local_hash', 64);
            $table->timestamp('trial_started_at');
            $table->unsignedInteger('trial_days')->nullable(); // null = durée globale
            $table->timestamp('paid_until')->nullable();       // abonnement renouvelé
            $table->boolean('blocked')->default(false);
            $table->unsignedBigInteger('rev')->default(0);
            $table->timestamp('last_sync_at')->nullable();
            $table->string('last_device', 120)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('account_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('device', 120)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('account_people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('uid', 40);
            $table->string('name', 40);
            $table->unsignedInteger('position')->default(0);
            $table->boolean('deleted')->default(false);
            $table->unsignedBigInteger('client_updated_at')->default(0);
            $table->unsignedBigInteger('rev')->default(0);
            $table->timestamps();
            $table->unique(['account_id', 'uid']);
            $table->index(['account_id', 'rev']);
        });

        Schema::create('account_foods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('uid', 40);
            $table->string('name', 40);
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('position')->default(0);
            // Photo 180×180 en JPEG, gardée en base : le service n'a pas de disque permanent.
            $table->longText('image_data')->nullable();
            $table->string('image_hash', 40)->nullable();
            $table->boolean('deleted')->default(false);
            $table->unsignedBigInteger('client_updated_at')->default(0);
            $table->unsignedBigInteger('rev')->default(0);
            $table->timestamps();
            $table->unique(['account_id', 'uid']);
            $table->index(['account_id', 'rev']);
        });

        Schema::create('account_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('uid', 40);
            $table->string('person_uid', 40);
            $table->unsignedInteger('amount');
            $table->boolean('pay')->default(false);
            $table->string('label', 60)->nullable();
            $table->json('items')->nullable();
            $table->unsignedBigInteger('happened_at');
            $table->boolean('deleted')->default(false);
            $table->unsignedBigInteger('client_updated_at')->default(0);
            $table->unsignedBigInteger('rev')->default(0);
            $table->timestamps();
            $table->unique(['account_id', 'uid']);
            $table->index(['account_id', 'rev']);
            $table->index(['account_id', 'person_uid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_entries');
        Schema::dropIfExists('account_foods');
        Schema::dropIfExists('account_people');
        Schema::dropIfExists('account_tokens');
        Schema::dropIfExists('accounts');
    }
};
