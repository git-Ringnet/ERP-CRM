<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE approval_histories MODIFY COLUMN level INT UNSIGNED NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE approval_histories MODIFY COLUMN level_name VARCHAR(255) NOT NULL DEFAULT ''");
        DB::statement("ALTER TABLE approval_histories MODIFY COLUMN approver_name VARCHAR(255) NOT NULL DEFAULT ''");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
