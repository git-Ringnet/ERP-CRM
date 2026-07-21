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
        Schema::create('opportunities', function (Blueprint $table) {

            $table->id();
            $table->string('customer_type', 10)->default('si');
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('contact_id')->nullable();
            $table->string('eu_company_name')->nullable();
            $table->string('eu_contact_name')->nullable();
            $table->string('eu_phone', 50)->nullable();
            $table->string('eu_email')->nullable();
            $table->string('eu_position')->nullable();
            $table->string('name');
            $table->string('activity_type', 50)->default('other');
            $table->string('activity_type_other')->nullable();
            $table->date('activity_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->text('materials_required')->nullable();
            $table->text('giveaway')->nullable();
            $table->string('giveaway_status', 20)->default('none');
            $table->string('status', 20)->default('planned');
            $table->text('cancel_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('needs_technical')->default(false);
            $table->foreignId('technical_user_id')->nullable();
            $table->text('customer_feedback')->nullable();
            $table->text('meeting_result')->nullable();
            $table->text('pain_points')->nullable();
            $table->string('next_action')->nullable();
            $table->string('potential_rating', 10)->nullable();
            $table->foreignId('project_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('lead_id')->nullable();
            $table->foreignId('assigned_to')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('assigned_to')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
            $table->foreign('technical_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
