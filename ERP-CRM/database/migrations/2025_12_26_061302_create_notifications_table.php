<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type', 50); // 'import_created', 'export_created', 'transfer_created', 'approved', 'rejected'
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Lưu thêm thông tin như document_id, document_type, etc.
            $table->string('link')->nullable(); // URL để chuyển đến khi click
            $table->string('icon', 50)->nullable(); // 'arrow-down', 'arrow-up', 'exchange', 'check', 'times'
            $table->string('color', 50)->nullable(); // 'blue', 'orange', 'purple', 'green', 'red'
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('is_read');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
