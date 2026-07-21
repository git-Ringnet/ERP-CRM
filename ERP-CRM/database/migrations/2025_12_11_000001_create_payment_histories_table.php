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
        Schema::create('payment_histories', function (Blueprint $table) {

            $table->id();
            $table->foreignId('sale_id');
            $table->foreignId('customer_id');
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash','bank_transfer','card','other'])->default('cash');
            $table->string('reference_number')->nullable()->comment('S? ch?ng t?/tham chi?u');
            $table->date('payment_date');
            $table->text('note')->nullable();
            $table->foreignId('currency_id')->nullable();
            $table->decimal('exchange_rate', 18, 6)->nullable();
            $table->decimal('amount_foreign', 18, 4)->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->index(['customer_id','payment_date'], 'payment_histories_customer_id_payment_date_index');
            $table->index(['sale_id','payment_date'], 'payment_histories_sale_id_payment_date_index');

            // Foreign keys
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_histories');
    }
};
