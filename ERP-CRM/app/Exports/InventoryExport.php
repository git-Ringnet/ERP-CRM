<?php

namespace App\Exports;

use App\Models\Inventory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Inventory::with(['product', 'warehouse'])
            ->orderBy('product_id')
            ->orderBy('warehouse_id');

        // Apply filters
        if (!empty($this->filters['warehouse_id'])) {
            $query->where('warehouse_id', $this->filters['warehouse_id']);
        }
        if (!empty($this->filters['product_id'])) {
            $query->where('product_id', $this->filters['product_id']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Mã sản phẩm',
            'Sản phẩm',
            'Kho',
            'Tồn kho',
            'Hạn sử dụng',
            'Giá trị (VNĐ)',
            'Trạng thái',
        ];
    }

    public function map($inventory): array
    {
        $value = $inventory->stock * $inventory->avg_cost;
        
        $status = 'Bình thường';
        if ($inventory->stock == 0) {
            $status = 'Hết hàng';
        } elseif ($inventory->is_low_stock) {
            $status = 'Sắp hết';
        }

        return [
            $inventory->product->code ?? '',
            $inventory->product->name ?? '',
            $inventory->warehouse->name ?? '',
            $inventory->stock,
            $inventory->expiry_date ? $inventory->expiry_date->format('d/m/Y') : '',
            number_format($value, 0, ',', '.'),
            $status,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '27ae60']]],
        ];
    }
}
