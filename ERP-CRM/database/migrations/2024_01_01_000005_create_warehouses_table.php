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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->enum('type', ['physical', 'virtual'])->default('physical');
            $table->text('address')->nullable();
            $table->decimal('area', 10, 2)->nullable()->comment('Diện tích (m2)');
            $table->integer('capacity')->nullable()->comment('Sức chứa');
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phone', 20)->nullable();
            $table->enum('status', ['active', 'maintenance', 'inactive'])->default('active');
            $table->string('product_type', 100)->nullable()->comment('Loại sản phẩm lưu trữ');
            $table->boolean('has_temperature_control')->default(false);
            $table->boolean('has_security_system')->default(false);
            $table->text('note')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
