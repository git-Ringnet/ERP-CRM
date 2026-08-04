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
        Schema::create('invoice_request_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_request_id')->constrained('invoice_requests')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('version')->default(1);
            $table->string('action'); // 'draft_uploaded', 'draft_rejected', 'official_issued', 'reimported'
            $table->string('draft_path')->nullable();
            $table->string('official_path')->nullable();
            $table->string('delivery_note_path')->nullable();
            $table->text('note')->nullable(); // reason or uploader note
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_request_revisions');
    }
};
