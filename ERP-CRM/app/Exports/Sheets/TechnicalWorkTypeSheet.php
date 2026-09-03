<?php

namespace App\Exports\Sheets;

use App\Models\TechnicalTicket;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class TechnicalWorkTypeSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
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
        return 'Theo Loại Ticket';
    }

    public function collection()
    {
        $tickets = $this->tickets ?: $this->getFilteredTickets();
        $now = Carbon::now();

        $workTypeLabels = [
            'survey' => 'Khảo sát / Tư vấn / Thiết kế',
            'BOM' => 'BOM Support',
            'documentation' => 'Technical Documents',
            'POC' => 'POC / Demo',
            'deployment' => 'Deployment',
            'after_sales' => 'After-sales support',
            'training' => 'Training / Update',
            'event' => 'Event / Speaker',
            'other' => 'Other / IT Support',
        ];

        $rows = collect();
        $stt = 1;

        foreach ($workTypeLabels as $wKey => $wLabel) {
            $groupTickets = $tickets->where('work_type', $wKey);
            $total = $groupTickets->count();
            if ($total === 0 && !empty($this->filters['work_type']) && $this->filters['work_type'] !== $wKey) {
                continue;
            }

            $completed = $groupTickets->whereIn('status', ['completed', 'closed'])->count();
            $inProgress = $groupTickets->whereIn('status', ['assigned', 'in_progress', 'waiting', 'pending', 'escalate'])->count();
            
            $onTime = $groupTickets->filter(function($t) use ($now) {
                if (in_array($t->status, ['completed', 'closed'])) {
                    return empty($t->sla_deadline) || (!empty($t->resolved_at) && $t->resolved_at->lte($t->sla_deadline));
                }
                return empty($t->sla_deadline) || $t->sla_deadline->gte($now);
            })->count();
            $overdue = $total - $onTime;

            $rows->push((object)[
                'stt' => $stt++,
                'name' => $wLabel,
                'total' => $total,
                'in_progress' => $inProgress,
                'completed' => $completed,
                'ontime' => $onTime,
                'overdue' => $overdue,
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'STT',
            'Loại việc (Work Type)',
            'Tổng số Ticket',
            'Đang thực hiện',
            'Đã hoàn thành',
            'Kịp hạn SLA',
            'Trễ hạn SLA',
        ];
    }

    public function map($row): array
    {
        return [
            $row->stt,
            $row->name,
            $row->total,
            $row->in_progress,
            $row->completed,
            $row->ontime,
            $row->overdue,
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
                    'startColor' => ['rgb' => '0284C7'] // Sky Blue
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
        $query = TechnicalTicket::query();

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }
        if (!empty($this->filters['work_type'])) {
            $query->where('work_type', $this->filters['work_type']);
        }

        return $query->get();
    }
}
