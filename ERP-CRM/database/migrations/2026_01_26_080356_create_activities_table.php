<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->enum('type', ['call', 'meeting', 'email', 'task', 'note'])->default('task');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();

            // Relationships
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Assigned To
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            // Polymorphic or specific? Let's use specific for now as per simple erp structure usually seen
            $table->foreignId('opportunity_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('lead_id')->nullable()->constrained()->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
