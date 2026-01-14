<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tạo bảng imports - tách từ inventory_transactions (type='import')
     */
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->date('date');
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('total_qty')->default(0)->comment('Tổng số lượng');
            $table->string('reference_type', 50)->nullable()->comment('Loại tham chiếu: purchase_order, etc.');
            $table->bigInteger('reference_id')->nullable()->comment('ID của đơn hàng tham chiếu');
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'completed', 'cancelled', 'rejected'])->default('pending');
            $table->timestamps();
            
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
        Schema::dropIfExists('imports');
    }
};
