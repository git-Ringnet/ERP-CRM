<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * DebtAgingReportService - Provides debt aging analysis
 * 
 * Aging buckets:
 * - Current (0-30 days)
 * - 31-60 days
 * - 61-90 days
 * - Over 90 days
 */
class DebtAgingReportService
{
    /**
     * Get aging report for all customers with debt
     * 
     * @param array $filters
     * @return array
     */
    public function getAgingReport(array $filters = []): array
    {
        $customers = $this->getCustomersWithDebt($filters);

        $report = [
            'customers' => [],
            'summary' => [
                'current' => 0, // 0-30 days
                'days_31_60' => 0,
                'days_61_90' => 0,
                'over_90' => 0,
                'total' => 0,
            ],
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];

        foreach ($customers as $customer) {
            $aging = $this->calculateCustomerAging($customer);

            if ($aging['total'] > 0) {
                $report['customers'][] = $aging;

                $report['summary']['current'] += $aging['current'];
                $report['summary']['days_31_60'] += $aging['days_31_60'];
                $report['summary']['days_61_90'] += $aging['days_61_90'];
                $report['summary']['over_90'] += $aging['over_90'];
                $report['summary']['total'] += $aging['total'];
            }
        }

        // Sort by total debt descending
        usort($report['customers'], fn($a, $b) => $b['total'] <=> $a['total']);

        return $report;
    }

    /**
     * Calculate aging for a single customer
     */
    public function calculateCustomerAging(Customer $customer): array
    {
        $sales = Sale::where('customer_id', $customer->id)
            ->whereIn('status', ['approved', 'shipping', 'completed'])
            ->where('debt_amount', '>', 0)
            ->get();

        $aging = [
            'customer_id' => $customer->id,
            'customer_code' => $customer->code,
            'customer_name' => $customer->name,
            'phone' => $customer->phone,
            'debt_limit' => $customer->debt_limit ?? 0,
            'debt_days' => $customer->debt_days ?? 30,
            'current' => 0, // 0-30 days
            'days_31_60' => 0,
            'days_61_90' => 0,
            'over_90' => 0,
            'total' => 0,
            'oldest_debt_days' => 0,
            'overdue_amount' => 0,
            'sales' => [],
        ];

        $today = Carbon::now();

        foreach ($sales as $sale) {
            $saleDate = Carbon::parse($sale->date);
            $daysPastDue = $today->diffInDays($saleDate);
            $dueDate = $saleDate->copy()->addDays($customer->debt_days ?? 30);
            $isOverdue = $today->gt($dueDate);
            $debtAmount = $sale->debt_amount;

            // Categorize by aging bucket
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

            // Add sale detail
            $aging['sales'][] = [
                'sale_id' => $sale->id,
                'code' => $sale->code,
                'date' => $sale->date->format('Y-m-d'),
                'due_date' => $dueDate->format('Y-m-d'),
                'total' => $sale->total,
                'paid_amount' => $sale->paid_amount,
                'debt_amount' => $debtAmount,
                'days_past_due' => $daysPastDue,
                'is_overdue' => $isOverdue,
            ];
        }

        // Calculate risk level
        $aging['risk_level'] = $this->calculateRiskLevel($aging);

        return $aging;
    }

    /**
     * Get summary statistics
     */
    public function getSummaryStats(): array
    {
        $today = Carbon::now();

        $sales = Sale::whereIn('status', ['approved', 'shipping', 'completed'])
            ->where('debt_amount', '>', 0)
            ->with('customer')
            ->get();

        $stats = [
            'total_customers' => $sales->pluck('customer_id')->unique()->count(),
            'total_debt' => $sales->sum('debt_amount'),
            'current' => 0,
            'days_31_60' => 0,
            'days_61_90' => 0,
            'over_90' => 0,
            'overdue_customers' => 0,
            'overdue_amount' => 0,
        ];

        $overdueCustomerIds = [];

        foreach ($sales as $sale) {
            $saleDate = Carbon::parse($sale->date);
            $daysPastDue = $today->diffInDays($saleDate);
            $debtDays = $sale->customer?->debt_days ?? 30;
            $dueDate = $saleDate->copy()->addDays($debtDays);
            $isOverdue = $today->gt($dueDate);

            if ($daysPastDue <= 30) {
                $stats['current'] += $sale->debt_amount;
            } elseif ($daysPastDue <= 60) {
                $stats['days_31_60'] += $sale->debt_amount;
            } elseif ($daysPastDue <= 90) {
                $stats['days_61_90'] += $sale->debt_amount;
            } else {
                $stats['over_90'] += $sale->debt_amount;
            }

            if ($isOverdue) {
                $stats['overdue_amount'] += $sale->debt_amount;
                $overdueCustomerIds[$sale->customer_id] = true;
            }
        }

        $stats['overdue_customers'] = count($overdueCustomerIds);

        // Calculate percentages
        if ($stats['total_debt'] > 0) {
            $stats['current_percent'] = round($stats['current'] / $stats['total_debt'] * 100, 1);
            $stats['days_31_60_percent'] = round($stats['days_31_60'] / $stats['total_debt'] * 100, 1);
            $stats['days_61_90_percent'] = round($stats['days_61_90'] / $stats['total_debt'] * 100, 1);
            $stats['over_90_percent'] = round($stats['over_90'] / $stats['total_debt'] * 100, 1);
        }

        return $stats;
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
     * Get customers with debt
     */
    protected function getCustomersWithDebt(array $filters = []): Collection
    {
        $query = Customer::whereHas('sales', function ($q) {
            $q->whereIn('status', ['approved', 'shipping', 'completed'])
                ->where('debt_amount', '>', 0);
        });

        if (!empty($filters['customer_id'])) {
            $query->where('id', $filters['customer_id']);
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
