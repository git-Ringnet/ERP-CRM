<?php

namespace App\Exports\Sheets;

use App\Models\TechnicalTicket;
use App\Models\User;
use App\Models\Customer;
use App\Models\Project;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TechnicalSalesProjectSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $filters;
    protected $tickets;

    public function __construct(array $filters = [], $tickets = null)
    {
        $this->filters = $filters;
        $this->tickets = $tickets;
    }

    public function title(): string
    {
        return 'Sales & Khách Hàng';
    }

    public function collection()
    {
        $tickets = $this->tickets ?: $this->getFilteredTickets();
        $totalAll = max(1, $tickets->count());

        $rows = collect();

        // 1. SECTION: THỐNG KÊ THEO SALES
        $rows->push((object)[
            'group' => 'THEO SALES PHỤ TRÁCH',
            'name' => '--- BẢNG THỐNG KÊ YÊU CẦU THEO SALES ---',
            'extra_info' => '',
            'total' => '',
            'completed' => '',
            'in_progress' => '',
            'completion_rate' => '',
        ]);

        $salesUsers = User::where('status', 'active')
            ->whereHas('roles', function($q) {
                $q->whereIn('slug', ['sales', 'sales_manager', 'super_admin', 'director']);
            })
            ->orderBy('name')
            ->get();

        foreach ($salesUsers as $sale) {
            $saleTickets = $tickets->filter(function($t) use ($sale) {
                return $t->created_by == $sale->id || $t->sales_owner_id == $sale->id;
            });

            $total = $saleTickets->count();
            if ($total === 0) continue;

            $completed = $saleTickets->whereIn('status', ['completed', 'closed'])->count();
            $inProgress = $total - $completed;
            $compRate = round(($completed / $total) * 100, 1) . '%';

            $rows->push((object)[
                'group' => 'Sales',
                'name' => $sale->name,
                'extra_info' => $sale->email,
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'completion_rate' => $compRate,
            ]);
        }

        // Blank separator
        $rows->push((object)[
            'group' => '',
            'name' => '',
            'extra_info' => '',
            'total' => '',
            'completed' => '',
            'in_progress' => '',
            'completion_rate' => '',
        ]);

        // 2. SECTION: THỐNG KÊ THEO KHÁCH HÀNG & DỰ ÁN
        $rows->push((object)[
            'group' => 'THEO KHÁCH HÀNG / DỰ ÁN',
            'name' => '--- BẢNG THỐNG KÊ THEO KHÁCH HÀNG & DỰ ÁN ---',
            'extra_info' => '',
            'total' => '',
            'completed' => '',
            'in_progress' => '',
            'completion_rate' => '',
        ]);

        $customers = Customer::orderBy('name')->get();
        foreach ($customers as $cust) {
            $custTickets = $tickets->where('customer_id', $cust->id);
            $total = $custTickets->count();
            if ($total === 0) continue;

            $completed = $custTickets->whereIn('status', ['completed', 'closed'])->count();
            $inProgress = $total - $completed;
            $compRate = round(($completed / $total) * 100, 1) . '%';

            // List of projects related
            $projectsList = $custTickets->pluck('project.name')->filter()->unique()->join(', ') ?: 'Không có dự án';

            $rows->push((object)[
                'group' => 'Khách hàng',
                'name' => $cust->name,
                'extra_info' => 'Dự án: ' . $projectsList,
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'completion_rate' => $compRate,
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Phân loại',
            'Tên Nhân viên Sales / Khách hàng',
            'Thông tin bổ sung / Email / Dự án',
            'Tổng yêu cầu Kỹ thuật',
            'Đã xử lý xong',
            'Đang thực hiện',
            'Tỷ lệ hoàn thành (%)',
        ];
    }

    public function map($row): array
    {
        return [
            $row->group,
            $row->name,
            $row->extra_info,
            $row->total,
            $row->completed,
            $row->in_progress,
            $row->completion_rate,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '7C3AED'] // Purple
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ]
            ],
        ];
    }

    protected function getFilteredTickets()
    {
        $query = TechnicalTicket::with(['customer', 'project', 'creator', 'salesOwner']);

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }
        if (!empty($this->filters['created_by'])) {
            $query->where('created_by', $this->filters['created_by']);
        }
        if (!empty($this->filters['customer_id'])) {
            $query->where('customer_id', $this->filters['customer_id']);
        }

        return $query->get();
    }
}
