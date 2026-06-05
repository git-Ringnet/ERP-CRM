<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('opportunity_attachments')) {
            Schema::create('opportunity_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
                $table->string('file_name');
                $table->string('file_path');
                $table->text('mime_type')->nullable();
                $table->integer('file_size')->default(0);
                $table->text('note')->nullable();
                $table->string('uploaded_by_name')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_attachments');
    }
};
