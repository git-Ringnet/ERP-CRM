<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\PurchaseOrder;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * SupplierDebtAgingReportService - Provides supplier debt aging analysis
 * 
 * Aging buckets: 0-30, 31-60, 61-90, >90 days
 * Based on order_date + payment_terms
 */
class SupplierDebtAgingReportService
{
    /**
     * Get aging report for all suppliers with debt
     */
    public function getAgingReport(array $filters = []): array
    {
        $suppliers = $this->getSuppliersWithDebt($filters);

        $report = [
            'suppliers' => [],
            'summary' => [
                'current' => 0,
                'days_31_60' => 0,
                'days_61_90' => 0,
                'over_90' => 0,
                'total' => 0,
            ],
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];

        foreach ($suppliers as $supplier) {
            $aging = $this->calculateSupplierAging($supplier);

            if ($aging['total'] > 0) {
                $report['suppliers'][] = $aging;

                $report['summary']['current'] += $aging['current'];
                $report['summary']['days_31_60'] += $aging['days_31_60'];
                $report['summary']['days_61_90'] += $aging['days_61_90'];
                $report['summary']['over_90'] += $aging['over_90'];
                $report['summary']['total'] += $aging['total'];
            }
        }

        usort($report['suppliers'], fn($a, $b) => $b['total'] <=> $a['total']);

        return $report;
    }

    /**
     * Calculate aging for a single supplier
     */
    public function calculateSupplierAging(Supplier $supplier): array
    {
        $purchaseOrders = PurchaseOrder::where('supplier_id', $supplier->id)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->where('debt_amount', '>', 0)
            ->get();

        $paymentTermsDays = $this->getPaymentTermsDays($supplier->payment_terms ?? 30);

        $aging = [
            'supplier_id' => $supplier->id,
            'supplier_code' => $supplier->code,
            'supplier_name' => $supplier->name,
            'phone' => $supplier->phone,
            'payment_terms' => $supplier->payment_terms,
            'current' => 0,
            'days_31_60' => 0,
            'days_61_90' => 0,
            'over_90' => 0,
            'total' => 0,
            'oldest_debt_days' => 0,
            'overdue_amount' => 0,
            'purchase_orders' => [],
        ];

        $today = Carbon::now();

        foreach ($purchaseOrders as $po) {
            $orderDate = Carbon::parse($po->order_date);
            $daysPastDue = $today->diffInDays($orderDate);
            $dueDate = $orderDate->copy()->addDays($paymentTermsDays);
            $isOverdue = $today->gt($dueDate);
            $debtAmount = (float) $po->debt_amount;

            if ($daysPastDue <= 30) {
                $aging['current'] += $debtAmount;
            } elseif ($daysPastDue <= 60) {
                $aging['days_31_60'] += $debtAmount;
            } elseif ($daysPastDue <= 90) {
                $aging['days_61_90'] += $debtAmount;
            } else {
                $aging['over_90'] += $debtAmount;
            }

            $aging['total'] += $debtAmount;

            if ($daysPastDue > $aging['oldest_debt_days']) {
                $aging['oldest_debt_days'] = $daysPastDue;
            }

            if ($isOverdue) {
                $aging['overdue_amount'] += $debtAmount;
            }

            $aging['purchase_orders'][] = [
                'po_id' => $po->id,
                'code' => $po->code,
                'order_date' => $po->order_date->format('Y-m-d'),
                'due_date' => $dueDate->format('Y-m-d'),
                'total' => (float) $po->total,
                'paid_amount' => (float) $po->paid_amount,
                'debt_amount' => $debtAmount,
                'days_past_due' => $daysPastDue,
                'is_overdue' => $isOverdue,
            ];
        }

        $aging['risk_level'] = $this->calculateRiskLevel($aging);

        return $aging;
    }

