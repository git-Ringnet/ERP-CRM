<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phân loại file đính kèm đơn bán: 'general' (mặc định) hoặc 'bank_guarantee' (thư bảo lãnh).
 * Đơn có bảo lãnh phải có thư bảo lãnh trước khi được yêu cầu xuất hóa đơn.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('sale_attachments', 'type')) {
            return;
        }

        Schema::table('sale_attachments', function (Blueprint $table) {
            $table->string('type', 30)->default('general')->after('sale_id');
            $table->index(['sale_id', 'type']);
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('sale_attachments', 'type')) {
            return;
        }

        Schema::table('sale_attachments', function (Blueprint $table) {
            $table->dropIndex(['sale_id', 'type']);
            $table->dropColumn('type');
        });
    }
};
