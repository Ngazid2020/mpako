<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Statut du paiement de la dette fournisseur
            // Séparé du statut stock (pending/completed/cancelled)
            $table->enum('payment_status', [
                'unpaid',  // Aucun paiement effectué
                'partial', // Partiellement payé
                'paid',    // Entièrement payé
            ])->default('unpaid')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });
    }
};