<?php

namespace App\Http\Controllers;

use App\Models\WarehouseJournalEntry;
use App\Models\Setting;
use Illuminate\Http\Request;

class AccountingJournalController extends Controller
{
    /**
     * Display the warehouse journal entries list.
     */
    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $type = $request->input('type');
        $search = $request->input('search');

        $query = WarehouseJournalEntry::query()
            ->whereBetween('entry_date', [$dateFrom, $dateTo]);

        if ($type) {
            $query->where('reference_type', $type);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('debit_account', 'like', "%{$search}%")
                  ->orWhere('credit_account', 'like', "%{$search}%");
            });
        }

        $entries = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Totals: Chỉ cộng giá trị mới nhất của mỗi mã phiếu để tránh trùng lặp do lưu lịch sử
        $totalAmount = WarehouseJournalEntry::whereIn('id', function($q) use ($dateFrom, $dateTo, $type) {
                $q->selectRaw('MAX(id)')
                    ->from('warehouse_journal_entries')
                    ->whereBetween('entry_date', [$dateFrom, $dateTo]);
                
                if ($type) $q->where('reference_type', $type);
                
                $q->groupBy('reference_code');
            })
            ->where('status', '!=', 'deleted')
            ->sum('amount');

        return view('accounting.journal.index', compact(
            'entries', 'dateFrom', 'dateTo', 'type', 'search', 'totalAmount'
        ));
    }
}
