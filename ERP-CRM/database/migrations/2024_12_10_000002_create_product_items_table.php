<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Requirements: 3.1 - Create product_items table
     */
    public function up(): void
    {
        Schema::create('product_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('sku', 100);
            $table->text('description')->nullable();
            $table->decimal('cost_usd', 15, 2)->default(0)->comment('Cost in USD');
            $table->json('price_tiers')->nullable()->comment('Dynamic price tiers: {1yr: 100, 2yr: 200, ...}');
            $table->integer('quantity')->default(1);
            $table->text('comments')->nullable();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('set null');
            $table->foreignId('inventory_transaction_id')->nullable()->constrained('inventory_transactions')->onDelete('set null');
            $table->enum('status', ['in_stock', 'sold', 'damaged', 'transferred'])->default('in_stock');
            $table->timestamps();

            // Unique index on product_id and sku
            $table->unique(['product_id', 'sku'], 'product_items_product_sku_unique');
            
            // Additional indexes
            $table->index('status');
            $table->index('warehouse_id');
            $table->index('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_items');
    }
};
