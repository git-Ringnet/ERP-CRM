<?php

namespace App\Exports;

use App\Models\SaleItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class WarrantyExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        $query = SaleItem::query()
            ->select([
                'sale_items.*',
                'products.code as product_code',
                'sales.code as sale_code',
                'sales.date as sale_date',
                'sales.customer_name',
            ])
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereNotNull('sale_items.warranty_months')
            ->where('sale_items.warranty_months', '>', 0);

        // Apply filters
        if (!empty($this->filters['status'])) {
            $status = $this->filters['status'];
            $now = now()->toDateString();
            
            if ($status === SaleItem::WARRANTY_STATUS_ACTIVE) {
                $query->whereRaw('DATE_ADD(sale_items.warranty_start_date, INTERVAL sale_items.warranty_months MONTH) >= ?', [$now]);
            } elseif ($status === SaleItem::WARRANTY_STATUS_EXPIRED) {
                $query->whereRaw('DATE_ADD(sale_items.warranty_start_date, INTERVAL sale_items.warranty_months MONTH) < ?', [$now]);
            }
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereRaw('DATE_ADD(sale_items.warranty_start_date, INTERVAL sale_items.warranty_months MONTH) >= ?', [$this->filters['date_from']]);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereRaw('DATE_ADD(sale_items.warranty_start_date, INTERVAL sale_items.warranty_months MONTH) <= ?', [$this->filters['date_to']]);
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('products.code', 'like', "%{$search}%")
                  ->orWhere('sale_items.product_name', 'like', "%{$search}%")
                  ->orWhere('sales.customer_name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('sale_items.warranty_start_date', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Mã đơn hàng',
            'Ngày bán',
            'Khách hàng',
            'Mã sản phẩm',
            'Tên sản phẩm',
            'Số lượng',
            'Bảo hành (tháng)',
            'Ngày bắt đầu BH',
            'Ngày hết hạn BH',
            'Trạng thái',
            'Còn lại (ngày)',
        ];
    }

    public function map($item): array
    {
        $statusLabels = SaleItem::getWarrantyStatusLabels();
        
        return [
            $item->sale_code,
            $item->sale_date ? date('d/m/Y', strtotime($item->sale_date)) : '',
            $item->customer_name,
            $item->product_code,
            $item->product_name,
            $item->quantity,
            $item->warranty_months,
            $item->warranty_start_date ? $item->warranty_start_date->format('d/m/Y') : '',
            $item->warranty_end_date ? $item->warranty_end_date->format('d/m/Y') : '',
            $statusLabels[$item->warranty_status] ?? $item->warranty_status,
            $item->warranty_days_remaining,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
