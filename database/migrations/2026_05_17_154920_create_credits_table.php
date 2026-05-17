<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credits', function (Blueprint $table) {
            $table->id();

            // Tenant
            $table->foreignId('shop_id')
                ->constrained()
                ->cascadeOnDelete();

            // Client concerné
            $table->foreignId('customer_id')
                ->constrained()
                ->cascadeOnDelete();

            // Qui a enregistré le crédit
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Référence unique : CRD-20240517-0001
            $table->string('reference')->unique();

            // Statut du crédit
            $table->enum('status', [
                'pending',  // Aucun paiement reçu
                'partial',  // Partiellement remboursé
                'paid',     // Entièrement remboursé
            ])->default('pending');

            // Montants
            $table->decimal('total_amount', 10, 2);     // Montant total du crédit
            $table->decimal('paid_amount', 10, 2)->default(0);      // Déjà remboursé
            $table->decimal('remaining_amount', 10, 2); // Reste à payer

            // Date limite de remboursement (optionnel)
            $table->date('due_date')->nullable();

            // Description de ce qui a été pris à crédit
            $table->text('description')->nullable();

            $table->string('note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};