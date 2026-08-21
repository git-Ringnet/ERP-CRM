<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Place the export permission with the Technical Dashboard, where the
     * export action is available.
     */
    public function up(): void
    {
        DB::table('permissions')
            ->where('slug', 'export_technical_tickets')
            ->update([
                'module' => 'technical_dashboard',
                'updated_at' => now(),
            ]);
    }

    /**
     * Restore the previous grouping if the migration is rolled back.
     */
    public function down(): void
    {
        DB::table('permissions')
            ->where('slug', 'export_technical_tickets')
            ->update([
                'module' => 'technical_tickets',
                'updated_at' => now(),
            ]);
    }
};
