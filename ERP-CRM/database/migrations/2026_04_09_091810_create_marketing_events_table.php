<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('event_date');
            $table->string('location')->nullable();
            $table->decimal('budget', 15, 2)->default(0); // Ngân sách dự toán
            $table->decimal('actual_cost', 15, 2)->default(0); // Chi phí thực tế
            // total = budget (alias để dùng với ApprovalService)
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'cancelled'])->default('draft');
            $table->integer('current_approval_level')->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_events');
    }
};
