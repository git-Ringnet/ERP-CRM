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
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('document_type');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique('document_type', 'approval_workflows_document_type_unique');
        });

        Schema::create('approval_levels', function (Blueprint $table) {

            $table->id();
            $table->foreignId('workflow_id');
            $table->unsignedInteger('level');
            $table->string('name');
            $table->string('approver_type');
            $table->string('approver_value');
            $table->decimal('min_amount', 15, 2)->nullable();
            $table->decimal('max_amount', 15, 2)->nullable();
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            $table->unique(['workflow_id','level'], 'approval_levels_workflow_id_level_unique');

            // Foreign keys
            $table->foreign('workflow_id')->references('id')->on('approval_workflows')->onDelete('cascade');
        });

        Schema::create('approval_histories', function (Blueprint $table) {

            $table->id();
            $table->string('document_type');
            $table->unsignedBigInteger('document_id');
            $table->unsignedInteger('level');
            $table->string('level_name');
            $table->foreignId('approver_id')->nullable();
            $table->string('approver_name');
            $table->foreignId('original_approver_id')->nullable();
            $table->foreignId('delegated_to_id')->nullable();
            $table->enum('action', ['pending','approved','rejected']);
            $table->text('comment')->nullable();
            $table->timestamp('action_at')->nullable();
            $table->timestamps();
            $table->index(['document_type','document_id'], 'approval_histories_document_type_document_id_index');

            // Foreign keys
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('delegated_to_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('original_approver_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_histories');
        Schema::dropIfExists('approval_levels');
        Schema::dropIfExists('approval_workflows');
    }
};
