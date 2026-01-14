<?php

namespace App\Exports;

use App\Models\Import;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Import::with(['warehouse', 'supplier', 'employee'])
            ->orderBy('date', 'desc');

        // Apply filters
        if (!empty($this->filters['warehouse_id'])) {
            $query->where('warehouse_id', $this->filters['warehouse_id']);
        }
        if (!empty($this->filters['supplier_id'])) {
            $query->where('supplier_id', $this->filters['supplier_id']);
        }
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        if (!empty($this->filters['date_from'])) {
            $query->whereDate('date', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('date', '<=', $this->filters['date_to']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Mã phiếu',
            'Ngày nhập',
            'Nhà cung cấp',
            'Kho',
            'Nhân viên',
            'Tổng số lượng',
            'Trạng thái',
            'Ghi chú',
        ];
    }

    public function map($import): array
    {
        $statusLabels = [
            'pending' => 'Chờ duyệt',
            'completed' => 'Hoàn thành',
            'rejected' => 'Từ chối',
        ];

        return [
            $import->code,
            $import->date->format('d/m/Y'),
            $import->supplier->name ?? '',
            $import->warehouse->name ?? 'Nhiều kho',
            $import->employee->name ?? '',
            $import->total_qty,
            $statusLabels[$import->status] ?? $import->status,
            $import->note ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '3498db']]],
        ];
    }
}
