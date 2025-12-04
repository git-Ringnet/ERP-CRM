<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * SuppliersExport handles exporting supplier data to Excel
 * Requirements: 7.3
 */
class SuppliersExport implements FromCollection, WithHeadings, WithMapping
{
    protected $suppliers;

    public function __construct(Collection $suppliers)
    {
        $this->suppliers = $suppliers;
    }

    /**
     * Return the collection to export
     */
    public function collection()
    {
        return $this->suppliers;
    }

    /**
     * Define column headings
     * Requirements: 7.3 - supplier code, name, email, phone, address, tax code, payment terms, and product type
     */
    public function headings(): array
    {
        return [
            'Mã nhà cung cấp',
            'Tên nhà cung cấp',
            'Email',
            'Số điện thoại',
            'Địa chỉ',
            'Mã số thuế',
            'Website',
            'Người liên hệ',
            'Điều khoản thanh toán (ngày)',
            'Loại sản phẩm',
            'Ghi chú',
        ];
    }

    /**
     * Map each row to the desired format
     */
    public function map($supplier): array
    {
        return [
            $supplier->code ?? '',
            $supplier->name ?? '',
            $supplier->email ?? '',
            $supplier->phone ?? '',
            $supplier->address ?? '',
            $supplier->tax_code ?? '',
            $supplier->website ?? '',
            $supplier->contact_person ?? '',
            $supplier->payment_terms ?? 30,
            $supplier->product_type ?? '',
            $supplier->note ?? '',
        ];
    }
}

