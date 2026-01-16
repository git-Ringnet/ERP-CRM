<?php

namespace App\Exports;

use App\Models\Export;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Export::with(['warehouse', 'employee', 'project', 'customer'])
            ->orderBy('date', 'desc');

        // Apply filters
        if (!empty($this->filters['warehouse_id'])) {
            $query->where('warehouse_id', $this->filters['warehouse_id']);
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
            'Ngày xuất',
            'Kho',
            'Dự án / Khách hàng',
            'Nhân viên',
            'Tổng số lượng',
            'Trạng thái',
            'Ghi chú',
        ];
    }

    public function map($export): array
    {
        $statusLabels = [
            'pending' => 'Chờ duyệt',
            'completed' => 'Hoàn thành',
            'rejected' => 'Từ chối',
        ];

        // Determine project or customer
        $projectOrCustomer = '';
        if ($export->project) {
            $projectOrCustomer = $export->project->code . ' - ' . $export->project->name;
        } elseif ($export->customer) {
            $projectOrCustomer = $export->customer->code . ' - ' . $export->customer->name;
        }

        return [
            $export->code,
            $export->date->format('d/m/Y'),
            $export->warehouse->name ?? '',
            $projectOrCustomer,
            $export->employee->name ?? '',
            $export->total_qty,
            $statusLabels[$export->status] ?? $export->status,
            $export->note ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'f39c12']]],
        ];
    }
}
