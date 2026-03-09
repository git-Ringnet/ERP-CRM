<?php

namespace App\Services\Reconciliation;

use App\Models\Sale;
use App\Models\PurchaseOrder;
use App\Models\PaymentHistory;
use App\Models\SupplierPaymentHistory;
use Illuminate\Support\Facades\DB;

/**
 * DebtPaymentReconciliation - Đối soát Công nợ ↔ Thanh toán
 * 
 * Customer checks (Sale ↔ PaymentHistory):
 * 1. debt_amount ≠ (total - sum(PaymentHistory.amount))
 * 2. paid_amount ≠ sum(PaymentHistory.amount)
 * 3. payment_status inconsistent with paid_amount vs total
 * 
 * Supplier checks (PurchaseOrder ↔ SupplierPaymentHistory):
 * 4. debt_amount ≠ (total - sum(SupplierPaymentHistory.amount))
 * 5. paid_amount ≠ sum(SupplierPaymentHistory.amount)
 * 6. payment_status inconsistent with paid_amount vs total
 */
class DebtPaymentReconciliation
{
    /**
     * Run all reconciliation checks
     */
    public function run(array $filters = []): array
    {
        $party = $filters['party'] ?? 'all';

        $result = [];

        if ($party === 'all' || $party === 'customer') {
            $result['debt_mismatches'] = $this->findDebtMismatches($filters);
            $result['paid_mismatches'] = $this->findPaidAmountMismatches($filters);
            $result['status_mismatches'] = $this->findPaymentStatusMismatches($filters);
        } else {
            $result['debt_mismatches'] = [];
            $result['paid_mismatches'] = [];
            $result['status_mismatches'] = [];
        }

        if ($party === 'all' || $party === 'supplier') {
            $result['supplier_debt_mismatches'] = $this->findSupplierDebtMismatches($filters);
            $result['supplier_paid_mismatches'] = $this->findSupplierPaidAmountMismatches($filters);
            $result['supplier_status_mismatches'] = $this->findSupplierStatusMismatches($filters);
        } else {
            $result['supplier_debt_mismatches'] = [];
            $result['supplier_paid_mismatches'] = [];
            $result['supplier_status_mismatches'] = [];
        }

        return $result;
    }

    /**
     * Get summary counts
     */
    public function summary(array $filters = []): array
    {
        $results = $this->run($filters);
        return [
            'total_issues' => 
                count($results['debt_mismatches']) + count($results['paid_mismatches']) + count($results['status_mismatches'])
                + count($results['supplier_debt_mismatches']) + count($results['supplier_paid_mismatches']) + count($results['supplier_status_mismatches']),
            'debt_mismatches' => count($results['debt_mismatches']),
            'paid_mismatches' => count($results['paid_mismatches']),
            'status_mismatches' => count($results['status_mismatches']),
            'supplier_debt_mismatches' => count($results['supplier_debt_mismatches']),
            'supplier_paid_mismatches' => count($results['supplier_paid_mismatches']),
            'supplier_status_mismatches' => count($results['supplier_status_mismatches']),
        ];
    }

    // ========================
    // CUSTOMER CHECKS
    // ========================

