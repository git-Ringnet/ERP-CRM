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
        Schema::create('import_items', function (Blueprint $table) {

            $table->id();
            $table->foreignId('import_id');
            $table->foreignId('product_id');
            $table->foreignId('warehouse_id')->nullable();
            $table->integer('quantity');
            $table->string('unit', 20)->nullable();
            $table->text('serial_number')->nullable()->comment('JSON array of serials');
            $table->decimal('cost', 15, 2)->nullable();
            $table->decimal('warehouse_price', 15, 2)->default(0.00);
            $table->text('comments')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->integer('warranty_months')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('import_id')->references('id')->on('imports')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
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
