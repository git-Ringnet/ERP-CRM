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
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('actual_working_days', 4, 1)->default(0);
            $table->decimal('total_allowance', 15, 2)->default(0);
            $table->decimal('total_deduction', 15, 2)->default(0);
            $table->decimal('commission_bonus', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->text('note')->nullable();
            
            // Cấu trúc JSON để lưu chi tiết các loại phụ cấp/khấu trừ để sau này in phiếu lương
            $table->json('details_json')->nullable();
            
            $table->timestamps();
            
            $table->unique(['payroll_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
