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
        Schema::create('pnl_approval_attachments', function (Blueprint $table) {

            $table->id();
            $table->foreignId('sale_id');
            $table->foreignId('approval_history_id')->nullable();
            $table->foreignId('uploaded_by')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('note')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('approval_history_id')->references('id')->on('approval_histories')->onDelete('set null');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pnl_approval_attachments');
    }
};
