<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thời điểm gửi duyệt P&L gần nhất: dùng để đối chiếu PriceList với bảng giá
     * có hiệu lực tại thời điểm gửi (không phải thời điểm BOD mở ra duyệt).
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->timestamp('pl_submitted_at')->nullable()->after('pl_status');
        });

        // Bổ sung cho các P&L đang chờ duyệt: lấy bản ghi chờ duyệt sớm nhất của vòng duyệt hiện tại
        DB::table('sales')->where('pl_status', 'pending')->orderBy('id')->each(function ($sale) {
            $lastReturnedAt = DB::table('approval_histories')
                ->where('document_type', 'sale_pnl')
                ->where('document_id', $sale->id)
                ->whereIn('action', ['need_revision', 'rejected'])
                ->max('action_at');

            $submittedAt = DB::table('approval_histories')
                ->where('document_type', 'sale_pnl')
                ->where('document_id', $sale->id)
                ->when($lastReturnedAt, fn ($q) => $q->where('created_at', '>=', $lastReturnedAt))
                ->min('created_at');

            DB::table('sales')->where('id', $sale->id)->update([
                'pl_submitted_at' => $submittedAt ?? $sale->updated_at,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('pl_submitted_at');
        });
    }
};
