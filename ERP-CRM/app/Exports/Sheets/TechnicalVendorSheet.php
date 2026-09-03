<?php

namespace App\Exports\Sheets;

use App\Models\TechnicalTicket;
use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class TechnicalVendorSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
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
        return 'Theo Vendor (Hãng)';
    }

    public function collection()
    {
        $tickets = $this->tickets ?: $this->getFilteredTickets();
        $rows = collect();
        $now = Carbon::now();

        $suppliers = Supplier::orderBy('name')->get();
        $stt = 1;

        foreach ($suppliers as $sup) {
            $supTickets = $tickets->where('supplier_id', $sup->id);
            $total = $supTickets->count();
            if ($total === 0 && !empty($this->filters['supplier_id']) && $this->filters['supplier_id'] != $sup->id) {
                continue;
            }
            if ($total === 0 && empty($this->filters['supplier_id'])) {
                continue;
            }

            $completed = $supTickets->whereIn('status', ['completed', 'closed'])->count();
            $inProgress = $supTickets->whereIn('status', ['assigned', 'in_progress', 'waiting', 'pending', 'escalate'])->count();
            
            $onTime = $supTickets->filter(function($t) use ($now) {
                if (in_array($t->status, ['completed', 'closed'])) {
                    return empty($t->sla_deadline) || (!empty($t->resolved_at) && $t->resolved_at->lte($t->sla_deadline));
                }
                return empty($t->sla_deadline) || $t->sla_deadline->gte($now);
            })->count();
            $overdue = $total - $onTime;

            $rows->push((object)[
                'stt' => $stt++,
                'name' => $sup->name,
                'total' => $total,
                'in_progress' => $inProgress,
                'completed' => $completed,
                'ontime' => $onTime,
                'overdue' => $overdue,
            ]);
        }

        // Unassigned vendor
        $noVendorTickets = $tickets->whereNull('supplier_id');
        if ($noVendorTickets->count() > 0 && empty($this->filters['supplier_id'])) {
            $total = $noVendorTickets->count();
            $completed = $noVendorTickets->whereIn('status', ['completed', 'closed'])->count();
            $inProgress = $total - $completed;
            $rows->push((object)[
                'stt' => $stt++,
                'name' => 'Khác / Chưa chỉ định Hãng',
                'total' => $total,
                'in_progress' => $inProgress,
                'completed' => $completed,
                'ontime' => '',
                'overdue' => '',
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'STT',
            'Tên Hãng / Nhà cung cấp (Vendor)',
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
                    'startColor' => ['rgb' => '0D9488'] // Teal
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
        $query = TechnicalTicket::with(['supplier']);

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }
        if (!empty($this->filters['supplier_id'])) {
            $query->where('supplier_id', $this->filters['supplier_id']);
        }

        return $query->get();
    }
}
