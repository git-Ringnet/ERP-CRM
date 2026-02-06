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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            
            // Người thực hiện
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable(); // Backup nếu user bị xóa
            
            // Hành động
            $table->string('action', 50); // created, updated, deleted, login, logout, approved, etc.
            $table->text('description')->nullable(); // Mô tả chi tiết
            
            // Đối tượng bị tác động (polymorphic)
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            
            // Dữ liệu thay đổi
            $table->json('properties')->nullable(); // { old: {...}, new: {...}, attributes: {...} }
            
            // Metadata
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamp('created_at')->nullable();
            
            // Indexes
            $table->index('user_id');
            $table->index(['subject_type', 'subject_id']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
