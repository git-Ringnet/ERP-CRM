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
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->integer('stock')->default(0)->comment('Số lượng tồn kho');
            $table->integer('min_stock')->default(0)->comment('Mức tồn kho tối thiểu');
            $table->decimal('avg_cost', 15, 2)->default(0)->comment('Giá vốn trung bình');
            $table->date('expiry_date')->nullable()->comment('Ngày hết hạn');
            $table->integer('warranty_months')->nullable()->comment('Số tháng bảo hành');
            $table->timestamps();
            
            // Unique constraint: one product per warehouse
            $table->unique(['product_id', 'warehouse_id']);
            
            $table->index('stock');
            $table->index('expiry_date');
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
