<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_event_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_event_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['invited', 'attended', 'cancelled'])->default('invited');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['marketing_event_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_event_customers');
    }
};
