<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique('purchases_reference_unique');
            $table->unique(['shop_id', 'reference'], 'purchases_shop_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique('purchases_shop_reference_unique');
            $table->unique('reference', 'purchases_reference_unique');
        });
    }
};