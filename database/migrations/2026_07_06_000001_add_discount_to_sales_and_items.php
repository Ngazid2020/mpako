<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->enum('discount_type', ['percent', 'fixed'])->nullable()->default(null)->after('unit_price');
            $table->decimal('discount_value', 10, 2)->default(0)->after('discount_type');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_value');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('discount_amount', 10, 2)->default(0)->after('change_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'discount_amount']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('discount_amount');
        });
    }
};
