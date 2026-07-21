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
        Schema::create('invoice_requests', function (Blueprint $table) {

            $table->id();
            $table->foreignId('sale_id');
            $table->foreignId('export_id')->nullable();
            $table->foreignId('requester_id');
            $table->foreignId('admin_id')->nullable();
            $table->foreignId('finance_id')->nullable();
            $table->enum('status', ['pending','draft_issued','official_issued','rejected'])->default('pending');
            $table->string('tax_name');
            $table->string('tax_address');
            $table->string('tax_code');
            $table->string('billing_email')->nullable();
            $table->string('draft_path')->nullable();
            $table->string('official_path')->nullable();
            $table->string('delivery_note_path')->nullable();
            $table->text('note')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('seller_name')->nullable();
            $table->string('seller_company')->nullable();
            $table->text('invoice_content_note')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('delivery_address')->nullable();
            $table->string('delivery_contact')->nullable();
            $table->string('delivery_phone')->nullable();
            $table->text('payment_terms_note')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('admin_id')->references('id')->on('users');
            $table->foreign('export_id')->references('id')->on('exports')->onDelete('set null');
            $table->foreign('finance_id')->references('id')->on('users');
            $table->foreign('requester_id')->references('id')->on('users');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_requests');
    }
};
