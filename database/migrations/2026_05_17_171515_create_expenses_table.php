<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            // Tenant
            $table->foreignId('shop_id')
                ->constrained()
                ->cascadeOnDelete();

            // Catégorie de dépense
            $table->foreignId('expense_category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Qui a enregistré
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('amount', 10, 2);          // Montant en KMF
            $table->string('description');              // Ce pour quoi on a dépensé
            $table->date('spent_at');                   // Date de la dépense
            $table->string('note')->nullable();         // Note libre

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};