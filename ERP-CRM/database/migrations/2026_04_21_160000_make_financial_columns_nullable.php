<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Set financial columns to nullable to prevent "Integrity constraint violation" 
     * when the controller sends null for empty inputs.
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('other_support_cost', 15, 2)->nullable()->change();
            $table->decimal('technical_poc_cost', 15, 2)->nullable()->change();
            $table->decimal('implementation_cost', 15, 2)->nullable()->change();
            $table->decimal('contractor_tax', 15, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('other_support_cost', 15, 2)->nullable(false)->default(0)->change();
            $table->decimal('technical_poc_cost', 15, 2)->nullable(false)->default(0)->change();
            $table->decimal('implementation_cost', 15, 2)->nullable(false)->default(0)->change();
            $table->decimal('contractor_tax', 15, 2)->nullable(false)->default(0)->change();
        });
    }
};
