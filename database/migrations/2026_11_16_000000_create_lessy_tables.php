<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
        });

        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40);
            $table->integer('debt')->default(0);
            $table->unsignedInteger('position');
            $table->timestamps();
        });

        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40);
            $table->unsignedInteger('price')->default(0);
            $table->string('image')->nullable();
            $table->unsignedInteger('position');
            $table->timestamps();
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->boolean('pay')->default(false);
            $table->string('label')->nullable();
            $table->timestamp('happened_at');
            $table->timestamps();
        });

        Schema::create('ledger_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_entry_id')->constrained()->cascadeOnDelete();
            $table->string('name', 40);
            $table->unsignedInteger('qty');
            $table->unsignedInteger('price');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_items');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('foods');
        Schema::dropIfExists('people');
        Schema::dropIfExists('settings');
    }
};
