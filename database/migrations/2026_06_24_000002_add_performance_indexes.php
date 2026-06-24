<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('barcode');
            $table->index(['shop_id', 'is_active']);
            $table->index(['shop_id', 'stock_qty']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index(['shop_id', 'status']);
            $table->index(['shop_id', 'created_at']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->index(['shop_id', 'status']);
            $table->index(['shop_id', 'payment_status']);
        });

        Schema::table('credits', function (Blueprint $table) {
            $table->index(['shop_id', 'status']);
            $table->index(['shop_id', 'due_date']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['shop_id', 'spent_at']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['shop_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['barcode']);
            $table->dropIndex(['shop_id', 'is_active']);
            $table->dropIndex(['shop_id', 'stock_qty']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['shop_id', 'status']);
            $table->dropIndex(['shop_id', 'created_at']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['shop_id', 'status']);
            $table->dropIndex(['shop_id', 'payment_status']);
        });

        Schema::table('credits', function (Blueprint $table) {
            $table->dropIndex(['shop_id', 'status']);
            $table->dropIndex(['shop_id', 'due_date']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['shop_id', 'spent_at']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['shop_id', 'created_at']);
        });
    }
};
