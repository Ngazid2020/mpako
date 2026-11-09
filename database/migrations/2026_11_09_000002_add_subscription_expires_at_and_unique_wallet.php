<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - shops.subscription_expires_at : utilisée par Shop (fillable/cast/isSubscriptionActive),
 *   ShopResource et SubscriptionSeeder mais jamais créée → seed et tri en erreur.
 * - wallets.shop_id unique : Shop::wallet() est un hasOne ; un second portefeuille
 *   rendrait $shop->wallet arbitraire.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('shops', 'subscription_expires_at')) {
            Schema::table('shops', function (Blueprint $table) {
                $table->timestamp('subscription_expires_at')->nullable()->after('is_active');
            });
        }

        Schema::table('wallets', function (Blueprint $table) {
            $table->unique('shop_id');
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // La FK shop_id doit garder un index : on en recrée un simple avant de retirer l'unique
            $table->index('shop_id', 'wallets_shop_id_index');
            $table->dropUnique(['shop_id']);
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('subscription_expires_at');
        });
    }
};
