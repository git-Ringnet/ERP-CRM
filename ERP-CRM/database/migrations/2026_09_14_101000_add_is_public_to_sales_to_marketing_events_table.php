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
        Schema::table('marketing_events', function (Blueprint $table) {
            if (!Schema::hasColumn('marketing_events', 'is_public_to_sales')) {
                $table->boolean('is_public_to_sales')->default(false)->after('scope');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketing_events', function (Blueprint $table) {
            if (Schema::hasColumn('marketing_events', 'is_public_to_sales')) {
                $table->dropColumn('is_public_to_sales');
            }
        });
    }
};
