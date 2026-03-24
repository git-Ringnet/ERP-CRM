<?php

namespace App\Services;

use App\Models\FinancialTransaction;
use App\Models\TransactionCategory;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExchangeRateDifferenceService
{
    /**
     * Ghi nhận chênh lệch tỷ giá thực hiện (Realized Gain/Loss)
     * Khi thanh toán một đơn hàng bằng ngoại tệ, so sánh tỷ giá lúc thanh toán vs tỷ giá lúc ghi nhận đơn hàng.
     * 
     * @param Model $model Sale hoặc PurchaseOrder
     * @param float $amountForeign Số tiền thanh toán (ngoại tệ)
     * @param float $paymentRate Tỷ giá tại thời điểm thanh toán
     * @param string $reference Số tham chiếu (mã đơn hàng)
     */
    public function recordRealizedDifference(Model $model, float $amountForeign, float $paymentRate, string $reference)
    {
        $originalRate = $model->exchange_rate;
        if (!$originalRate || $originalRate == $paymentRate) {
            return null;
        }

        // Giá trị VND theo tỷ giá gốc
        $originalVnd = round($amountForeign * $originalRate, 0);
        // Giá trị VND theo tỷ giá thanh toán
        $paymentVnd = round($amountForeign * $paymentRate, 0);

        $difference = $paymentVnd - $originalVnd;

        if ($difference == 0) {
            return null;
        }

        // Đối với Sale (Phải thu): 
        // paymentVnd > originalVnd => Lãi (Thu được nhiều VND hơn dự kiến)
        // paymentVnd < originalVnd => Lỗ
        
        // Đối with PurchaseOrder (Phải trả):
        // paymentVnd > originalVnd => Lỗ (Phải chi nhiều VND hơn dự kiến)
        // paymentVnd < originalVnd => Lãi

        $isSale = $model instanceof \App\Models\Sale;
        $isGain = $isSale ? ($difference > 0) : ($difference < 0);
        
        $absDifference = abs($difference);

        if ($isGain) {
            return $this->recordGain($absDifference, $reference, $model->currency_id);
        } else {
            return $this->recordLoss($absDifference, $reference, $model->currency_id);
        }
    }

    /**
     * Ghi nhận lãi tỷ giá
     */
    private function recordGain(float $amountVnd, string $reference, $currencyId)
    {
        $category = TransactionCategory::firstOrCreate(
            ['name' => 'Lãi tỷ giá', 'type' => 'income'],
            ['description' => 'Lãi phát sinh do chênh lệch tỷ giá hối đoái']
        );

        return FinancialTransaction::create([
            'transaction_category_id' => $category->id,
            'type' => 'income', // Or specialized type if enum supports it
            'amount' => $amountVnd,
            'amount_foreign' => 0, // Recorded in base currency difference
            'currency_id' => Currency::getBaseCurrencyId(),
            'exchange_rate' => 1,
            'date' => now(),
            'payment_method' => 'other',
            'reference_number' => $reference,
            'note' => "Lãi tỷ giá thực hiện từ đơn hàng {$reference}",
            'created_by' => Auth::user()->name ?? 'System',
        ]);
    }

    /**
     * Ghi nhận lỗ tỷ giá
     */
    private function recordLoss(float $amountVnd, string $reference, $currencyId)
    {
        $category = TransactionCategory::firstOrCreate(
            ['name' => 'Lỗ tỷ giá', 'type' => 'expense'],
            ['description' => 'Lỗ phát sinh do chênh lệch tỷ giá hối đoái']
        );

        return FinancialTransaction::create([
            'transaction_category_id' => $category->id,
            'type' => 'expense',
            'amount' => $amountVnd,
            'amount_foreign' => 0,
            'currency_id' => Currency::getBaseCurrencyId(),
            'exchange_rate' => 1,
            'date' => now(),
            'payment_method' => 'other',
            'reference_number' => $reference,
            'note' => "Lỗ tỷ giá thực hiện từ đơn hàng {$reference}",
            'created_by' => Auth::user()->name ?? 'System',
        ]);
    }

    /**
     * Revalue outstanding debts (unrealized exchange gain/loss)
     * For all Sales and PurchaseOrders with debt_amount_foreign > 0
     * 
     * @param string|null $revaluationDate Ngày đánh giá lại (mặc định hôm nay)
     */
    public function revalueOutstandingDebts($revaluationDate = null)
    {
        $revaluationDate = $revaluationDate ?: now()->format('Y-m-d');
        $baseCurrencyId = Currency::getBaseCurrencyId();

        // 1. Revalue Sales (Receivables)
        $sales = \App\Models\Sale::where('debt_amount_foreign', '>', 0)
            ->whereNotNull('currency_id')
            ->where('currency_id', '!=', $baseCurrencyId)
            ->get();

        foreach ($sales as $sale) {
            $this->revalueItem($sale, $revaluationDate);
        }

        // 2. Revalue PurchaseOrders (Payables)
        $pos = \App\Models\PurchaseOrder::where('debt_amount_foreign', '>', 0)
            ->whereNotNull('currency_id')
            ->where('currency_id', '!=', $baseCurrencyId)
            ->get();

        foreach ($pos as $po) {
            $this->revalueItem($po, $revaluationDate);
        }
    }

    /**
     * Revalue a single item (Sale or PurchaseOrder)
     */
    private function revalueItem(Model $model, $date)
    {
        $currencyId = $model->currency_id;
        $debtForeign = $model->debt_amount_foreign;
        $currentVndValue = $model->debt_amount; // Giá trị VND hiện tại trên sổ sách
        
        // Lấy tỷ giá mới nhất (hoặc tỷ giá tại ngày revaluation)
        $exchangeRateService = app(\App\Services\ExchangeRateService::class);
        $newRate = $exchangeRateService->getLatestRate($currencyId);
        
        if (!$newRate || $newRate == 0) return;

        $newVndValue = round($debtForeign * $newRate, 0);
        $difference = $newVndValue - $currentVndValue;

        if (abs($difference) < 1) return; // Bỏ qua nếu chênh lệch quá nhỏ

        $isSale = $model instanceof \App\Models\Sale;
        $isGain = $isSale ? ($difference > 0) : ($difference < 0);
        $absDifference = abs($difference);

        // Record as Unrealized Gain/Loss
        $categoryName = $isGain ? 'Lãi tỷ giá đánh giá lại' : 'Lỗ tỷ giá đánh giá lại';
        $type = $isGain ? 'income' : 'expense';
        $notePrefix = $isGain ? 'Lãi tỷ giá (đánh giá lại)' : 'Lỗ tỷ giá (đánh giá lại)';

        $category = TransactionCategory::firstOrCreate(
            ['name' => $categoryName, 'type' => $type],
            ['description' => "Chênh lệch tỷ giá chưa thực hiện khi đánh giá lại cuối kỳ"]
        );

        FinancialTransaction::create([
            'transaction_category_id' => $category->id,
            'type' => $type,
            'amount' => $absDifference,
            'amount_foreign' => 0,
            'currency_id' => Currency::getBaseCurrencyId(),
            'exchange_rate' => 1,
            'date' => $date,
            'payment_method' => 'other',
            'reference_number' => $model->code,
            'note' => "{$notePrefix} cho đơn hàng {$model->code} (Số dư: {$debtForeign} " . ($model->currency->code ?? '') . ")",
            'created_by' => 'System (Revaluation)',
        ]);

        // Cập nhật lại debt_amount (VND) của model để khớp với giá trị mới sau khi đánh giá lại
        // Lưu ý: Trong kế toán, việc cập nhật trực tiếp vào model chính hay qua bút toán điều chỉnh tùy thuộc vào hệ thống.
        // Ở đây ta cập nhật trực tiếp để debt_amount VND phản ánh đúng giá trị trị thực tế.
        $model->debt_amount = $newVndValue;
        $model->save();
    }
}
