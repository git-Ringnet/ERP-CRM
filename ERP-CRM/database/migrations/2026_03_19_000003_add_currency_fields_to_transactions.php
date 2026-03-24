<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thêm các cột tiền tệ vào bảng giao dịch
     */
    public function up(): void
    {
        // === SALES ===
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('note')->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 18, 6)->nullable()->after('currency_id')->comment('Tỷ giá chốt lúc tạo đơn');
            $table->decimal('total_foreign', 18, 4)->nullable()->after('exchange_rate')->comment('Tổng tiền nguyên tệ');
        });

        // === QUOTATIONS ===
        Schema::table('quotations', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('note')->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 18, 6)->nullable()->after('currency_id');
            $table->decimal('total_foreign', 18, 4)->nullable()->after('exchange_rate');
        });

        // === PURCHASE ORDERS ===
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('note')->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 18, 6)->nullable()->after('currency_id');
            $table->decimal('total_foreign', 18, 4)->nullable()->after('exchange_rate');
        });

        // === FINANCIAL TRANSACTIONS ===
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('note')->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 18, 6)->nullable()->after('currency_id');
            $table->decimal('amount_foreign', 18, 4)->nullable()->after('exchange_rate');
        });

        // === PAYMENT HISTORIES ===
        Schema::table('payment_histories', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('note')->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 18, 6)->nullable()->after('currency_id');
            $table->decimal('amount_foreign', 18, 4)->nullable()->after('exchange_rate');
        });

        // === SUPPLIER PAYMENT HISTORIES ===
        // Bảng này đã có cột exchange_rate, amount_foreign, currency (string)
        // Chỉ thêm currency_id FK để liên kết với bảng currencies
        Schema::table('supplier_payment_histories', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('note')->constrained('currencies')->nullOnDelete();
        });

        // ======================================
        // DATA MIGRATION: Chuẩn hóa dữ liệu cũ về VND
        // ======================================
        $vndId = DB::table('currencies')->where('code', 'VND')->value('id');

        if ($vndId) {
            // Sales: gán VND, exchange_rate = 1, total_foreign = total
            DB::table('sales')->whereNull('currency_id')->update([
                'currency_id' => $vndId,
                'exchange_rate' => 1.000000,
                'total_foreign' => DB::raw('`total`'),
            ]);

            // Quotations
            DB::table('quotations')->whereNull('currency_id')->update([
                'currency_id' => $vndId,
                'exchange_rate' => 1.000000,
                'total_foreign' => DB::raw('`total`'),
            ]);

            // Purchase Orders
            DB::table('purchase_orders')->whereNull('currency_id')->update([
                'currency_id' => $vndId,
                'exchange_rate' => 1.000000,
                'total_foreign' => DB::raw('`total`'),
            ]);

            // Financial Transactions
            DB::table('financial_transactions')->whereNull('currency_id')->update([
                'currency_id' => $vndId,
                'exchange_rate' => 1.000000,
                'amount_foreign' => DB::raw('`amount`'),
            ]);

            // Payment Histories
            DB::table('payment_histories')->whereNull('currency_id')->update([
                'currency_id' => $vndId,
                'exchange_rate' => 1.000000,
                'amount_foreign' => DB::raw('`amount`'),
            ]);

            // Supplier Payment Histories
            // Map string currency -> currency_id FK
            // Bước 1: Gán VND cho records chưa có currency_id
            DB::table('supplier_payment_histories')
                ->whereNull('currency_id')
                ->update(['currency_id' => $vndId]);

            // Bước 2: Map existing string currency codes to currency_id
            $currencies = DB::table('currencies')->pluck('id', 'code');
            foreach ($currencies as $code => $id) {
                DB::table('supplier_payment_histories')
                    ->where('currency', $code)
                    ->update(['currency_id' => $id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_payment_histories', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id']);
        });

        Schema::table('payment_histories', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate', 'amount_foreign']);
        });

        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate', 'amount_foreign']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate', 'total_foreign']);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate', 'total_foreign']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate', 'total_foreign']);
        });
    }
};
