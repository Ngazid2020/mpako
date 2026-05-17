<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();

            // Tenant
            $table->foreignId('shop_id')
                ->constrained()
                ->cascadeOnDelete();

            // Fournisseur (optionnel — achat sans fournisseur possible)
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Qui a enregistré l'achat
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Référence unique : ex "ACH-20240517-0001"
            $table->string('reference')->unique();

            // Statut
            $table->enum('status', [
                'pending',   // En attente de validation
                'completed', // Validé → stock mis à jour
                'cancelled', // Annulé
            ])->default('pending');

            // Montants en KMF
            $table->decimal('total_amount', 10, 2)->default(0); // Total de l'achat
            $table->decimal('paid_amount', 10, 2)->default(0);  // Montant payé
            $table->decimal('debt_amount', 10, 2)->default(0);  // Reste à payer (dette)

            // Date de l'achat (peut être différente de created_at)
            $table->date('purchased_at');

            $table->string('note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};