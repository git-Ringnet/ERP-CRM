<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_pricings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('quantity')->default(1);
            $table->decimal('purchase_price', 15, 2)->comment('Giá nhập gốc');
            $table->decimal('discount_percent', 5, 2)->default(0)->comment('Chiết khấu NCC (%)');
            $table->decimal('price_after_discount', 15, 2)->comment('Giá sau chiết khấu');
            $table->decimal('vat_percent', 5, 2)->default(10)->comment('VAT (%)');
            $table->decimal('shipping_cost', 15, 2)->default(0)->comment('Chi phí vận chuyển');
            $table->decimal('loading_cost', 15, 2)->default(0)->comment('Chi phí bốc xếp');
            $table->decimal('inspection_cost', 15, 2)->default(0)->comment('Chi phí kiểm tra');
            $table->decimal('other_cost', 15, 2)->default(0)->comment('Chi phí khác');
            $table->decimal('total_service_cost', 15, 2)->default(0)->comment('Tổng chi phí phục vụ');
            $table->decimal('service_cost_per_unit', 15, 2)->default(0)->comment('CP phục vụ/đơn vị');
            $table->decimal('warehouse_price', 15, 2)->comment('Giá kho');
            $table->enum('pricing_method', ['fifo', 'lifo', 'average'])->default('average')->comment('Phương pháp tính giá');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['product_id', 'supplier_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_pricings');
    }
};
