<?php

namespace App\Exports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjectsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Project::with(['customer', 'manager'])->orderBy('created_at', 'desc');

        if (!empty($this->filters['search'])) {
            $query->search($this->filters['search']);
        }
        if (!empty($this->filters['status'])) {
            $query->filterByStatus($this->filters['status']);
        }
        if (!empty($this->filters['customer_id'])) {
            $query->where('customer_id', $this->filters['customer_id']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Mã dự án',
            'Tên dự án',
            'Khách hàng',
            'Địa chỉ',
            'Ngày bắt đầu',
            'Ngày kết thúc',
            'Dự toán',
            'Doanh thu',
            'Chi phí',
            'Lợi nhuận',
            'Trạng thái',
            'Quản lý',
            'Ghi chú',
        ];
    }

    public function map($project): array
    {
        $statusLabels = [
            'planning' => 'Lên kế hoạch',
            'in_progress' => 'Đang thực hiện',
            'completed' => 'Hoàn thành',
            'on_hold' => 'Tạm dừng',
            'cancelled' => 'Đã hủy',
        ];

        return [
            $project->code,
            $project->name,
            $project->customer_name ?? '',
            $project->address ?? '',
            $project->start_date ? $project->start_date->format('d/m/Y') : '',
            $project->end_date ? $project->end_date->format('d/m/Y') : '',
            $project->budget,
            $project->total_revenue,
            $project->total_cost,
            $project->profit,
            $statusLabels[$project->status] ?? $project->status,
            $project->manager->name ?? '',
            $project->note ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '8B5CF6']]],
        ];
    }
}
