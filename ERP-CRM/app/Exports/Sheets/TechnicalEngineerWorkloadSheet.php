<?php

namespace App\Exports\Sheets;

use App\Models\TechnicalTicket;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class TechnicalEngineerWorkloadSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
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
        return 'Theo Engineer (Workload)';
    }

    public function collection()
    {
        $engineersQuery = User::where('status', 'active')
            ->whereHas('roles', function($q) {
                $q->whereIn('slug', ['technical_lead', 'technical_engineer']);
            })
            ->orderBy('name');

        if (!empty($this->filters['assigned_to'])) {
            $engineersQuery->where('id', $this->filters['assigned_to']);
        }

        $engineers = $engineersQuery->get();
        $tickets = $this->tickets ?: $this->getFilteredTickets();
        $rows = collect();

        foreach ($engineers as $index => $engineer) {
            $engTickets = $tickets->filter(function($t) use ($engineer) {
                return $t->assigned_to == $engineer->id 
                    || $t->assignedEngineers->contains('id', $engineer->id);
            });

            $total = $engTickets->count();
            $completed = $engTickets->whereIn('status', ['completed', 'closed'])->count();
            $inProgress = $engTickets->whereIn('status', ['assigned', 'in_progress', 'waiting', 'pending', 'escalate'])->count();

            $now = Carbon::now();
            $onTime = $engTickets->filter(function($t) use ($now) {
                if (in_array($t->status, ['completed', 'closed'])) {
                    return empty($t->sla_deadline) || (
                        !empty($t->resolved_at) && $t->resolved_at->lte($t->sla_deadline)
                    );
                } else {
                    return empty($t->sla_deadline) || $t->sla_deadline->gte($now);
                }
            })->count();

            $overdue = $total - $onTime;
            $onTimeRate = $total > 0 ? (round(($onTime / $total) * 100, 1) . '%') : '-';

            // Workload (Total Hours)
            $totalHours = 0;
            foreach ($engTickets as $t) {
                if (!empty($t->resolved_at)) {
                    $totalHours += round($t->created_at->diffInMinutes($t->resolved_at) / 60, 2);
                } else {
                    $totalHours += round($t->created_at->diffInMinutes($now) / 60, 2);
                }
            }

            $avgHours = $total > 0 ? round($totalHours / $total, 1) : 0;
            $logsCount = $engTickets->sum(fn($t) => $t->supportLogs->count());
            $commentsCount = $engTickets->sum(fn($t) => $t->comments->count());

            $rows->push((object)[
                'stt' => $index + 1,
                'name' => $engineer->name,
                'email' => $engineer->email,
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'on_time' => $onTime,
                'overdue' => $overdue,
                'ontime_rate' => $onTimeRate,
                'total_hours' => $totalHours,
                'avg_hours' => $avgHours,
                'logs_count' => $logsCount,
                'comments_count' => $commentsCount,
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'STT',
            'Kỹ sư thực hiện (Engineer)',
            'Email',
            'Tổng Ticket nhận',
            'Đã hoàn thành',
            'Đang xử lý',
            'Kịp hạn SLA',
            'Trễ hạn SLA',
            'Tỷ lệ đúng hạn (%)',
            'Tổng giờ Workload (h)',
            'Giờ TB / Ticket (h)',
            'Số lượt Log hỗ trợ',
            'Số trao đổi (Comments)',
        ];
    }

    public function map($row): array
    {
        return [
            $row->stt,
            $row->name,
            $row->email,
            $row->total,
            $row->completed,
            $row->in_progress,
            $row->on_time,
            $row->overdue,
            $row->ontime_rate,
            $row->total_hours,
            $row->avg_hours,
            $row->logs_count,
            $row->comments_count,
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
                    'startColor' => ['rgb' => '1E40AF'] // Dark Blue
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
        $query = TechnicalTicket::with(['supportLogs', 'comments', 'assignedEngineers']);

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }
        if (!empty($this->filters['customer_id'])) {
            $query->where('customer_id', $this->filters['customer_id']);
        }
        if (!empty($this->filters['supplier_id'])) {
            $query->where('supplier_id', $this->filters['supplier_id']);
        }
        if (!empty($this->filters['project_id'])) {
            $query->where('project_id', $this->filters['project_id']);
        }
        if (!empty($this->filters['created_by'])) {
            $query->where('created_by', $this->filters['created_by']);
        }
        if (!empty($this->filters['work_type'])) {
            $query->where('work_type', $this->filters['work_type']);
        }

        return $query->get();
    }
}
