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
        Schema::table('sale_order_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_order_requests', 'is_license_from_other_distributor')) {
                $table->boolean('is_license_from_other_distributor')->default(false)->after('note');
            }
            if (!Schema::hasColumn('sale_order_requests', 'other_distributor_name')) {
                $table->string('other_distributor_name')->nullable()->after('is_license_from_other_distributor');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_order_requests', function (Blueprint $table) {
            if (Schema::hasColumn('sale_order_requests', 'other_distributor_name')) {
                $table->dropColumn('other_distributor_name');
            }
            if (Schema::hasColumn('sale_order_requests', 'is_license_from_other_distributor')) {
                $table->dropColumn('is_license_from_other_distributor');
            }
        });
    }
};
