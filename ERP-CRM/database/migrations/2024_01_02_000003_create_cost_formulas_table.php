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
        Schema::create('cost_formulas', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Mã công thức');
            $table->string('name')->comment('Tên công thức');
            $table->enum('type', ['shipping', 'marketing', 'commission', 'other'])->comment('Loại chi phí');
            $table->enum('calculation_type', ['fixed', 'percentage', 'formula'])->comment('Cách tính: Cố định, %, Công thức');
            $table->decimal('fixed_amount', 15, 2)->nullable()->comment('Số tiền cố định');
            $table->decimal('percentage', 5, 2)->nullable()->comment('Phần trăm');
            $table->string('formula')->nullable()->comment('Công thức tính');
            $table->enum('apply_to', ['all', 'product', 'category', 'customer'])->default('all')->comment('Áp dụng cho');
            $table->json('apply_conditions')->nullable()->comment('Điều kiện áp dụng (product_ids, category_ids, customer_ids)');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_formulas');
    }
};
