<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);              // Amount in VND (after conversion)
            $table->decimal('amount_foreign', 15, 2)->nullable(); // Amount in foreign currency
            $table->string('currency', 10)->default('VND'); // VND, USD, etc.
            $table->decimal('exchange_rate', 10, 4)->default(1.0000);
            $table->string('payment_method', 20);           // cash, bank_transfer, card, other
            $table->string('reference_number', 100)->nullable();
            $table->date('payment_date');
            $table->text('note')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();

            $table->index('purchase_order_id');
            $table->index('supplier_id');
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payment_histories');
    }
};
