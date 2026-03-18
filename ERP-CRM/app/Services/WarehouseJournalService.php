<?php

namespace App\Services;

use App\Models\Import;
use App\Models\Export;
use App\Models\Transfer;
use App\Models\Inventory;
use App\Models\WarehouseJournalEntry;
use Illuminate\Support\Facades\Auth;

class WarehouseJournalService
{
    /**
     * Default account mappings (can be overridden by Settings).
     */
    protected array $defaultAccounts = [
        'import_from_supplier' => ['debit' => '156', 'credit' => '331'],
        'import_direct'        => ['debit' => '156', 'credit' => '111'],
        'export_project'       => ['debit' => '621', 'credit' => '156'],
        'export_liquidation'   => ['debit' => '811', 'credit' => '156'],
        'transfer_internal'    => ['debit' => '156', 'credit' => '156'],
    ];

    /**
     * Get account mapping for a transaction type.
     */
    protected function getAccounts(string $type): array
    {
        return $this->defaultAccounts[$type] ?? ['debit' => 'N/A', 'credit' => 'N/A'];
    }

    /**
     * Create journal entry for an Import action.
     */
    public function createForImport(Import $import, string $action = 'create'): WarehouseJournalEntry
    {
        $actionLabels = [
            'create' => 'Tạo mới',
            'update' => 'Cập nhật',
            'approve' => 'Đã duyệt',
            'reject'  => 'Từ chối',
            'delete'  => 'Xóa phiếu',
        ];
        $label = $actionLabels[$action] ?? $action;

        // Determine sub-type based on whether import has a supplier
        $subType = $import->supplier_id ? 'from_supplier' : 'direct';
        $accountKey = $import->supplier_id ? 'import_from_supplier' : 'import_direct';
        $accounts = $this->getAccounts($accountKey);

        // Calculate total amount: sum of (cost * quantity) for all items
        $totalAmount = $import->items->sum(function ($item) {
            return ($item->cost ?? 0) * $item->quantity;
        });

        // Add service costs
        $totalAmount += $import->total_service_cost ?? 0;

        $supplierName = $import->supplier ? $import->supplier->name : '';
        $warehouseName = $import->warehouse ? $import->warehouse->name : '';

        return WarehouseJournalEntry::create([
            'entry_date' => now(), // Ghi nhận thời điểm thực hiện hành động
            'reference_type' => 'import',
            'reference_id' => $import->id,
            'reference_code' => $import->code,
            'transaction_sub_type' => $subType,
            'debit_account' => $accounts['debit'],
            'credit_account' => $accounts['credit'],
            'amount' => $totalAmount,
            'action' => $action,
            'status' => $import->status,
            'description' => "[{$label}] Nhập kho {$warehouseName}" . ($supplierName ? " từ NCC {$supplierName}" : '') . " - Phiếu {$import->code}",
            'created_by' => Auth::user()->name ?? 'System',
        ]);
    }

    /**
     * Create journal entry for an Export action.
     */
    public function createForExport(Export $export, string $action = 'create'): WarehouseJournalEntry
    {
        $actionLabels = [
            'create' => 'Tạo mới',
            'update' => 'Cập nhật',
            'approve' => 'Đã duyệt',
            'reject'  => 'Từ chối',
            'delete'  => 'Xóa phiếu',
        ];
        $label = $actionLabels[$action] ?? $action;

        // Determine sub-type: check if any item is liquidation
        $hasLiquidation = $export->items->contains('is_liquidation', true);
        $subType = $hasLiquidation ? 'liquidation' : 'project';
        $accountKey = $hasLiquidation ? 'export_liquidation' : 'export_project';
        $accounts = $this->getAccounts($accountKey);

        // Calculate total amount: use average cost from inventory
        $totalAmount = 0;
        foreach ($export->items as $item) {
            $inventory = Inventory::where('product_id', $item->product_id)
                ->where('warehouse_id', $export->warehouse_id)
                ->first();
            $avgCost = $inventory ? $inventory->avg_cost : 0;
            $totalAmount += $avgCost * $item->quantity;
        }

        $warehouseName = $export->warehouse ? $export->warehouse->name : '';
        $projectName = $export->project ? $export->project->name : '';
        $customerName = $export->customer ? $export->customer->name : '';

        $desc = "[{$label}] Xuất kho {$warehouseName}";
        if ($projectName) $desc .= " cho DA {$projectName}";
        if ($customerName) $desc .= " - KH {$customerName}";
        $desc .= " - Phiếu {$export->code}";

        return WarehouseJournalEntry::create([
            'entry_date' => now(), // Ghi nhận thời điểm thực hiện hành động
            'reference_type' => 'export',
            'reference_id' => $export->id,
            'reference_code' => $export->code,
            'transaction_sub_type' => $subType,
            'debit_account' => $accounts['debit'],
            'credit_account' => $accounts['credit'],
            'amount' => $totalAmount,
            'action' => $action,
            'status' => $export->status,
            'description' => $desc,
            'created_by' => Auth::user()->name ?? 'System',
        ]);
    }

    /**
     * Create journal entry for a Transfer action.
     */
    public function createForTransfer(Transfer $transfer, string $action = 'create'): WarehouseJournalEntry
    {
        $actionLabels = [
            'create' => 'Tạo mới',
            'update' => 'Cập nhật',
            'approve' => 'Đã duyệt',
            'reject'  => 'Từ chối',
            'delete'  => 'Xóa phiếu',
        ];
        $label = $actionLabels[$action] ?? $action;

        $accounts = $this->getAccounts('transfer_internal');

        // Calculate total amount: use average cost from source warehouse
        $totalAmount = 0;
        foreach ($transfer->items as $item) {
            $inventory = Inventory::where('product_id', $item->product_id)
                ->where('warehouse_id', $transfer->from_warehouse_id)
                ->first();
            $avgCost = $inventory ? $inventory->avg_cost : 0;
            $totalAmount += $avgCost * $item->quantity;
        }

        $fromName = $transfer->fromWarehouse ? $transfer->fromWarehouse->name : '';
        $toName = $transfer->toWarehouse ? $transfer->toWarehouse->name : '';

        return WarehouseJournalEntry::create([
            'entry_date' => now(), // Ghi nhận thời điểm thực hiện hành động
            'reference_type' => 'transfer',
            'reference_id' => $transfer->id,
            'reference_code' => $transfer->code,
            'transaction_sub_type' => 'internal',
            'debit_account' => $accounts['debit'],
            'credit_account' => $accounts['credit'],
            'amount' => $totalAmount,
            'action' => $action,
            'status' => $transfer->status,
            'description' => "[{$label}] Chuyển kho từ {$fromName} đến {$toName} - Phiếu {$transfer->code}",
            'created_by' => Auth::user()->name ?? 'System',
        ]);
    }
}
