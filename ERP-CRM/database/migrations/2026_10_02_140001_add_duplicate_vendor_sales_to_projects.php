<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'duplicate_vendor_sales_info')) {
                $table->text('duplicate_vendor_sales_info')->nullable()->after('duplicate_sales_info');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'duplicate_vendor_sales_info')) {
                $table->dropColumn('duplicate_vendor_sales_info');
            }
        });
    }
};
