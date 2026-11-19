<?php

namespace App\Console\Commands;

use App\Models\ApprovalHistory;
use App\Models\Sale;
use Illuminate\Console\Command;

/**
 * Đồng bộ lại sales.current_approval_level theo cấp đang chờ duyệt thực tế (bản ghi pending trong approval_histories).
 *
 * Lỗi cũ: current_approval_level chưa nằm trong $fillable của Sale nên submit() không reset được về 0 khi gửi duyệt lại
 * → đơn đã từng được duyệt cấp 1 rồi gửi lại vẫn giữ giá trị 1 và bị hiển thị "Chờ duyệt PNL cấp 2" dù đang chờ cấp 1.
 */
class FixPnlApprovalLevel extends Command
{
    protected $signature = 'sales:fix-pnl-approval-level {--apply : Ghi thay đổi vào DB (mặc định chỉ xem trước)}';

    protected $description = 'Tính lại current_approval_level của các đơn đang chờ duyệt P&L theo lịch sử duyệt thực tế';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $rows = [];
        $warnings = [];

        Sale::where('status', 'pending')
            ->where('pl_status', 'pending')
            ->orderBy('id')
            ->chunkById(200, function ($sales) use ($apply, &$rows, &$warnings) {
                foreach ($sales as $sale) {
                    $pendingLevel = ApprovalHistory::where('document_type', 'sale_pnl')
                        ->where('document_id', $sale->id)
                        ->where('action', 'pending')
                        ->min('level');

                    if ($pendingLevel === null) {
                        $warnings[] = [$sale->code, (int) $sale->current_approval_level, 'Không có bản ghi chờ duyệt — bỏ qua'];
                        continue;
                    }

                    $current = (int) $sale->current_approval_level;
                    $correct = max(0, (int) $pendingLevel - 1);

                    if ($current === $correct) {
                        continue;
                    }

                    $rows[] = [
                        $sale->code,
                        $current,
                        $correct,
                        'Đang chờ cấp ' . $pendingLevel,
                        $current >= 1 ? 'Chờ duyệt PNL cấp 2' : 'Chờ duyệt PNL cấp 1',
                        $correct >= 1 ? 'Chờ duyệt PNL cấp 2' : 'Chờ duyệt PNL cấp 1',
                    ];

                    if ($apply) {
                        // Dùng query trực tiếp để không kích hoạt event/observer của Sale
                        Sale::whereKey($sale->id)->update(['current_approval_level' => $correct]);
                    }
                }
            });

        if ($rows) {
            $this->table(['Mã đơn', 'Level cũ', 'Level mới', 'Lịch sử duyệt', 'Nhãn cũ', 'Nhãn mới'], $rows);
        } else {
            $this->info('Không có đơn nào cần sửa.');
        }

        if ($warnings) {
            $this->warn('Các đơn cần kiểm tra tay:');
            $this->table(['Mã đơn', 'Level hiện tại', 'Ghi chú'], $warnings);
        }

        $this->line($apply
            ? 'Đã cập nhật ' . count($rows) . ' đơn.'
            : 'Chế độ xem trước: ' . count($rows) . ' đơn sẽ được cập nhật. Chạy lại với --apply để ghi vào DB.');

        return self::SUCCESS;
    }
}
