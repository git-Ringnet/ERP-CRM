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
        $query = Project::with(['customer', 'manager', 'vendor', 'initialProcessedBy'])->orderBy('created_at', 'desc');

        if (!empty($this->filters['project_id'])) {
            $query->where('id', $this->filters['project_id']);
        }
        if (!empty($this->filters['search'])) {
            $query->search($this->filters['search']);
        }
        if (!empty($this->filters['status'])) {
            $query->filterByStatus($this->filters['status']);
        }
        if (!empty($this->filters['customer_id'])) {
            $query->where('customer_id', $this->filters['customer_id']);
        }
        if (!empty($this->filters['vendor_id'])) {
            $query->where('vendor_id', $this->filters['vendor_id']);
        }
        if (!empty($this->filters['manager_id'])) {
            $query->where('manager_id', $this->filters['manager_id']);
        }
        if (!empty($this->filters['initial_processed_by'])) {
            $query->where('initial_processed_by', $this->filters['initial_processed_by']);
        }
        if (!empty($this->filters['registration_status'])) {
            $query->where('registration_status', $this->filters['registration_status']);
        }
        if (!empty($this->filters['quarter'])) {
            $quarter = $this->filters['quarter'];
            $year = $this->filters['year'] ?? date('Y');
            $quarterMonths = [
                'Q1' => [1, 3],
                'Q2' => [4, 6],
                'Q3' => [7, 9],
                'Q4' => [10, 12],
            ];
            if (isset($quarterMonths[$quarter])) {
                $query->whereYear('created_at', $year)
                      ->whereMonth('created_at', '>=', $quarterMonths[$quarter][0])
                      ->whereMonth('created_at', '<=', $quarterMonths[$quarter][1]);
            }
        }
        if (!empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }
        if (!empty($this->filters['expiry_start_date'])) {
            $query->whereDate('end_date', '>=', $this->filters['expiry_start_date']);
        }
        if (!empty($this->filters['expiry_end_date'])) {
            $query->whereDate('end_date', '<=', $this->filters['expiry_end_date']);
        }
        if (!empty($this->filters['is_overdue_sla'])) {
            $query->where(function($q) {
                $q->where(function($q1) {
                    $q1->where('intake_status', 'pending')
                       ->whereNotNull('initial_sla_due_at')
                       ->where('initial_sla_due_at', '<', now());
                })->orWhere(function($q2) {
                    $q2->whereIn('registration_status', ['vendor_processing', 'vendor_reminded', 'processing'])
                       ->whereNotNull('vendor_due_at')
                       ->where('vendor_due_at', '<', now());
                });
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        if (!empty($this->filters['project_id']) || ($this->filters['export_type'] ?? '') === 'detailed') {
            return [
                'Mã dự án',
                'Tên dự án',
                'Hãng (Vendor)',
                'Đại lý phụ trách (Distributor AM)',
                'End-User Tiếng Việt',
                'End-User Tiếng Anh',
                'MST End-User',
                'Địa phương End-User',
                'Ngành nghề End-User',
                'Hình thức hợp tác',
                'Tên Đại lý / SI',
                'MST Đại lý',
                'PIC Đại lý',
                'Chức vụ PIC',
                'Số điện thoại PIC',
                'Email PIC',
                'Hạn dự án (End Date)',
                'Sản phẩm / BOM',
                'Deal Type (Fortinet)',
                'S/N cũ (Fortinet)',
                'Yêu cầu thêm',
                'Ghi chú yêu cầu thêm',
                'Note',
                'Trạng thái đăng ký',
                'Người đăng ký',
                'Ngày đăng ký',
            ];
        }

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
        if (!empty($this->filters['project_id']) || ($this->filters['export_type'] ?? '') === 'detailed') {
            return [
                $project->code,
                $project->name,
                $project->vendor->name ?? '-',
                $project->distributor_am,
                $project->eu_name_vi,
                $project->eu_name_en,
                $project->eu_tax_code,
                $project->eu_province,
                $project->eu_industry,
                $project->collaborate_type === 'partner' ? 'Hợp tác qua Đại lý/SI' : 'Làm việc trực tiếp End-User',
                $project->collaborate_company,
                $project->collaborate_tax_code,
                $project->collaborate_pic_name,
                $project->collaborate_pic_title,
                $project->collaborate_pic_phone,
                $project->collaborate_pic_email,
                $project->end_date ? $project->end_date->format('d/m/Y') : '',
                $project->bom_data,
                $project->deal_type === 'trade_up' ? 'Trade up' : ($project->deal_type === 'new_buy' ? 'Newbuy' : '-'),
                $project->sn_numbers,
                $project->special_request_type === 'bom_project' ? 'Bom dự án' : ($project->special_request_type === 'urgent_price' ? 'Cần giá gấp' : 'Không'),
                $project->special_request_note,
                $project->note,
                $project->registration_status_badge['label'] ?? $project->registration_status,
                $project->manager->name ?? '',
                $project->created_at ? $project->created_at->format('d/m/Y H:i') : '',
            ];
        }

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
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '1E3A8A'] // Dark navy text
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => 'DBEAFE'] // Soft, light blue background
                ]
            ],
        ];
    }
}
