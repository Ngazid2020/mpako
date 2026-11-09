<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();

            // L'achat concerné
            $table->foreignId('purchase_id')
                ->constrained()
                ->cascadeOnDelete();

            // Le fournisseur concerné (redondant mais pratique pour les requêtes)
            $table->foreignId('supplier_id')
                ->constrained()
                ->cascadeOnDelete();

            // Qui a effectué le paiement
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('amount', 10, 2);   // Montant payé
            $table->date('paid_at');            // Date du paiement
            $table->string('note')->nullable(); // Note libre

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};