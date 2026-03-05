<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Collection;

/**
 * DashboardExport handles exporting dashboard data to Excel/CSV
 * Requirements: 8.2, 8.5, 8.6, 8.7, 8.8
 */
class DashboardExport implements WithMultipleSheets
{
    protected $data;
    protected $filters;
    protected $format;

    public function __construct(array $data, array $filters, string $format = 'excel')
    {
        $this->data = $data;
        $this->filters = $filters;
        $this->format = $format;
    }

    /**
     * Return array of sheets for Excel export
     * For CSV, only the first sheet (metrics) will be exported
     */
    public function sheets(): array
    {
        $sheets = [
            new DashboardMetricsSheet($this->data, $this->filters),
        ];

        // Only include additional sheets for Excel format
        if ($this->format === 'excel') {
            if (isset($this->data['sales_analysis'])) {
                $sheets[] = new DashboardSalesSheet($this->data['sales_analysis']);
            }
            
            if (isset($this->data['purchase_analysis'])) {
                $sheets[] = new DashboardPurchaseSheet($this->data['purchase_analysis']);
            }
            
            if (isset($this->data['inventory_analysis'])) {
                $sheets[] = new DashboardInventorySheet($this->data['inventory_analysis']);
            }
        }

        return $sheets;
    }
}

/**
 * Metrics sheet - main KPIs and metrics
 */
class DashboardMetricsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $data;
    protected $filters;

    public function __construct(array $data, array $filters)
    {
        $this->data = $data;
        $this->filters = $filters;
    }

    public function title(): string
    {
        return 'Chỉ Số Chính';
    }

    public function collection()
    {
        $metrics = $this->data['metrics'] ?? [];
        
        return collect([
            [
                'metric' => 'Doanh Thu',
                'current' => $metrics['revenue']['current'] ?? 0,
                'previous' => $metrics['revenue']['previous'] ?? 0,
                'growth_rate' => $metrics['revenue']['growth_rate'] ?? 0,
            ],
            [
                'metric' => 'Lợi Nhuận',
                'current' => $metrics['profit']['current'] ?? 0,
                'previous' => $metrics['profit']['previous'] ?? 0,
                'growth_rate' => $metrics['profit']['growth_rate'] ?? 0,
            ],
            [
                'metric' => 'Tỷ Suất Lợi Nhuận (%)',
                'current' => $metrics['profit_margin'] ?? 0,
                'previous' => null,
                'growth_rate' => null,
            ],
            [
                'metric' => 'Chi Phí Mua Hàng',
                'current' => $metrics['purchase_cost']['current'] ?? 0,
                'previous' => $metrics['purchase_cost']['previous'] ?? 0,
                'growth_rate' => $metrics['purchase_cost']['growth_rate'] ?? 0,
            ],
            [
                'metric' => 'Giá Trị Tồn Kho',
                'current' => $metrics['inventory_value'] ?? 0,
                'previous' => null,
                'growth_rate' => null,
            ],
            [
                'metric' => 'Vòng Quay Kho',
                'current' => $metrics['inventory_turnover'] ?? 0,
                'previous' => null,
                'growth_rate' => null,
            ],
            [
                'metric' => 'Số Đơn Bán Hàng',
                'current' => $metrics['sales_count']['current'] ?? 0,
                'previous' => $metrics['sales_count']['previous'] ?? 0,
                'growth_rate' => $metrics['sales_count']['growth_rate'] ?? 0,
            ],
            [
                'metric' => 'Số Đơn Mua Hàng',
                'current' => $metrics['purchase_orders_count']['current'] ?? 0,
                'previous' => $metrics['purchase_orders_count']['previous'] ?? 0,
                'growth_rate' => $metrics['purchase_orders_count']['growth_rate'] ?? 0,
            ],
        ]);
    }

    public function headings(): array
    {
        $periodLabel = $this->getPeriodLabel();
        $generatedAt = now()->format('d/m/Y H:i:s');
        
        return [
            ['BÁO CÁO DASHBOARD KINH DOANH'],
            ['Kỳ báo cáo: ' . $periodLabel],
            ['Ngày xuất: ' . $generatedAt],
            [],
            ['Chỉ Số', 'Giá Trị Hiện Tại', 'Giá Trị Kỳ Trước', 'Tăng Trưởng (%)'],
        ];
    }

    public function map($row): array
    {
        return [
            $row['metric'],
            $row['current'],
            $row['previous'] ?? 'N/A',
            $row['growth_rate'] !== null ? number_format($row['growth_rate'], 2, ',', '.') : 'N/A',
        ];
    }

    private function getPeriodLabel(): string
    {
        $periodType = $this->filters['period_type'] ?? 'custom';
        $startDate = $this->filters['start_date'] ?? '';
        $endDate = $this->filters['end_date'] ?? '';
        
        $labels = [
            'today' => 'Hôm nay',
            'week' => 'Tuần này',
            'month' => 'Tháng này',
            'quarter' => 'Quý này',
            'year' => 'Năm này',
        ];
        
        if (isset($labels[$periodType])) {
            return $labels[$periodType];
        }
        
        if ($startDate && $endDate) {
            return "Từ {$startDate} đến {$endDate}";
        }
        
        return 'Tùy chỉnh';
    }
}

