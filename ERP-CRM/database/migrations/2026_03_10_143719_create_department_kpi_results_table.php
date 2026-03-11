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
        Schema::create('department_kpi_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_kpi_id')->constrained()->cascadeOnDelete();
            $table->string('criterion_name');
            $table->decimal('weight', 5, 2)->default(0);
            $table->string('target')->nullable();
            $table->string('actual_value')->nullable();
            $table->decimal('score', 8, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_kpi_results');
    }
};
