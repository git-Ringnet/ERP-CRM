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
        Schema::create('supplier_payment_histories', function (Blueprint $table) {

            $table->id();
            $table->foreignId('purchase_order_id');
            $table->foreignId('supplier_id');
            $table->decimal('amount', 15, 2);
            $table->decimal('amount_foreign', 15, 2)->nullable();
            $table->string('currency', 10)->default('VND');
            $table->decimal('exchange_rate', 10, 4)->default(1.0000);
            $table->string('payment_method', 20);
            $table->string('reference_number', 100)->nullable();
            $table->date('payment_date');
            $table->text('note')->nullable();
            $table->foreignId('currency_id')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
            $table->index('payment_date', 'supplier_payment_histories_payment_date_index');

            // Foreign keys
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_payment_histories');
    }
};
