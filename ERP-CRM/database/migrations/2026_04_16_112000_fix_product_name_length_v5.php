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
        // Step 1: Drop index in a separate block
        try {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['name']);
            });
        } catch (\Exception $e) {
            // Index might already be gone
        }

        // Step 2: Change column to TEXT in a separate block
        Schema::table('products', function (Blueprint $table) {
            $table->text('name')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('name', 255)->change();
            $table->index('name');
        });
    }
};
