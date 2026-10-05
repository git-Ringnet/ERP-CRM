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
        if (!Schema::hasColumn('imports', 'po_code')) {
            Schema::table('imports', function (Blueprint $table) {
                $table->string('po_code', 100)->nullable()->after('reference_id')->comment('Mã đơn mua hàng PO nhập thủ công hoặc từ Excel');
                $table->index('po_code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('imports', 'po_code')) {
            Schema::table('imports', function (Blueprint $table) {
                $table->dropIndex(['po_code']);
                $table->dropColumn('po_code');
            });
        }
    }
};
