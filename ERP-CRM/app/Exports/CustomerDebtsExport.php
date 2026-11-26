<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerDebtsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected Collection $customers;

    public function __construct(Collection $customers)
    {
        $this->customers = $customers;
    }

    public function collection(): Collection
    {
        return $this->customers;
    }

    public function headings(): array
    {
        return [
            'Mã KH (MST)',
            'Tên khách hàng',
            'Điện thoại',
            'Tổng mua',
            'Đã thanh toán',
            'Công nợ còn lại',
            'Hạn mức nợ',
            'Số ngày nợ',
            'Đơn chưa TT',
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->tax_code ?? '',
            $customer->name ?? '',
            $customer->phone ?? '',
            (float) ($customer->receivable_sales ?? 0),
            (float) ($customer->receivable_paid ?? 0),
            (float) ($customer->receivable_debt ?? 0),
            (float) ($customer->debt_limit ?? 0),
            (int) ($customer->debt_days ?? 30),
            (int) ($customer->unpaid_orders ?? 0),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
