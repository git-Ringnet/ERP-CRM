<?php

namespace App\Console\Commands;

use App\Models\Export;
use App\Models\ExportItem;
use App\Models\ExchangeRateDifference;
use App\Models\FinancialTransaction;
use App\Models\Notification;
use App\Models\PaymentEvidence;
use App\Models\PaymentHistory;
use App\Models\ProductItem;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\SaleOrderRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateNovemberSaleCodes extends Command
{
    protected $signature = 'sales:update-november-codes
                            {--from=2026-11-01 : Ngày bắt đầu áp dụng công thức mã mới (YYYY-MM-DD)}
                            {--dry-run : Chỉ kiểm tra và hiển thị danh sách thay đổi, không ghi vào DB}';

    protected $description = 'Cập nhật lại mã SO từ tháng 11/2026 theo công thức SO-{năm}-{000x} và đồng bộ các module liên quan';

    public function handle(): int
    {
        $fromDate = $this->option('from');
        $dryRun = (bool) $this->option('dry-run');
        $year = date('Y', strtotime($fromDate));
        $prefix = 'SO-' . $year . '-';

        $this->info('================================================================');
        $this->info("CẬP NHẬT MÃ SO TỪ {$fromDate} THEO CÔNG THỨC {$prefix}000x");
        if ($dryRun) {
            $this->warn('[CHẾ ĐỘ DRY-RUN: Không thay đổi dữ liệu thật]');
        }
        $this->info('================================================================');

        // Lấy các đơn hàng từ tháng 11/2026 trở đi (theo created_at, date hoặc mã SO-202611%)
        $sales = Sale::where(function ($q) use ($fromDate, $year, $prefix) {
                $q->where('created_at', '>=', $fromDate . ' 00:00:00')
                  ->orWhere('date', '>=', $fromDate)
                  ->orWhere('code', 'like', 'SO-' . $year . '11%')
                  ->orWhere('code', 'like', 'SO-' . $year . '12%')
                  ->orWhere('code', 'like', $prefix . '%');
            })
            ->orderBy('id', 'asc')
            ->get();

        if ($sales->isEmpty()) {
            $this->info('Không tìm thấy đơn hàng nào cần cập nhật.');
            return self::SUCCESS;
        }

        $mappings = [];
        $seq = 1;
        foreach ($sales as $sale) {
            $newCode = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
            $mappings[] = [
                'sale' => $sale,
                'old_code' => $sale->code,
                'new_code' => $newCode,
                'changed' => $sale->code !== $newCode,
            ];
            $seq++;
        }

        $tableRows = array_map(fn ($m) => [
            $m['sale']->id,
            $m['old_code'],
            $m['new_code'],
            optional($m['sale']->created_at)->format('d/m/Y H:i'),
            $m['sale']->customer_name,
            $m['changed'] ? 'Cập nhật' : 'Giữ nguyên',
        ], $mappings);

        $this->table(
            ['ID', 'Mã SO cũ', 'Mã SO mới', 'Ngày tạo', 'Khách hàng', 'Trạng thái'],
            $tableRows
        );

        $toChange = array_filter($mappings, fn ($m) => $m['changed']);
        if (empty($toChange)) {
            $this->info('Tất cả đơn hàng đã đúng định dạng mã SO mới.');
            return self::SUCCESS;
        }

        DB::beginTransaction();
        try {
            // Bước 1: Đặt mã tạm cho bảng sales để tránh đụng unique constraint nếu có đơn trùng mã
            if (!$dryRun) {
                foreach ($toChange as $m) {
                    DB::table('sales')
                        ->where('id', $m['sale']->id)
                        ->update(['code' => '__TMP_' . $m['sale']->id . '__']);
                }
            }

            // Bước 2: Cập nhật mã chính thức và các bảng/module liên quan lưu chuỗi mã SO
            foreach ($toChange as $m) {
                /** @var Sale $sale */
                $sale = $m['sale'];
                $oldCode = $m['old_code'];
                $newCode = $m['new_code'];

                if (!$dryRun) {
                    DB::table('sales')
                        ->where('id', $sale->id)
                        ->update(['code' => $newCode]);
                }

                $this->syncRelatedModules($sale, $oldCode, $newCode, $dryRun);
            }

            if ($dryRun) {
                DB::rollBack();
                $this->newLine();
                $this->warn('Đã chạy kiểm tra (dry-run). Chạy lại không có --dry-run để áp dụng thay đổi.');
            } else {
                DB::commit();
                $this->newLine();
                $this->info('Đã cập nhật thành công ' . count($toChange) . ' đơn hàng bán và đồng bộ các module liên quan!');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Lỗi khi cập nhật mã SO: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function syncRelatedModules(Sale $sale, string $oldCode, string $newCode, bool $dryRun): void
    {
        $stats = [];

        // 1. Phiếu xuất kho (exports) liên kết với đơn bán hoặc chứa mã SO cũ
        $exports = Export::where('reference_type', 'sale')
            ->where('reference_id', $sale->id)
            ->orWhere('code', 'like', $oldCode . '%')
            ->orWhere('note', 'like', '%' . $oldCode . '%')
            ->get();

        foreach ($exports as $export) {
            $updates = [];
            if ($export->code === $oldCode || str_starts_with($export->code, $oldCode . '-')) {
                $UpdatedExportCode = $newCode . substr($export->code, strlen($oldCode));
                $updates['code'] = $UpdatedExportCode;
            }
            if ($export->note && str_contains($export->note, $oldCode)) {
                $updates['note'] = str_replace($oldCode, $newCode, $export->note);
            }
            if (!empty($updates)) {
                $stats['exports'] = ($stats['exports'] ?? 0) + 1;
                if (!$dryRun) {
                    $export->update($updates);
                }
            }
        }

        // 2. Chi tiết phiếu xuất kho (export_items.comments)
        $exportItemsCount = ExportItem::where('comments', 'like', '%' . $oldCode . '%')->count();
        if ($exportItemsCount > 0) {
            $stats['export_items'] = $exportItemsCount;
            if (!$dryRun) {
                DB::table('export_items')
                    ->where('comments', 'like', '%' . $oldCode . '%')
                    ->update(['comments' => DB::raw("REPLACE(comments, '{$oldCode}', '{$newCode}')")]);
            }
        }

        // 3. Giao dịch tài chính / Thu chi (financial_transactions.reference_number & note)
        $finCount = FinancialTransaction::where('reference_number', $oldCode)
            ->orWhere('note', 'like', '%' . $oldCode . '%')
            ->count();
        if ($finCount > 0) {
            $stats['financial_transactions'] = $finCount;
            if (!$dryRun) {
                FinancialTransaction::where('reference_number', $oldCode)
                    ->update(['reference_number' => $newCode]);
                DB::table('financial_transactions')
                    ->where('note', 'like', '%' . $oldCode . '%')
                    ->update(['note' => DB::raw("REPLACE(note, '{$oldCode}', '{$newCode}')")]);
            }
        }

        // 4. Chênh lệch tỷ giá (exchange_rate_differences)
        if (Schema::hasTable('exchange_rate_differences')) {
            $erdCount = ExchangeRateDifference::where('reference_number', $oldCode)
                ->orWhere('note', 'like', '%' . $oldCode . '%')
                ->count();
            if ($erdCount > 0) {
                $stats['exchange_rate_differences'] = $erdCount;
                if (!$dryRun) {
                    ExchangeRateDifference::where('reference_number', $oldCode)
                        ->update(['reference_number' => $newCode]);
                    DB::table('exchange_rate_differences')
                        ->where('note', 'like', '%' . $oldCode . '%')
                        ->update(['note' => DB::raw("REPLACE(note, '{$oldCode}', '{$newCode}')")]);
                }
            }
        }

        // 5. Lịch sử thanh toán & Minh chứng thanh toán (payment_histories, payment_evidences)
        if (Schema::hasTable('payment_histories')) {
            $phCount = PaymentHistory::where('reference_number', $oldCode)->count();
            if ($phCount > 0) {
                $stats['payment_histories'] = $phCount;
                if (!$dryRun) {
                    PaymentHistory::where('reference_number', $oldCode)
                        ->update(['reference_number' => $newCode]);
                }
            }
        }
        if (Schema::hasTable('payment_evidences')) {
            $peCount = PaymentEvidence::where('reference_number', $oldCode)->count();
            if ($peCount > 0) {
                $stats['payment_evidences'] = $peCount;
                if (!$dryRun) {
                    PaymentEvidence::where('reference_number', $oldCode)
                        ->update(['reference_number' => $newCode]);
                }
            }
        }

        // 6. Dự án (projects.po_code)
        $projCount = Project::where('po_code', $oldCode)->count();
        if ($projCount > 0) {
            $stats['projects'] = $projCount;
            if (!$dryRun) {
                Project::where('po_code', $oldCode)->update(['po_code' => $newCode]);
            }
        }

        // 7. Thiết bị / Serial trong kho (product_items.comments)
        $piCount = ProductItem::where('comments', 'like', '%' . $oldCode . '%')->count();
        if ($piCount > 0) {
            $stats['product_items'] = $piCount;
            if (!$dryRun) {
                DB::table('product_items')
                    ->where('comments', 'like', '%' . $oldCode . '%')
                    ->update(['comments' => DB::raw("REPLACE(comments, '{$oldCode}', '{$newCode}')")]);
            }
        }

        // 8. Đơn mua hàng (purchase_orders.note) & Yêu cầu đặt hàng (sale_order_requests.notes)
        $poNoteCount = PurchaseOrder::where('note', 'like', '%' . $oldCode . '%')->count();
        if ($poNoteCount > 0) {
            $stats['purchase_orders.note'] = $poNoteCount;
            if (!$dryRun) {
                DB::table('purchase_orders')
                    ->where('note', 'like', '%' . $oldCode . '%')
                    ->update(['note' => DB::raw("REPLACE(note, '{$oldCode}', '{$newCode}')")]);
            }
        }

        $sorNoteCol = Schema::hasColumn('sale_order_requests', 'note') ? 'note' : (Schema::hasColumn('sale_order_requests', 'notes') ? 'notes' : null);
        if ($sorNoteCol) {
            $sorNoteCount = DB::table('sale_order_requests')->where($sorNoteCol, 'like', '%' . $oldCode . '%')->count();
            if ($sorNoteCount > 0) {
                $stats["sale_order_requests.{$sorNoteCol}"] = $sorNoteCount;
                if (!$dryRun) {
                    DB::table('sale_order_requests')
                        ->where($sorNoteCol, 'like', '%' . $oldCode . '%')
                        ->update([$sorNoteCol => DB::raw("REPLACE({$sorNoteCol}, '{$oldCode}', '{$newCode}')")]);
                }
            }
        }

        // 9. Thông báo hệ thống (notifications.message, link, data)
        if (Schema::hasTable('notifications')) {
            $notifCount = Notification::where('message', 'like', '%' . $oldCode . '%')
                ->orWhere('link', 'like', '%' . $oldCode . '%')
                ->orWhere('data', 'like', '%' . $oldCode . '%')
                ->count();
            if ($notifCount > 0) {
                $stats['notifications'] = $notifCount;
                if (!$dryRun) {
                    DB::table('notifications')
                        ->where('message', 'like', '%' . $oldCode . '%')
                        ->orWhere('link', 'like', '%' . $oldCode . '%')
                        ->orWhere('data', 'like', '%' . $oldCode . '%')
                        ->update([
                            'message' => DB::raw("REPLACE(message, '{$oldCode}', '{$newCode}')"),
                            'link' => DB::raw("REPLACE(link, '{$oldCode}', '{$newCode}')"),
                            'data' => DB::raw("REPLACE(data, '{$oldCode}', '{$newCode}')"),
                        ]);
                }
            }
        }

        // 10. Nhật ký dự án (project_activity_logs) & Nhật ký hệ thống (activity_logs)
        if (Schema::hasTable('project_activity_logs')) {
            $palCount = DB::table('project_activity_logs')
                ->where('description', 'like', '%' . $oldCode . '%')
                ->orWhere('properties', 'like', '%' . $oldCode . '%')
                ->count();
            if ($palCount > 0) {
                $stats['project_activity_logs'] = $palCount;
                if (!$dryRun) {
                    DB::table('project_activity_logs')
                        ->where('description', 'like', '%' . $oldCode . '%')
                        ->orWhere('properties', 'like', '%' . $oldCode . '%')
                        ->update([
                            'description' => DB::raw("REPLACE(description, '{$oldCode}', '{$newCode}')"),
                            'properties' => DB::raw("REPLACE(properties, '{$oldCode}', '{$newCode}')"),
                        ]);
                }
            }
        }

        if (Schema::hasTable('activity_logs')) {
            $alCount = DB::table('activity_logs')
                ->where('description', 'like', '%' . $oldCode . '%')
                ->orWhere('properties', 'like', '%' . $oldCode . '%')
                ->count();
            if ($alCount > 0) {
                $stats['activity_logs'] = $alCount;
                if (!$dryRun) {
                    DB::table('activity_logs')
                        ->where('description', 'like', '%' . $oldCode . '%')
                        ->orWhere('properties', 'like', '%' . $oldCode . '%')
                        ->update([
                            'description' => DB::raw("REPLACE(description, '{$oldCode}', '{$newCode}')"),
                            'properties' => DB::raw("REPLACE(properties, '{$oldCode}', '{$newCode}')"),
                        ]);
                }
            }
        }

        $detailStr = empty($stats)
            ? 'chỉ liên kết qua sale_id (tự động cập nhật hiển thị)'
            : implode(', ', array_map(fn ($k, $v) => "{$k}: {$v}", array_keys($stats), $stats));

        $this->line("  • #{$sale->id}: {$oldCode} -> {$newCode} ({$detailStr})");
    }
}
