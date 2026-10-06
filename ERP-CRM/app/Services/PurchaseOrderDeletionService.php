<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Import;
use App\Models\ImportItem;
use App\Models\Export;
use App\Models\ExportItem;
use App\Models\ProductItem;
use App\Models\Inventory;
use App\Models\InvoiceRequest;
use App\Models\SupplierPaymentHistory;
use App\Models\ShippingAllocation;
use App\Models\ShippingAllocationItem;
use App\Models\ApprovalHistory;
use App\Models\WarehouseJournalEntry;
use App\Models\SaleOrderRequest;
use App\Models\SaleOrderRequestItem;
use App\Models\FinancialTransaction;
use App\Models\SalesRevenue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PurchaseOrderDeletionService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Cascade delete a purchase order and all its associated data:
     * Nhập kho (Imports), Tồn kho (ProductItems & Inventory resync),
     * Xuất hóa đơn (Invoice Requests), Xuất kho (Exports).
     *
     * @param PurchaseOrder $purchaseOrder
     * @return void
     * @throws \Exception
     */
    public function deletePurchaseOrderCascade(PurchaseOrder $purchaseOrder): void
    {
        DB::beginTransaction();

        try {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            }

            $affectedProductIds = collect();
            $affectedWarehouseIds = collect();

            // 0. Thu thập product_id từ chính các items trong PO
            foreach ($purchaseOrder->items as $poItem) {
                if ($poItem->product_id) {
                    $affectedProductIds->push($poItem->product_id);
                }
            }

            // -------------------------------------------------------------
            // 1. TÌM TẤT CẢ PHIẾU NHẬP KHO (IMPORTS) LIÊN QUAN ĐẾN PO NÀY
            // -------------------------------------------------------------
            $imports = Import::where('reference_type', 'purchase_order')
                ->where('reference_id', $purchaseOrder->id)
                ->get();

            $codeImports = Import::where('po_code', $purchaseOrder->code)->get();
            $allImports = $imports->merge($codeImports)->unique('id');
            $importIds = $allImports->pluck('id')->toArray();

            foreach ($allImports as $imp) {
                foreach ($imp->items as $iItem) {
                    if ($iItem->product_id) {
                        $affectedProductIds->push($iItem->product_id);
                    }
                    if ($iItem->warehouse_id) {
                        $affectedWarehouseIds->push($iItem->warehouse_id);
                    }
                }
                if ($imp->warehouse_id) {
                    $affectedWarehouseIds->push($imp->warehouse_id);
                }
            }

            // -------------------------------------------------------------
            // 2. TÌM CÁC PRODUCT ITEMS (SERIAL / TỒN KHO) PHÁT SINH TỪ PO
            // -------------------------------------------------------------
            $poItemIds = $purchaseOrder->items->pluck('id')->toArray();
            $poProductItems = collect();

            if (!empty($importIds) || !empty($poItemIds)) {
                $poProductItems = ProductItem::where(function ($q) use ($importIds, $poItemIds) {
                    if (!empty($importIds)) {
                        $q->whereIn('import_id', $importIds);
                    }
                    if (!empty($poItemIds)) {
                        $q->orWhere(function ($subQ) use ($poItemIds) {
                            foreach ($poItemIds as $poiId) {
                                $subQ->orWhere('comments', 'like', "%[POItem:{$poiId}]%");
                            }
                        });
                    }
                })->get();
            }

            foreach ($poProductItems as $pi) {
                if ($pi->product_id) {
                    $affectedProductIds->push($pi->product_id);
                }
                if ($pi->warehouse_id) {
                    $affectedWarehouseIds->push($pi->warehouse_id);
                }
            }

            // -------------------------------------------------------------
            // 3. XỬ LÝ PHIẾU XUẤT KHO (EXPORTS) VÀ HÓA ĐƠN LIÊN QUAN
            // -------------------------------------------------------------
            $exportIdsFromItems = $poProductItems->whereNotNull('export_id')->pluck('export_id')->unique()->toArray();
            $directExportIds = Export::where('reference_type', 'purchase_order')
                ->where('reference_id', $purchaseOrder->id)
                ->pluck('id')
                ->toArray();
            $allExportIds = array_values(array_unique(array_merge($exportIdsFromItems, $directExportIds)));

            if (!empty($allExportIds)) {
                $exports = Export::whereIn('id', $allExportIds)->get();

                foreach ($exports as $export) {
                    foreach ($export->items as $eItem) {
                        if ($eItem->product_id) {
                            $affectedProductIds->push($eItem->product_id);
                        }
                        if ($eItem->warehouse_id) {
                            $affectedWarehouseIds->push($eItem->warehouse_id);
                        }
                    }
                    if ($export->warehouse_id) {
                        $affectedWarehouseIds->push($export->warehouse_id);
                    }

                    // 3.1. Xóa yêu cầu xuất hóa đơn (InvoiceRequest) liên kết với phiếu xuất
                    $invoiceRequests = InvoiceRequest::where('export_id', $export->id)->get();
                    foreach ($invoiceRequests as $ir) {
                        $this->deleteFile($ir->draft_path);
                        $this->deleteFile($ir->official_path);
                        $this->deleteFile($ir->delivery_note_path);

                        if (Schema::hasTable('invoice_request_revisions')) {
                            DB::table('invoice_request_revisions')->where('invoice_request_id', $ir->id)->delete();
                        }

                        $ir->delete();
                    }

                    // 3.2. Nếu có ProductItem khác không thuộc PO này trong phiếu xuất, trả về trạng thái in_stock
                    ProductItem::where('export_id', $export->id)
                        ->whereNotIn('id', $poProductItems->pluck('id')->toArray())
                        ->update([
                            'status' => ProductItem::STATUS_IN_STOCK,
                            'export_id' => null,
                        ]);

                    // 3.3. Xóa bút toán sổ kho của phiếu xuất
                    if (Schema::hasTable('warehouse_journal_entries')) {
                        WarehouseJournalEntry::where('reference_type', 'export')
                            ->where('reference_id', $export->id)
                            ->delete();
                    }

                    // 3.4. Xóa chi tiết xuất kho và phiếu xuất
                    $export->items()->delete();
                    $export->delete();
                }
            }

            // -------------------------------------------------------------
            // 4. XÓA CÁC PRODUCT ITEMS TẠO TỪ PO VÀ NHẬP KHO
            // -------------------------------------------------------------
            if ($poProductItems->isNotEmpty()) {
                ProductItem::whereIn('id', $poProductItems->pluck('id')->toArray())->delete();
            }
            if (!empty($importIds)) {
                ProductItem::whereIn('import_id', $importIds)->delete();
            }

            // -------------------------------------------------------------
            // 5. XÓA CÁC PHIẾU NHẬP KHO (IMPORTS) VÀ BÚT TOÁN KHO
            // -------------------------------------------------------------
            foreach ($allImports as $imp) {
                if (Schema::hasTable('warehouse_journal_entries')) {
                    WarehouseJournalEntry::where('reference_type', 'import')
                        ->where('reference_id', $imp->id)
                        ->delete();
                }

                $imp->items()->delete();
                $imp->delete();
            }

            // -------------------------------------------------------------
            // 6. ĐỒNG BỘ LẠI TỒN KHO CHO TẤT CẢ SẢN PHẨM BỊ ẢNH HƯỞNG
            // -------------------------------------------------------------
            $uniqueProductIds = $affectedProductIds->filter()->unique()->values();
            foreach ($uniqueProductIds as $pId) {
                $this->inventoryService->resyncStockFromItems($pId);
            }

            // -------------------------------------------------------------
            // 7. XÓA LỊCH SỬ THANH TOÁN NCC VÀ GIAO DỊCH TÀI CHÍNH
            // -------------------------------------------------------------
            SupplierPaymentHistory::where('purchase_order_id', $purchaseOrder->id)->delete();
            FinancialTransaction::where('reference_number', $purchaseOrder->code)->delete();

            // -------------------------------------------------------------
            // 8. XÓA PHÂN BỔ VẬN CHUYỂN (SHIPPING ALLOCATIONS)
            // -------------------------------------------------------------
            $saList = ShippingAllocation::where('purchase_order_id', $purchaseOrder->id)->get();
            foreach ($saList as $sa) {
                ShippingAllocationItem::where('shipping_allocation_id', $sa->id)->delete();
                $sa->delete();
            }

            // -------------------------------------------------------------
            // 9. XÓA LỊCH SỬ DUYỆT PO
            // -------------------------------------------------------------
            ApprovalHistory::where('document_type', 'purchase_order')
                ->where('document_id', $purchaseOrder->id)
                ->delete();

            // -------------------------------------------------------------
            // 10. GỠ LIÊN KẾT DOANH THU (NẾU CÓ)
            // -------------------------------------------------------------
            if (Schema::hasTable('sales_revenues')) {
                SalesRevenue::where('purchase_order_id', $purchaseOrder->id)->update(['purchase_order_id' => null]);
            }

            // -------------------------------------------------------------
            // 11. XỬ LÝ QUAN HỆ VỚI SALE ORDER REQUEST (PR) VÀ FILE ĐÍNH KÈM
            // -------------------------------------------------------------
            $prIdsToCheck = [];
            foreach ($purchaseOrder->items as $poItem) {
                if ($poItem->saleOrderRequestItem && $poItem->saleOrderRequestItem->sale_order_request_id) {
                    $prIdsToCheck[] = $poItem->saleOrderRequestItem->sale_order_request_id;
                }

                // Xóa file license vật lý nếu có
                if (!empty($poItem->license_file)) {
                    $files = is_array($poItem->license_file) ? $poItem->license_file : [$poItem->license_file];
                    foreach ($files as $f) {
                        $this->deleteFile($f);
                    }
                }
            }
            $prIdsToCheck = array_unique($prIdsToCheck);

            // Xóa items của PO và PO chính
            $purchaseOrder->items()->delete();
            $purchaseOrder->delete();

            // Cập nhật lại trạng thái các PR nếu không còn PO nào khác đang đặt
            foreach ($prIdsToCheck as $prId) {
                $pr = SaleOrderRequest::find($prId);
                if ($pr) {
                    $remainingPoItemsCount = PurchaseOrderItem::whereHas('saleOrderRequestItem', function ($q) use ($prId) {
                        $q->where('sale_order_request_id', $prId);
                    })->count();

                    if ($remainingPoItemsCount === 0 && in_array($pr->status, [SaleOrderRequest::STATUS_PROCESSING, SaleOrderRequest::STATUS_COMPLETED])) {
                        $pr->update(['status' => SaleOrderRequest::STATUS_SUBMITTED]);
                    }
                }
            }

            // Dọn dẹp thư mục storage của PO nếu có
            Storage::disk('public')->deleteDirectory('purchase-orders/' . $purchaseOrder->id);
            Storage::disk('public')->deleteDirectory('po-licenses/' . $purchaseOrder->id);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("PurchaseOrderDeletionService failed for PO #{$purchaseOrder->id} ({$purchaseOrder->code}): " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        } finally {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }

    /**
     * Xóa file vật lý khỏi public disk an toàn.
     */
    protected function deleteFile(?string $path): void
    {
        if (empty($path)) {
            return;
        }
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
