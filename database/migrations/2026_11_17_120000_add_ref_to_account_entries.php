<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un paiement peut viser un bon précis (ref = uid du bon) : solde ou avance.
 * Sans ref, il rembourse les bons du plus ancien au plus récent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_entries', function (Blueprint $table) {
            $table->string('ref', 40)->nullable()->after('person_uid');
        });
    }

    public function down(): void
    {
        Schema::table('account_entries', function (Blueprint $table) {
            $table->dropColumn('ref');
        });
    }
};
