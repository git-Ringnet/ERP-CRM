<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bảng báo giá
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('customer_name');
            $table->string('title');
            $table->date('date');
            $table->date('valid_until');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 5, 2)->default(0);
            $table->decimal('vat', 5, 2)->default(10);
            $table->decimal('total', 15, 2)->default(0);
            $table->text('payment_terms')->nullable();
            $table->string('delivery_time')->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'sent', 'accepted', 'declined', 'expired', 'converted'])->default('draft');
            $table->unsignedInteger('current_approval_level')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('converted_to_sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->timestamps();
        });

        // Chi tiết sản phẩm trong báo giá
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('product_name');
            $table->string('product_code')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
