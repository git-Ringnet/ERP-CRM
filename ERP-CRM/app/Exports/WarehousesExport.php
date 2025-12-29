<?php

namespace App\Exports;

use App\Models\Warehouse;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WarehousesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        return Warehouse::with('manager')->orderBy('code')->get();
    }

    public function headings(): array
    {
        return [
            'Mã kho',
            'Tên kho',
            'Loại',
            'Địa chỉ',
            'Số điện thoại',
            'Quản lý',
            'Trạng thái',
            'Ghi chú',
        ];
    }

    public function map($warehouse): array
    {
        $statusLabels = [
            'active' => 'Đang hoạt động',
            'maintenance' => 'Đang bảo trì',
            'inactive' => 'Ngừng hoạt động',
        ];

        $typeLabels = [
            'physical' => 'Kho vật lý',
            'virtual' => 'Kho ảo',
        ];

        return [
            $warehouse->code,
            $warehouse->name,
            $typeLabels[$warehouse->type] ?? $warehouse->type,
            $warehouse->address ?? '',
            $warehouse->phone ?? '',
            $warehouse->manager?->name ?? '',
            $statusLabels[$warehouse->status] ?? $warehouse->status,
            $warehouse->description ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'a0bcf2']]],
        ];
    }
}
