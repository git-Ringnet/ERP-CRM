<?php

namespace App\Http\Controllers;

use App\Services\Reconciliation\SaleExportReconciliation;
use App\Services\Reconciliation\PurchaseImportReconciliation;
use App\Services\Reconciliation\InventoryReconciliation;
use App\Services\Reconciliation\DebtPaymentReconciliation;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class ReconciliationController extends Controller
{
    protected SaleExportReconciliation $saleExportReconciliation;
    protected PurchaseImportReconciliation $purchaseImportReconciliation;
    protected InventoryReconciliation $inventoryReconciliation;
    protected DebtPaymentReconciliation $debtPaymentReconciliation;

    public function __construct(
        SaleExportReconciliation $saleExportReconciliation,
        PurchaseImportReconciliation $purchaseImportReconciliation,
        InventoryReconciliation $inventoryReconciliation,
        DebtPaymentReconciliation $debtPaymentReconciliation
    ) {
        $this->saleExportReconciliation = $saleExportReconciliation;
        $this->purchaseImportReconciliation = $purchaseImportReconciliation;
        $this->inventoryReconciliation = $inventoryReconciliation;
        $this->debtPaymentReconciliation = $debtPaymentReconciliation;
    }

    /**
     * Dashboard - Summary of all reconciliation checks
     */
    public function index()
    {
        $saleExportSummary = $this->saleExportReconciliation->summary();
        $purchaseImportSummary = $this->purchaseImportReconciliation->summary();
        $inventorySummary = $this->inventoryReconciliation->summary();
        $debtPaymentSummary = $this->debtPaymentReconciliation->summary();

        $totalIssues = $saleExportSummary['total_issues']
            + $purchaseImportSummary['total_issues']
            + $inventorySummary['total_issues']
            + $debtPaymentSummary['total_issues'];

        return view('reconciliation.index', compact(
            'saleExportSummary',
            'purchaseImportSummary',
            'inventorySummary',
            'debtPaymentSummary',
            'totalIssues'
        ));
    }

    /**
     * Sale ↔ Export reconciliation detail
     */
    public function saleExport(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to']);
        $results = $this->saleExportReconciliation->run($filters);

        return view('reconciliation.sale-export', compact('results', 'filters'));
    }

    /**
     * PurchaseOrder ↔ Import reconciliation detail
     */
    public function purchaseImport(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to']);
        $results = $this->purchaseImportReconciliation->run($filters);

        return view('reconciliation.purchase-import', compact('results', 'filters'));
    }

    /**
     * Inventory reconciliation detail
     */
    public function inventory(Request $request)
    {
        $filters = $request->only(['warehouse_id']);
        $results = $this->inventoryReconciliation->run($filters);
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();

        return view('reconciliation.inventory', compact('results', 'filters', 'warehouses'));
    }

    /**
     * Debt ↔ Payment reconciliation detail
     */
    public function debtPayment(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'customer_id', 'supplier_id', 'party']);
        $results = $this->debtPaymentReconciliation->run($filters);

        return view('reconciliation.debt-payment', compact('results', 'filters'));
    }
}
