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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->enum('type', ['import', 'export', 'transfer'])->comment('Loại giao dịch');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete()->comment('Kho đích (cho chuyển kho)');
            $table->date('date');
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('total_qty')->default(0)->comment('Tổng số lượng');
            $table->string('reference_type', 50)->nullable()->comment('Loại tham chiếu: purchase_order, sales_order, etc.');
            $table->bigInteger('reference_id')->nullable()->comment('ID của đơn hàng tham chiếu');
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
            
            $table->index('type');
            $table->index('date');
            $table->index('status');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
