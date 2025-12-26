<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_price_list_items', function (Blueprint $table) {
            $table->string('sku', 255)->change();
            $table->text('product_name')->change();
            $table->string('category', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_price_list_items', function (Blueprint $table) {
            $table->string('sku', 100)->change();
            $table->string('product_name', 255)->change();
            $table->string('category', 255)->nullable()->change();
        });
    }
};
