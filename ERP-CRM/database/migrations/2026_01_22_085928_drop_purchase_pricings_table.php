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
        Schema::dropIfExists('purchase_pricings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('purchase_pricings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('quantity');
            $table->decimal('purchase_price', 15, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('price_after_discount', 15, 2);
            $table->decimal('vat_percent', 5, 2)->default(10);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('loading_cost', 15, 2)->default(0);
            $table->decimal('inspection_cost', 15, 2)->default(0);
            $table->decimal('other_cost', 15, 2)->default(0);
            $table->decimal('total_service_cost', 15, 2)->default(0);
            $table->decimal('service_cost_per_unit', 15, 2)->default(0);
            $table->decimal('warehouse_price', 15, 2);
            $table->enum('pricing_method', ['fifo', 'lifo', 'average'])->default('average');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }
};
