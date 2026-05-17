<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            // Clé étrangère vers le commerce (tenant)
            $table->foreignId('shop_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');           // Ex: Boissons, Alimentaire
            $table->string('color')          
                ->default('#6366f1');          // Couleur pour l'interface
            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};