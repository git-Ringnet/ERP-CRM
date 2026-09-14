<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\ImportItem;
use App\Models\Import;
use App\Models\SaleOrderRequestItem;
use App\Models\PurchaseOrderItem;
use App\Services\InventoryService;
use App\Services\PurchaseImportSyncService;
use Illuminate\Support\Facades\DB;

class FixLicenseWarehouses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:fix-license-warehouses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chuyển toàn bộ các mặt hàng Coterm và License về đúng Kho License (WH_LICENSE) và đồng bộ lại tồn kho';

    /**
     * Execute the console command.
     */
    public function handle(InventoryService $inventoryService, PurchaseImportSyncService $syncService): int
    {
        $this->newLine();
        $this->info('================================================================');
        $this->info('🔄 BẮT ĐẦU CẬP NHẬT KHO CHO HÀNG COTERM VÀ LICENSE...');
        $this->info('================================================================');

        $licenseWarehouse = Warehouse::where('code', 'WH_LICENSE')->first()
            ?: Warehouse::where('name', 'like', '%license%')->first();

        if (!$licenseWarehouse) {
            $this->error('❌ Không tìm thấy Kho License (WH_LICENSE) trong hệ thống.');
            return Command::FAILURE;
        }

        $this->info("✅ Kho License: [ID: {$licenseWarehouse->id}] {$licenseWarehouse->name} ({$licenseWarehouse->code})");

        DB::beginTransaction();

        try {
            $affectedProductIds = [];

            // 1. Cập nhật Type = 'License' cho các SaleOrderRequestItem là Coterm / License
            $this->line("\n1. Đang kiểm tra và cập nhật Yêu cầu đặt hàng (SaleOrderRequestItem)...");
            $sorItems = SaleOrderRequestItem::all();
            $sorCount = 0;
            foreach ($sorItems as $sorItem) {
                $pn = strtoupper(trim((string)$sorItem->part_number));
                $pName = strtoupper(trim((string)($sorItem->product?->name ?? '')));
                $pCat = strtoupper(trim((string)($sorItem->product?->category?->name ?? '')));

                $isLic = str_contains($pn, 'COTERM')
                    || str_contains($pn, 'CO-TERM')
                    || str_contains($pName, 'COTERM')
                    || str_contains($pName, 'CO-TERM')
                    || str_contains($pName, 'LICENSE')
                    || str_contains($pName, 'BẢN QUYỀN')
                    || str_contains($pName, 'GIA HẠN')
                    || str_starts_with($pn, 'FC-')
                    || str_starts_with($pn, 'LIC-')
                    || str_contains($pCat, 'LICENSE');

                if ($isLic && $sorItem->type !== 'License') {
                    $sorItem->update(['type' => 'License']);
                    $sorCount++;
                }
            }
            $this->info("   -> Đã cập nhật {$sorCount} Yêu cầu đặt hàng sang Type = 'License'.");

            // 2. Tìm tất cả ProductItem là Coterm / License đang ở kho khác WH_LICENSE
            $this->line("\n2. Đang kiểm tra và chuyển ProductItem về Kho License...");
            $productItems = ProductItem::all();
            $piCount = 0;

            foreach ($productItems as $pi) {
                $product = $pi->product;
                $pCode = strtoupper(trim((string)($product?->code ?? '')));
                $pName = strtoupper(trim((string)($product?->name ?? '')));
                $pCat = strtoupper(trim((string)($product?->category?->name ?? '')));
                $comments = strtoupper(trim((string)($pi->comments ?? '')));
                $sku = strtoupper(trim((string)($pi->sku ?? '')));

                $isLic = str_contains($pCode, 'COTERM')
                    || str_contains($pCode, 'CO-TERM')
                    || str_contains($pName, 'COTERM')
                    || str_contains($pName, 'CO-TERM')
                    || str_contains($pName, 'LICENSE')
                    || str_contains($pName, 'BẢN QUYỀN')
                    || str_contains($pName, 'GIA HẠN')
                    || str_starts_with($pCode, 'FC-')
                    || str_starts_with($pCode, 'LIC-')
                    || str_contains($pCat, 'LICENSE')
                    || str_contains($comments, 'COTERM')
                    || str_contains($comments, 'CO-TERM')
                    || ($product?->type ?? '') === 'license'
                    || ($product?->type ?? '') === 'service';

                // Also check if linked to import item from PO that has license type or coterm
                if (!$isLic && $pi->import_id) {
                    $importItem = ImportItem::where('import_id', $pi->import_id)
                        ->where('product_id', $pi->product_id)
                        ->first();
                    if ($importItem && (str_contains(strtoupper($importItem->comments ?? ''), 'COTERM') || str_contains(strtoupper($importItem->comments ?? ''), 'CO-TERM'))) {
                        $isLic = true;
                    }
                }

                if ($isLic) {
                    if ($pi->product_id) {
                        $affectedProductIds[$pi->product_id] = true;
                    }

                    if ($pi->warehouse_id !== $licenseWarehouse->id) {
                        $pi->update(['warehouse_id' => $licenseWarehouse->id]);
                        $piCount++;
                    }
                }
            }
            $this->info("   -> Đã chuyển {$piCount} ProductItem về Kho License.");

            // 3. Cập nhật ImportItem và Import
            $this->line("\n3. Đang kiểm tra và cập nhật các Phiếu nhập kho (Import & ImportItem)...");
            $importItems = ImportItem::all();
            $iiCount = 0;

            foreach ($importItems as $ii) {
                $product = $ii->product;
                $pCode = strtoupper(trim((string)($product?->code ?? '')));
                $pName = strtoupper(trim((string)($product?->name ?? '')));
                $pCat = strtoupper(trim((string)($product?->category?->name ?? '')));
                $comments = strtoupper(trim((string)($ii->comments ?? '')));

                $isLic = str_contains($pCode, 'COTERM')
                    || str_contains($pCode, 'CO-TERM')
                    || str_contains($pName, 'COTERM')
                    || str_contains($pName, 'CO-TERM')
                    || str_contains($pName, 'LICENSE')
                    || str_contains($pName, 'BẢN QUYỀN')
                    || str_contains($pName, 'GIA HẠN')
                    || str_starts_with($pCode, 'FC-')
                    || str_starts_with($pCode, 'LIC-')
                    || str_contains($pCat, 'LICENSE')
                    || str_contains($comments, 'COTERM')
                    || str_contains($comments, 'CO-TERM')
                    || ($product?->type ?? '') === 'license'
                    || ($product?->type ?? '') === 'service';

                if ($isLic) {
                    if ($ii->product_id) {
                        $affectedProductIds[$ii->product_id] = true;
                    }

                    if ($ii->warehouse_id !== $licenseWarehouse->id) {
                        $ii->update(['warehouse_id' => $licenseWarehouse->id]);
                        $iiCount++;
                    }
                }
            }
            $this->info("   -> Đã cập nhật {$iiCount} dòng phiếu nhập (ImportItem) sang Kho License.");

            // Cập nhật warehouse_id cho Import nếu tất cả items của nó thuộc kho license
            $imports = Import::all();
            $impCount = 0;
            foreach ($imports as $imp) {
                $whIds = $imp->items()->pluck('warehouse_id')->filter()->unique();
                if ($whIds->count() === 1 && $whIds->first() === $licenseWarehouse->id) {
                    if ($imp->warehouse_id !== $licenseWarehouse->id) {
                        $imp->update(['warehouse_id' => $licenseWarehouse->id]);
                        $impCount++;
                    }
                }
            }
            $this->info("   -> Đã cập nhật {$impCount} phiếu nhập kho (Import) sang Kho License.");

            // 4. Đồng bộ lại Tồn kho (Inventory) cho tất cả sản phẩm bị ảnh hưởng
            $this->line("\n4. Đang đồng bộ lại bảng Tồn kho (Inventory)...");
            $uniqueProductIds = array_keys($affectedProductIds);
            foreach ($uniqueProductIds as $productId) {
                $inventoryService->resyncStockFromItems($productId);
            }
            $this->info("   -> Đã đồng bộ lại tồn kho cho " . count($uniqueProductIds) . " sản phẩm.");

            DB::commit();

            $this->newLine();
            $this->info('================================================================');
            $this->info('🎉 HOÀN TẤT CẬP NHẬT KHO LICENSE THÀNH CÔNG!');
            $this->info('================================================================');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Lỗi khi cập nhật kho license: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
