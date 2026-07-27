<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_ticket_id')->constrained('marketing_tickets')->onDelete('cascade');
            $table->foreignId('marketing_event_id')->constrained('marketing_events')->onDelete('cascade');
            $table->string('code')->unique();
            $table->string('support_team')->nullable(); // technical, sales, accounting, hr, etc.
            $table->string('pic_type')->nullable(); // lead, all, specific
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->string('support_content')->nullable(); // speaker, technical_support, customer_list, invite_customers, others
            $table->string('support_content_other')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('deadline')->nullable();
            $table->string('status')->default('received'); // pending_approval, pending_payment, received, in_progress, completed, overdue, rejected
            $table->json('attachment_path')->nullable(); // JSON list of files
            $table->dateTime('completed_at')->nullable();
            
            // Business Trip fields
            $table->date('departure_date')->nullable();
            $table->text('departure_date_note')->nullable();
            $table->integer('personnel_count')->nullable();
            
            // Payment fields
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('amount_in_words')->nullable();
            $table->string('reference_request_code')->nullable();
            $table->string('funding_source')->nullable();
            $table->boolean('supplier_debt_checked')->default(false);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_requests');
    }
};
