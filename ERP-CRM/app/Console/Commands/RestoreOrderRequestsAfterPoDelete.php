<?php

namespace App\Console\Commands;

use App\Models\SaleOrderRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RestoreOrderRequestsAfterPoDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchase-requests:restore-after-po-delete
                            {--at=2026-10-19 09:57:12 : Thời điểm PO bị xóa (Y-m-d H:i:s) – các yêu cầu bị đẩy về "Đã gửi" đúng lúc này}
                            {--dry-run : Chỉ liệt kê, không cập nhật}
                            {--force : Không hỏi xác nhận}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Đưa các yêu cầu đặt hàng bị chuyển về "Đã gửi" do xóa PO quay lại "Đang xử lý" (hiện lại ở Gom đơn).';

    public function handle(): int
    {
        $at = (string) $this->option('at');
        $dryRun = (bool) $this->option('dry-run');

        // Yêu cầu bị bước xóa PO đẩy về "Đã gửi" đúng thời điểm này, chưa ai sửa lại sau đó
        // và không nằm trong PO nào đang hoạt động (tránh đặt trùng).
        $requests = SaleOrderRequest::with('sale:id,code')
            ->where('status', SaleOrderRequest::STATUS_SUBMITTED)
            ->where('updated_at', $at)
            ->whereNotNull('sale_id')
            ->whereDoesntHave('items.purchaseOrderItems.purchaseOrder', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->orderBy('id')
            ->get();

        if ($requests->isEmpty()) {
            $this->info("Không có yêu cầu đặt hàng nào cần khôi phục (trạng thái \"Đã gửi\", cập nhật lúc {$at}).");
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Yêu cầu', 'Đơn bán (SO)', 'Trạng thái hiện tại'],
            $requests->map(fn ($r) => [$r->id, $r->code, $r->sale?->code, $r->status])->all()
        );
        $this->info("Tìm thấy {$requests->count()} yêu cầu sẽ được đưa về \"Đang xử lý\" (Gom đơn).");

        if ($dryRun) {
            $this->warn('DRY-RUN: chưa cập nhật. Chạy lại không có --dry-run để áp dụng.');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Xác nhận cập nhật?', false)) {
            $this->warn('Đã huỷ, không thay đổi dữ liệu.');
            return self::SUCCESS;
        }

        $updated = DB::transaction(fn () => SaleOrderRequest::whereIn('id', $requests->pluck('id'))
            ->where('status', SaleOrderRequest::STATUS_SUBMITTED)
            ->update(['status' => SaleOrderRequest::STATUS_PROCESSING, 'updated_at' => now()]));

        $this->info("✅ Đã đưa {$updated} yêu cầu đặt hàng về \"Đang xử lý\". Các SO đã hiện lại ở Gom đơn.");

        return self::SUCCESS;
    }
}
