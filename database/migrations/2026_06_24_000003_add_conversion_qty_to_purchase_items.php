<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            // Nombre d'unités de détail contenues dans l'unité d'achat (ex: 12 bouteilles/carton)
            $table->decimal('conversion_qty', 10, 2)->default(1)->after('unit_cost');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('conversion_qty');
        });
    }
};
