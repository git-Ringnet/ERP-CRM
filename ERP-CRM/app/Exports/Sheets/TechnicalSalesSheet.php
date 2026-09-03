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

class TechnicalSalesSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
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
        return 'Theo Sales';
    }

    public function collection()
    {
        $tickets = $this->tickets ?: $this->getFilteredTickets();
        $rows = collect();

        $salesQuery = User::where('status', 'active')
            ->whereHas('roles', function($q) {
                $q->whereIn('slug', ['sales', 'sales_manager', 'super_admin', 'director']);
            })
            ->orderBy('name');

        if (!empty($this->filters['created_by'])) {
            $salesQuery->where('id', $this->filters['created_by']);
        }

        $salesUsers = $salesQuery->get();

        foreach ($salesUsers as $index => $sale) {
            $saleTickets = $tickets->filter(function($t) use ($sale) {
                return $t->created_by == $sale->id || $t->sales_owner_id == $sale->id;
            });

            $total = $saleTickets->count();
            if ($total === 0 && !empty($this->filters['created_by']) && $this->filters['created_by'] != $sale->id) {
                continue;
            }

            $completed = $saleTickets->whereIn('status', ['completed', 'closed'])->count();
            $inProgress = $saleTickets->whereIn('status', ['assigned', 'in_progress', 'waiting', 'pending', 'escalate'])->count();
            $open = $saleTickets->where('status', 'open')->count();
            $compRate = $total > 0 ? (round(($completed / $total) * 100, 1) . '%') : '-';

            $rows->push((object)[
                'stt' => $index + 1,
                'name' => $sale->name,
                'email' => $sale->email,
                'total' => $total,
                'open' => $open,
                'in_progress' => $inProgress,
                'completed' => $completed,
                'completion_rate' => $compRate,
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'STT',
            'Nhân viên Sales',
            'Email',
            'Tổng yêu cầu Kỹ thuật',
            'Mới tạo',
            'Đang xử lý',
            'Đã hoàn thành',
            'Tỷ lệ hoàn thành (%)',
        ];
    }

    public function map($row): array
    {
        return [
            $row->stt,
            $row->name,
            $row->email,
            $row->total,
            $row->open,
            $row->in_progress,
            $row->completed,
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
        $query = TechnicalTicket::with(['creator', 'salesOwner']);

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }
        if (!empty($this->filters['created_by'])) {
            $query->where('created_by', $this->filters['created_by']);
        }

        return $query->get();
    }
}
