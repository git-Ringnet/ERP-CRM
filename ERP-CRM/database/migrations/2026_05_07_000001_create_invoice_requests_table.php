<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('invoice_requests', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('sale_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('requester_id')->constrained('users');
            $blueprint->foreignId('admin_id')->nullable()->constrained('users');
            $blueprint->foreignId('finance_id')->nullable()->constrained('users');
            $blueprint->enum('status', ['pending', 'draft_issued', 'official_issued', 'rejected'])->default('pending');
            $blueprint->string('tax_name');
            $blueprint->string('tax_address');
            $blueprint->string('tax_code');
            $blueprint->string('billing_email')->nullable();
            $blueprint->string('draft_path')->nullable();
            $blueprint->string('official_path')->nullable();
            $blueprint->string('delivery_note_path')->nullable();
            $blueprint->text('note')->nullable();
            $blueprint->text('rejection_reason')->nullable();
            $blueprint->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('invoice_requests');
    }
};
