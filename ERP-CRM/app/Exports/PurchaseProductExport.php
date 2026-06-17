<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseProductExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'Sản phẩm (Part #)',
            'Tên sản phẩm',
            'SL nhập',
            'Giá TB (USD)',
            'Tổng giá trị (USD)',
            'Giá kho TB (VND)',
            'CP phục vụ (VND)',
            'Số NCC',
            'Các nhà cung cấp'
        ];
    }

    public function map($row): array
    {
        return [
            $row['product_code'],
            $row['product_name'],
            $row['total_quantity'],
            $row['avg_purchase_price_usd'],
            $row['total_value_usd'],
            $row['avg_warehouse_price'],
            $row['total_service_cost'],
            $row['supplier_count'],
            $row['supplier_names']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
