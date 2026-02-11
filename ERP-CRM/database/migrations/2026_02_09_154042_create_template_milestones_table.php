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
        Schema::create('template_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_template_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('days_from_start')->default(0); // Days offset from care stage start date
            $table->integer('order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('milestone_template_id');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_milestones');
    }
};
