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
        Schema::create('sale_order_requests', function (Blueprint $table) {

            $table->id();
            $table->string('code', 30);
            $table->foreignId('sale_id')->nullable();
            $table->string('source_type')->default('sale_order');
            $table->foreignId('ticket_id')->nullable();
            $table->foreignId('created_by');
            $table->text('note')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('status', 20)->default('submitted');
            $table->text('rejection_note')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique('code', 'sale_order_requests_code_unique');

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
        });

        Schema::create('sale_order_request_items', function (Blueprint $table) {

            $table->id();
            $table->foreignId('sale_order_request_id');
            $table->foreignId('sale_item_id')->nullable();
            $table->foreignId('vendor_id')->nullable();
            $table->decimal('quantity', 18, 2)->default(1.00);
            $table->string('unit')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->decimal('received_quantity', 18, 2)->default(0.00);
            $table->text('note')->nullable();
            $table->boolean('is_cancelled')->default(false);
            $table->string('vendor');
            $table->string('type');
            $table->boolean('needs_cq')->default(false);
            $table->string('part_number');
            $table->string('serial_number')->nullable();
            $table->date('exp_date')->nullable();
            $table->string('si_name');
            $table->string('pos_id')->nullable();
            $table->string('eu_name_mst');
            $table->string('address')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('sale_item_id')->references('id')->on('sale_items')->onDelete('set null');
            $table->foreign('sale_order_request_id')->references('id')->on('sale_order_requests')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('suppliers')->onDelete('set null');
        });

        Schema::create('sale_order_request_attachments', function (Blueprint $table) {

            $table->id();
            $table->foreignId('sale_order_request_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->foreignId('uploaded_by');
            $table->string('note')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('sale_order_request_id')->references('id')->on('sale_order_requests')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_order_request_attachments');
        Schema::dropIfExists('sale_order_request_items');
        Schema::dropIfExists('sale_order_requests');
    }
};
