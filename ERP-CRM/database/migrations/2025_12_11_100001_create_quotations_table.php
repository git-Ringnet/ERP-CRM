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
        Schema::create('quotations', function (Blueprint $table) {

            $table->id();
            $table->string('code', 50);
            $table->foreignId('customer_id');
            $table->foreignId('contact_id')->nullable();
            $table->string('customer_name');
            $table->string('title');
            $table->date('date');
            $table->date('valid_until');
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('discount', 5, 2)->default(0.00);
            $table->decimal('vat', 5, 2)->default(10.00);
            $table->decimal('vat_amount', 15, 2)->default(0.00);
            $table->decimal('total', 15, 2)->default(0.00);
            $table->text('payment_terms')->nullable();
            $table->string('delivery_time')->nullable();
            $table->text('note')->nullable();
            $table->text('disclaimer')->nullable();
            $table->json('custom_columns')->nullable();
            $table->foreignId('currency_id')->nullable();
            $table->decimal('exchange_rate', 18, 6)->nullable();
            $table->decimal('total_foreign', 18, 4)->nullable();
            $table->enum('status', ['draft','pending','approved','rejected','sent','accepted','declined','expired','converted'])->default('draft');
            $table->unsignedInteger('current_approval_level')->default(0);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('converted_to_sale_id')->nullable();
            $table->timestamps();
            $table->unique('code', 'quotations_code_unique');

            // Foreign keys
            $table->foreign('converted_to_sale_id')->references('id')->on('sales')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });

        Schema::create('quotation_items', function (Blueprint $table) {

            $table->id();
            $table->foreignId('quotation_id');
            $table->foreignId('product_id')->nullable();
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->string('product_code')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 15, 2)->default(0.00);
            $table->decimal('vat', 5, 2)->default(0.00);
            $table->decimal('vat_amount', 15, 2)->default(0.00);
            $table->decimal('total', 15, 2)->default(0.00);
            $table->json('custom_fields')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('quotation_id')->references('id')->on('quotations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
