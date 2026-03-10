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
        Schema::create('department_kpi_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('department');
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2)->default(0); // Trọng số (%)
            $table->string('target')->nullable(); // Có thể là số hoặc text
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_kpi_criteria');
    }
};
