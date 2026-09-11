<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'sales' => ['vat'],
            'quotations' => ['vat'],
            'supplier_quotations' => ['vat_percent'],
            'purchase_orders' => ['vat_percent'],
            'imports' => ['vat_percent'],
        ];

        foreach ($columns as $table => $tableColumns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($tableColumns, $table) {
                foreach ($tableColumns as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $blueprint->decimal($column, 5, 2)->default(8)->change();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // Historical records retain their stored VAT. Reverting only changes
        // defaults for future records.
    }
};
