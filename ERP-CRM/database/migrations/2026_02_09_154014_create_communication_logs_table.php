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
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_care_stage_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['call', 'email', 'meeting', 'sms', 'whatsapp', 'zalo', 'other'])->default('other');
            $table->string('subject');
            $table->text('description')->nullable();
            $table->enum('sentiment', ['positive', 'neutral', 'negative'])->nullable();
            $table->integer('duration_minutes')->nullable(); // For calls and meetings
            $table->dateTime('occurred_at');
            $table->timestamps();

            // Indexes
            $table->index('customer_care_stage_id');
            $table->index('user_id');
            $table->index('type');
            $table->index('occurred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
    }
};
