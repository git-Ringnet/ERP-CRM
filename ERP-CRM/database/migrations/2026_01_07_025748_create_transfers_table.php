<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tạo bảng transfers - tách từ inventory_transactions (type='transfer')
     */
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete()->comment('Kho nguồn');
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete()->comment('Kho đích');
            $table->date('date');
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('total_qty')->default(0)->comment('Tổng số lượng');
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'completed', 'cancelled', 'rejected'])->default('pending');
            $table->timestamps();
            
            $table->index('date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
