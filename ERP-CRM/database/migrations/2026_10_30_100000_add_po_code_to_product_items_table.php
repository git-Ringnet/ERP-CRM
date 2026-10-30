<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Số PO riêng của từng thiết bị: phiếu nhập tồn đầu kỳ (VD "FTN STOCK") gồm nhiều mã hàng,
     * khi tìm được PO thật của 1 S/N thì chỉ sửa S/N đó, không đổi Số PO cả phiếu nhập.
     */
    public function up(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            $table->string('po_code', 100)->nullable()->after('import_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            $table->dropIndex(['po_code']);
            $table->dropColumn('po_code');
        });
    }
};
