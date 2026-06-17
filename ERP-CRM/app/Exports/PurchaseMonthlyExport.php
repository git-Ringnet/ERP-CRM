<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseMonthlyExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
            'Tháng',
            'Số đơn',
            'Tổng giá trị (USD)',
            'Chiết khấu (VND)',
            'CP vận chuyển (VND)',
            'Thực trả (USD)',
            'So với tháng trước'
        ];
    }

    public function map($row): array
    {
        return [
            $row['month'],
            $row['order_count'],
            $row['total_amount_usd'],
            $row['total_discount'],
            $row['total_shipping'],
            $row['total_paid_usd'],
            $row['change'] !== null ? ($row['change'] >= 0 ? '+' : '') . $row['change'] . '%' : '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
