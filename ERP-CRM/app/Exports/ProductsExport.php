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
     * Requirements: 7.5 - product code, name, unit, selling price, cost price, stock, management type, and category
     */
    public function headings(): array
    {
        return [
            'Mã sản phẩm',
            'Tên sản phẩm',
            'Đơn vị',
            'Giá bán',
            'Giá vốn',
            'Tồn kho',
            'Loại quản lý',
            'Danh mục',
            'Tồn kho tối thiểu',
            'Tồn kho tối đa',
            'Tự động tạo serial',
            'Tiền tố serial',
            'Số tháng hết hạn',
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
            $product->price ?? 0,
            $product->cost ?? 0,
            $product->stock ?? 0,
            $product->management_type ?? '',
            $product->category ?? '',
            $product->min_stock ?? 0,
            $product->max_stock ?? 0,
            $product->auto_generate_serial ? 'Có' : 'Không',
            $product->serial_prefix ?? '',
            $product->expiry_months ?? '',
            $product->track_expiry ? 'Có' : 'Không',
            $product->description ?? '',
            $product->note ?? '',
        ];
    }
}

