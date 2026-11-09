<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            // Tenant
            $table->foreignId('shop_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');               // Nom du client
            $table->string('phone')->nullable();  // Téléphone
            $table->string('address')->nullable(); // Quartier / Localisation

            // Balance totale due par ce client
            // Augmente à chaque crédit
            // Diminue à chaque remboursement
            $table->decimal('balance', 10, 2)->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};