<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FinancialTransaction;
use App\Models\TransactionCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FinancialTransactionController extends Controller
{
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
        return view('financial-transactions.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'transaction_category_id' => 'required|exists:transaction_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:1000',
        ]);

        $category = TransactionCategory::findOrFail($request->transaction_category_id);

        FinancialTransaction::create([
            'transaction_category_id' => $request->transaction_category_id,
            'type' => $category->type,
            'amount' => $request->amount,
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
        return view('financial-transactions.edit', compact('financialTransaction', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FinancialTransaction $financialTransaction)
    {
        $request->validate([
            'transaction_category_id' => 'required|exists:transaction_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:1000',
        ]);

        $category = TransactionCategory::findOrFail($request->transaction_category_id);

        $financialTransaction->update([
            'transaction_category_id' => $request->transaction_category_id,
            'type' => $category->type,
            'amount' => $request->amount,
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
        return view('financial-transactions.categories', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
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
}
