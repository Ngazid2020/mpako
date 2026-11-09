<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forfait d'essai : chaque nouvelle boutique démarre avec un mois d'essai gratuit
 * (voir App\Services\ShopOnboarding). Le forfait est créé à la demande.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->boolean('is_trial')->default(false)->after('is_default');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('is_trial');
        });
    }
};
