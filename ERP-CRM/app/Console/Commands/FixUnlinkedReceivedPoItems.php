<?php

namespace App\Console\Commands;

use App\Models\ImportItem;
use App\Models\PurchaseOrderItem;
use App\Services\PurchaseImportSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixUnlinkedReceivedPoItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchase-orders:fix-unlinked-received-items
                            {--link=* : Chỉ định sản phẩm cho dòng PO không tự xác định được, dạng "ID_dòng_PO=MÃ_SẢN_PHẨM" (mã chưa có sẽ được tạo mới, tên lấy theo dòng PO)}
                            {--dry-run : Chỉ liệt kê, không cập nhật}
                            {--force : Không hỏi xác nhận}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Liên kết sản phẩm danh mục và bổ sung nhập kho cho các dòng PO đã nhận hàng nhưng chưa có trong kho (do dòng PO chưa liên kết sản phẩm).';

    public function handle(PurchaseImportSyncService $syncService): int
    {
        $links = [];
        foreach ((array) $this->option('link') as $pair) {
            [$poItemId, $code] = array_pad(explode('=', (string) $pair, 2), 2, '');
            if ((int) $poItemId > 0 && trim($code) !== '') {
                $links[(int) $poItemId] = strtoupper(trim($code));
            }
        }
        $items = PurchaseOrderItem::with(['purchaseOrder', 'saleOrderRequestItem.saleItem'])
            ->whereNull('product_id')
            ->where('received_quantity', '>', 0)
            ->whereHas('purchaseOrder', fn ($q) => $q->whereIn('status', ['received', 'partial_received', 'shipping', 'approved']))
            ->orderBy('purchase_order_id')
            ->get();

        if ($items->isEmpty()) {
            $this->info('Không có dòng PO đã nhận hàng nào chưa liên kết sản phẩm.');
            return self::SUCCESS;
        }

        $this->table(
            ['PO', 'Dòng PO', 'Mã/Tên hàng', 'Đã nhận', 'Serial trên PO'],
            $items->map(fn ($i) => [
                $i->purchaseOrder->code,
                $i->id,
                mb_strimwidth((string) $i->product_name, 0, 60, '…'),
                $i->received_quantity,
                count(json_decode((string) $i->serial_number, true) ?: []),
            ])->all()
        );

        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN: chưa cập nhật. Chạy lại không có --dry-run để áp dụng.');
            return self::SUCCESS;
        }
        if (!$this->option('force') && !$this->confirm('Liên kết/tạo sản phẩm và bổ sung nhập kho cho các dòng trên?', false)) {
            $this->warn('Đã huỷ, không thay đổi dữ liệu.');
            return self::SUCCESS;
        }

        $rows = [];
        foreach ($items as $item) {
            $po = $item->purchaseOrder;
            try {
                $result = DB::transaction(function () use ($item, $po, $syncService, $links) {
                    // Sản phẩm chỉ định tay qua --link (VD license coterm chỉ có mô tả, không có mã)
                    if (isset($links[$item->id])) {
                        $code = $links[$item->id];
                        $product = \App\Models\Product::where('code', $code)->first()
                            ?? \App\Models\Product::create([
                                'code' => mb_substr($code, 0, 191),
                                'name' => mb_substr((string) ($item->product_name ?: $code), 0, 255),
                                'unit' => $item->unit ?: 'Cái',
                                'category' => 'Z',
                            ]);
                        $item->update(['product_id' => $product->id]);
                    }

                    $productId = $syncService->resolveProductIdForPoItem($item);
                    if (!$productId) {
                        return 'KHÔNG xác định được sản phẩm (tên không phải mã hàng) – cần liên kết tay';
                    }

                    $alreadyImported = ImportItem::whereHas('import', fn ($q) => $q->where('reference_type', 'purchase_order')->where('reference_id', $po->id))
                        ->where('comments', 'like', "%[POItem:{$item->id}]%")
                        ->exists();
                    if ($alreadyImported) {
                        return "Đã liên kết sản phẩm #{$productId} (phiếu nhập đã có dòng này)";
                    }

                    $po->load('items');
                    $import = $syncService->createPartialImportFromPO($po, [$item->id => (float) $item->received_quantity], null, true);

                    return $import
                        ? "Đã liên kết sản phẩm #{$productId}, nhập kho {$item->received_quantity} vào phiếu {$import->code}"
                        : "Đã liên kết sản phẩm #{$productId} (dòng dịch vụ/không tạo nhập kho)";
                });
            } catch (\Throwable $e) {
                $result = 'Lỗi: ' . $e->getMessage();
            }
            $rows[] = [$po->code, $item->id, $result];
        }

        $this->table(['PO', 'Dòng PO', 'Kết quả'], $rows);

        return self::SUCCESS;
    }
}
