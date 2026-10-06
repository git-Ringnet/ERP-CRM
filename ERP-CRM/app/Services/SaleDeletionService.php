<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleExpense;
use App\Models\SaleAttachment;
use App\Models\PnlApprovalAttachment;
use App\Models\SaleOrderRequest;
use App\Models\SaleOrderRequestItem;
use App\Models\SaleOrderRequestAttachment;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierPaymentHistory;
use App\Models\ShippingAllocation;
use App\Models\ShippingAllocationItem;
use App\Models\Import;
use App\Models\ImportItem;
use App\Models\Export;
use App\Models\ExportItem;
use App\Models\ProductItem;
use App\Models\Inventory;
use App\Models\InvoiceRequest;
use App\Models\PaymentHistory;
use App\Models\SalePaymentSchedule;
use App\Models\PaymentApprovalLog;
use App\Models\FinancialTransaction;
use App\Models\SalesRevenue;
use App\Models\ApprovalHistory;
use App\Models\Quotation;
use App\Models\WarehouseJournalEntry;
use App\Models\TechnicalTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SaleDeletionService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Cascade delete a sale and all its associated data.
     *
     * @param Sale $sale
     * @param bool $isAdmin
     * @return void
     * @throws \Exception
     */
    public function deleteSaleCascade(Sale $sale, bool $isAdmin = true): void
    {
        DB::beginTransaction();
        try {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            }

            $affectedProductIds = collect();
            $affectedWarehouseIds = collect();

            // 0. Lưu danh sách sản phẩm từ đơn bán để đồng bộ tồn kho sau khi xóa
            foreach ($sale->items as $sItem) {
                if ($sItem->product_id) {
                    $affectedProductIds->push($sItem->product_id);
                }
            }

            // -------------------------------------------------------------
            // 1. XỬ LÝ PHIẾU XUẤT KHO (EXPORTS) VÀ HOÀN TỒN KHO / SERIAL
            // -------------------------------------------------------------
            $exports = Export::where('reference_type', 'sale')
                ->where('reference_id', $sale->id)
                ->get();

            foreach ($exports as $export) {
                // Thu thập danh sách sản phẩm và kho bị ảnh hưởng
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

                // Trả các ProductItem đã xuất về trạng thái 'in_stock'
                ProductItem::where('export_id', $export->id)->update([
                    'status' => ProductItem::STATUS_IN_STOCK,
                    'export_id' => null,
                ]);

                // Xóa yêu cầu xuất hóa đơn liên kết với phiếu xuất
                InvoiceRequest::where('export_id', $export->id)->delete();

                // Xóa bút toán kho của phiếu xuất
                if (Schema::hasTable('warehouse_journal_entries')) {
                    WarehouseJournalEntry::where('reference_type', 'export')
                        ->where('reference_id', $export->id)
                        ->delete();
                }

                // Xóa chi tiết xuất kho và phiếu xuất
                $export->items()->delete();
                $export->delete();
            }

            // -------------------------------------------------------------
            // 2. XỬ LÝ YÊU CẦU ĐẶT HÀNG (SALE ORDER REQUESTS - PR) VÀ GOM ĐƠN
            // -------------------------------------------------------------
            $sorList = SaleOrderRequest::where('sale_id', $sale->id)->get();
            $sorIds = $sorList->pluck('id')->toArray();
            $sorItemIds = SaleOrderRequestItem::whereIn('sale_order_request_id', $sorIds)->pluck('id')->toArray();

            // -------------------------------------------------------------
            // 3. XỬ LÝ ĐẶT HÀNG HÃNG (PURCHASE ORDERS - PO)
            // -------------------------------------------------------------
            $poQuery = PurchaseOrder::query();
            $poQuery->where('sale_id', $sale->id);
            if (!empty($sorItemIds)) {
                $poQuery->orWhereHas('items', function ($q) use ($sorItemIds) {
                    $q->whereIn('sale_order_request_item_id', $sorItemIds);
                });
            }
            $relatedPos = $poQuery->get();

            foreach ($relatedPos as $po) {
                $allPoItems = $po->items;
                // Các items trong PO thuộc về đơn bán này
                $thisSalePoItems = $allPoItems->filter(function ($item) use ($sorItemIds, $sale, $po) {
                    return ($item->sale_order_request_item_id && in_array($item->sale_order_request_item_id, $sorItemIds))
                        || ($po->sale_id === $sale->id);
                });

                $otherSalePoItems = $allPoItems->diff($thisSalePoItems);

                if ($otherSalePoItems->isEmpty()) {
                    // TOÀN BỘ PO THUỘC ĐƠN HÀNG BÁN NÀY -> XÓA PO VÀ NHẬP KHO CỦA NÓ
                    
                    // 3.1. Xóa các phiếu nhập kho (Imports) tạo từ PO này
                    $poImports = Import::where('reference_type', 'purchase_order')
                        ->where('reference_id', $po->id)
                        ->get();

                    foreach ($poImports as $pImp) {
                        foreach ($pImp->items as $piItem) {
                            if ($piItem->product_id) {
                                $affectedProductIds->push($piItem->product_id);
                            }
                            if ($piItem->warehouse_id) {
                                $affectedWarehouseIds->push($piItem->warehouse_id);
                            }
                        }
                        if ($pImp->warehouse_id) {
                            $affectedWarehouseIds->push($pImp->warehouse_id);
                        }

                        // Xóa các ProductItem sinh ra từ phiếu nhập này
                        ProductItem::where('import_id', $pImp->id)->delete();

                        // Xóa bút toán sổ kho
                        if (Schema::hasTable('warehouse_journal_entries')) {
                            WarehouseJournalEntry::where('reference_type', 'import')
                                ->where('reference_id', $pImp->id)
                                ->delete();
                        }

                        $pImp->items()->delete();
                        $pImp->delete();
                    }

                    // 3.2. Xóa lịch sử thanh toán nhà cung cấp của PO
                    SupplierPaymentHistory::where('purchase_order_id', $po->id)->delete();

                    // 3.3. Xóa phân bổ vận chuyển
                    $saList = ShippingAllocation::where('purchase_order_id', $po->id)->get();
                    foreach ($saList as $sa) {
                        ShippingAllocationItem::where('shipping_allocation_id', $sa->id)->delete();
                        $sa->delete();
                    }

                    // 3.4. Xóa lịch sử duyệt PO
                    ApprovalHistory::where('document_type', 'purchase_order')
                        ->where('document_id', $po->id)
                        ->delete();

                    // 3.5. Xóa items và PO
                    $po->items()->delete();
                    $po->delete();
                } else {
                    // PO DÙNG CHUNG (GOM ĐƠN CHO NHIỀU ĐƠN BÁN KHÁC NHAU)
                    // -> CHỈ GỠ CÁC ITEMS LIÊN KẾT ĐƠN NÀY, GIỮ LẠI CHO CÁC ĐƠN KHÁC
                    foreach ($thisSalePoItems as $itemToDelete) {
                        // Nếu có dòng nhập kho cụ thể của POItem này, xóa dòng nhập kho đó
                        ImportItem::whereHas('import', function ($q) use ($po) {
                            $q->where('reference_type', 'purchase_order')->where('reference_id', $po->id);
                        })->where('comments', 'like', "%[POItem:{$itemToDelete->id}]%")->delete();

                        $itemToDelete->delete();
                    }

                    if ($po->sale_id === $sale->id) {
                        $po->sale_id = null;
                    }

                    $po->calculateTotals();
                    $po->updateDebt();
                    $po->save();
                }
            }

            // -------------------------------------------------------------
            // 4. XÓA PR ITEMS, TỆP ĐÍNH KÈM VÀ PR CHÍNH
            // -------------------------------------------------------------
            foreach ($sorList as $sor) {
                foreach ($sor->attachments as $sorAtt) {
                    $this->deleteFile($sorAtt->file_path);
                    $sorAtt->delete();
                }
                Storage::disk('public')->deleteDirectory('sale-order-requests/' . $sor->id);

                ApprovalHistory::where('document_type', 'sale_order_request')
                    ->where('document_id', $sor->id)
                    ->delete();

                $sor->items()->delete();
                $sor->delete();
            }

            // -------------------------------------------------------------
            // 5. XÓA CÁC PHIẾU NHẬP KHO TRỰC TIẾP LIÊN KẾT VỚI SALE (NẾU CÓ)
            // -------------------------------------------------------------
            $directImports = Import::where('reference_type', 'sale')
                ->where('reference_id', $sale->id)
                ->get();

            foreach ($directImports as $dImp) {
                foreach ($dImp->items as $diItem) {
                    if ($diItem->product_id) {
                        $affectedProductIds->push($diItem->product_id);
                    }
                    if ($diItem->warehouse_id) {
                        $affectedWarehouseIds->push($diItem->warehouse_id);
                    }
                }
                ProductItem::where('import_id', $dImp->id)->delete();
                if (Schema::hasTable('warehouse_journal_entries')) {
                    WarehouseJournalEntry::where('reference_type', 'import')
                        ->where('reference_id', $dImp->id)
                        ->delete();
                }
                $dImp->items()->delete();
                $dImp->delete();
            }

            // -------------------------------------------------------------
            // 6. ĐỒNG BỘ LẠI TỒN KHO CHO TẤT CẢ SẢN PHẨM BỊ ẢNH HƯỞNG
            // -------------------------------------------------------------
            $uniqueProductIds = $affectedProductIds->filter()->unique()->values();
            foreach ($uniqueProductIds as $pId) {
                $this->inventoryService->resyncStockFromItems($pId);
            }

            // -------------------------------------------------------------
            // 7. XÓA YÊU CẦU XUẤT HÓA ĐƠN VÀ CÁC TỆP ĐÍNH KÈM HÓA ĐƠN
            // -------------------------------------------------------------
            $invoiceRequests = InvoiceRequest::where('sale_id', $sale->id)->get();
            foreach ($invoiceRequests as $ir) {
                $this->deleteFile($ir->draft_path);
                $this->deleteFile($ir->official_path);
                $this->deleteFile($ir->delivery_note_path);
                $ir->delete();
            }

            // -------------------------------------------------------------
            // 8. XÓA LỊCH SỬ DUYỆT ĐƠN HÀNG VÀ DUYỆT P&L
            // -------------------------------------------------------------
            ApprovalHistory::whereIn('document_type', ['sale', 'sale_pnl'])
                ->where('document_id', $sale->id)
                ->delete();

            // -------------------------------------------------------------
            // 9. CẬP NHẬT LẠI BÁO GIÁ LIÊN KẾT (NẾU ĐƠN ĐƯỢC CHUYỂN TỪ BÁO GIÁ)
            // -------------------------------------------------------------
            Quotation::where('converted_to_sale_id', $sale->id)->update([
                'converted_to_sale_id' => null,
                'status' => 'approved',
            ]);

            // -------------------------------------------------------------
            // 10. GỠ LIÊN KẾT TICKET KỸ THUẬT (NẾU CÓ)
            // -------------------------------------------------------------
            if (Schema::hasTable('technical_tickets')) {
                TechnicalTicket::where('sale_id', $sale->id)->update(['sale_id' => null]);
            }

            // -------------------------------------------------------------
            // 11. XÓA GIAO DỊCH TÀI CHÍNH, DOANH THU & LỊCH SỬ THANH TOÁN
            // -------------------------------------------------------------
            FinancialTransaction::where('reference_number', $sale->code)->delete();
            PaymentHistory::where('sale_id', $sale->id)->delete();
            SalePaymentSchedule::where('sale_id', $sale->id)->delete();
            PaymentApprovalLog::where('sale_id', $sale->id)->delete();
            SalesRevenue::where('sale_id', $sale->id)->delete();

            // -------------------------------------------------------------
            // 12. XÓA TỆP ĐÍNH KÈM CỦA ĐƠN BÁN & P&L VÀ DỌN DẸP STORAGE
            // -------------------------------------------------------------
            foreach ($sale->attachments as $att) {
                $this->deleteFile($att->file_path);
                $att->delete();
            }
            foreach ($sale->pnlAttachments as $pnlAtt) {
                $this->deleteFile($pnlAtt->file_path);
                $pnlAtt->delete();
            }
            Storage::disk('public')->deleteDirectory('sale-attachments/' . $sale->id);

            // -------------------------------------------------------------
            // 13. XÓA CHI PHÍ P&L, CHI TIẾT SẢN PHẨM VÀ ĐƠN HÀNG BÁN CHÍNH
            // -------------------------------------------------------------
            $sale->expenses()->delete();
            $sale->items()->delete();
            $sale->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("SaleDeletionService failed for Sale #{$sale->id} ({$sale->code}): " . $e->getMessage(), [
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
