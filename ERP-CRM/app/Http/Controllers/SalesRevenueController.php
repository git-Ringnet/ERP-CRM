<?php

namespace App\Http\Controllers;

use App\Models\SalesRevenue;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesRevenueController extends Controller
{
    /**
     * Main tracking page — auto-syncs PO/SO data and displays the spreadsheet
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', SalesRevenue::class);

        $year = $request->input('year', now()->year);
        $supplierId = $request->input('supplier_id');
        $search = $request->input('search');

        $revenues = SalesRevenue::query()
            ->byYear($year)
            ->bySupplier($supplierId)
            ->search($search)
            ->with(['purchaseOrder', 'sale', 'supplier', 'customer', 'project'])
            ->orderBy('po_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(100)
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->get();

        // Stats
        $statsQuery = SalesRevenue::query()->byYear($year)->bySupplier($supplierId)->search($search);
        $totalRecords = (clone $statsQuery)->count();
        $stats = [
            'total_records' => $totalRecords,
            'total_amount' => (clone $statsQuery)->sum('total_amount'),
            'total_selling' => (clone $statsQuery)->sum('selling_price'),
            'total_quantity' => (clone $statsQuery)->sum('quantity'),
        ];

        $years = SalesRevenue::selectRaw('DISTINCT year')->orderBy('year', 'desc')->pluck('year');
        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        return view('sales-revenues.index', compact(
            'revenues', 'suppliers', 'stats', 'years',
            'year', 'supplierId', 'search'
        ));
    }

    /**
     * Sync all PO items from a given supplier into the tracking table
     * Only creates records for PO items not yet tracked
     */
    public function syncFromPO(Request $request)
    {
        $this->authorize('create', SalesRevenue::class);

        $year = $request->input('year', now()->year);
        $supplierId = $request->input('supplier_id');

        // Get all PO items from approved POs, optionally filtered by supplier
        $query = PurchaseOrderItem::query()
            ->whereHas('purchaseOrder', function ($q) use ($supplierId) {
                $q->whereIn('status', ['approved', 'sent', 'confirmed', 'shipping', 'partial_received', 'received']);
                if ($supplierId) {
                    $q->where('supplier_id', $supplierId);
                }
            })
            ->with([
                'purchaseOrder.supplier',
                'product',
                'saleOrderRequestItem.saleItem',
                'saleOrderRequestItem.saleOrderRequest.sale.customer',
                'saleOrderRequestItem.saleOrderRequest.sale.project',
                'saleOrderRequestItem.saleOrderRequest.sale.items',
            ]);

        // Filter by year based on PO date
        $query->whereHas('purchaseOrder', function ($q) use ($year) {
            $q->whereYear('order_date', $year);
        });

        $poItems = $query->get();

        // Get already-tracked PO item IDs
        $existingPoItemIds = SalesRevenue::where('year', $year)
            ->whereNotNull('purchase_order_item_id')
            ->pluck('purchase_order_item_id')
            ->toArray();

        $count = 0;
        foreach ($poItems as $poItem) {
            if (in_array($poItem->id, $existingPoItemIds)) {
                continue; // Already tracked
            }

            $revenue = new SalesRevenue();
            $revenue->populateFromPurchaseOrderItem($poItem);

            // Trace PO → Sale via SaleOrderRequestItem chain
            $sorItem = $poItem->saleOrderRequestItem;
            $sale = $sorItem?->saleOrderRequest?->sale;

            if ($sale) {
                // First try direct link via SaleOrderRequestItem.sale_item_id
                $saleItem = $sorItem?->saleItem;
                // Fallback: match by product_id
                if (!$saleItem) {
                    $saleItem = $sale->items->first(fn($si) => $si->product_id === $poItem->product_id);
                }
                $revenue->populateFromSale($sale, $saleItem);
            }

            $revenue->year = $year;
            $revenue->created_by = Auth::id();
            $revenue->save();
            $count++;
        }

        if ($count > 0) {
            return redirect()->route('sales-revenues.index', $request->only('year', 'supplier_id'))
                ->with('success', "Đã đồng bộ thêm {$count} dòng mới từ PO.");
        }

        return redirect()->route('sales-revenues.index', $request->only('year', 'supplier_id'))
            ->with('info', 'Không có dòng mới nào để đồng bộ — dữ liệu đã cập nhật.');
    }

    /**
     * AJAX: Inline update a single cell
     */
    public function updateCell(Request $request, SalesRevenue $salesRevenue)
    {
        $this->authorize('update', $salesRevenue);

        $field = $request->input('field');
        $value = $request->input('value');

        // Only allow editing specific fields (manual/editable columns)
        $editableFields = [
            'cpq_number', 'invoice_status', 'warehouse_status', 'license_exported',
            'serial_number', 'quote_id', 'list_price', 'discount_percent',
            'expired_date', 'selling_price', 'end_user_partner', 'equipment',
            'partner_name', 'end_user', 'industry', 'note',
            'customer_name', 'quantity',
        ];

        if (!in_array($field, $editableFields)) {
            return response()->json(['error' => 'Trường không cho phép chỉnh sửa'], 403);
        }

        // Type casting
        if (in_array($field, ['list_price', 'discount_percent', 'selling_price'])) {
            $value = $value !== '' ? (float) $value : null;
        }
        if ($field === 'quantity') {
            $value = $value !== '' ? (int) $value : null;
        }
        if ($field === 'expired_date') {
            $value = $value ?: null;
        }

        $salesRevenue->update([$field => $value]);

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu',
            'value' => $salesRevenue->fresh()->$field,
        ]);
    }

    /**
     * Delete a revenue record
     */
    public function destroy(SalesRevenue $salesRevenue)
    {
        $this->authorize('delete', $salesRevenue);

        $salesRevenue->delete();

        return redirect()->route('sales-revenues.index')
            ->with('success', 'Đã xóa dòng doanh số.');
    }

    /**
     * Export to CSV/Excel
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', SalesRevenue::class);

        $year = $request->input('year', now()->year);
        $supplierId = $request->input('supplier_id');
        $search = $request->input('search');

        $revenues = SalesRevenue::query()
            ->byYear($year)
            ->bySupplier($supplierId)
            ->search($search)
            ->with(['supplier'])
            ->orderBy('po_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $supplierName = $supplierId ? Supplier::find($supplierId)?->name : 'ALL';
        $filename = "Tong_Doanh_So_{$supplierName}_{$year}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($revenues) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'STT', 'CPQ', 'Tình trạng XHĐ', 'Hàng đã nhập kho (WH)',
                'Đã Xuất POS (License)', 'Số PO', 'Ngày PO', 'Hàng hóa',
                'SL', 'S.N', 'Quote ID', 'ListPrice', 'Discount',
                'Unit Price', 'Thành Tiền', 'Expired date', 'Khách hàng',
                'Giá bán', 'End User/Partner (Project)', 'Equipment',
                'Partner', 'EU', 'Industries'
            ]);

            foreach ($revenues as $i => $r) {
                fputcsv($file, [
                    $i + 1,
                    $r->cpq_number,
                    $r->invoice_status_label,
                    $r->warehouse_status,
                    $r->license_exported,
                    $r->po_code,
                    $r->po_date?->format('d/m/Y'),
                    $r->product_name,
                    $r->quantity,
                    $r->serial_number,
                    $r->quote_id,
                    $r->list_price,
                    $r->discount_percent ? $r->discount_percent . '%' : '',
                    $r->unit_price,
                    $r->total_amount,
                    $r->expired_date?->format('d/m/Y'),
                    $r->customer_name,
                    $r->selling_price,
                    $r->end_user_partner,
                    $r->equipment,
                    $r->partner_name,
                    $r->end_user,
                    $r->industry,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
