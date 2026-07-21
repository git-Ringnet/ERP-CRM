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
            $table->string('code', 150);
            $table->text('name');
            $table->char('category', 1)->nullable();
            $table->string('unit', 50);
            $table->integer('warranty_months')->nullable()->comment('Default warranty period in months');
            $table->text('description')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index('category', 'products_category_index');
            $table->index('code', 'products_code_index');
            $table->unique('code', 'products_code_unique');
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
