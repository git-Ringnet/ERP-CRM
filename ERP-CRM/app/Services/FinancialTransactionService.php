<?php

namespace App\Services;

use App\Models\FinancialTransaction;
use App\Models\TransactionCategory;
use App\Models\Sale;
use App\Models\Import;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinancialTransactionService
{
    /**
     * Tạo giao dịch thu từ đơn bán hàng
     */
    public function createFromSale(Sale $sale, float $amount, string $paymentMethod = 'cash', ?string $note = null)
    {
        // Tìm hoặc tạo category "Doanh thu bán hàng"
        $category = TransactionCategory::firstOrCreate(
            ['name' => 'Doanh thu bán hàng', 'type' => 'income'],
            ['description' => 'Thu từ bán hàng']
        );

        return FinancialTransaction::create([
            'transaction_category_id' => $category->id,
            'type' => 'income',
            'amount' => $amount,
            'date' => now(),
            'payment_method' => $paymentMethod,
            'reference_number' => $sale->code,
            'note' => $note ?? "Thu tiền từ đơn hàng {$sale->code}",
            'created_by' => Auth::user()->name ?? 'System',
        ]);
    }

    /**
     * Tạo giao dịch chi từ đơn nhập hàng
     */
    public function createFromImport(Import $import, float $amount, string $paymentMethod = 'cash', ?string $note = null)
    {
        // Tìm hoặc tạo category "Chi phí nhập hàng"
        $category = TransactionCategory::firstOrCreate(
            ['name' => 'Chi phí nhập hàng', 'type' => 'expense'],
            ['description' => 'Chi cho nhập hàng từ nhà cung cấp']
        );

        return FinancialTransaction::create([
            'transaction_category_id' => $category->id,
            'type' => 'expense',
            'amount' => $amount,
            'date' => now(),
            'payment_method' => $paymentMethod,
            'reference_number' => $import->code,
            'note' => $note ?? "Chi tiền cho đơn nhập {$import->code}",
            'created_by' => Auth::user()->name ?? 'System',
        ]);
    }

    /**
     * Kiểm tra xem đã có giao dịch cho reference này chưa
     */
    public function hasTransactionForReference(string $referenceNumber): bool
    {
        return FinancialTransaction::where('reference_number', $referenceNumber)->exists();
    }

    /**
     * Lấy tổng số tiền đã ghi nhận cho một reference
     */
    public function getTotalAmountForReference(string $referenceNumber): float
    {
        return FinancialTransaction::where('reference_number', $referenceNumber)->sum('amount');
    }
}
