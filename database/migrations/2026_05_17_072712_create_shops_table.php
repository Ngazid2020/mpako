<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();

            // Identité du commerce
            $table->string('name');              // Ex: "Boutique Ali"
            $table->string('slug')->unique();    // Ex: "boutique-ali" (pour les URLs)

            // Localisation — contexte comorien
            $table->enum('island', [
                'Grande Comore',
                'Anjouan',
                'Mohéli',
                'Mayotte',
            ])->default('Grande Comore');
            $table->string('city')->nullable();   // Ex: "Moroni", "Mutsamudu"
            $table->string('address')->nullable(); // Adresse libre

            // Contact
            $table->string('phone')->nullable();   // Numéro du commerce
            $table->string('email')->nullable();

            // Configuration du commerce
            $table->string('currency', 10)->default('KMF'); // Franc comorien
            $table->boolean('is_active')->default(true);    // Le super-admin peut désactiver

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
