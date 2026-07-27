<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('marketing_requests', function (Blueprint $table) {
            $table->foreignId('marketing_supplier_fund_id')
                ->nullable()
                ->constrained('marketing_supplier_funds', 'id', 'mkt_req_fund_foreign')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_requests', function (Blueprint $table) {
            $table->dropForeign('mkt_req_fund_foreign');
            $table->dropColumn('marketing_supplier_fund_id');
        });
    }
};
