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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('cpq_number')->nullable();
            $table->foreignId('supplier_id');
            $table->foreignId('supplier_quotation_id')->nullable();
            $table->foreignId('sale_id')->nullable();
            $table->date('order_date');
            $table->date('expected_delivery')->nullable();
            $table->date('actual_delivery')->nullable();
            $table->date('expected_arrival_date')->nullable();
            $table->date('manufacturer_release_date')->nullable();
            $table->string('delivery_address')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0.00);
            $table->decimal('discount_percent', 5, 2)->default(0.00);
            $table->decimal('discount_amount', 18, 2)->default(0.00);
            $table->decimal('shipping_cost', 18, 2)->default(0.00);
            $table->decimal('other_cost', 18, 2)->default(0.00);
            $table->decimal('vat_percent', 5, 2)->default(10.00);
            $table->decimal('vat_amount', 18, 2)->default(0.00);
            $table->decimal('total', 18, 2)->default(0.00);
            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->decimal('paid_amount_foreign', 18, 4)->default(0.0000);
            $table->decimal('debt_amount', 15, 2)->default(0.00);
            $table->decimal('debt_amount_foreign', 18, 4)->default(0.0000);
            $table->string('payment_status', 20)->default('unpaid');
            $table->enum('payment_terms', ['immediate','cod','net15','net30','net45','net60'])->default('net30');
            $table->enum('status', ['draft','pending_approval','approved','sent','confirmed','shipping','partial_received','received','cancelled'])->default('draft');
            $table->boolean('is_hold')->default(false);
            $table->text('hold_reason')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('currency_id')->nullable();
            $table->decimal('exchange_rate', 18, 6)->nullable();
            $table->decimal('total_foreign', 18, 4)->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->foreign('supplier_quotation_id')->references('id')->on('supplier_quotations')->onDelete('set null');

            $table->index(['order_date','status'], 'idx_purchase_orders_date_status');
            $table->index(['supplier_id','order_date'], 'idx_purchase_orders_supplier_date');
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id');
            $table->foreignId('sale_order_request_item_id')->nullable();
            $table->decimal('ordered_quantity', 18, 2)->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('product_name');
            $table->integer('quantity');
            $table->text('serial_number')->nullable();
            $table->integer('received_quantity')->default(0);
            $table->string('unit')->default('Cái');
            $table->decimal('warehouse_unit_price', 15, 4)->nullable();
            $table->decimal('unit_price', 18, 2);
            $table->decimal('discount_percent', 5, 2)->default(0.00);
            $table->decimal('total', 18, 2);
            $table->decimal('vat_percent', 5, 2)->default(0.00);
            $table->decimal('vat_amount', 20, 4)->default(0.0000);
            $table->text('note')->nullable();
            $table->string('status')->default('ordered');
            $table->string('license_file')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('sale_order_request_item_id', 'poi_sori_foreign')->references('id')->on('sale_order_request_items')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
