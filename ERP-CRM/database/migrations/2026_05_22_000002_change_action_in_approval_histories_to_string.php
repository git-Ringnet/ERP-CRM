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
        Schema::table('approval_histories', function (Blueprint $table) {
            $table->string('action', 255)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_histories', function (Blueprint $table) {
            // Revert back is not directly possible if there are new values, but we can change it back to enum
            // for safety we keep it as string
            $table->string('action', 255)->change();
        });
    }
};
