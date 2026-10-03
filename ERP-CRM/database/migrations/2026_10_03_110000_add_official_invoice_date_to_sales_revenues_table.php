<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_revenues', function (Blueprint $table) {
            $table->date('official_invoice_date')->nullable()->after('invoice_status');
        });
    }

    public function down(): void
    {
        Schema::table('sales_revenues', function (Blueprint $table) {
            $table->dropColumn('official_invoice_date');
        });
    }
};
