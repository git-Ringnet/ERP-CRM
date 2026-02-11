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
        Schema::create('customer_care_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->enum('stage', ['new', 'onboarding', 'active', 'follow_up', 'retention', 'at_risk', 'inactive'])->default('new');
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'on_hold'])->default('not_started');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->date('start_date');
            $table->date('target_completion_date')->nullable();
            $table->date('actual_completion_date')->nullable();
            $table->integer('completion_percentage')->default(0);
            $table->text('notes')->nullable();
            
            // Next Action fields
            $table->text('next_action')->nullable();
            $table->dateTime('next_action_due_at')->nullable();
            $table->boolean('next_action_completed')->default(false);
            
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Indexes for better query performance
            $table->index('customer_id');
            $table->index('stage');
            $table->index('status');
            $table->index('priority');
            $table->index('assigned_to');
            $table->index('start_date');
            $table->index('target_completion_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_care_stages');
    }
};
