<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_event_id')->constrained('marketing_events')->onDelete('cascade');
            $table->string('code')->unique();
            $table->enum('type', ['internal_collaboration', 'business_trip', 'payment', 'others']);
            $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_tickets');
    }
};
