<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            // On sauvegarde le nom du produit au moment de la vente
            // Si le produit est modifié plus tard, l'historique reste correct
            $table->string('product_name');

            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2);  // Prix au moment de la vente
            $table->decimal('subtotal', 10, 2);    // quantity * unit_price

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};