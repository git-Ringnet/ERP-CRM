<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class SaleReportController extends Controller
{
    /**
     * Display the sales report dashboard.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', \App\Models\SaleReport::class);
        
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $customerId = $request->input('customer_id');
        $productId = $request->input('product_id');
        $userId = $request->input('user_id');

        // Summary statistics
        $stats = $this->getSummaryStats($dateFrom, $dateTo, $customerId, $productId);

        // Customer report
        $customerReport = $this->getCustomerReport($dateFrom, $dateTo, $customerId);

        // Product report
        $productReport = $this->getProductReport($dateFrom, $dateTo, $productId);

        // Monthly report
        $monthlyReport = $this->getMonthlyReport($dateFrom, $dateTo);

        // Profit analysis
        $profitAnalysis = $this->getProfitAnalysis($dateFrom, $dateTo);

        // Margin report (new)
        $marginReport = $this->getMarginReport($dateFrom, $dateTo, $customerId, $userId);

        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('sale-reports.index', compact(
            'stats', 'customerReport', 'productReport', 'monthlyReport',
            'profitAnalysis', 'marginReport', 'customers', 'products', 'users',
            'dateFrom', 'dateTo', 'customerId', 'productId', 'userId'
        ));
    }

    /**
     * Get margin report data — matches the Excel template for Misa reconciliation.
     * Each row = one Sale order.
     */
    private function getMarginReport($dateFrom, $dateTo, $customerId = null, $userId = null): array
    {
        $query = Sale::with(['customer', 'user', 'items.product'])
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->whereIn('status', ['pending', 'approved', 'shipping', 'completed']);

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $sales = $query->orderBy('date', 'asc')->get();

        $report = [];
        foreach ($sales as $index => $sale) {
            // Get main product code (first item's product code)
            $mainProductCode = '';
            if ($sale->items->isNotEmpty()) {
                $firstItem = $sale->items->first();
                $mainProductCode = $firstItem->product->code ?? '';
            }

            // Calculate margin from P/L data
            $costTotal = $sale->items->sum('cost_total');
            $revenueTotal = $sale->total;
            
            // Total expenses from items
            $totalExpenses = 0;
            foreach ($sale->items as $item) {
                $totalExpenses += $item->total_expenses;
            }

            $margin = $revenueTotal - $costTotal - $totalExpenses;
            $marginPercent = $revenueTotal > 0 ? ($margin / $revenueTotal) * 100 : 0;

            // Payment info
            $paidAmount = (float) $sale->paid_amount;
            $paymentPercent = $revenueTotal > 0 ? ($paidAmount / $revenueTotal) * 100 : 0;

            $report[] = [
                'sale_id' => $sale->id,
                'stt' => $index + 1,
                'customer_name' => $sale->customer_name ?: ($sale->customer->name ?? ''),
                'invoice_number' => $sale->code,
                'invoice_date' => $sale->date ? $sale->date->format('d/m/Y') : '',
                'brand' => '', // Manual field — not in DB yet
                'license' => '', // Manual field — not in DB yet
                'product_type' => '', // Manual field — not in DB yet
                'main_product_code' => $mainProductCode,
                'margin' => round($margin),
                'margin_percent' => round($marginPercent, 1),
                'salesperson' => $sale->user->name ?? '',
                'paid_amount' => round($paidAmount),
                'payment_percent' => round($paymentPercent, 1),
                'payment_status_text' => $paymentPercent >= 100 ? 'Đã thanh toán' : ($paidAmount > 0 ? 'Thanh toán một phần' : 'Chưa thanh toán'),
            ];
        }

        return $report;
    }

    private function getSummaryStats($dateFrom, $dateTo, $customerId = null, $productId = null): array
    {
        $query = Sale::whereBetween('date', [$dateFrom, $dateTo])
            ->whereIn('status', ['approved', 'shipping', 'completed']); // Only include confirmed orders

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($productId) {
            $query->whereHas('items', function ($q) use ($productId) {
                $q->where('product_id', $productId);
            });
        }

        // Clone query for sums to avoid issues if we needed to group (not needed here but good practice)
        
        $totalOrders = $query->count();
        $totalRevenue = $query->sum('total');
        $totalMargin = $query->sum('margin');

        // Cost = Revenue - Margin (margin already net of COGS + expenses)
        $totalCalculatedCost = $totalRevenue - $totalMargin;

        $marginPercent = $totalRevenue > 0 ? ($totalMargin / $totalRevenue) * 100 : 0;

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue ?? 0,
            'total_cost' => $totalCalculatedCost ?? 0,
            'total_profit' => $totalMargin ?? 0,
            'margin_percent' => round($marginPercent, 1),
        ];
    }

    private function getCustomerReport($dateFrom, $dateTo, $customerId = null): array
    {
        $query = Sale::select(
                'customer_id',
                'customer_name',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('SUM(margin) as total_profit')
            )
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->whereIn('status', ['approved', 'shipping', 'completed'])
            ->groupBy('customer_id', 'customer_name');

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $results = $query->orderByDesc('total_revenue')->get();

        return $results->map(function ($item) {
            $marginPercent = $item->total_revenue > 0 
                ? ($item->total_profit / $item->total_revenue) * 100 
                : 0;

            return [
                'customer' => $item->customer_name,
                'order_count' => $item->order_count,
                'total_revenue' => $item->total_revenue,
                'total_profit' => $item->total_profit,
                'margin_percent' => round($marginPercent, 1),
            ];
        })->toArray();
    }

    private function getProductReport($dateFrom, $dateTo, $productId = null): array
    {
        $query = SaleItem::select(
                'sale_items.product_id',
                'sale_items.product_name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.total * sales.exchange_rate) as total_revenue'),
                DB::raw('SUM((sale_items.total * sales.exchange_rate) - sale_items.cost_total) as total_profit')
            )
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereIn('sales.status', ['approved', 'shipping', 'completed'])
            ->whereBetween('sales.date', [$dateFrom, $dateTo])
            ->groupBy('sale_items.product_id', 'sale_items.product_name');

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $results = $query->orderByDesc('total_revenue')->get();

        return $results->map(function ($item) {
            $marginPercent = $item->total_revenue > 0 
                ? ($item->total_profit / $item->total_revenue) * 100 
                : 0;

            return [
                'product' => $item->product_name,
                'total_quantity' => $item->total_quantity,
                'total_revenue' => $item->total_revenue,
                'total_profit' => $item->total_profit, // Note: This is Gross Profit per item, excludes sale-level expenses like shipping
                'margin_percent' => round($marginPercent, 1),
            ];
        })->toArray();
    }

    private function getMonthlyReport($dateFrom, $dateTo): array
    {
        $results = Sale::select(
                DB::raw("DATE_FORMAT(date, '%Y-%m') as month"),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('SUM(margin) as total_profit')
            )
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->whereIn('status', ['approved', 'shipping', 'completed'])
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();

        $report = [];
        $previousRevenue = null;

        foreach ($results as $item) {
            $growth = null;
            if ($previousRevenue !== null && $previousRevenue > 0) {
                // Determine growth compared to NEXT row (which is previous month due to desc order)
                // Actually to do this correctly in a loop, we usually process asc or look ahead.
                // Let's just keep it simple or fix logic if needed. 
                // Since it is ordered DESC, previous loop iteration was the NEXT month.
                // So comparison is tricky here without reordering.
                // Let's simpler: just calculate margin %
            }

            $marginPercent = $item->total_revenue > 0 
                ? ($item->total_profit / $item->total_revenue) * 100 
                : 0;

            $report[] = [
                'month' => $item->month,
                'order_count' => $item->order_count,
                'total_revenue' => $item->total_revenue,
                'total_profit' => $item->total_profit,
                'margin_percent' => round($marginPercent, 1),
            ];
        }

        return $report;
    }

    private function getProfitAnalysis($dateFrom, $dateTo): array
    {
        $totals = Sale::whereBetween('date', [$dateFrom, $dateTo])
            ->whereIn('status', ['approved', 'shipping', 'completed'])
            ->selectRaw('
                SUM(subtotal) as subtotal,
                SUM(discount) as discount_percent_sum, -- This is meaningless
                SUM(total) as total_revenue,
                SUM(cost) as total_expenses,
                SUM(margin) as total_profit
            ')
            ->first();

        // Calculate COGS (Cost of Goods Sold)
        // Profit = Revenue - COGS - Expenses
        // => COGS = Revenue - Profit - Expenses
        
        $revenue = $totals->total_revenue ?? 0;
        $profit = $totals->total_profit ?? 0;
        $expenses = $totals->total_expenses ?? 0;
        $cogs = $revenue - $profit - $expenses;
        
        $base = $revenue > 0 ? $revenue : 1;

        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'expenses' => $expenses,
            'profit' => $profit,
            'breakdown' => [
                ['name' => 'Giá vốn hàng bán (COGS)', 'value' => $cogs, 'rate' => round(($cogs / $base) * 100, 1), 'color' => 'text-blue-600'],
                ['name' => 'Chi phí bán hàng', 'value' => $expenses, 'rate' => round(($expenses / $base) * 100, 1), 'color' => 'text-yellow-600'],
                ['name' => 'Lợi nhuận ròng', 'value' => $profit, 'rate' => round(($profit / $base) * 100, 1), 'color' => 'text-green-600'],
            ]
        ];
    }

    public function export(Request $request)
    {
        $this->authorize('export', \App\Models\SaleReport::class);
        
        // Export logic here (Reuse existing export or create new one)
        return redirect()->back()->with('warning', 'Chức năng xuất báo cáo đang được phát triển.');
    }

    /**
     * Export Margin Report to Excel (CSV with BOM for Vietnamese chars).
     */
    public function exportMargin(Request $request)
    {
        $this->authorize('export', \App\Models\SaleReport::class);

        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $customerId = $request->input('customer_id');
        $userId = $request->input('user_id');

        $marginReport = $this->getMarginReport($dateFrom, $dateTo, $customerId, $userId);

        $fromFormatted = date('d/m/Y', strtotime($dateFrom));
        $toFormatted = date('d/m/Y', strtotime($dateTo));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Báo cáo Margin');

        // ── Styles ──
        $headerFill = [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '1a3a5c'],
        ];
        $headerFont = [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 10,
            'name' => 'Arial',
        ];
        $titleFont = [
            'bold' => true,
            'size' => 13,
            'name' => 'Arial',
        ];
        $borderAll = [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ];

        // ── Row 1: Title ──
        $sheet->mergeCells('A1:M1');
        $sheet->setCellValue('A1', "Báo cáo Lãi/Lỗ (Margin) theo đơn hàng tháng .../(Từ {$fromFormatted} đến {$toFormatted})");
        $sheet->getStyle('A1')->getFont()->applyFromArray($titleFont);

        // ── Row 3: Header ──
        $headers = [
            'A3' => 'STT',
            'B3' => 'Tên khách hàng',
            'C3' => "Số Hóa đơn tài chính\n(hoặc đối với hàng khởi tạo theo phần mềm)",
            'D3' => 'Ngày xuất hóa đơn',
            'E3' => 'HÃNG',
            'F3' => 'License',
            'G3' => 'Loại hàng',
            'H3' => 'Mã Hàng hóa chính',
            'I3' => 'Margin',
            'J3' => 'Margin %',
            'K3' => 'NV Kinh doanh',
            'L3' => "Tổng Tiền khách hàng\nđã thanh toán",
            'M3' => "Tỷ lệ khách hàng\nđã thanh toán (%)",
        ];

        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }

        // Apply header style
        $headerRange = 'A3:M3';
        $sheet->getStyle($headerRange)->applyFromArray([
            'fill' => $headerFill,
            'font' => $headerFont,
            'borders' => $borderAll,
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(45);

        // ── Column widths ──
        $widths = ['A' => 5, 'B' => 28, 'C' => 20, 'D' => 14, 'E' => 10, 'F' => 8, 'G' => 22, 'H' => 14, 'I' => 16, 'J' => 10, 'K' => 18, 'L' => 20, 'M' => 16];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // ── Data rows ──
        $row = 4;
        foreach ($marginReport as $data) {
            $sheet->setCellValue("A{$row}", $data['stt']);
            $sheet->setCellValue("B{$row}", $data['customer_name']);
            $sheet->setCellValue("C{$row}", $data['invoice_number']);
            $sheet->setCellValue("D{$row}", $data['invoice_date']);
            $sheet->setCellValue("E{$row}", $data['brand']);
            $sheet->setCellValue("F{$row}", $data['license']);
            $sheet->setCellValue("G{$row}", $data['product_type']);
            $sheet->setCellValue("H{$row}", $data['main_product_code']);
            $sheet->setCellValue("I{$row}", $data['margin']);
            $sheet->setCellValue("J{$row}", $data['margin_percent'] / 100);
            $sheet->setCellValue("K{$row}", $data['salesperson']);

            if ($data['paid_amount'] > 0) {
                $sheet->setCellValue("L{$row}", $data['paid_amount']);
            } else {
                $sheet->setCellValue("L{$row}", 'Chưa thanh toán');
            }

            $sheet->setCellValue("M{$row}", $data['payment_percent'] / 100);

            // Format numbers
            $sheet->getStyle("I{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("J{$row}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle("L{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("M{$row}")->getNumberFormat()->setFormatCode('0%');

            // Margin color: red if negative, green if positive
            if ($data['margin'] < 0) {
                $sheet->getStyle("I{$row}")->getFont()->getColor()->setRGB('CC0000');
            } else {
                $sheet->getStyle("I{$row}")->getFont()->getColor()->setRGB('006600');
            }

            // Alignment
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("L{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("M{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Alternate row colors
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:M{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle("A{$row}:M{$row}")->getFill()->getStartColor()->setRGB('F2F7FB');
            }

            $row++;
        }

        // Data borders
        $lastRow = $row - 1;
        if ($lastRow >= 4) {
            $sheet->getStyle("A4:M{$lastRow}")->applyFromArray([
                'borders' => $borderAll,
                'font' => ['size' => 10, 'name' => 'Arial'],
            ]);
        }

        // ── Download ──
        $filename = 'Bao_cao_Margin_' . date('Ymd', strtotime($dateFrom)) . '_' . date('Ymd', strtotime($dateTo)) . '.xlsx';

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
