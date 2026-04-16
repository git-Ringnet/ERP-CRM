<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_quotations', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('total')->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency_id');
            $table->decimal('total_foreign', 18, 4)->nullable()->after('exchange_rate');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_quotations', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate', 'total_foreign']);
        });
    }
};
