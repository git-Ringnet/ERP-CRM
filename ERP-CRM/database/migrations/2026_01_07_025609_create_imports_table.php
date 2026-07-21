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
        Schema::create('imports', function (Blueprint $table) {

            $table->id();
            $table->string('code', 100);
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('supplier_id')->nullable();
            $table->date('date');
            $table->foreignId('employee_id')->nullable();
            $table->integer('total_qty')->default(0)->comment('T?ng s? l??ng');
            $table->decimal('shipping_cost', 15, 2)->default(0.00);
            $table->decimal('loading_cost', 15, 2)->default(0.00);
            $table->decimal('inspection_cost', 15, 2)->default(0.00);
            $table->decimal('other_cost', 15, 2)->default(0.00);
            $table->decimal('total_service_cost', 15, 2)->default(0.00);
            $table->decimal('discount_percent', 5, 2)->default(0.00)->comment('Chi?t kh?u (%)');
            $table->decimal('vat_percent', 5, 2)->default(10.00)->comment('VAT (%)');
            $table->string('reference_type', 50)->nullable()->comment('Lo?i tham chi?u: purchase_order, etc.');
            $table->bigInteger('reference_id')->nullable();
            $table->foreignId('shipping_allocation_id')->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['pending','completed','cancelled','rejected'])->default('pending');
            $table->timestamps();
            $table->unique('code', 'imports_code_unique');
            $table->index('date', 'imports_date_index');
            $table->index(['reference_type','reference_id'], 'imports_reference_type_reference_id_index');
            $table->index('status', 'imports_status_index');

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('shipping_allocation_id')->references('id')->on('shipping_allocations')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
