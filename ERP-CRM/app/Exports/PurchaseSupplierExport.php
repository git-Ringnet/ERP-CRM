<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseSupplierExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
            'Nhà cung cấp',
            'Số đơn',
            'Tổng giá trị (USD)',
            'Chiết khấu (USD)',
            'CP vận chuyển (VND)',
            'Thực trả (USD)',
            'Tỷ lệ CK'
        ];
    }

    public function map($row): array
    {
        return [
            $row['supplier'],
            $row['order_count'] . ' đơn',
            $row['total_amount_usd'],
            $row['total_discount'] / 25000,
            $row['total_shipping'],
            $row['total_paid_usd'],
            $row['discount_rate'] . '%'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
