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
        Schema::create('export_items', function (Blueprint $table) {

            $table->id();
            $table->foreignId('export_id');
            $table->foreignId('product_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 18, 2)->nullable();
            $table->decimal('total', 18, 2)->nullable();
            $table->integer('requested_quantity')->nullable();
            $table->boolean('is_liquidation')->default(false);
            $table->string('unit', 20)->nullable();
            $table->text('serial_number')->nullable()->comment('JSON array of product_item_ids');
            $table->text('comments')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('export_id')->references('id')->on('exports')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_items');
    }
};
