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
        if (!Schema::hasTable('meeting_room_bookings')) {
            Schema::create('meeting_room_bookings', function (Blueprint $table) {
                $table->id();
                $table->string('room_name');
                $table->string('title');
                $table->text('description')->nullable();
                $table->dateTime('start_time');
                $table->dateTime('end_time');
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->string('status')->default('confirmed'); // confirmed, cancelled
                $table->boolean('is_private')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('meeting_room_attendees')) {
            Schema::create('meeting_room_attendees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('meeting_room_booking_id')->constrained('meeting_room_bookings')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('status')->default('pending'); // pending, accepted, declined
                $table->string('note')->nullable();
                $table->dateTime('responded_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_room_attendees');
        Schema::dropIfExists('meeting_room_bookings');
    }
};
