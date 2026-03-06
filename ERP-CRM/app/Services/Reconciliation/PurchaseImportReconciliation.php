<?php

namespace App\Services\Reconciliation;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Import;
use App\Models\ImportItem;
use Illuminate\Support\Facades\DB;

/**
 * PurchaseImportReconciliation - Đối soát Mua hàng ↔ Nhập kho
 * 
 * Checks:
 * 1. POs received/partial_received but no linked Import
 * 2. PO item quantities ≠ ImportItem quantities per product
 * 3. Imports referencing cancelled POs
 */
class PurchaseImportReconciliation
{
    /**
     * Run all reconciliation checks
     */
    public function run(array $filters = []): array
    {
        return [
            'missing_imports' => $this->findMissingImports($filters),
            'quantity_mismatches' => $this->findQuantityMismatches($filters),
            'mismatched_imports' => $this->findMismatchedImports($filters),
        ];
    }

    /**
     * Get summary counts
     */
    public function summary(array $filters = []): array
    {
        $results = $this->run($filters);
        return [
            'total_issues' => count($results['missing_imports']) + count($results['quantity_mismatches']) + count($results['mismatched_imports']),
            'missing_imports' => count($results['missing_imports']),
            'quantity_mismatches' => count($results['quantity_mismatches']),
            'mismatched_imports' => count($results['mismatched_imports']),
        ];
    }

    /**
     * Find POs that are received but have no linked import
     */
    protected function findMissingImports(array $filters = []): array
    {
        $query = PurchaseOrder::whereIn('status', ['received', 'partial_received'])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('imports')
                    ->where('reference_type', 'purchase_order')
                    ->whereColumn('reference_id', 'purchase_orders.id')
                    ->whereIn('status', ['completed']);
            });

        if (!empty($filters['date_from'])) {
            $query->where('order_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('order_date', '<=', $filters['date_to']);
        }

        return $query->with(['supplier', 'items'])
            ->orderBy('order_date', 'desc')
            ->get()
            ->map(function ($po) {
                return [
                    'po_id' => $po->id,
                    'po_code' => $po->code,
                    'supplier_name' => $po->supplier?->name ?? 'N/A',
                    'date' => $po->order_date?->format('d/m/Y'),
                    'status' => $po->status,
                    'status_label' => $po->status_label,
                    'total' => $po->total,
                    'total_items' => $po->items->sum('quantity'),
                    'issue' => 'Đã nhận hàng nhưng chưa hoàn thành nhập kho',
                ];
            })
            ->toArray();
    }

    /**
     * Find POs where imported quantities don't match PO quantities
     */
    protected function findQuantityMismatches(array $filters = []): array
    {
        $mismatches = [];

        $query = PurchaseOrder::whereIn('status', ['received', 'partial_received'])
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('imports')
                    ->where('reference_type', 'purchase_order')
                    ->whereColumn('reference_id', 'purchase_orders.id')
                    ->whereIn('status', ['completed']);
            });

        if (!empty($filters['date_from'])) {
            $query->where('order_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('order_date', '<=', $filters['date_to']);
        }

        $pos = $query->with(['items'])->get();

        foreach ($pos as $po) {
            // PO items grouped by product
            $poQty = $po->items->groupBy('product_id')->map(function ($items) {
                return $items->sum('quantity');
            });

            // Import items grouped by product
            $imports = Import::where('reference_type', 'purchase_order')
                ->where('reference_id', $po->id)
                ->whereIn('status', ['pending', 'completed'])
                ->with('items')
                ->get();

            $importQty = collect();
            foreach ($imports as $import) {
                foreach ($import->items as $item) {
                    $current = $importQty->get($item->product_id, 0);
                    $importQty->put($item->product_id, $current + $item->quantity);
                }
            }

            // Compare
            $allProductIds = $poQty->keys()->merge($importQty->keys())->unique();
            $productMismatches = [];

            foreach ($allProductIds as $productId) {
                $ordered = $poQty->get($productId, 0);
                $imported = $importQty->get($productId, 0);

                if ($ordered != $imported) {
                    $productMismatches[] = [
                        'product_id' => $productId,
                        'po_qty' => $ordered,
                        'import_qty' => $imported,
                        'difference' => $ordered - $imported,
                    ];
                }
            }

            if (!empty($productMismatches)) {
                $mismatches[] = [
                    'po_id' => $po->id,
                    'po_code' => $po->code,
                    'supplier_name' => $po->supplier?->name ?? 'N/A',
                    'date' => $po->order_date?->format('d/m/Y'),
                    'status' => $po->status,
                    'status_label' => $po->status_label,
                    'product_mismatches' => $productMismatches,
                    'issue' => 'Số lượng nhập kho không khớp với đơn mua',
                ];
            }
        }

        return $mismatches;
    }

    /**
     * Find imports with status mismatches (orphan or rejected for active POs)
     */
    protected function findMismatchedImports(array $filters = []): array
    {
        $query = Import::where('reference_type', 'purchase_order')
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
            ->filter(function ($import) {
                $po = PurchaseOrder::find($import->reference_id);
                if (!$po) return false;

                // Case 1: Import is active (pending/completed) but PO is cancelled
                if (in_array($import->status, ['pending', 'completed']) && $po->status === 'cancelled') {
                    $import->issue_type = 'Phiếu nhập kho dư (đơn mua đã hủy)';
                    return true;
                }

                // Case 2: Import is inactive (cancelled/rejected) but PO is active
                if (in_array($import->status, ['cancelled', 'rejected']) && in_array($po->status, ['received', 'partial_received'])) {
                    $import->issue_type = 'Phiếu nhập bị ' . ($import->status === 'rejected' ? 'từ chối' : 'hủy') . ' nhưng đơn mua vẫn đang hoạt động';
                    return true;
                }

                return false;
            })
            ->map(function ($import) {
                $po = PurchaseOrder::find($import->reference_id);
                return [
                    'import_id' => $import->id,
                    'import_code' => $import->code,
                    'po_code' => $po?->code ?? 'N/A',
                    'warehouse_name' => $import->warehouse?->name ?? 'N/A',
                    'date' => $import->date?->format('d/m/Y'),
                    'status' => $import->status,
                    'status_label' => $import->status_label,
                    'total_qty' => $import->total_qty,
                    'issue' => $import->issue_type,
                ];
            })
            ->values()
            ->toArray();
    }
}
