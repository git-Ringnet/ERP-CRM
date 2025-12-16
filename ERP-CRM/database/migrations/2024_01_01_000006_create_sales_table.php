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
            $table->string('code', 50)->unique();
            $table->enum('type', ['retail', 'project'])->default('retail');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('customer_name');
            $table->date('date');
            $table->text('delivery_address')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 5, 2)->default(0);
            $table->decimal('vat', 5, 2)->default(10);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('margin', 15, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'shipping', 'completed', 'cancelled'])->default('pending');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('product_name');
            $table->integer('quantity')->default(1);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->integer('warranty_months')->nullable()->comment('Số tháng bảo hành');
            $table->timestamps();
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
