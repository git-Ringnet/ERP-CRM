<?php

namespace App\Console\Commands;

use App\Models\ProductItem;
use App\Models\ProductItemBorrowLog;
use App\Models\Ticket;
use App\Models\TicketItem;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ResetRunrateHolds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:reset-runrate-holds
                            {--dry-run : Chỉ liệt kê, không cập nhật}
                            {--force : Không hỏi xác nhận}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xoá lịch sử giữ hàng runrate (ticket "Giữ hàng", log chuyển giữ, người giữ trên thiết bị đã giữ qua ticket trong Kho runrate) để Sales giữ lại theo model + số lượng.';

    public function handle(): int
    {
        $runrateWarehouseId = Warehouse::where('code', 'WH_RUNRATE')->value('id');
        if (!$runrateWarehouseId) {
            $this->error('Chưa cấu hình Kho runrate (WH_RUNRATE).');
            return self::FAILURE;
        }

        // Chỉ thiết bị có người giữ qua thao tác giữ hàng (có log chuyển giữ). Thiết bị giữ từ ticket
        // Preload hoặc ghi người giữ lúc nhập kho không có log nên được giữ nguyên.
        $heldItemsQuery = fn () => ProductItem::where('product_items.warehouse_id', $runrateWarehouseId)
            ->where('product_items.status', ProductItem::STATUS_IN_STOCK)
            ->whereNotNull('product_items.borrower')
            ->where('product_items.borrower', '<>', '')
            ->whereExists(fn ($q) => $q->select(DB::raw(1))
                ->from('product_item_borrow_logs')
                ->whereColumn('product_item_borrow_logs.product_item_id', 'product_items.id'));

        $itemsToRelease = $heldItemsQuery()
            ->with(['product', 'import.purchaseOrder'])
            ->orderBy('product_items.product_id')
            ->orderBy('product_items.borrower')
            ->get();

        $borrowTicketIds = Ticket::where('type', 'borrow')->pluck('id');
        $ticketItemCount = TicketItem::whereIn('ticket_id', $borrowTicketIds)->count();
        $logCount = ProductItemBorrowLog::count();

        if ($itemsToRelease->isNotEmpty()) {
            $this->table(['Người đang giữ', 'Mã SP', 'Số lượng'], $itemsToRelease
                ->groupBy(fn ($i) => $i->borrower . '|' . $i->product?->code)
                ->map(fn ($g) => [$g->first()->borrower, $g->first()->product?->code, $g->sum('quantity')])
                ->values()
                ->all());
        }

        $this->info("Thiết bị đang có người giữ trong Kho runrate: {$itemsToRelease->sum('quantity')} (sẽ trả về kho, chưa ai giữ)");
        $this->info("Ticket \"Giữ hàng\": {$borrowTicketIds->count()} phiếu / {$ticketItemCount} dòng (sẽ xoá)");
        $this->info("Log chuyển giữ hàng: {$logCount} dòng (sẽ xoá)");
        $this->line('Thiết bị giữ từ ticket Preload / ghi người giữ lúc nhập kho được giữ nguyên.');

        if ($itemsToRelease->isEmpty() && $borrowTicketIds->isEmpty() && $logCount === 0) {
            $this->info('Không có dữ liệu giữ hàng cần xoá.');
            return self::SUCCESS;
        }

        $csvPath = $this->writeReleasedItemsCsv($itemsToRelease, $this->option('dry-run'));
        $this->info('Danh sách chi tiết từng S/N: ' . Storage::path($csvPath));

        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN: chưa cập nhật. Chạy lại không có --dry-run để áp dụng.');
            return self::SUCCESS;
        }
        if (!$this->option('force') && !$this->confirm('Xác nhận xoá toàn bộ lịch sử giữ hàng runrate? Hãy backup database trước khi chạy.', false)) {
            $this->warn('Đã huỷ, không thay đổi dữ liệu.');
            return self::SUCCESS;
        }

        $releasedByProduct = $itemsToRelease->groupBy('product_id')->map(fn ($g) => (int) $g->sum('quantity'));

        try {
            $comparison = DB::transaction(function () use ($runrateWarehouseId, $itemsToRelease, $releasedByProduct, $borrowTicketIds) {
                $before = $this->stockSnapshot($runrateWarehouseId);

                ProductItem::whereIn('id', $itemsToRelease->pluck('id'))->update(['borrower' => null]);
                ProductItemBorrowLog::query()->delete();
                TicketItem::whereIn('ticket_id', $borrowTicketIds)->delete();
                Ticket::whereIn('id', $borrowTicketIds)->delete();

                $after = $this->stockSnapshot($runrateWarehouseId);

                // Đối chiếu từng mã: tổng tồn không đổi; số đang giữ giảm đúng bằng số đã trả về kho
                $rows = [];
                $mismatches = [];
                foreach ($before->keys()->merge($after->keys())->unique() as $productId) {
                    $b = $before->get($productId, ['code' => null, 'total' => 0, 'held' => 0]);
                    $a = $after->get($productId, ['code' => null, 'total' => 0, 'held' => 0]);
                    $released = $releasedByProduct->get($productId, 0);
                    $ok = $a['total'] === $b['total'] && $a['held'] === $b['held'] - $released;
                    if (!$ok) {
                        $mismatches[] = $b['code'] ?? $a['code'] ?? "ID {$productId}";
                    }
                    if ($released > 0 || !$ok) {
                        $rows[] = [
                            $b['code'] ?? $a['code'],
                            $b['total'], $a['total'],
                            $b['total'] - $b['held'], $a['total'] - $a['held'],
                            $released,
                            $ok ? 'Khớp' : 'LỆCH',
                        ];
                    }
                }

                if (!empty($mismatches)) {
                    throw new \RuntimeException('Số liệu sau khi xoá không khớp ở mã: ' . implode(', ', $mismatches) . '. Đã huỷ toàn bộ, không thay đổi dữ liệu.');
                }

                return $rows;
            });
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->table(['Mã SP', 'Tổng tồn trước', 'Tổng tồn sau', 'Khả dụng trước', 'Khả dụng sau', 'Trả về kho', 'Kiểm tra'], $comparison);
        $this->info('✅ Đã xoá lịch sử giữ hàng runrate. Tổng tồn từng mã không đổi, số khả dụng tăng đúng bằng số đã trả về kho.');
        return self::SUCCESS;
    }

    /**
     * Tồn trong Kho runrate theo từng mã: tổng số lượng và số đang có người giữ.
     *
     * @return Collection<int, array{code:?string, total:int, held:int}>
     */
    private function stockSnapshot(int $warehouseId): Collection
    {
        return ProductItem::where('product_items.warehouse_id', $warehouseId)
            ->where('product_items.status', ProductItem::STATUS_IN_STOCK)
            ->join('products', 'products.id', '=', 'product_items.product_id')
            ->groupBy('product_items.product_id', 'products.code')
            ->select(
                'product_items.product_id',
                'products.code',
                DB::raw('SUM(product_items.quantity) as total'),
                DB::raw("SUM(CASE WHEN product_items.borrower IS NOT NULL AND product_items.borrower <> '' THEN product_items.quantity ELSE 0 END) as held")
            )
            ->lockForUpdate()
            ->get()
            ->mapWithKeys(fn ($r) => [$r->product_id => ['code' => $r->code, 'total' => (int) $r->total, 'held' => (int) $r->held]]);
    }

    /**
     * Lưu danh sách từng S/N sẽ trả về kho (kèm người đang giữ, PO) để đối chiếu và Sales giữ lại.
     */
    private function writeReleasedItemsCsv(Collection $items, bool $dryRun): string
    {
        $path = 'holds-reset/' . ($dryRun ? 'dry-run-' : 'reset-') . now()->format('Ymd-His') . '.csv';
        $lines = ["\u{FEFF}Mã SP,Tên SP,Serial,PO,Người đang giữ,Số lượng"];
        foreach ($items as $item) {
            $lines[] = collect([
                $item->product?->code,
                $item->product?->name,
                $item->sku,
                $item->purchase_order_code,
                $item->borrower,
                $item->quantity,
            ])->map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"')->implode(',');
        }
        Storage::put($path, implode("\n", $lines));

        return $path;
    }
}
