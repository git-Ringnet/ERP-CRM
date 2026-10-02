<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->text('product_name')->change();
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->text('product_name')->change();
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->text('product_name')->change();
        });

        Schema::table('supplier_quotation_items', function (Blueprint $table) {
            $table->text('product_name')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('product_name', 255)->change();
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->string('product_name', 255)->change();
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->string('product_name', 255)->change();
        });

        Schema::table('supplier_quotation_items', function (Blueprint $table) {
            $table->string('product_name', 255)->change();
        });
    }
};
