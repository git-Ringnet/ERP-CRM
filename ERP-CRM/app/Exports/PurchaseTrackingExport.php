<?php
/*
 * Created At: 2026-05-13T05:18:00Z
 */

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseTrackingExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
            'Sale Order',
            'PR Codes',
            'Sản phẩm (Part Number)',
            'Nhà cung cấp',
            'Đơn giá USD',
            'SL Yêu cầu',
            'SL Đã đặt',
            'SL Đã về',
            'Còn thiếu',
            'Thành tiền USD',
            'Trạng thái',
            'Các đơn mua (PO)',
            'Ngày tạo'
        ];
    }

    public function map($row): array
    {
        $poCodes = collect($row['po_links'])->pluck('code')->implode(', ');
        $prCodes = implode(', ', $row['pr_codes']);

        return [
            $row['sale_code'],
            $prCodes,
            $row['part_number'],
            $row['vendor_name'],
            $row['unit_price_usd'],
            $row['requested'],
            $row['ordered'],
            $row['received'],
            $row['remaining'],
            $row['total_usd'],
            $row['status_label'],
            $poCodes,
            $row['created_at'] ? $row['created_at']->format('d/m/Y') : ''
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
