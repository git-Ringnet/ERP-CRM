<?php

namespace App\Services\Reconciliation;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Export;
use App\Models\ExportItem;
use Illuminate\Support\Facades\DB;

/**
 * SaleExportReconciliation - Đối soát Bán hàng ↔ Xuất kho
 * 
 * Checks:
 * 1. Sales approved/shipping/completed but no linked Export
 * 2. SaleItem quantities ≠ ExportItem quantities per product
 * 3. Exports referencing cancelled sales
 */
class SaleExportReconciliation
{
    /**
     * Run all reconciliation checks
     */
    public function run(array $filters = []): array
    {
        return [
            'missing_exports' => $this->findMissingExports($filters),
            'quantity_mismatches' => $this->findQuantityMismatches($filters),
            'mismatched_exports' => $this->findMismatchedExports($filters),
        ];
    }

    /**
     * Get summary counts
     */
    public function summary(array $filters = []): array
    {
        $results = $this->run($filters);
        return [
            'total_issues' => count($results['missing_exports']) + count($results['quantity_mismatches']) + count($results['mismatched_exports']),
            'missing_exports' => count($results['missing_exports']),
            'quantity_mismatches' => count($results['quantity_mismatches']),
            'mismatched_exports' => count($results['mismatched_exports']),
        ];
    }

    /**
     * Find sales that are approved/shipping/completed but have no linked export
     */
    protected function findMissingExports(array $filters = []): array
    {
        $query = Sale::whereIn('status', ['approved', 'shipping', 'completed'])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('exports')
                    ->where('reference_type', 'sale')
                    ->whereColumn('reference_id', 'sales.id')
                    ->whereIn('status', ['completed']);
            });

        if (!empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        return $query->with(['customer', 'user', 'items'])
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($sale) {
                return [
                    'sale_id' => $sale->id,
                    'sale_code' => $sale->code,
                    'customer_name' => $sale->customer_name ?? $sale->customer?->name ?? 'N/A',
                    'date' => $sale->date?->format('d/m/Y'),
                    'status' => $sale->status,
                    'status_label' => $sale->status_label,
                    'total' => $sale->total,
                    'total_items' => $sale->items->sum('quantity'),
                    'issue' => 'Đã duyệt nhưng chưa hoàn thành xuất kho',
                ];
            })
            ->toArray();
    }

    /**
     * Find sales where exported quantities don't match sale quantities
     */
    protected function findQuantityMismatches(array $filters = []): array
    {
        $mismatches = [];

        $query = Sale::whereIn('status', ['approved', 'shipping', 'completed'])
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('exports')
                    ->where('reference_type', 'sale')
                    ->whereColumn('reference_id', 'sales.id')
                    ->whereIn('status', ['completed']);
            });

        if (!empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        $sales = $query->with(['items'])->get();

        foreach ($sales as $sale) {
            // Get sale items grouped by product
            $saleQty = $sale->items->groupBy('product_id')->map(function ($items) {
                return $items->sum('quantity');
            });

            // Get export items grouped by product
            $exports = Export::where('reference_type', 'sale')
                ->where('reference_id', $sale->id)
                ->whereIn('status', ['pending', 'completed'])
                ->with('items')
                ->get();

            $exportQty = collect();
            foreach ($exports as $export) {
                foreach ($export->items as $item) {
                    $current = $exportQty->get($item->product_id, 0);
                    $exportQty->put($item->product_id, $current + $item->quantity);
                }
            }

            // Compare quantities
            $allProductIds = $saleQty->keys()->merge($exportQty->keys())->unique();
            $productMismatches = [];

            foreach ($allProductIds as $productId) {
                $sold = $saleQty->get($productId, 0);
                $exported = $exportQty->get($productId, 0);

                if ($sold != $exported) {
                    $productMismatches[] = [
                        'product_id' => $productId,
                        'sale_qty' => $sold,
                        'export_qty' => $exported,
                        'difference' => $sold - $exported,
                    ];
                }
            }

            if (!empty($productMismatches)) {
                $mismatches[] = [
                    'sale_id' => $sale->id,
                    'sale_code' => $sale->code,
                    'customer_name' => $sale->customer_name ?? 'N/A',
                    'date' => $sale->date?->format('d/m/Y'),
                    'status' => $sale->status,
                    'status_label' => $sale->status_label,
                    'product_mismatches' => $productMismatches,
                    'issue' => 'Số lượng xuất kho không khớp với đơn bán',
                ];
            }
        }

        return $mismatches;
    }

    /**
     * Find exports with status mismatches (orphan or rejected for active sales)
     */
    protected function findMismatchedExports(array $filters = []): array
    {
        $query = Export::where('reference_type', 'sale')
            ->whereIn('status', ['pending', 'completed', 'cancelled', 'rejected']);

        if (!empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        return $query->with(['warehouse', 'items'])
            ->orderBy('date', 'desc')
            ->get()
            ->filter(function ($export) {
                $sale = Sale::find($export->reference_id);
                if (!$sale) return false;

                // Case 1: Export is active (pending/completed) but Sale is cancelled
                if (in_array($export->status, ['pending', 'completed']) && $sale->status === 'cancelled') {
                    $export->issue_type = 'Phiếu xuất kho dư (đơn bán đã hủy)';
                    return true;
                }

                // Case 2: Export is inactive (cancelled/rejected) but Sale is active
                if (in_array($export->status, ['cancelled', 'rejected']) && in_array($sale->status, ['approved', 'shipping', 'completed'])) {
                    $export->issue_type = 'Phiếu xuất bị ' . ($export->status === 'rejected' ? 'từ chối' : 'hủy') . ' nhưng đơn bán vẫn đang hoạt động';
                    return true;
                }

                return false;
            })
            ->map(function ($export) {
                $sale = Sale::find($export->reference_id);
                return [
                    'export_id' => $export->id,
                    'export_code' => $export->code,
                    'sale_code' => $sale?->code ?? 'N/A',
                    'warehouse_name' => $export->warehouse?->name ?? 'N/A',
                    'date' => $export->date?->format('d/m/Y'),
                    'status' => $export->status,
                    'status_label' => $export->status_label,
                    'total_qty' => $export->total_qty,
                    'issue' => $export->issue_type,
                ];
            })
            ->values()
            ->toArray();
    }
}
