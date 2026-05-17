<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            // Tenant
            $table->foreignId('shop_id')
                ->constrained()
                ->cascadeOnDelete();

            // Qui a fait la vente
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Référence unique : ex "VNT-20240517-0001"
            $table->string('reference')->unique();

            // Statut de la vente
            $table->enum('status', [
                'completed', // Vente terminée
                'cancelled', // Vente annulée
            ])->default('completed');

            // Montants en KMF
            $table->decimal('total_amount', 10, 2)->default(0); // Total à payer
            $table->decimal('paid_amount', 10, 2)->default(0);  // Montant reçu
            $table->decimal('change_amount', 10, 2)->default(0);// Monnaie rendue

            // Note libre (optionnel)
            $table->string('note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};