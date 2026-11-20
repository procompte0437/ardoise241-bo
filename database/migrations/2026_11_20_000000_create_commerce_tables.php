<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Espace client : un compte « client » suit des boutiques (comptes commerçants),
 * leur passe des commandes et reçoit leurs messages.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('role', 12)->default('merchant')->after('phone'); // merchant | customer
            $table->index('role');
        });

        // Un client qui suit une boutique. Prospect tant qu'il n'a rien acheté.
        Schema::create('shop_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('person_uid', 40); // sa fiche dans le cahier de bons du commerçant
            $table->timestamp('first_purchase_at')->nullable();
            $table->timestamps();
            $table->unique(['customer_id', 'shop_id']);
            $table->index('shop_id');
        });

        Schema::create('shop_orders', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 40); // créé sur le téléphone du client : renvoi sans doublon
            $table->foreignId('customer_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('accounts')->cascadeOnDelete();
            $table->json('items');
            $table->unsignedBigInteger('total')->default(0);
            $table->string('note', 300)->nullable();
            $table->string('status', 12)->default('envoyee'); // envoyee, acceptee, prete, livree, refusee, annulee
            $table->string('mode', 8)->nullable(); // livree : cash | bon
            $table->unsignedBigInteger('ordered_at'); // heure du téléphone (ms), même hors ligne
            $table->timestamp('status_at')->nullable();
            $table->timestamps();
            $table->unique(['customer_id', 'uid']);
            $table->index(['shop_id', 'status']);
        });

        Schema::create('shop_messages', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 40);
            $table->foreignId('shop_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('kind', 10)->default('info'); // info | promo
            $table->string('title', 80);
            $table->text('body');
            $table->timestamps();
            $table->unique(['shop_id', 'uid']);
        });

        Schema::create('shop_message_recipients', function (Blueprint $table) {
            $table->foreignId('message_id')->constrained('shop_messages')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('accounts')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->primary(['message_id', 'customer_id']);
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_message_recipients');
        Schema::dropIfExists('shop_messages');
        Schema::dropIfExists('shop_orders');
        Schema::dropIfExists('shop_links');
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
