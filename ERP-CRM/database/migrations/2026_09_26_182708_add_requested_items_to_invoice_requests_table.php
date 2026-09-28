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
        Schema::table('invoice_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_requests', 'requested_items')) {
                $table->json('requested_items')->nullable()->after('item_descriptions');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_requests', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_requests', 'requested_items')) {
                $table->dropColumn('requested_items');
            }
        });
    }
};
