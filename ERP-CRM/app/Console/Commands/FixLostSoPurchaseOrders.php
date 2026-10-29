<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\ProductItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SaleOrderRequest;
use App\Models\SaleOrderRequestItem;
use App\Models\User;
use App\Services\PurchaseImportSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Sửa PO bị mất SO do xóa yêu cầu đặt hàng / xóa SO sau khi PO đã đặt (29/10/2026).
 * Nguyên tắc: giữ yêu cầu đặt hàng HIỆN TẠI của SO, gắn dòng PO cũ vào dòng yêu cầu hiện tại cùng
 * S/N thiết bị (yêu cầu cũ để nguyên đã xóa), hủy PO đặt trùng.
 *
 * SO-20261020-0029:
 *   - Hủy PO trùng 0002/2026/TH-VIETNET (license FG200ETK19913719 đã về theo HCM2610079).
 *   - HCM2610079 (đã về, FG200ETK19913719)       → SOR2610290007 (Hoàn thành)
 *   - 0001/2026/TH-Vnet (đã hủy, FG200ETK18920847) → SOR2610290006 (vẫn ở Gom đơn để đặt với Fortinet);
 *     bỏ "Chờ Sales chỉnh sửa" của PO này.
 * SO-20261028-0003 (tạo lại từ SO-20261015-0001 đã xóa):
 *   - HCM2610128 (đã về, FG100FTK23085196)       → SOR2610290005 (Hoàn thành, không bị gom đặt lại)
 *   - 9869/2026/TH-FOR (đã về, FGT60FTK22099UDL) bị xóa hẳn cùng SO cũ: dựng lại từ nhật ký, gắn
 *     SOR2610290004, nhập lại Kho license (công nợ Fortinet + tồn kho trở lại như trước khi xóa).
 *
 * Kiểm tra lại dữ liệu trước khi sửa; dữ liệu đã đổi thì dừng và hoàn tác toàn bộ.
 */
class FixLostSoPurchaseOrders extends Command
{
    protected $signature = 'purchase-orders:fix-lost-so-links
                            {--dry-run : Chạy thử trong transaction rồi hoàn tác, chỉ in kết quả}';

    protected $description = 'Gắn PO mất SO (HCM2610079, 0001/2026/TH-Vnet, HCM2610128) vào yêu cầu đặt hàng hiện tại của SO, hủy PO đặt trùng, khôi phục PO 9869/2026/TH-FOR bị xóa.';

    private const MARKER = '[Fix mất SO 29/10]';

    /** [PO, S/N thiết bị, yêu cầu đặt hàng hiện tại] */
    private const RELINKS = [
        ['HCM2610079', 'FG200ETK19913719', 'SOR2610290007'],
        ['0001/2026/TH-Vnet', 'FG200ETK18920847', 'SOR2610290006'],
        ['HCM2610128', 'FG100FTK23085196', 'SOR2610290005'],
    ];