    protected function findDebtMismatches(array $filters = []): array
    {
        $mismatches = [];
        $query = Sale::where('status', '!=', 'cancelled');

        if (!empty($filters['date_from'])) $query->where('date', '>=', $filters['date_from']);
        if (!empty($filters['date_to'])) $query->where('date', '<=', $filters['date_to']);
        if (!empty($filters['customer_id'])) $query->where('customer_id', $filters['customer_id']);

        $sales = $query->with('customer')->get();

        foreach ($sales as $sale) {
            $totalPaid = PaymentHistory::where('sale_id', $sale->id)->sum('amount');
            $expectedDebt = (float)$sale->total - (float)$totalPaid;
            $recordedDebt = (float)$sale->debt_amount;

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

    protected function findPaidAmountMismatches(array $filters = []): array
    {
        $mismatches = [];
        $query = Sale::where('status', '!=', 'cancelled');

        if (!empty($filters['date_from'])) $query->where('date', '>=', $filters['date_from']);
        if (!empty($filters['date_to'])) $query->where('date', '<=', $filters['date_to']);

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

    protected function findPaymentStatusMismatches(array $filters = []): array
    {
        $mismatches = [];
        $query = Sale::where('status', '!=', 'cancelled');

        if (!empty($filters['date_from'])) $query->where('date', '>=', $filters['date_from']);
        if (!empty($filters['date_to'])) $query->where('date', '<=', $filters['date_to']);

        $sales = $query->with('customer')->get();

        foreach ($sales as $sale) {
            $paidAmount = (float)$sale->paid_amount;
            $total = (float)$sale->total;

            if ($paidAmount <= 0) {
                $expectedStatus = 'unpaid';
            } elseif ($paidAmount >= $total) {
                $expectedStatus = 'paid';
            } else {
                $expectedStatus = 'partial';
            }

            if ($sale->payment_status !== $expectedStatus) {
                $statusLabels = ['unpaid' => 'Chưa thanh toán', 'partial' => 'Thanh toán một phần', 'paid' => 'Đã thanh toán'];

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
                ];
            }
        }

        return $mismatches;
    }

    // ========================
    // SUPPLIER CHECKS
    // ========================

    protected function findSupplierDebtMismatches(array $filters = []): array
    {
        $mismatches = [];
        $query = PurchaseOrder::whereNotIn('status', ['cancelled', 'draft']);

        if (!empty($filters['date_from'])) $query->where('order_date', '>=', $filters['date_from']);
        if (!empty($filters['date_to'])) $query->where('order_date', '<=', $filters['date_to']);
        if (!empty($filters['supplier_id'])) $query->where('supplier_id', $filters['supplier_id']);

        $pos = $query->with('supplier')->get();

        foreach ($pos as $po) {
            $totalPaid = SupplierPaymentHistory::where('purchase_order_id', $po->id)->sum('amount');
            $expectedDebt = (float)$po->total - (float)$totalPaid;
            $recordedDebt = (float)$po->debt_amount;

            if (abs($recordedDebt - $expectedDebt) > 0.01) {
                $mismatches[] = [
                    'po_id' => $po->id,
                    'po_code' => $po->code,
                    'supplier_name' => $po->supplier?->name ?? 'N/A',
                    'date' => $po->order_date?->format('d/m/Y'),
                    'total' => (float)$po->total,
                    'total_paid' => (float)$totalPaid,
                    'recorded_debt' => $recordedDebt,
                    'expected_debt' => $expectedDebt,
                    'difference' => round($recordedDebt - $expectedDebt, 2),
                ];
            }
        }

        return $mismatches;
    }

    protected function findSupplierPaidAmountMismatches(array $filters = []): array
    {
        $mismatches = [];
        $query = PurchaseOrder::whereNotIn('status', ['cancelled', 'draft']);

        if (!empty($filters['date_from'])) $query->where('order_date', '>=', $filters['date_from']);
        if (!empty($filters['date_to'])) $query->where('order_date', '<=', $filters['date_to']);

        $pos = $query->with('supplier')->get();

        foreach ($pos as $po) {
            $totalPaid = SupplierPaymentHistory::where('purchase_order_id', $po->id)->sum('amount');
            $recordedPaid = (float)$po->paid_amount;

            if (abs($recordedPaid - (float)$totalPaid) > 0.01) {
                $mismatches[] = [
                    'po_id' => $po->id,
                    'po_code' => $po->code,
                    'supplier_name' => $po->supplier?->name ?? 'N/A',
                    'date' => $po->order_date?->format('d/m/Y'),
                    'total' => (float)$po->total,
                    'recorded_paid' => $recordedPaid,
                    'actual_paid' => (float)$totalPaid,
                    'difference' => round($recordedPaid - (float)$totalPaid, 2),
                ];
            }
        }

        return $mismatches;
    }

    protected function findSupplierStatusMismatches(array $filters = []): array
    {
        $mismatches = [];
        $query = PurchaseOrder::whereNotIn('status', ['cancelled', 'draft']);

        if (!empty($filters['date_from'])) $query->where('order_date', '>=', $filters['date_from']);
        if (!empty($filters['date_to'])) $query->where('order_date', '<=', $filters['date_to']);

        $pos = $query->with('supplier')->get();

        foreach ($pos as $po) {
            $paidAmount = (float)$po->paid_amount;
            $total = (float)$po->total;

            if ($paidAmount <= 0) {
                $expectedStatus = 'unpaid';
            } elseif ($paidAmount >= $total) {
                $expectedStatus = 'paid';
            } else {
                $expectedStatus = 'partial';
            }

            $currentStatus = $po->payment_status ?? 'unpaid';
            if ($currentStatus !== $expectedStatus) {
                $statusLabels = ['unpaid' => 'Chưa thanh toán', 'partial' => 'Thanh toán một phần', 'paid' => 'Đã thanh toán'];

                $mismatches[] = [
                    'po_id' => $po->id,
                    'po_code' => $po->code,
                    'supplier_name' => $po->supplier?->name ?? 'N/A',
                    'date' => $po->order_date?->format('d/m/Y'),
                    'total' => $total,
                    'paid_amount' => $paidAmount,
                    'recorded_status' => $currentStatus,
                    'recorded_status_label' => $statusLabels[$currentStatus] ?? $currentStatus,
                    'expected_status' => $expectedStatus,
                    'expected_status_label' => $statusLabels[$expectedStatus] ?? $expectedStatus,
                ];
            }
        }

        return $mismatches;
    }
}

