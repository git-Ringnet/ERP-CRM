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
        Schema::create('employee_sales_revenues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->decimal('total_revenue', 15, 0)->default(0);
            $table->decimal('total_profit', 15, 0)->default(0);
            $table->unsignedInteger('quantity_on_target')->default(0);
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();

            // Unique index to prevent duplicate records for same employee in same month/year
            $table->unique(['user_id', 'month', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_sales_revenues');
    }
};
