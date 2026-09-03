<?php

namespace App\Exports\Sheets;

use App\Models\TechnicalTicket;
use App\Models\Project;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TechnicalProjectSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
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
        return 'Theo Dự Án';
    }

    public function collection()
    {
        $tickets = $this->tickets ?: $this->getFilteredTickets();
        $rows = collect();
        $stt = 1;

        $projects = Project::with('customer')->orderBy('name')->get();

        foreach ($projects as $proj) {
            $projTickets = $tickets->where('project_id', $proj->id);
            $total = $projTickets->count();
            if ($total === 0 && !empty($this->filters['project_id']) && $this->filters['project_id'] != $proj->id) {
                continue;
            }
            if ($total === 0 && empty($this->filters['project_id'])) {
                continue;
            }

            $completed = $projTickets->whereIn('status', ['completed', 'closed'])->count();
            $inProgress = $total - $completed;

            // Resolve customer name with fallbacks
            $customerName = $proj->customer->name 
                ?? $proj->customer_name 
                ?? $projTickets->pluck('customer.name')->filter()->first()
                ?? 'Chưa cập nhật';

            $rows->push((object)[
                'stt' => $stt++,
                'name' => $proj->name,
                'customer' => $customerName,
                'total' => $total,
                'in_progress' => $inProgress,
                'completed' => $completed,
            ]);
        }

        // Group manual project names if any (project_id is null, project_name is filled)
        $manualProjectTickets = $tickets->whereNull('project_id')->filter(fn($t) => !empty($t->project_name));
        $manualGroups = $manualProjectTickets->groupBy('project_name');
        foreach ($manualGroups as $pName => $pTickets) {
            $total = $pTickets->count();
            $completed = $pTickets->whereIn('status', ['completed', 'closed'])->count();
            $inProgress = $pTickets->whereIn('status', ['assigned', 'in_progress', 'waiting', 'pending', 'escalate'])->count();
            $customerName = $pTickets->pluck('customer.name')->filter()->first() ?? 'Chưa cập nhật';

            $rows->push((object)[
                'stt' => $stt++,
                'name' => $pName . ' (Nhập tay)',
                'customer' => $customerName,
                'total' => $total,
                'in_progress' => $inProgress,
                'completed' => $completed,
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'STT',
            'Tên Dự án',
            'Khách hàng',
            'Tổng yêu cầu Kỹ thuật',
            'Đang thực hiện',
            'Đã hoàn thành',
        ];
    }

    public function map($row): array
    {
        return [
            $row->stt,
            $row->name,
            $row->customer,
            $row->total,
            $row->in_progress,
            $row->completed,
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
                    'startColor' => ['rgb' => 'EA580C'] // Orange
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
        $query = TechnicalTicket::with(['project.customer']);

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }
        if (!empty($this->filters['project_id'])) {
            $query->where('project_id', $this->filters['project_id']);
        }

        return $query->get();
    }
}
