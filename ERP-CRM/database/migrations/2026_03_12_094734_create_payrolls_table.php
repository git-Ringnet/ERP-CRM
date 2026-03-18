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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('month');
            $table->integer('year');
            $table->decimal('standard_working_days', 4, 1)->default(26);
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'paid'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Một tháng năm chỉ có 1 bảng lương chính thức (tuỳ công ty, nhưng thường thế)
            $table->unique(['month', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
