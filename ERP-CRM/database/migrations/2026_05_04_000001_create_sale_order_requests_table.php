<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_order_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->text('note')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_order_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_order_request_id')->constrained('sale_order_requests')->cascadeOnDelete();
            $table->string('vendor');
            $table->string('type');
            $table->string('part_number');
            $table->string('serial_number')->nullable();
            $table->date('exp_date')->nullable();
            $table->string('si_name');
            $table->string('eu_name_mst');
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_order_request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_order_request_id')->constrained('sale_order_requests')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_order_request_attachments');
        Schema::dropIfExists('sale_order_request_items');
        Schema::dropIfExists('sale_order_requests');
    }
};
