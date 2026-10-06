<?php

namespace App\Http\Controllers;

use App\Models\MarketingItem;
use App\Models\MarketingItemTransaction;
use App\Models\Opportunity;
use App\Models\MarketingEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MarketingItemsImport;
use App\Exports\MarketingItemTemplateExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarketingItemController extends Controller
{
    public function index(Request $request)
    {
        $query = MarketingItem::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'low') {
                $query->whereColumn('stock_quantity', '<=', 'min_stock_alert');
            } elseif ($request->stock_status === 'out') {
                $query->where('stock_quantity', '<=', 0);
            }
        }

        $items = $query->with(['submitter', 'approver', 'fund.supplier', 'event'])->latest('id')->paginate(15)->withQueryString();

        // Statistics
        $totalTypes = MarketingItem::count();
        $totalQuantity = MarketingItem::sum('stock_quantity');
        $totalValue = MarketingItem::select(DB::raw('SUM(stock_quantity * unit_cost) as total_val'))->value('total_val') ?? 0;
        $lowStockCount = MarketingItem::whereColumn('stock_quantity', '<=', 'min_stock_alert')->count();
        $pendingApprovalCount = MarketingItem::where('approval_status', 'pending')->count();

        $categories = MarketingItem::CATEGORIES;
        $allItems = MarketingItem::where('status', 'active')->where(function($q) {
            $q->where('approval_status', 'approved')->orWhereNull('approval_status');
        })->orderBy('name')->get(['id', 'code', 'name', 'stock_quantity', 'unit', 'unit_cost']);

        $supplierFunds = \App\Models\MarketingSupplierFund::with('supplier')->latest()->get();
        
        $opportunities = Opportunity::with('customer')
            ->whereNotIn('status', ['cancelled'])
            ->latest('id')
            ->take(100)
            ->get()
            ->map(function ($opp) {
                $customerName = $opp->customer ? $opp->customer->name : ($opp->eu_company_name ?: 'Khách hàng vãng lai');
                return [
                    'id' => $opp->id,
                    'name' => $opp->name,
                    'customer_name' => $customerName,
                    'status_label' => $opp->status_label,
                ];
            });

        $marketingEvents = MarketingEvent::latest('id')
            ->take(100)
            ->get()
            ->map(function ($ev) {
                return [
                    'id' => $ev->id,
                    'code' => $ev->code,
                    'title' => $ev->title,
                    'event_date' => $ev->event_date ? $ev->event_date->format('d/m/Y') : null,
                    'status' => $ev->status,
                ];
            });

        // Recent transactions
        $recentTransactions = MarketingItemTransaction::with(['marketingItem', 'creator', 'opportunity', 'marketingEvent'])
            ->latest('id')
            ->take(10)
            ->get();

        return view('marketing.items.index', compact(
            'items',
            'allItems',
            'totalTypes',
            'totalQuantity',
            'totalValue',
            'lowStockCount',
            'pendingApprovalCount',
            'categories',
            'supplierFunds',
            'opportunities',
            'marketingEvents',
            'recentTransactions'
        ));
    }

    public function store(Request $request)
    {
        $this->normalizeMoneyFields($request, ['unit_cost']);

        $validated = $request->validate([
            'name'                       => 'required|string|max:255',
            'category'                   => 'required|in:' . implode(',', array_keys(MarketingItem::CATEGORIES)),
            'unit'                       => 'required|string|max:50',
            'stock_quantity'             => 'nullable|integer|min:0',
            'min_stock_alert'            => 'nullable|integer|min:0',
            'unit_cost'                  => 'nullable|numeric|min:0',
            'description'                => 'nullable|string',
            'status'                     => 'nullable|in:active,inactive',
            'funding_source'             => 'nullable|string|max:50',
            'marketing_supplier_fund_id' => 'nullable|exists:marketing_supplier_funds,id',
            'purpose'                    => 'nullable|string|max:1000',
            'marketing_event_id'         => 'nullable|exists:marketing_events,id',
        ]);

        $user = Auth::user();
        $isBOD = $user->hasAnyRole(['super_admin', 'director', 'admin']);

        $qty = (int)($validated['stock_quantity'] ?? 0);
        $cost = (float)($validated['unit_cost'] ?? 0);

        $validated['code'] = MarketingItem::generateCode();
        $validated['stock_quantity'] = $qty;
        $validated['min_stock_alert'] = $validated['min_stock_alert'] ?? 10;
        $validated['unit_cost'] = $cost;
        $validated['total_estimated_cost'] = $qty * $cost;
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['approval_status'] = $isBOD ? 'approved' : 'pending';
        $validated['submitted_by'] = $user->id;
        if ($isBOD) {
            $validated['approved_by'] = $user->id;
            $validated['approved_at'] = now();
        }

        DB::transaction(function () use ($validated, $isBOD) {
            $item = MarketingItem::create($validated);

            if ($isBOD && $item->stock_quantity > 0) {
                MarketingItemTransaction::create([
                    'marketing_item_id' => $item->id,
                    'type' => 'import',
                    'quantity' => $item->stock_quantity,
                    'remaining_stock' => $item->stock_quantity,
                    'created_by' => Auth::id(),
                    'reference_code' => 'INIT-' . $item->code,
                    'note' => 'Khởi tạo tồn kho ban đầu (BOD trực tiếp duyệt)',
                ]);
            }
        });

        $msg = $isBOD 
            ? 'Đã thêm vật phẩm mới vào Kho Marketing thành công.' 
            : 'Đã gửi đề xuất vật phẩm mới lên Ban Giám đốc (BOD) phê duyệt.';

        return redirect()->route('marketing-items.index')->with('success', $msg);
    }

    public function update(Request $request, MarketingItem $marketingItem)
    {
        $this->normalizeMoneyFields($request, ['unit_cost']);

        $validated = $request->validate([
            'name'                       => 'required|string|max:255',
            'category'                   => 'required|in:' . implode(',', array_keys(MarketingItem::CATEGORIES)),
            'unit'                       => 'required|string|max:50',
            'min_stock_alert'            => 'nullable|integer|min:0',
            'unit_cost'                  => 'nullable|numeric|min:0',
            'description'                => 'nullable|string',
            'status'                     => 'required|in:active,inactive',
            'funding_source'             => 'nullable|string|max:50',
            'marketing_supplier_fund_id' => 'nullable|exists:marketing_supplier_funds,id',
            'purpose'                    => 'nullable|string|max:1000',
            'marketing_event_id'         => 'nullable|exists:marketing_events,id',
        ]);

        if (isset($validated['unit_cost'])) {
            $validated['total_estimated_cost'] = $marketingItem->stock_quantity * (float)$validated['unit_cost'];
        }

        $marketingItem->update($validated);

        return back()->with('success', 'Đã cập nhật thông tin vật phẩm.');
    }

    public function destroy(MarketingItem $marketingItem)
    {
        if ($marketingItem->stock_quantity > 0) {
            return back()->with('error', 'Không thể xóa vật phẩm còn tồn kho. Vui lòng xuất hết tồn trước khi xóa.');
        }

        $marketingItem->delete();
        return back()->with('success', 'Đã xóa vật phẩm khỏi Kho Marketing.');
    }

    /**
     * Nhập kho vật phẩm
     */
    public function importStock(Request $request)
    {
        $validated = $request->validate([
            'marketing_item_id' => 'required|exists:marketing_items,id',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'reference_code' => 'nullable|string|max:100',
            'note' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            $item = MarketingItem::lockForUpdate()->findOrFail($validated['marketing_item_id']);
            $newStock = $item->stock_quantity + $validated['quantity'];

            $updateData = ['stock_quantity' => $newStock];
            if (isset($validated['unit_cost']) && $validated['unit_cost'] > 0) {
                $updateData['unit_cost'] = $validated['unit_cost'];
            }
            $item->update($updateData);

            MarketingItemTransaction::create([
                'marketing_item_id' => $item->id,
                'type' => 'import',
                'quantity' => $validated['quantity'],
                'remaining_stock' => $newStock,
                'created_by' => Auth::id(),
                'reference_code' => $validated['reference_code'] ?? ('IMP-' . date('YmdHis')),
                'note' => $validated['note'] ?? 'Nhập bổ sung kho Marketing',
            ]);
        });

        return back()->with('success', 'Đã nhập kho vật phẩm Marketing thành công.');
    }

    /**
     * Xuất kho vật phẩm (quà tặng cho cơ hội / sự kiện)
     */
    public function exportStock(Request $request)
    {
        $validated = $request->validate([
            'marketing_item_id' => 'required|exists:marketing_items,id',
            'quantity' => 'required|integer|min:1',
            'opportunity_id' => 'nullable|exists:opportunities,id',
            'marketing_event_id' => 'nullable|exists:marketing_events,id',
            'reference_code' => 'nullable|string|max:100',
            'note' => 'nullable|string',
        ]);

        $item = MarketingItem::findOrFail($validated['marketing_item_id']);

        if ($item->stock_quantity < $validated['quantity']) {
            return back()->with('error', "Không đủ tồn kho để xuất. Tồn hiện tại: {$item->stock_quantity} {$item->unit}, yêu cầu: {$validated['quantity']} {$item->unit}.");
        }

        DB::transaction(function () use ($validated, $item) {
            $lockedItem = MarketingItem::lockForUpdate()->findOrFail($item->id);
            $newStock = $lockedItem->stock_quantity - $validated['quantity'];

            $lockedItem->update(['stock_quantity' => $newStock]);

            MarketingItemTransaction::create([
                'marketing_item_id' => $lockedItem->id,
                'type' => 'export',
                'quantity' => $validated['quantity'],
                'remaining_stock' => $newStock,
                'opportunity_id' => $validated['opportunity_id'] ?? null,
                'marketing_event_id' => $validated['marketing_event_id'] ?? null,
                'created_by' => Auth::id(),
                'reference_code' => $validated['reference_code'] ?? ('EXP-' . date('YmdHis')),
                'note' => $validated['note'] ?? 'Xuất quà tặng Marketing',
            ]);
        });

        return back()->with('success', 'Đã xuất kho vật phẩm Marketing thành công.');
    }

    /**
     * Lịch sử giao dịch của 1 vật phẩm
     */
    public function transactions(MarketingItem $marketingItem)
    {
        $transactions = $marketingItem->transactions()
            ->with(['creator', 'opportunity', 'marketingEvent'])
            ->paginate(20);

        return view('marketing.items.transactions', compact('marketingItem', 'transactions'));
    }

    /**
     * BOD Phê duyệt vật phẩm mới
     */
    public function approve(MarketingItem $marketingItem)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'director', 'admin'])) {
            return back()->with('error', 'Chỉ Ban Giám Đốc (BOD) hoặc Quản trị viên mới có quyền phê duyệt vật phẩm.');
        }

        DB::transaction(function () use ($marketingItem, $user) {
            $marketingItem->update([
                'approval_status'  => 'approved',
                'status'           => 'active',
                'approved_by'      => $user->id,
                'approved_at'      => now(),
                'rejection_reason' => null,
            ]);

            // Nếu có số lượng dự kiến nhập và chưa có transaction import ban đầu
            if ($marketingItem->stock_quantity > 0 && $marketingItem->transactions()->where('type', 'import')->count() === 0) {
                MarketingItemTransaction::create([
                    'marketing_item_id' => $marketingItem->id,
                    'type'              => 'import',
                    'quantity'          => $marketingItem->stock_quantity,
                    'remaining_stock'   => $marketingItem->stock_quantity,
                    'created_by'        => $user->id,
                    'reference_code'    => 'INIT-' . $marketingItem->code,
                    'note'              => 'BOD phê duyệt đề xuất vật phẩm mới - Khởi tạo tồn kho',
                ]);
            }
        });

        return back()->with('success', "BOD đã phê duyệt vật phẩm: {$marketingItem->name} (Mã: {$marketingItem->code}).");
    }

    /**
     * BOD Từ chối vật phẩm mới
     */
    public function reject(Request $request, MarketingItem $marketingItem)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'director', 'admin'])) {
            return back()->with('error', 'Chỉ Ban Giám Đốc (BOD) hoặc Quản trị viên mới có quyền từ chối vật phẩm.');
        }

        $reason = trim($request->input('rejection_reason', 'BOD không phê duyệt mẫu vật phẩm này.'));

        $marketingItem->update([
            'approval_status'  => 'rejected',
            'approved_by'      => $user->id,
            'approved_at'      => now(),
            'rejection_reason' => $reason,
        ]);

        return back()->with('success', "Đã từ chối vật phẩm: {$marketingItem->name}.");
    }

    /**
     * Import danh sách vật phẩm / quà tặng từ file Excel / CSV
     */
    public function importFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        try {
            $import = new MarketingItemsImport();
            Excel::import($import, $request->file('file'));

            $imported = $import->getImportedCount();
            $updated = $import->getUpdatedCount();
            $errors = $import->getErrors();

            $msg = "Import thành công: Thêm mới {$imported} vật phẩm, Cập nhật {$updated} vật phẩm.";
            if (!empty($errors)) {
                $msg .= " (Có " . count($errors) . " dòng lỗi: " . implode('; ', array_slice($errors, 0, 3)) . ")";
                return back()->with('warning', $msg);
            }

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Lỗi khi đọc file import: ' . $e->getMessage());
        }
    }

    /**
     * Tải file Excel mẫu (.xlsx) để import Quà tặng / Vật phẩm MKT
     */
    public function downloadTemplate()
    {
        return Excel::download(
            new MarketingItemTemplateExport(),
            'Mau_Import_Vat_Pham_MKT.xlsx'
        );
    }

    private function normalizeMoneyFields(Request $request, array $fields): void
    {
        $normalized = [];
        foreach ($fields as $field) {
            if (!$request->has($field)) {
                continue;
            }
            $raw = $request->input($field);
            if ($raw === null || trim((string) $raw) === '') {
                continue;
            }
            $clean = preg_replace('/[,\s]/', '', (string)$raw);
            $normalized[$field] = $clean;
        }
        if (!empty($normalized)) {
            $request->merge($normalized);
        }
    }
}
