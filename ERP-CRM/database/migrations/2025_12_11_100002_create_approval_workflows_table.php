<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cấu hình quy trình duyệt
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('document_type')->unique(); // quotation, contract, order, purchase
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Các cấp duyệt trong quy trình
        Schema::create('approval_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('approval_workflows')->onDelete('cascade');
            $table->unsignedInteger('level'); // 1, 2, 3...
            $table->string('name'); // Trưởng phòng, Giám đốc, Pháp chế...
            $table->string('approver_type'); // role, user
            $table->string('approver_value'); // role name hoặc user_id
            $table->decimal('min_amount', 15, 2)->nullable(); // Điều kiện giá trị tối thiểu
            $table->decimal('max_amount', 15, 2)->nullable(); // Điều kiện giá trị tối đa
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['workflow_id', 'level']);
        });

        // Lịch sử duyệt
        Schema::create('approval_histories', function (Blueprint $table) {
            $table->id();
            $table->string('document_type'); // quotation, contract, order
            $table->unsignedBigInteger('document_id');
            $table->unsignedInteger('level');
            $table->string('level_name');
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approver_name');
            $table->enum('action', ['pending', 'approved', 'rejected']);
            $table->text('comment')->nullable();
            $table->timestamp('action_at')->nullable();
            $table->timestamps();

            $table->index(['document_type', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_histories');
        Schema::dropIfExists('approval_levels');
        Schema::dropIfExists('approval_workflows');
    }
};
