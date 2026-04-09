<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_expenses', function (Blueprint $table) {
            $table->string('input_mode', 10)->default('fixed')->after('type')->comment('percent or fixed');
            $table->decimal('percent_value', 8, 2)->nullable()->after('input_mode')->comment('% value if input_mode=percent');
        });

        // Change type from enum to varchar to allow custom expense names
        DB::statement("ALTER TABLE sale_expenses MODIFY COLUMN type VARCHAR(100) NOT NULL DEFAULT 'other'");
    }

    public function down(): void
    {
        Schema::table('sale_expenses', function (Blueprint $table) {
            $table->dropColumn(['input_mode', 'percent_value']);
        });

        DB::statement("ALTER TABLE sale_expenses MODIFY COLUMN type ENUM('shipping','marketing','commission','other') NOT NULL DEFAULT 'other'");
    }
};
