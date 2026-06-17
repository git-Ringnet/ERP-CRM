<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseCancelledExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
            'Mã PO',
            'Ngày đặt',
            'Nhà cung cấp',
            'Mã SO liên quan',
            'SI (Partner)',
            'End User',
            'Salesperson',
            'Tổng tiền (USD)',
            'Tổng tiền (VND)',
            'Ghi chú / Lý do hủy'
        ];
    }

    public function map($row): array
    {
        return [
            $row['code'],
            $row['order_date'],
            $row['supplier_name'],
            $row['linked_so_codes'],
            $row['linked_partner_names'],
            $row['linked_end_user_names'],
            $row['linked_salesperson_names'],
            $row['total_usd'],
            $row['total_vnd'],
            $row['note']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
