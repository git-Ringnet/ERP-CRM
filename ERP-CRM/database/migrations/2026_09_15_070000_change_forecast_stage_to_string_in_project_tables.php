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
        // Change forecast_stage in projects and project_status_updates from enum to string
        try {
            DB::statement("ALTER TABLE `projects` MODIFY `forecast_stage` VARCHAR(100) NULL");
        } catch (\Throwable $e) {
            // fallback
        }

        try {
            DB::statement("ALTER TABLE `project_status_updates` MODIFY `forecast_stage` VARCHAR(100) NOT NULL");
        } catch (\Throwable $e) {
            // fallback
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
