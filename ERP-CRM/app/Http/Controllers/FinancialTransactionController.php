<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FinancialTransaction;
use App\Models\TransactionCategory;
use App\Models\Currency;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class FinancialTransactionController extends Controller
{
    protected $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $type = $request->input('type');
        $categoryId = $request->input('category_id');

        $query = FinancialTransaction::with('category')
            ->whereBetween('date', [$dateFrom, $dateTo]);

        if ($type) {
            $query->where('type', $type);
        }

        if ($categoryId) {
            $query->where('transaction_category_id', $categoryId);
        }

        $transactions = $query->orderBy('date', 'desc')->paginate(15)->withQueryString();
        
        $categories = TransactionCategory::orderBy('name')->get();

        // Calculate totals for the filtered range
        $incomeTotal = FinancialTransaction::whereBetween('date', [$dateFrom, $dateTo])
            ->where('type', 'income')
            ->sum('amount');
        
        $expenseTotal = FinancialTransaction::whereBetween('date', [$dateFrom, $dateTo])
            ->where('type', 'expense')
            ->sum('amount');

        return view('financial-transactions.index', compact(
            'transactions', 'categories', 'incomeTotal', 'expenseTotal', 
            'dateFrom', 'dateTo', 'type', 'categoryId'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = TransactionCategory::orderBy('name')->get();
        $currencies = Currency::where('is_active', true)->get();
        $baseCurrencyId = Currency::getBaseCurrencyId();
        
        return view('financial-transactions.create', compact('categories', 'currencies', 'baseCurrencyId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'transaction_category_id' => 'required|exists:transaction_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'currency_id' => 'required|exists:currencies,id',
            'exchange_rate' => 'required|numeric|min:1',
            'date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:1000',
        ]);

        $category = TransactionCategory::findOrFail($request->transaction_category_id);

        $currency = Currency::find($request->currency_id);
        $isForeign = $this->currencyService->isForeignTransaction($request->currency_id);
        
        $amountVnd = $request->amount;
        $amountForeign = null;
        
        if ($isForeign) {
            $amountForeign = $request->amount;
            $amountVnd = $this->currencyService->toBase($request->amount, $request->exchange_rate);
        }

        FinancialTransaction::create([
            'transaction_category_id' => $request->transaction_category_id,
            'type' => $category->type,
            'amount' => $amountVnd,
            'amount_foreign' => $amountForeign,
            'currency_id' => $request->currency_id,
            'exchange_rate' => $request->exchange_rate ?? 1,
            'date' => $request->date,
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
            'note' => $request->note,
            'created_by' => Auth::user()->name ?? 'System',
        ]);

        return redirect()->route('financial-transactions.index')->with('success', 'Ghi nhận giao dịch thành công!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FinancialTransaction $financialTransaction)
    {
        $categories = TransactionCategory::orderBy('name')->get();
        $currencies = Currency::where('is_active', true)->get();
        $baseCurrencyId = Currency::getBaseCurrencyId();
        
        return view('financial-transactions.edit', compact('financialTransaction', 'categories', 'currencies', 'baseCurrencyId'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FinancialTransaction $financialTransaction)
    {
        $request->validate([
            'transaction_category_id' => 'required|exists:transaction_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'currency_id' => 'required|exists:currencies,id',
            'exchange_rate' => 'required|numeric|min:1',
            'date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:1000',
        ]);

        $category = TransactionCategory::findOrFail($request->transaction_category_id);

        $isForeign = $this->currencyService->isForeignTransaction($request->currency_id);
        
        $amountVnd = $request->amount;
        $amountForeign = null;
        
        if ($isForeign) {
            $amountForeign = $request->amount;
            $amountVnd = $this->currencyService->toBase($request->amount, $request->exchange_rate);
        }

        $financialTransaction->update([
            'transaction_category_id' => $request->transaction_category_id,
            'type' => $category->type,
            'amount' => $amountVnd,
            'amount_foreign' => $amountForeign,
            'currency_id' => $request->currency_id,
            'exchange_rate' => $request->exchange_rate ?? 1,
            'date' => $request->date,
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
            'note' => $request->note,
        ]);

        return redirect()->route('financial-transactions.index')->with('success', 'Cập nhật giao dịch thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FinancialTransaction $financialTransaction)
    {
        $financialTransaction->delete();
        return redirect()->route('financial-transactions.index')->with('success', 'Đã xóa giao dịch!');
    }

    /**
     * Categories management
     */
    public function categories()
    {
        $categories = TransactionCategory::orderBy('type')->orderBy('name')->get();
        $standardItems = \App\Http\Controllers\CashFlowReportController::getStandardItems();
        return view('financial-transactions.categories', compact('categories', 'standardItems'));
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'cash_flow_code' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:500',
        ]);

        TransactionCategory::create($request->all());

        return redirect()->back()->with('success', 'Đã thêm danh mục mới!');
    }

    public function destroyCategory(TransactionCategory $category)
    {
        if ($category->transactions()->exists()) {
            return redirect()->back()->with('error', 'Không thể xóa danh mục đã có giao dịch!');
        }
        
        $category->delete();
        return redirect()->back()->with('success', 'Đã xóa danh mục!');
    }

    public function updateCategory(Request $request, TransactionCategory $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'cash_flow_code' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:500',
        ]);

        $category->update($request->all());

        return redirect()->back()->with('success', 'Cập nhật danh mục thành công!');
    }

    /**
     * Print financial transaction voucher
     */
    public function print(FinancialTransaction $transaction)
    {
        $transaction->load('category');
        $view = $transaction->type === 'income' ? 'phieu-thu' : 'phieu-chi';
        return view('reports.vouchers.' . $view, compact('transaction'));
    }

    /**
     * Export to Misa Excel
     */
    public function exportMisa(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'type']);
        return Excel::download(new \App\Exports\MisaReceiptsExport($filters), 'Misa_ThuChi_' . date('Ymd') . '.xlsx');
    }
}
