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
        Schema::create('sale_expenses', function (Blueprint $table) {

            $table->id();
            $table->foreignId('sale_id');
            $table->string('type', 100)->default('other');
            $table->string('input_mode', 10)->default('fixed')->comment('percent or fixed');
            $table->decimal('percent_value', 15, 2)->nullable()->comment('% value if input_mode=percent');
            $table->string('description');
            $table->decimal('amount', 15, 2)->default(0.00)->comment('S? ti?n');
            $table->text('note')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_expenses');
    }
};
