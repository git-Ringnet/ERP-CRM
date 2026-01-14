<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tạo bảng import_items - tách từ inventory_transaction_items
     */
    public function up(): void
    {
        Schema::create('import_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('imports')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->integer('quantity');
            $table->string('unit', 20)->nullable();
            $table->text('serial_number')->nullable()->comment('JSON array of serials');
            $table->decimal('cost', 15, 2)->nullable()->comment('Giá vốn tại thời điểm nhập');
            $table->text('comments')->nullable();
            $table->timestamps();
            
            $table->index('import_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_items');
    }
};
