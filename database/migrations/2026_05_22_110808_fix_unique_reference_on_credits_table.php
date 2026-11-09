<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->dropUnique('credits_reference_unique');
            $table->unique(['shop_id', 'reference'], 'credits_shop_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->dropUnique('credits_shop_reference_unique');
            $table->unique('reference', 'credits_reference_unique');
        });
    }
};