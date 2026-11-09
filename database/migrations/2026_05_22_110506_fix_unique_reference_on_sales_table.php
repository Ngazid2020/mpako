<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Supprimer l'ancienne contrainte unique sur reference seul
            $table->dropUnique('sales_reference_unique');

            // Ajouter une contrainte composite : reference + shop_id
            $table->unique(['shop_id', 'reference'], 'sales_shop_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_shop_reference_unique');
            $table->unique('reference', 'sales_reference_unique');
        });
    }
};