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
        Schema::table('sales', function (Blueprint $table) {
            $table->string('eu_name', 500)->nullable()->after('customer_name');
            $table->string('eu_tax_code', 100)->nullable()->after('eu_name');
            $table->text('eu_address')->nullable()->after('eu_tax_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['eu_name', 'eu_tax_code', 'eu_address']);
        });
    }
};
