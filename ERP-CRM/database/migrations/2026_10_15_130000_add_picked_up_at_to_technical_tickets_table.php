<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TECH 1: SLA = thời gian pickup/assign ticket -> lưu thời điểm pickup/phân công đầu tiên.
     * TECH 3-7: BOM / Technical Documents / After-Sales / IT nội bộ / Others cho toàn bộ kỹ thuật tự pickup.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('technical_tickets', 'picked_up_at')) {
            Schema::table('technical_tickets', function (Blueprint $table) {
                $table->timestamp('picked_up_at')->nullable()->after('sla_deadline');
            });
        }

        // Ticket đã có kỹ sư: lấy thời điểm gán kỹ sư sớm nhất
        DB::statement("
            UPDATE technical_tickets t
            JOIN (
                SELECT technical_ticket_id, MIN(created_at) AS first_at
                FROM technical_ticket_engineers
                GROUP BY technical_ticket_id
            ) e ON e.technical_ticket_id = t.id
            SET t.picked_up_at = GREATEST(e.first_at, t.created_at)
            WHERE t.picked_up_at IS NULL
        ");
        DB::table('technical_tickets')
            ->whereNull('picked_up_at')
            ->whereNotNull('assigned_to')
            ->update(['picked_up_at' => DB::raw('updated_at')]);

        // Cấu hình phạm vi pickup đã lưu (nếu có): mở các loại tự pickup cho toàn bộ kỹ thuật
        $saved = Setting::get('technical_ticket_work_type_permissions');
        $decoded = is_array($saved) ? $saved : json_decode((string) $saved, true);
        if (is_array($decoded) && !empty($decoded)) {
            foreach (['BOM', 'documentation', 'after_sales', 'it_support', 'other'] as $type) {
                $current = $decoded[$type] ?? null;
                $scope = is_array($current) ? ($current['scope'] ?? 'leader') : ($current ?? 'leader');
                if ($scope === 'leader') {
                    $decoded[$type] = ['scope' => 'all', 'roles' => []];
                }
            }
            Setting::set('technical_ticket_work_type_permissions', json_encode($decoded));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('technical_tickets', 'picked_up_at')) {
            Schema::table('technical_tickets', function (Blueprint $table) {
                $table->dropColumn('picked_up_at');
            });
        }
    }
};
