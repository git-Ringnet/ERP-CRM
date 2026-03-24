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
    public function createFromSale(Sale $sale, float $amount, string $paymentMethod = 'cash', ?string $note = null, $currencyId = null, $exchangeRate = 1)
    {
        $currencyId = $currencyId ?? $sale->currency_id ?? \App\Models\Currency::getBaseCurrencyId();
        $exchangeRate = $exchangeRate ?? $sale->exchange_rate ?? 1;

        // Tìm hoặc tạo category "Doanh thu bán hàng"
        $category = TransactionCategory::firstOrCreate(
            ['name' => 'Doanh thu bán hàng', 'type' => 'income'],
            ['description' => 'Thu từ bán hàng']
        );

        // Convert to VND for base amount
        $currencyService = app(\App\Services\CurrencyService::class);
        $isForeign = $currencyService->isForeign($currencyId);
        
        $amountVnd = $amount;
        $amountForeign = 0;
        
        if ($isForeign) {
            $amountForeign = $amount;
            $amountVnd = $currencyService->convertToVnd($amount, $exchangeRate);
        }

        $transaction = FinancialTransaction::create([
            'transaction_category_id' => $category->id,
            'type' => 'income',
            'amount' => $amountVnd,
            'amount_foreign' => $amountForeign,
            'currency_id' => $currencyId,
            'exchange_rate' => $exchangeRate,
            'date' => now(),
            'payment_method' => $paymentMethod,
            'reference_number' => $sale->code,
            'note' => $note ?? "Thu tiền từ đơn hàng {$sale->code}",
            'created_by' => Auth::user()->name ?? 'System',
        ]);

        // Ghi nhận chênh lệch tỷ giá thực hiện (nếu có)
        if ($isForeign) {
            app(\App\Services\ExchangeRateDifferenceService::class)->recordRealizedDifference($sale, $amountForeign, $exchangeRate, $sale->code);
        }

        return $transaction;
    }

    /**
     * Tạo giao dịch chi từ đơn nhập hàng (Sử dụng model Import)
     */
    public function createFromImport(Import $import, float $amount, string $paymentMethod = 'cash', ?string $note = null, $currencyId = null, $exchangeRate = 1)
    {
        // Ta có thể tìm PO từ Import nếu cần, hoặc cứ dùng Import mã số
        $category = TransactionCategory::firstOrCreate(
            ['name' => 'Chi phí nhập hàng', 'type' => 'expense'],
            ['description' => 'Chi cho nhập hàng từ nhà cung cấp']
        );

        return $this->createBaseTransaction($import, $category, 'expense', $amount, $paymentMethod, $note, $currencyId, $exchangeRate);
    }

    /**
     * Tạo giao dịch chi từ đơn mua hàng (Sử dụng model PurchaseOrder)
     */
    public function createFromPurchaseOrder(\App\Models\PurchaseOrder $po, float $amount, string $paymentMethod = 'cash', ?string $note = null, $currencyId = null, $exchangeRate = 1)
    {
        $category = TransactionCategory::firstOrCreate(
            ['name' => 'Chi phí nhập hàng', 'type' => 'expense'],
            ['description' => 'Chi cho nhập hàng từ nhà cung cấp']
        );

        return $this->createBaseTransaction($po, $category, 'expense', $amount, $paymentMethod, $note, $currencyId, $exchangeRate);
    }

    /**
     * Helper chung để tạo giao dịch tài chính có hỗ trợ đa tiền tệ và chênh lệch tỷ giá
     */
    private function createBaseTransaction(\Illuminate\Database\Eloquent\Model $model, TransactionCategory $category, string $type, float $amount, string $paymentMethod, ?string $note, $currencyId, $exchangeRate)
    {
        $currencyId = $currencyId ?? $model->currency_id ?? \App\Models\Currency::getBaseCurrencyId();
        $exchangeRate = $exchangeRate ?? $model->exchange_rate ?? 1;

        $currencyService = app(\App\Services\CurrencyService::class);
        $isForeign = $currencyService->isForeign($currencyId);
        
        $amountVnd = $amount;
        $amountForeign = 0;
        
        if ($isForeign) {
            $amountForeign = $amount;
            $amountVnd = $currencyService->convertToVnd($amount, $exchangeRate);
        }

        $transaction = FinancialTransaction::create([
            'transaction_category_id' => $category->id,
            'type' => $type,
            'amount' => $amountVnd,
            'amount_foreign' => $amountForeign,
            'currency_id' => $currencyId,
            'exchange_rate' => $exchangeRate,
            'date' => now(),
            'payment_method' => $paymentMethod,
            'reference_number' => $model->code,
            'note' => $note ?? ($type === 'income' ? "Thu tiền từ {$model->code}" : "Chi tiền cho {$model->code}"),
            'created_by' => Auth::user()->name ?? 'System',
        ]);

        if ($isForeign) {
            app(\App\Services\ExchangeRateDifferenceService::class)->recordRealizedDifference($model, $amountForeign, $exchangeRate, $model->code);
        }

        return $transaction;
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
