<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * ProductsExport handles exporting product data to Excel
 * Requirements: 7.5
 */
class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $products;

    public function __construct(Collection $products)
    {
        $this->products = $products;
    }

    /**
     * Return the collection to export
     */
    public function collection()
    {
        return $this->products;
    }

    /**
     * Define column headings
     * Updated for simplified product structure
     */
    public function headings(): array
    {
        return [
            'Mã sản phẩm',
            'Tên sản phẩm',
            'Đơn vị',
            'Danh mục',
            'Mô tả',
            'Ghi chú',
            'Theo dõi hết hạn',
            'Mô tả',
            'Ghi chú',
        ];
    }

    /**
     * Map each row to the desired format
     */
    public function map($product): array
    {
        return [
            $product->code ?? '',
            $product->name ?? '',
            $product->unit ?? '',
            $product->category ?? '',
            $product->description ?? '',
            $product->note ?? '',
            $product->track_expiry ? 'Có' : 'Không',
            $product->description ?? '',
            $product->note ?? '',
        ];
    }
}