    public function handle(PurchaseImportSyncService $syncService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        DB::beginTransaction();
        try {
            $this->cancelDuplicatePo('0002/2026/TH-VIETNET', 'SOR2610290007', 'Trùng với PO HCM2610079 (license FG200ETK19913719 đã đặt và đã về theo HCM2610079)');

            foreach (self::RELINKS as [$poCode, $serial, $requestCode]) {
                $this->relink($poCode, $serial, $requestCode);
            }

            $this->clearAwaitingRevision('0001/2026/TH-Vnet');

            $this->restoreDeletedPo9869($syncService);

            $this->newLine();
            $this->printResult();

            if ($dryRun) {
                DB::rollBack();
                $this->warn('DRY-RUN: đã hoàn tác, dữ liệu không thay đổi.');
            } else {
                DB::commit();
                $this->info('Đã cập nhật dữ liệu.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Dừng, đã hoàn tác toàn bộ: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Hủy PO đặt trùng (chưa duyệt) của yêu cầu hiện tại.
     */
    private function cancelDuplicatePo(string $poCode, string $requestCode, string $reason): void
    {
        $po = PurchaseOrder::where('code', $poCode)->first();
        if (!$po || $po->status === 'cancelled') {
            $this->line("- {$poCode}: không có hoặc đã hủy, bỏ qua");
            return;
        }
        if (!in_array($po->status, ['draft', 'pending_approval'], true)) {
            throw new \RuntimeException("{$poCode} đang ở trạng thái '{$po->status}' (đã duyệt/đặt hãng) – PO team cần xử lý tay.");
        }

        $requestItemIds = SaleOrderRequestItem::whereHas('saleOrderRequest', fn ($q) => $q->where('code', $requestCode))->pluck('id');
        if ($po->items()->whereNotIn('sale_order_request_item_id', $requestItemIds)->exists()) {
            throw new \RuntimeException("{$poCode} có dòng của yêu cầu khác {$requestCode} – cần xử lý tay.");
        }

        $po->items()->update(['status' => 'cancelled']);
        $po->update([
            'status' => 'cancelled',
            'awaiting_revision' => false,
            'revision_previous_status' => null,
            'revision_sale_order_request_ids' => null,
            'note' => trim(($po->note ? $po->note . "\n" : '') . '[' . now()->format('d/m/Y H:i') . "] Đã hủy đơn: {$reason} " . self::MARKER),
        ]);
        $this->log('cancelled', $po, "Hủy PO {$poCode}: {$reason}");
        $this->line("- Hủy PO trùng {$poCode}");
    }

    /**
     * Gắn dòng PO (đang trỏ vào yêu cầu đã xóa) sang dòng yêu cầu hiện tại cùng S/N thiết bị.
     */
    private function relink(string $poCode, string $serial, string $requestCode): void
    {
        $po = PurchaseOrder::where('code', $poCode)->first();
        $request = SaleOrderRequest::where('code', $requestCode)->first();
        $newItem = $request
            ? SaleOrderRequestItem::where('sale_order_request_id', $request->id)->where('serial_number', $serial)->first()
            : null;
        if (!$po || !$newItem) {
            throw new \RuntimeException("Không tìm thấy {$poCode} hoặc dòng {$serial} của {$requestCode}.");
        }

        $line = $po->items()->get()->first(function (PurchaseOrderItem $poItem) use ($serial) {
            return SaleOrderRequestItem::find($poItem->sale_order_request_item_id)?->serial_number === $serial;
        });
        if (!$line) {
            throw new \RuntimeException("{$poCode} không có dòng cho thiết bị {$serial}.");
        }
        if ((int) $line->sale_order_request_item_id === (int) $newItem->id) {
            $this->line("- {$poCode} đã gắn {$requestCode} từ trước, bỏ qua");
            return;
        }

        // Chỉ chuyển dòng đang trỏ vào yêu cầu đã xóa (mất SO), không đụng dòng của yêu cầu còn hiệu lực
        $oldRequest = SaleOrderRequest::withTrashed()
            ->find(SaleOrderRequestItem::find($line->sale_order_request_item_id)?->sale_order_request_id);
        if ($oldRequest && !$oldRequest->trashed()) {
            throw new \RuntimeException("Dòng {$poCode} đang gắn yêu cầu còn hiệu lực {$oldRequest->code} – cần kiểm tra tay.");
        }
        // Dòng PO còn hiệu lực: yêu cầu hiện tại không được đã đặt ở PO khác (tránh đặt trùng)
        $lineActive = $line->status !== 'cancelled' && $po->status !== 'cancelled';
        if ($lineActive && $newItem->ordered_quantity_total > 0) {
            throw new \RuntimeException("{$requestCode} ({$serial}) đã được đặt ở PO khác – cần hủy PO đó trước.");
        }

        $line->update(['sale_order_request_item_id' => $newItem->id]);
        $request->refresh()->checkAndUpdateStatus();
        $this->log('updated', $request, "Gắn dòng {$serial} của PO {$poCode} vào {$requestCode} (thay yêu cầu cũ {$oldRequest?->code} đã xóa) " . self::MARKER);
        $this->line("- {$poCode} ({$serial}): {$oldRequest?->code} (đã xóa) → {$requestCode}");

        // Ghi chú thiết bị trong kho theo SO hiện tại
        $saleCode = $request->sale?->code;
        if ($saleCode) {
            ProductItem::where('comments', 'like', "%[POItem:{$line->id}]%")->get()
                ->each(function (ProductItem $productItem) use ($saleCode) {
                    $comments = preg_replace('/\(Đơn SO-[\w-]+\)/u', "(Đơn {$saleCode})", $productItem->comments);
                    if ($comments !== $productItem->comments) {
                        $productItem->update(['comments' => $comments]);
                        $this->line("  Ghi chú kho {$productItem->sku} → {$saleCode}");
                    }
                });
        }
    }

    private function clearAwaitingRevision(string $poCode): void
    {
        $po = PurchaseOrder::where('code', $poCode)->first();
        if ($po?->awaiting_revision) {
            $po->update([
                'awaiting_revision' => false,
                'revision_previous_status' => null,
                'revision_sale_order_request_ids' => null,
            ]);
            $this->line("- {$poCode}: bỏ \"Chờ Sales chỉnh sửa\" (yêu cầu cũ đã xóa, đặt lại theo yêu cầu hiện tại)");
        }
    }

    /**
     * PO 9869/2026/TH-FOR (Fortinet, đã về) bị xóa hẳn khi admin xóa SO-20261015-0001 (28/10): dựng lại PO
     * từ nhật ký, dòng PO gắn vào yêu cầu hiện tại SOR2610290004 (FC-10-0060F cho FGT60FTK22099UDL), nhập kho
     * lại qua luồng nhận hàng của hệ thống (phiếu nhập, thiết bị Kho license, tồn kho) – không đặt mua lại.
     */
    private function restoreDeletedPo9869(PurchaseImportSyncService $syncService): void
    {
        $code = '9869/2026/TH-FOR';
        $serial = 'FGT60FTK22099UDL';

        if (PurchaseOrder::where('code', $code)->exists()) {
            $this->line("- {$code} đã có trong hệ thống, bỏ qua");
            return;
        }

        $deletedLog = ActivityLog::where('action', 'deleted')->where('description', "Xóa PurchaseOrder: {$code}")->first();
        $deletedImportLog = ActivityLog::where('action', 'deleted')->where('description', "Xóa Import: {$code}")->first();
        $poAttributes = $deletedLog?->properties['attributes'] ?? null;
        $importAttributes = $deletedImportLog?->properties['attributes'] ?? null;
        if (!$poAttributes || !$importAttributes) {
            throw new \RuntimeException("Không tìm thấy nhật ký xóa của {$code} để khôi phục.");
        }

        $request = SaleOrderRequest::where('code', 'SOR2610290004')->first();
        $requestItem = $request
            ? SaleOrderRequestItem::where('sale_order_request_id', $request->id)->where('serial_number', $serial)->first()
            : null;
        if (!$requestItem) {
            throw new \RuntimeException("Không tìm thấy dòng {$serial} của SOR2610290004.");
        }
        if ($requestItem->ordered_quantity_total > 0) {
            throw new \RuntimeException("SOR2610290004 ({$serial}) đã được đặt ở PO khác – cần hủy PO đó trước khi khôi phục {$code}.");
        }

        // Dòng PO đã xóa: 1 license, giá P&L 562,10 USD chiết khấu 45% = 309,16 (khớp tổng PO trong nhật ký)
        $saleItem = $requestItem->saleItem;
        $warehousePrice = (float) ($saleItem->usd_price ?? 0);
        $discount = (float) ($saleItem->discount_rate ?? 0);
        $unitPrice = round($warehousePrice * (1 - $discount / 100), 2);
        if (abs($unitPrice - (float) $poAttributes['total']) > 0.01) {
            throw new \RuntimeException("Giá dòng {$serial} ({$unitPrice}) không khớp tổng {$code} trong nhật ký ({$poAttributes['total']}).");
        }

        $po = new PurchaseOrder();
        $po->forceFill(collect($poAttributes)->except(PurchaseOrder::where('id', $poAttributes['id'])->exists() ? ['id'] : [])->all());
        $po->note = '[' . now()->format('d/m/Y H:i') . '] Khôi phục PO bị xóa theo đơn SO-20261015-0001 (28/10) – hàng đã về, không đặt lại ' . self::MARKER;
        $po->save();

        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'sale_order_request_item_id' => $requestItem->id,
            'product_id' => $requestItem->product_id,
            'product_name' => $requestItem->part_number,
            'ordered_quantity' => 1,
            'quantity' => 1,
            'received_quantity' => 1,
            'unit' => 'Cái',
            'unit_price' => $unitPrice,
            'warehouse_unit_price' => $warehousePrice,
            'discount_percent' => $discount,
            'total' => $unitPrice,
            'vat_percent' => 0,
            'vat_amount' => 0,
            'status' => 'received',
            'license_file' => $this->findOrphanLicenseFile($requestItem->part_number),
        ]);

        // Nhập kho lại như lúc nhận hàng (người nhập như phiếu cũ)
        $previousUser = Auth::user();
        Auth::setUser(User::findOrFail($importAttributes['employee_id']));
        try {
            $import = $syncService->createImportFromPO($po->fresh('items', 'supplier'), (int) $importAttributes['warehouse_id'], true);
        } finally {
            $previousUser ? Auth::setUser($previousUser) : Auth::forgetUser();
        }
        if (!$import) {
            throw new \RuntimeException("Không tạo lại được phiếu nhập {$code}.");
        }

        $saleCode = $request->sale?->code;
        $comment = "[POItem:{$poItem->id}] Hàng về từ PO {$code}" . ($saleCode ? " (Đơn {$saleCode})" : '');
        $import->update([
            'date' => $importAttributes['date'],
            'vat_percent' => $importAttributes['vat_percent'],
            'note' => $importAttributes['note'],
        ]);
        $import->items()->update(['comments' => $comment]);
        ProductItem::where('import_id', $import->id)->update(['comments' => $comment]);

        $request->refresh()->checkAndUpdateStatus();
        $this->log('restored', $po, "Khôi phục PO {$code} (bị xóa cùng SO-20261015-0001), gắn {$request->code} " . self::MARKER);
        $this->line("- Khôi phục {$code} → {$request->code} ({$serial}), phiếu nhập #{$import->id} vào Kho license"
            . ($poItem->license_file ? ', đã gắn lại file license cũ' : ', chưa có file license (cần upload lại)'));
    }

    /**
     * File license cũ của dòng PO đã xóa: file trong po-licenses cùng mã hàng, tải lên lúc xử lý PO
     * (26/10 10:15–10:35) và không dòng PO nào đang dùng. Chỉ nhận khi xác định được duy nhất 1 file.
     */
    private function findOrphanLicenseFile(string $partNumber): ?string
    {
        $disk = Storage::disk('public');
        if (!$disk->exists('po-licenses')) {
            return null;
        }

        $candidates = collect($disk->files('po-licenses'))
            ->filter(fn ($path) => str_contains(basename($path), "_{$partNumber}_"))
            ->filter(function ($path) use ($disk) {
                $uploadedAt = $disk->lastModified($path);
                return $uploadedAt >= strtotime('2026-10-26 10:15:00') && $uploadedAt <= strtotime('2026-10-26 10:35:00');
            })
            ->reject(fn ($path) => PurchaseOrderItem::where('license_file', 'like', '%' . str_replace('/', '\\\\/', $path) . '%')
                ->orWhere('license_file', 'like', '%' . $path . '%')->exists())
            ->values();

        return $candidates->count() === 1 ? json_encode([$candidates->first()]) : null;
    }

    private function printResult(): void
    {
        $this->info('Kết quả');

        $rows = [];
        foreach (['HCM2610079', '0001/2026/TH-Vnet', '0002/2026/TH-VIETNET', 'HCM2610128', '9869/2026/TH-FOR'] as $code) {
            $po = PurchaseOrder::where('code', $code)->with('items.saleOrderRequestItem.saleOrderRequest.sale')->first();
            if ($po) {
                $requests = $po->items->map(fn ($item) => $item->saleOrderRequestItem?->saleOrderRequest);
                $rows[] = [
                    $code,
                    $po->status . ($po->awaiting_revision ? ' (chờ chỉnh sửa)' : ''),
                    $requests->pluck('code')->filter()->unique()->implode(', ') ?: '-',
                    $requests->map(fn ($r) => $r?->sale?->code)->filter()->unique()->implode(', ') ?: '-',
                ];
            }
        }
        $this->table(['PO', 'Trạng thái', 'Yêu cầu đặt hàng', 'SO'], $rows);

        $rows = [];
        foreach (['SOR2610290004', 'SOR2610290005', 'SOR2610290006', 'SOR2610290007'] as $code) {
            $request = SaleOrderRequest::where('code', $code)->first();
            if ($request) {
                $item = $request->items()->first();
                $rows[] = [$code, $request->sale?->code ?? '-', $item?->serial_number, $request->status, $item?->ordered_quantity_total . '/' . (float) $item?->quantity];
            }
        }
        $this->table(['Yêu cầu hiện tại', 'SO', 'Thiết bị', 'Trạng thái', 'Đã đặt'], $rows);
    }

    private function log(string $action, $subject, string $description): void
    {
        ActivityLog::create([
            'user_name' => 'System',
            'action' => $action,
            'description' => $description,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
        ]);
    }
}
