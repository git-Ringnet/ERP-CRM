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
        Schema::create('financial_transactions', function (Blueprint $table) {

            $table->id();
            $table->foreignId('transaction_category_id');
            $table->enum('type', ['income','expense']);
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->string('payment_method');
            $table->string('reference_number')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('currency_id')->nullable();
            $table->decimal('exchange_rate', 18, 6)->nullable();
            $table->decimal('amount_foreign', 18, 4)->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('transaction_category_id')->references('id')->on('transaction_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
