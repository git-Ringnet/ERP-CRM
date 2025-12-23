<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventorySummaryExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $inventories;

    public function __construct(Collection $inventories)
    {
        $this->inventories = $inventories;
    }

    public function collection()
    {
        return $this->inventories->map(function ($inventory, $index) {
            return [
                'stt' => $index + 1,
                'product_code' => $inventory->product->code ?? '',
                'product_name' => $inventory->product->name ?? '',
                'warehouse' => $inventory->warehouse->name ?? '',
                'stock' => $inventory->stock,
                'min_stock' => $inventory->min_stock,
                'avg_cost' => $inventory->avg_cost,
                'total_value' => $inventory->stock * $inventory->avg_cost,
                'status' => $inventory->stock <= $inventory->min_stock ? 'Tồn thấp' : 'Bình thường',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'STT',
            'Mã SP',
            'Tên sản phẩm',
            'Kho',
            'Tồn kho',
            'Tồn tối thiểu',
            'Giá vốn TB',
            'Giá trị',
            'Trạng thái',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4472C4']]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 15,
            'C' => 30,
            'D' => 20,
            'E' => 12,
            'F' => 12,
            'G' => 15,
            'H' => 18,
            'I' => 12,
        ];
    }
}
