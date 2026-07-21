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
        Schema::create('inventories', function (Blueprint $table) {

            $table->id();
            $table->foreignId('product_id');
            $table->foreignId('warehouse_id');
            $table->integer('stock')->default(0)->comment('S? l??ng t?n kho');
            $table->integer('min_stock')->default(0)->comment('M?c t?n kho t?i thi?u');
            $table->decimal('avg_cost', 15, 2)->default(0.00);
            $table->date('expiry_date')->nullable();
            $table->integer('warranty_months')->nullable();
            $table->timestamps();
            $table->index('expiry_date', 'inventories_expiry_date_index');
            $table->unique(['product_id','warehouse_id'], 'inventories_product_id_warehouse_id_unique');
            $table->index('stock', 'inventories_stock_index');

            // Foreign keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
