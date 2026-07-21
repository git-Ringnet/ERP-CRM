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
        Schema::create('sales', function (Blueprint $table) {

            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('code', 50);
            $table->enum('type', ['retail','project'])->default('retail');
            $table->foreignId('project_id')->nullable();
            $table->foreignId('customer_id');
            $table->foreignId('contact_id')->nullable();
            $table->string('customer_name');
            $table->date('date');
            $table->date('invoice_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->text('delivery_address')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('discount', 5, 2)->default(0.00);
            $table->decimal('vat', 5, 2)->default(10.00);
            $table->decimal('vat_amount', 15, 2)->default(0.00);
            $table->decimal('total', 15, 2)->default(0.00);
            $table->decimal('cost', 15, 2)->default(0.00);
            $table->decimal('margin', 15, 2)->default(0.00);
            $table->decimal('margin_percent', 8, 2)->default(0.00)->comment('T? l? l?i nhu?n (%)');
            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->decimal('paid_amount_foreign', 18, 4)->default(0.0000);
            $table->decimal('debt_amount', 15, 2)->default(0.00);
            $table->decimal('debt_amount_foreign', 18, 4)->default(0.0000);
            $table->enum('payment_status', ['unpaid','partial','paid'])->default('unpaid');
            $table->date('payment_due_date')->nullable();
            $table->text('payment_term')->nullable();
            $table->json('payment_terms')->nullable();
            $table->string('status', 50)->default('pending');
            $table->string('pl_status')->default('draft');
            $table->integer('current_approval_level')->default(0);
            $table->timestamp('pl_approved_at')->nullable();
            $table->foreignId('pl_approved_by')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('currency_id')->nullable();
            $table->decimal('exchange_rate', 18, 6)->nullable();
            $table->decimal('total_foreign', 18, 4)->nullable();
            $table->string('payment_term_type')->nullable();
            $table->boolean('is_payment_exception')->default(false);
            $table->string('payment_exception_file')->nullable();
            $table->foreignId('payment_exception_delegated_to')->nullable();
            $table->timestamps();
            $table->index(['customer_id','date'], 'idx_sales_customer_date');
            $table->index(['date','status'], 'idx_sales_date_status');
            $table->unique('code', 'sales_code_unique');

            // Foreign keys
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('payment_exception_delegated_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('pl_approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('sale_items', function (Blueprint $table) {

            $table->id();
            $table->foreignId('sale_id');
            $table->foreignId('product_id')->nullable();
            $table->string('product_name');
            $table->foreignId('project_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->boolean('is_liquidation')->default(false);
            $table->decimal('price', 15, 2)->default(0.00);
            $table->decimal('vat', 5, 2)->default(0.00);
            $table->decimal('vat_amount', 15, 2)->default(0.00);
            $table->decimal('usd_price', 15, 2)->default(0.00);
            $table->decimal('exchange_rate', 15, 2)->default(1.00);
            $table->decimal('discount_rate', 5, 2)->default(0.00);
            $table->decimal('import_cost_rate', 5, 2)->default(0.00);
            $table->decimal('estimated_cost_usd', 15, 2)->default(0.00);
            $table->decimal('cost_price', 15, 2)->default(0.00);
            $table->decimal('total', 15, 2)->default(0.00);
            $table->decimal('cost_total', 15, 2)->default(0.00);
            $table->integer('warranty_months')->nullable();
            $table->date('warranty_start_date')->nullable();
            $table->decimal('finance_cost_percent', 15, 2)->nullable();
            $table->decimal('finance_cost', 15, 2)->nullable();
            $table->decimal('overdue_interest_cost', 15, 2)->nullable();
            $table->decimal('overdue_interest_percent', 15, 2)->nullable();
            $table->decimal('management_cost_percent', 15, 2)->nullable();
            $table->decimal('management_cost', 15, 2)->nullable();
            $table->decimal('support_247_cost_percent', 15, 2)->nullable();
            $table->decimal('support_247_cost', 15, 2)->nullable();
            $table->decimal('other_support_cost', 15, 2)->nullable()->default(0.00);
            $table->decimal('other_support_cost_percent', 15, 2)->nullable();
            $table->decimal('technical_poc_cost', 15, 2)->nullable()->default(0.00);
            $table->decimal('technical_poc_percent', 15, 2)->nullable();
            $table->decimal('implementation_cost', 15, 2)->nullable()->default(0.00);
            $table->decimal('implementation_cost_percent', 15, 2)->nullable();
            $table->decimal('contractor_tax', 15, 2)->nullable()->default(0.00);
            $table->decimal('contractor_tax_percent', 15, 2)->nullable();
            $table->boolean('contractor_tax_enabled')->default(false);
            $table->json('extra_expenses_data')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->index(['product_id','sale_id'], 'idx_sale_items_product_sale');

            // Foreign keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
