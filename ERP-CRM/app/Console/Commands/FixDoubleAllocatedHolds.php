<?php

namespace App\Console\Commands;

use App\Models\ProductItem;
use App\Models\ProductItemBorrowLog;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDoubleAllocatedHolds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:fix-double-allocated-holds
                            {--dry-run : Chỉ liệt kê, không cập nhật}
                            {--force : Không hỏi xác nhận}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bù thiết bị cho người bị lấy mất do lỗi duyệt ticket "Giữ từ kho" gán trùng serial đang có người giữ.';

    private const MARKER = '[Bù gán trùng log#%d]';

    public function handle(): int
    {
        $runrateWarehouseId = Warehouse::where('code', 'WH_RUNRATE')->value('id');
        if (!$runrateWarehouseId) {
            $this->error('Chưa cấu hình Kho runrate (WH_RUNRATE).');
            return self::FAILURE;
        }

        // Lần chuyển do lỗi: ticket "Giữ từ kho" nhưng lấy thiết bị đang có người giữ
        $wrongTransfers = ProductItemBorrowLog::query()
            ->join('tickets', 'tickets.id', '=', 'product_item_borrow_logs.ticket_id')
            ->where('tickets.source', 'warehouse')
            ->whereNotNull('product_item_borrow_logs.from_user_id')
            ->select('product_item_borrow_logs.*', 'tickets.code as ticket_code')
            ->orderBy('product_item_borrow_logs.id')
            ->with(['product_item.product'])
            ->get();

        $rows = [];
        $plans = [];
        $reservedIds = [];

        foreach ($wrongTransfers as $log) {
            $marker = sprintf(self::MARKER, $log->id);
            $victim = User::find($log->from_user_id);
            $productItem = $log->product_item;
            $product = $productItem?->product;

            $alreadyFixed = ProductItemBorrowLog::where('note', 'like', '%' . $marker . '%')->exists();
            $replacement = null;
            if (!$alreadyFixed && $product && $victim) {
                $replacement = ProductItem::where('product_id', $product->id)
                    ->where('warehouse_id', $runrateWarehouseId)
                    ->where('status', ProductItem::STATUS_IN_STOCK)
                    ->where(fn ($q) => $q->whereNull('borrower')->orWhere('borrower', ''))
                    ->whereNotIn('id', $reservedIds)
                    // Serial có thể bị ghi trùng ở mã sản phẩm khác: bỏ qua nếu bản trùng đang có
                    // người giữ hoặc đã xuất, để không bù 1 thiết bị thực tế đang đứng tên người khác
                    ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                        ->from('product_items as dup')
                        ->whereColumn('dup.sku', 'product_items.sku')
                        ->whereColumn('dup.id', '<>', 'product_items.id')
                        ->where(fn ($d) => $d->where('dup.status', '<>', ProductItem::STATUS_IN_STOCK)
                            ->orWhere(fn ($b) => $b->whereNotNull('dup.borrower')->where('dup.borrower', '<>', ''))))
                    ->orderBy('id')
                    ->first();
                if ($replacement) {
                    $reservedIds[] = $replacement->id;
                    $plans[] = compact('log', 'victim', 'replacement', 'marker', 'productItem');
                }
            }

            $rows[] = [
                $log->ticket_code,
                $product?->code,
                $productItem?->sku,
                $victim?->name ?? "#{$log->from_user_id}",
                $alreadyFixed ? 'Đã bù trước đó' : ($replacement ? "Bù serial {$replacement->sku}" : 'KHÔNG còn thiết bị trống để bù'),
            ];
        }

        if (empty($rows)) {
            $this->info('Không có lần giữ hàng nào bị gán trùng.');
            return self::SUCCESS;
        }

        $this->table(['Ticket gây lỗi', 'Mã SP', 'Serial bị lấy', 'Người bị mất', 'Xử lý'], $rows);
        $this->info('Sẽ bù ' . count($plans) . ' thiết bị.');

        if (empty($plans)) {
            return self::SUCCESS;
        }
        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN: chưa cập nhật. Chạy lại không có --dry-run để áp dụng.');
            return self::SUCCESS;
        }
        if (!$this->option('force') && !$this->confirm('Xác nhận bù thiết bị?', false)) {
            $this->warn('Đã huỷ, không thay đổi dữ liệu.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($plans) {
            foreach ($plans as $plan) {
                $plan['replacement']->update(['borrower' => $plan['victim']->name]);
                ProductItemBorrowLog::create([
                    'product_item_id' => $plan['replacement']->id,
                    'from_user_id' => null,
                    'to_user_id' => $plan['victim']->id,
                    'ticket_id' => null,
                    'note' => mb_substr($plan['marker'] . " Bù cho serial {$plan['productItem']?->sku} bị gán trùng bởi ticket {$plan['log']->ticket_code}", 0, 255),
                ]);
            }
        });

        $this->info('✅ Đã bù ' . count($plans) . ' thiết bị.');
        return self::SUCCESS;
    }
}
