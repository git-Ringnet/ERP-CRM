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
        Schema::create('damaged_goods', function (Blueprint $table) {

            $table->id();
            $table->string('code');
            $table->enum('type', ['damaged','liquidation']);
            $table->foreignId('product_id');
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('product_item_id')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('original_value', 15, 2);
            $table->decimal('recovery_value', 15, 2)->default(0.00);
            $table->text('reason');
            $table->enum('status', ['pending','approved','rejected','processed'])->default('pending');
            $table->date('discovery_date');
            $table->foreignId('discovered_by');
            $table->text('solution')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique('code', 'damaged_goods_code_unique');

            // Foreign keys
            $table->foreign('discovered_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('damaged_goods');
    }
};