/**
 * Sales analysis sheet
 */
class DashboardSalesSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Phân Tích Bán Hàng';
    }

    public function collection()
    {
        $rows = collect([
            ['Thống Kê Bán Hàng', '', '', ''],
            ['Đơn hoàn thành', $this->data['completed_count'] ?? 0, '', ''],
            ['Đơn chờ xử lý', $this->data['pending_count'] ?? 0, '', ''],
            ['Giá trị trung bình', $this->data['average_value'] ?? 0, '', ''],
            [],
            ['Top 10 Sản Phẩm Bán Chạy', '', '', ''],
            ['STT', 'Tên Sản Phẩm', 'Số Lượng', 'Doanh Thu'],
        ]);

        if (isset($this->data['top_products'])) {
            foreach ($this->data['top_products'] as $index => $product) {
                $rows->push([
                    $index + 1,
                    $product['name'] ?? 'N/A',
                    $product['quantity'] ?? 0,
                    $product['revenue'] ?? 0,
                ]);
            }
        }

        $rows->push([]);
        $rows->push(['Top 10 Khách Hàng', '', '', '']);
        $rows->push(['STT', 'Tên Khách Hàng', 'Số Đơn', 'Tổng Doanh Thu']);

        if (isset($this->data['top_customers'])) {
            foreach ($this->data['top_customers'] as $index => $customer) {
                $rows->push([
                    $index + 1,
                    $customer['name'] ?? 'N/A',
                    $customer['orders_count'] ?? 0,
                    $customer['revenue'] ?? 0,
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [];
    }
}

/**
 * Purchase analysis sheet
 */
class DashboardPurchaseSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Phân Tích Mua Hàng';
    }

    public function collection()
    {
        $rows = collect([
            ['Thống Kê Mua Hàng', '', '', ''],
            ['Tổng đơn mua', $this->data['total_count'] ?? 0, '', ''],
            ['Đơn hoàn thành', $this->data['completed_count'] ?? 0, '', ''],
            ['Đơn chờ xử lý', $this->data['pending_count'] ?? 0, '', ''],
            ['Giá trị trung bình', $this->data['average_value'] ?? 0, '', ''],
            [],
            ['Top 10 Nhà Cung Cấp', '', '', ''],
            ['STT', 'Tên Nhà Cung Cấp', 'Số Đơn', 'Tổng Chi Phí'],
        ]);

        if (isset($this->data['top_suppliers'])) {
            foreach ($this->data['top_suppliers'] as $index => $supplier) {
                $rows->push([
                    $index + 1,
                    $supplier['name'] ?? 'N/A',
                    $supplier['orders_count'] ?? 0,
                    $supplier['cost'] ?? 0,
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [];
    }
}

/**
 * Inventory analysis sheet
 */
class DashboardInventorySheet implements FromCollection, WithHeadings, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Phân Tích Tồn Kho';
    }

    public function collection()
    {
        $rows = collect([
            ['Thống Kê Tồn Kho', '', '', ''],
            ['Tổng giá trị tồn kho', $this->data['total_value'] ?? 0, '', ''],
            ['Số sản phẩm', $this->data['unique_products'] ?? 0, '', ''],
            ['Tổng số lượng', $this->data['total_quantity'] ?? 0, '', ''],
            ['Vòng quay kho', $this->data['turnover_ratio'] ?? 0, '', ''],
            ['Sản phẩm tồn kho thấp', $this->data['low_stock_count'] ?? 0, '', ''],
            ['Sản phẩm tồn kho cao', $this->data['overstock_count'] ?? 0, '', ''],
        ]);

        return $rows;
    }

    public function headings(): array
    {
        return [];
    }
}
