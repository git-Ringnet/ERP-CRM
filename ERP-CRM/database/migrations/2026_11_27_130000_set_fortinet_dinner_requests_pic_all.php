<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sự kiện "Fortinet _Networking Dinner" (MKT-2026-0004): chuyển 2 yêu cầu gửi team Kỹ thuật
 * từ "Trưởng nhóm phân công" sang "Cả team tự nhận việc" theo yêu cầu nghiệp vụ.
 */
return new class extends Migration
{
    private array $codes = ['REQ-2026-0006', 'REQ-2026-0007'];

    public function up(): void
    {
        DB::table('marketing_requests')
            ->whereIn('code', $this->codes)
            ->where('support_team', 'technical')
            ->where('pic_type', 'lead')
            ->whereNull('assigned_to')
            ->update(['pic_type' => 'all', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('marketing_requests')
            ->whereIn('code', $this->codes)
            ->where('pic_type', 'all')
            ->update(['pic_type' => 'lead']);
    }
};
