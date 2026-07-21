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
        Schema::create('payment_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique('code', 'payment_templates_code_unique');
        });

        Schema::create('payment_template_items', function (Blueprint $table) {

            $table->id();
            $table->foreignId('template_id');
            $table->integer('sort_order')->default(0);
            $table->string('milestone_name');
            $table->decimal('percentage', 5, 2);
            $table->string('trigger_type');
            $table->string('trigger_value')->nullable();
            $table->string('blocking_stage')->nullable();
            $table->string('due_base');
            $table->integer('due_days')->default(0);
            $table->string('required_docs')->default('none');
            $table->timestamps();

            // Foreign keys
            $table->foreign('template_id')->references('id')->on('payment_templates')->onDelete('cascade');
        });

        Schema::create('sale_payment_schedules', function (Blueprint $table) {

            $table->id();
            $table->foreignId('sale_id');
            $table->foreignId('template_id')->nullable();
            $table->integer('template_version')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('milestone_name');
            $table->decimal('percentage', 5, 2);
            $table->decimal('amount', 15, 2);
            $table->string('trigger_type');
            $table->string('trigger_value')->nullable();
            $table->string('blocking_stage')->nullable();
            $table->string('due_base');
            $table->integer('due_days')->default(0);
            $table->string('required_docs')->default('none');
            $table->string('status')->default('pending');
            $table->date('trigger_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->string('proof_file_path')->nullable();
            $table->string('bod_approval_file_path')->nullable();
            $table->string('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('delegated_to_id')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('delegated_to_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('payment_templates')->onDelete('set null');
        });

        Schema::create('payment_evidences', function (Blueprint $table) {

            $table->id();
            $table->foreignId('schedule_id');
            $table->string('doc_type');
            $table->string('reference_number')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('file_path');
            $table->foreignId('uploaded_by');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('schedule_id')->references('id')->on('sale_payment_schedules')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('payment_approval_logs', function (Blueprint $table) {

            $table->id();
            $table->foreignId('schedule_id')->nullable();
            $table->foreignId('sale_id');
            $table->string('action');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('reason')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('performed_by');
            $table->timestamp('performed_at')->useCurrent();
            $table->timestamps();

            // Foreign keys
            $table->foreign('performed_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('schedule_id')->references('id')->on('sale_payment_schedules')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_approval_logs');
        Schema::dropIfExists('payment_evidences');
        Schema::dropIfExists('sale_payment_schedules');
        Schema::dropIfExists('payment_template_items');
        Schema::dropIfExists('payment_templates');
    }
};
