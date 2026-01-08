<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tạo bảng transfer_items - tách từ inventory_transaction_items
     */
    public function up(): void
    {
        Schema::create('transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('transfers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('quantity');
            $table->string('unit', 20)->nullable();
            $table->text('serial_number')->nullable()->comment('JSON array of product_item_ids');
            $table->text('comments')->nullable();
            $table->timestamps();
            
            $table->index('transfer_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_items');
    }
};