    /**
     * Get summary statistics
     */
    public function getSummaryStats(): array
    {
        $today = Carbon::now();

        $purchaseOrders = PurchaseOrder::whereNotIn('status', ['cancelled', 'draft'])
            ->where('debt_amount', '>', 0)
            ->with('supplier')
            ->get();

        $stats = [
            'total_suppliers' => $purchaseOrders->pluck('supplier_id')->unique()->count(),
            'total_debt' => $purchaseOrders->sum('debt_amount'),
            'current' => 0,
            'days_31_60' => 0,
            'days_61_90' => 0,
            'over_90' => 0,
            'overdue_suppliers' => 0,
            'overdue_amount' => 0,
        ];

        $overdueSupplierIds = [];

        foreach ($purchaseOrders as $po) {
            $orderDate = Carbon::parse($po->order_date);
            $daysPastDue = $today->diffInDays($orderDate);
            $paymentTermsDays = $this->getPaymentTermsDays($po->payment_terms ?? 30);
            $dueDate = $orderDate->copy()->addDays($paymentTermsDays);
            $isOverdue = $today->gt($dueDate);

            if ($daysPastDue <= 30) {
                $stats['current'] += $po->debt_amount;
            } elseif ($daysPastDue <= 60) {
                $stats['days_31_60'] += $po->debt_amount;
            } elseif ($daysPastDue <= 90) {
                $stats['days_61_90'] += $po->debt_amount;
            } else {
                $stats['over_90'] += $po->debt_amount;
            }

            if ($isOverdue) {
                $stats['overdue_amount'] += $po->debt_amount;
                $overdueSupplierIds[$po->supplier_id] = true;
            }
        }

        $stats['overdue_suppliers'] = count($overdueSupplierIds);

        if ($stats['total_debt'] > 0) {
            $stats['current_percent'] = round($stats['current'] / $stats['total_debt'] * 100, 1);
            $stats['days_31_60_percent'] = round($stats['days_31_60'] / $stats['total_debt'] * 100, 1);
            $stats['days_61_90_percent'] = round($stats['days_61_90'] / $stats['total_debt'] * 100, 1);
            $stats['over_90_percent'] = round($stats['over_90'] / $stats['total_debt'] * 100, 1);
        }

        return $stats;
    }

    /**
     * Convert payment_terms label to number of days
     */
    protected function getPaymentTermsDays($paymentTerms): int
    {
        return match($paymentTerms) {
            'immediate', 'cod' => 0,
            'net15' => 15,
            'net30' => 30,
            'net45' => 45,
            'net60' => 60,
            default => is_numeric($paymentTerms) ? (int) $paymentTerms : 30,
        };
    }

    /**
     * Calculate risk level based on aging
     */
    protected function calculateRiskLevel(array $aging): string
    {
        if ($aging['over_90'] > 0) {
            return 'high';
        }
        if ($aging['days_61_90'] > 0) {
            return 'medium';
        }
        if ($aging['days_31_60'] > 0) {
            return 'low';
        }
        return 'normal';
    }

    /**
     * Get suppliers with debt
     */
    protected function getSuppliersWithDebt(array $filters = []): Collection
    {
        $query = Supplier::whereHas('purchaseOrders', function ($q) {
            $q->whereNotIn('status', ['cancelled', 'draft'])
                ->where('debt_amount', '>', 0);
        });

        if (!empty($filters['supplier_id'])) {
            $query->where('id', $filters['supplier_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    /**
     * Get risk label in Vietnamese
     */
    public static function getRiskLabel(string $risk): string
    {
        return match ($risk) {
            'high' => 'Rủi ro cao',
            'medium' => 'Rủi ro trung bình',
            'low' => 'Rủi ro thấp',
            default => 'Bình thường',
        };
    }

    /**
     * Get risk color for badge
     */
    public static function getRiskColor(string $risk): string
    {
        return match ($risk) {
            'high' => 'red',
            'medium' => 'orange',
            'low' => 'yellow',
            default => 'green',
        };
    }
}
