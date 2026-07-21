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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en', 500)->nullable();
            $table->string('abv_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->enum('type', ['normal','vip'])->default('normal');
            $table->string('tax_code', 50);
            $table->string('website')->nullable();
            $table->decimal('debt_limit', 15, 2)->default(0.00);
            $table->string('debt_limit_type')->default('amount');
            $table->decimal('debt_limit_value', 15, 2)->default(0.00);
            $table->integer('debt_days')->default(0);
            $table->json('payment_terms')->nullable();
            $table->text('note')->nullable();
            $table->string('am')->nullable();
            $table->timestamps();
            $table->index('name', 'customers_name_index');
            $table->unique('tax_code', 'customers_tax_code_unique');
            $table->index('type', 'customers_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
