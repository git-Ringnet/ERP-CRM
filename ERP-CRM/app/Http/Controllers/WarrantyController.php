<?php

namespace App\Http\Controllers;

use App\Exports\WarrantyExport;
use App\Models\SaleItem;
use App\Services\WarrantyService;
use App\Services\WarrantyReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class WarrantyController extends Controller
{
    protected WarrantyService $warrantyService;
    protected WarrantyReportService $reportService;

    public function __construct(WarrantyService $warrantyService, WarrantyReportService $reportService)
    {
        $this->warrantyService = $warrantyService;
        $this->reportService = $reportService;
    }

    /**
     * Display warranty tracking list
     * Requirements: 3.1, 3.2
     */
    public function index(Request $request)
    {
        $filters = [
            'status' => $request->get('status'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => $request->get('search'),
            'customer_id' => $request->get('customer_id'),
            'product_id' => $request->get('product_id'),
            'per_page' => 20,
        ];

        $warranties = $this->warrantyService->getWarrantyList($filters);
        $statusLabels = SaleItem::getWarrantyStatusLabels();
        $statusColors = SaleItem::getWarrantyStatusColors();

        return view('warranties.index', compact('warranties', 'filters', 'statusLabels', 'statusColors'));
    }

    /**
     * Display expiring warranties
     * Requirements: 4.1, 4.2
     */
    public function expiring(Request $request)
    {
        $days = $request->get('days', 30);
        $warranties = $this->warrantyService->getExpiringWarranties($days);
        $statusLabels = SaleItem::getWarrantyStatusLabels();

        return view('warranties.expiring', compact('warranties', 'days', 'statusLabels'));
    }


    /**
     * Display warranty detail
     * Requirements: 4.5
     */
    public function show(SaleItem $saleItem)
    {
        $saleItem->load(['sale.customer', 'product']);
        $statusLabels = SaleItem::getWarrantyStatusLabels();
        $statusColors = SaleItem::getWarrantyStatusColors();

        return view('warranties.show', compact('saleItem', 'statusLabels', 'statusColors'));
    }

    /**
     * Display warranty report
     * Requirements: 5.1, 5.2, 5.3
     */
    public function report(Request $request)
    {
        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'expiring_days' => $request->get('expiring_days', 30),
        ];

        $summary = $this->reportService->getSummaryReport($filters);
        $byCustomer = $this->reportService->getReportByCustomer($filters);
        $byProduct = $this->reportService->getReportByProduct($filters);

        return view('warranties.report', compact('summary', 'byCustomer', 'byProduct', 'filters'));
    }

    /**
     * Export warranty data to Excel
     * Requirements: 5.4
     */
    public function export(Request $request)
    {
        $filters = [
            'status' => $request->get('status'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => $request->get('search'),
        ];

        $filename = 'warranty_report_' . date('Y-m-d_His') . '.xlsx';
        
        return Excel::download(new WarrantyExport($filters), $filename);
    }
}
