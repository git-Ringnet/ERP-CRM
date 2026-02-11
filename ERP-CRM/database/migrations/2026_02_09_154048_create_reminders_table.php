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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->string('remindable_type'); // Polymorphic relation
            $table->unsignedBigInteger('remindable_id'); // Polymorphic relation
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->dateTime('remind_at');
            $table->text('message');
            $table->boolean('is_sent')->default(false);
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['remindable_type', 'remindable_id']);
            $table->index('user_id');
            $table->index('remind_at');
            $table->index('is_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
