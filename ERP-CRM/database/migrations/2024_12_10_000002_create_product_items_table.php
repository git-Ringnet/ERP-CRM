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
        Schema::create('product_items', function (Blueprint $table) {

            $table->id();
            $table->foreignId('product_id');
            $table->string('sku', 100);
            $table->text('description')->nullable();
            $table->decimal('cost_usd', 15, 2)->default(0.00)->comment('Cost in USD');
            $table->json('price_tiers')->nullable()->comment('Dynamic price tiers: {1yr: 100, 2yr: 200, ...}');
            $table->integer('quantity')->default(1);
            $table->text('comments')->nullable();
            $table->string('borrower')->nullable()->comment('Ng??i m??n thi?t b?');
            $table->json('custom_fields')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('import_id')->nullable();
            $table->foreignId('export_id')->nullable();
            $table->enum('status', ['in_stock','sold','damaged','transferred','liquidation'])->default('in_stock');
            $table->integer('warranty_months')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
            $table->unique(['product_id','sku'], 'product_items_product_sku_unique');
            $table->index('sku', 'product_items_sku_index');
            $table->index('status', 'product_items_status_index');

            // Foreign keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
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
