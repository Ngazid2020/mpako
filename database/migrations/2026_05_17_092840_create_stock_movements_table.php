<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            // Tenant
            $table->foreignId('shop_id')
                ->constrained()
                ->cascadeOnDelete();

            // Produit concerné
            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            // Qui a fait le mouvement
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Type de mouvement
            $table->enum('type', [
                'in',          // Entrée de stock (achat, réception)
                'out',         // Sortie de stock (vente manuelle, perte)
                'adjustment',  // Ajustement (inventaire)
            ]);

            $table->decimal('quantity', 10, 2); // Quantité du mouvement
            $table->decimal('stock_before', 10, 2); // Stock avant
            $table->decimal('stock_after', 10, 2);  // Stock après

            $table->string('reason')->nullable(); // Motif

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};