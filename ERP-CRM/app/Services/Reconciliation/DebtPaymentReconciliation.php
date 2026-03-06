<?php

namespace App\Services\Reconciliation;

use App\Models\Sale;
use App\Models\PaymentHistory;
use Illuminate\Support\Facades\DB;

/**
 * DebtPaymentReconciliation - Đối soát Công nợ ↔ Thanh toán
 * 
 * Checks:
 * 1. debt_amount ≠ (total - sum(PaymentHistory.amount))
 * 2. paid_amount ≠ sum(PaymentHistory.amount)
 * 3. payment_status inconsistent with paid_amount vs total
 */
class DebtPaymentReconciliation
{
    /**
     * Run all reconciliation checks
     */
    public function run(array $filters = []): array
    {
        return [
            'debt_mismatches' => $this->findDebtMismatches($filters),
            'paid_mismatches' => $this->findPaidAmountMismatches($filters),
            'status_mismatches' => $this->findPaymentStatusMismatches($filters),
        ];
    }

    /**
     * Get summary counts
     */
    public function summary(array $filters = []): array
    {
        $results = $this->run($filters);
        return [
            'total_issues' => count($results['debt_mismatches']) + count($results['paid_mismatches']) + count($results['status_mismatches']),
            'debt_mismatches' => count($results['debt_mismatches']),
            'paid_mismatches' => count($results['paid_mismatches']),
            'status_mismatches' => count($results['status_mismatches']),
        ];
    }

    /**
     * Find sales where debt_amount doesn't match total - sum(payments)
     */
    protected function findDebtMismatches(array $filters = []): array
    {
        $mismatches = [];

        $query = Sale::where('status', '!=', 'cancelled');

        if (!empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        $sales = $query->with('customer')->get();

        foreach ($sales as $sale) {
            $totalPaid = PaymentHistory::where('sale_id', $sale->id)->sum('amount');
            $expectedDebt = (float)$sale->total - (float)$totalPaid;
            $recordedDebt = (float)$sale->debt_amount;

            // Allow small rounding differences (0.01)
            if (abs($recordedDebt - $expectedDebt) > 0.01) {
                $mismatches[] = [
                    'sale_id' => $sale->id,
                    'sale_code' => $sale->code,
                    'customer_name' => $sale->customer_name ?? $sale->customer?->name ?? 'N/A',
                    'date' => $sale->date?->format('d/m/Y'),
                    'total' => (float)$sale->total,
                    'total_paid' => (float)$totalPaid,
                    'recorded_debt' => $recordedDebt,
                    'expected_debt' => $expectedDebt,
                    'difference' => round($recordedDebt - $expectedDebt, 2),
                    'issue' => 'Công nợ ghi nhận không khớp (total - tổng thanh toán)',
                ];
            }
        }

        return $mismatches;
    }

    /**
     * Find sales where paid_amount doesn't match sum(PaymentHistory.amount)
     */
    protected function findPaidAmountMismatches(array $filters = []): array
    {
        $mismatches = [];

        $query = Sale::where('status', '!=', 'cancelled');

        if (!empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        $sales = $query->with('customer')->get();

        foreach ($sales as $sale) {
            $totalPaid = PaymentHistory::where('sale_id', $sale->id)->sum('amount');
            $recordedPaid = (float)$sale->paid_amount;

            if (abs($recordedPaid - (float)$totalPaid) > 0.01) {
                $mismatches[] = [
                    'sale_id' => $sale->id,
                    'sale_code' => $sale->code,
                    'customer_name' => $sale->customer_name ?? $sale->customer?->name ?? 'N/A',
                    'date' => $sale->date?->format('d/m/Y'),
                    'total' => (float)$sale->total,
                    'recorded_paid' => $recordedPaid,
                    'actual_paid' => (float)$totalPaid,
                    'difference' => round($recordedPaid - (float)$totalPaid, 2),
                    'issue' => 'Số tiền đã thanh toán không khớp với lịch sử thanh toán',
                ];
            }
        }

        return $mismatches;
    }

    /**
     * Find sales where payment_status is inconsistent with actual paid vs total
     */
    protected function findPaymentStatusMismatches(array $filters = []): array
    {
        $mismatches = [];

        $query = Sale::where('status', '!=', 'cancelled');

        if (!empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        $sales = $query->with('customer')->get();

        foreach ($sales as $sale) {
            $paidAmount = (float)$sale->paid_amount;
            $total = (float)$sale->total;

            // Determine expected payment status
            if ($paidAmount <= 0) {
                $expectedStatus = 'unpaid';
            } elseif ($paidAmount >= $total) {
                $expectedStatus = 'paid';
            } else {
                $expectedStatus = 'partial';
            }

            if ($sale->payment_status !== $expectedStatus) {
                $statusLabels = [
                    'unpaid' => 'Chưa thanh toán',
                    'partial' => 'Thanh toán một phần',
                    'paid' => 'Đã thanh toán',
                ];

                $mismatches[] = [
                    'sale_id' => $sale->id,
                    'sale_code' => $sale->code,
                    'customer_name' => $sale->customer_name ?? $sale->customer?->name ?? 'N/A',
                    'date' => $sale->date?->format('d/m/Y'),
                    'total' => $total,
                    'paid_amount' => $paidAmount,
                    'recorded_status' => $sale->payment_status,
                    'recorded_status_label' => $statusLabels[$sale->payment_status] ?? $sale->payment_status,
                    'expected_status' => $expectedStatus,
                    'expected_status_label' => $statusLabels[$expectedStatus] ?? $expectedStatus,
                    'issue' => 'Trạng thái thanh toán không đúng',
                ];
            }
        }

        return $mismatches;
    }
}
