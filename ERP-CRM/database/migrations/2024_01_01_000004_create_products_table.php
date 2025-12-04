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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->string('category', 100)->nullable();
            $table->string('unit', 50);
            $table->decimal('price', 15, 2);
            $table->decimal('cost', 15, 2);
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);
            $table->integer('max_stock')->default(0);
            $table->enum('management_type', ['normal', 'serial', 'lot'])->default('normal');
            $table->boolean('auto_generate_serial')->default(false);
            $table->string('serial_prefix', 20)->nullable();
            $table->integer('expiry_months')->nullable();
            $table->boolean('track_expiry')->default(false);
            $table->text('description')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('code');
            $table->index('category');
            $table->index('management_type');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
