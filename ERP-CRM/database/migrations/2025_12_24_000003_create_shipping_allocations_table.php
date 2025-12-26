<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_allocations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->date('allocation_date');
            $table->decimal('total_shipping_cost', 15, 2)->comment('Tổng chi phí vận chuyển');
            $table->enum('allocation_method', ['value', 'quantity', 'weight', 'volume'])->default('value')->comment('Phương pháp phân bổ');
            $table->decimal('total_value', 15, 2)->default(0)->comment('Tổng giá trị hàng');
            $table->decimal('total_allocated', 15, 2)->default(0)->comment('Tổng CP đã phân bổ');
            $table->enum('status', ['draft', 'approved', 'completed'])->default('draft');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['purchase_order_id', 'warehouse_id']);
            $table->index('status');
        });

        Schema::create('shipping_allocation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_allocation_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('unit_value', 15, 2)->comment('Giá trị đơn vị');
            $table->decimal('total_value', 15, 2)->comment('Tổng giá trị');
            $table->decimal('weight', 10, 3)->nullable()->comment('Trọng lượng (kg)');
            $table->decimal('volume', 10, 4)->nullable()->comment('Thể tích (m3)');
            $table->decimal('allocated_cost', 15, 2)->comment('Chi phí phân bổ');
            $table->decimal('allocated_cost_per_unit', 15, 2)->comment('CP phân bổ/đơn vị');
            $table->timestamps();

            $table->index('shipping_allocation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_allocation_items');
        Schema::dropIfExists('shipping_allocations');
    }
};
