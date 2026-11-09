<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            // Snapshot du nom au moment de l'achat
            $table->string('product_name');

            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_cost', 10, 2);   // Coût unitaire d'achat
            $table->decimal('subtotal', 10, 2);    // quantity * unit_cost

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};