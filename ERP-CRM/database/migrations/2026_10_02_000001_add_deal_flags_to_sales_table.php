<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('is_license_vnet')->default(false)->after('project_id');
            $table->enum('trade_up_matrix', ['none', 'correct', 'incorrect'])->default('none')->after('is_license_vnet');
            $table->boolean('ohf_cost_added')->default(false)->after('trade_up_matrix');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['is_license_vnet', 'trade_up_matrix', 'ohf_cost_added']);
        });
    }
};
