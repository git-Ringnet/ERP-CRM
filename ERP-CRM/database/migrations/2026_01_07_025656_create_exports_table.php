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
        Schema::create('exports', function (Blueprint $table) {

            $table->id();
            $table->string('code', 100);
            $table->foreignId('warehouse_id');
            $table->foreignId('project_id')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->date('date');
            $table->foreignId('employee_id')->nullable();
            $table->integer('total_qty')->default(0)->comment('T?ng s? l??ng');
            $table->string('reference_type', 50)->nullable()->comment('Lo?i tham chi?u: sales_order, etc.');
            $table->bigInteger('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->string('status', 50)->default('pending');
            $table->timestamps();
            $table->unique('code', 'exports_code_unique');
            $table->index('date', 'exports_date_index');
            $table->index(['reference_type','reference_id'], 'exports_reference_type_reference_id_index');
            $table->index('status', 'exports_status_index');

            // Foreign keys
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
