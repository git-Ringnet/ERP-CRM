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
        Schema::create('department_kpis', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('department');
            $table->string('evaluation_period'); // Tháng/Năm vd: 2026-03
            $table->string('status')->default('draft'); // draft, pending, approved, completed
            $table->decimal('total_score', 8, 2)->default(0);
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_kpis');
    }
};
