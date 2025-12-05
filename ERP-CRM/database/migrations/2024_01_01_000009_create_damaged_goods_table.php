<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('damaged_goods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['damaged', 'liquidation']);
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 10, 2);
            $table->decimal('original_value', 15, 2);
            $table->decimal('recovery_value', 15, 2)->default(0);
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'processed'])->default('pending');
            $table->date('discovery_date');
            $table->foreignId('discovered_by')->constrained('users')->onDelete('cascade');
            $table->text('solution')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('damaged_goods');
    }
};
