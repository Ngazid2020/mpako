<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal d'idempotence de la sync offline (POST /api/shop/{slug}/push).
 * Une opération (shop_id, local_id) déjà traitée renvoie son résultat stocké
 * au lieu d'être rejouée (évite ventes/paiements en double après un timeout réseau).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('local_id', 100);
            $table->string('type', 50);
            $table->json('result')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'local_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_operations');
    }
};
