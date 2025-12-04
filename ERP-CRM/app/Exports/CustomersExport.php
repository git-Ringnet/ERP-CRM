<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * CustomersExport handles exporting customer data to Excel
 * Requirements: 7.2
 */
class CustomersExport implements FromCollection, WithHeadings, WithMapping
{
    protected $customers;

    public function __construct(Collection $customers)
    {
        $this->customers = $customers;
    }

    /**
     * Return the collection to export
     */
    public function collection()
    {
        return $this->customers;
    }

    /**
     * Define column headings
     * Requirements: 7.2 - customer code, name, email, phone, address, type, tax code, debt limit, and debt days
     */
    public function headings(): array
    {
        return [
            'Mã khách hàng',
            'Tên khách hàng',
            'Email',
            'Số điện thoại',
            'Địa chỉ',
            'Loại khách hàng',
            'Mã số thuế',
            'Website',
            'Người liên hệ',
            'Hạn mức công nợ',
            'Số ngày công nợ',
            'Ghi chú',
        ];
    }

    /**
     * Map each row to the desired format
     */
    public function map($customer): array
    {
        return [
            $customer->code ?? '',
            $customer->name ?? '',
            $customer->email ?? '',
            $customer->phone ?? '',
            $customer->address ?? '',
            $customer->type ?? '',
            $customer->tax_code ?? '',
            $customer->website ?? '',
            $customer->contact_person ?? '',
            $customer->debt_limit ?? 0,
            $customer->debt_days ?? 0,
            $customer->note ?? '',
        ];
    }
}

