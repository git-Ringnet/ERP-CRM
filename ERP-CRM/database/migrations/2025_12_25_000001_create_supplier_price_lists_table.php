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
        Schema::create('supplier_price_lists', function (Blueprint $table) {

            $table->id();
            $table->string('code', 50);
            $table->string('name');
            $table->foreignId('supplier_id');
            $table->string('file_name')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('currency', 10)->default('USD');
            $table->decimal('exchange_rate', 15, 4)->default(1.0000);
            $table->json('custom_columns')->nullable();
            $table->string('primary_price_column', 100)->nullable();
            $table->enum('price_type', ['list','partner','cost'])->default('list');
            $table->decimal('supplier_discount_percent', 5, 2)->default(0.00)->comment('Chi?t kh?u t? NCC (%)');
            $table->decimal('shipping_percent', 5, 2)->default(0.00);
            $table->decimal('shipping_fixed', 15, 2)->default(0.00);
            $table->decimal('margin_percent', 5, 2)->default(0.00)->comment('Margin/Markup (%)');
            $table->decimal('other_fees', 15, 2)->default(0.00);
            $table->json('pricing_formula')->nullable();
            $table->text('notes')->nullable();
            $table->json('import_log')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->unique('code', 'supplier_price_lists_code_unique');

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
        });

        Schema::create('supplier_price_list_items', function (Blueprint $table) {

            $table->id();
            $table->foreignId('supplier_price_list_id');
            $table->string('sku');
            $table->text('product_name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('list_price', 15, 2)->nullable();
            $table->decimal('price_1yr', 15, 2)->nullable();
            $table->decimal('price_2yr', 15, 2)->nullable();
            $table->decimal('price_3yr', 15, 2)->nullable();
            $table->decimal('price_4yr', 15, 2)->nullable();
            $table->decimal('price_5yr', 15, 2)->nullable();
            $table->string('source_sheet')->nullable();
            $table->json('extra_data')->nullable();
            $table->timestamps();
            $table->index('sku', 'supplier_price_list_items_sku_index');
            $table->index(['supplier_price_list_id','sku'], 'supplier_price_list_items_supplier_price_list_id_sku_index');

            // Foreign keys
            $table->foreign('supplier_price_list_id')->references('id')->on('supplier_price_lists')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_price_list_items');
        Schema::dropIfExists('supplier_price_lists');
    }
};
