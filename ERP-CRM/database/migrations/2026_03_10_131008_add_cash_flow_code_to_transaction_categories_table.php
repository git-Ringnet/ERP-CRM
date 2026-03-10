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
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->string('cash_flow_code')->nullable()->after('type');
        });

        // Seed initial mapping for existing categories
        \DB::table('transaction_categories')->where('name', 'Lương nhân viên')->update(['cash_flow_code' => '17']);
        \DB::table('transaction_categories')->where('name', 'Doanh thu bán hàng')->update(['cash_flow_code' => '1']);
    }

    public function down(): void
    {
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->dropColumn('cash_flow_code');
        });
    }
};
