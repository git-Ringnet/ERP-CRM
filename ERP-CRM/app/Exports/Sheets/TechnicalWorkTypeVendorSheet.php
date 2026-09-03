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

class TechnicalWorkTypeVendorSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
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
        return 'Loại Việc & Hãng (Vendor)';
    }

    public function collection()
    {
        $tickets = $this->tickets ?: $this->getFilteredTickets();
        $totalAll = max(1, $tickets->count());

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

        // 1. SECTION: PHÂN BỔ THEO LOẠI TICKET
        $rows->push((object)[
            'group' => 'PHÂN BỔ THEO LOẠI TICKET',
            'name' => '--- BẢNG THỐNG KÊ THEO LOẠI CÔNG VIỆC ---',
            'total' => '',
            'completed' => '',
            'in_progress' => '',
            'ontime' => '',
            'overdue' => '',
            'percentage' => '',
        ]);

        foreach ($workTypeLabels as $wKey => $wLabel) {
            $groupTickets = $tickets->where('work_type', $wKey);
            $total = $groupTickets->count();
            if ($total === 0 && !empty($this->filters['work_type']) && $this->filters['work_type'] !== $wKey) {
                continue;
            }

            $completed = $groupTickets->whereIn('status', ['completed', 'closed'])->count();
            $inProgress = $total - $completed;
            
            $now = Carbon::now();
            $onTime = $groupTickets->filter(function($t) use ($now) {
                if (in_array($t->status, ['completed', 'closed'])) {
                    return empty($t->sla_deadline) || (!empty($t->resolved_at) && $t->resolved_at->lte($t->sla_deadline));
                }
                return empty($t->sla_deadline) || $t->sla_deadline->gte($now);
            })->count();
            $overdue = $total - $onTime;
            $percent = round(($total / $totalAll) * 100, 1) . '%';

            $rows->push((object)[
                'group' => 'Loại việc',
                'name' => $wLabel,
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'ontime' => $onTime,
                'overdue' => $overdue,
                'percentage' => $percent,
            ]);
        }

        // Blank separator row
        $rows->push((object)[
            'group' => '',
            'name' => '',
            'total' => '',
            'completed' => '',
            'in_progress' => '',
            'ontime' => '',
            'overdue' => '',
            'percentage' => '',
        ]);

        // 2. SECTION: PHÂN BỔ THEO HÃNG / VENDOR
        $rows->push((object)[
            'group' => 'PHÂN BỔ THEO HÃNG / VENDOR',
            'name' => '--- BẢNG THỐNG KÊ THEO HÃNG (VENDOR) ---',
            'total' => '',
            'completed' => '',
            'in_progress' => '',
            'ontime' => '',
            'overdue' => '',
            'percentage' => '',
        ]);

        $suppliers = Supplier::orderBy('name')->get();
        foreach ($suppliers as $sup) {
            $supTickets = $tickets->where('supplier_id', $sup->id);
            $total = $supTickets->count();
            if ($total === 0) continue;

            $completed = $supTickets->whereIn('status', ['completed', 'closed'])->count();
            $inProgress = $total - $completed;
            
            $now = Carbon::now();
            $onTime = $supTickets->filter(function($t) use ($now) {
                if (in_array($t->status, ['completed', 'closed'])) {
                    return empty($t->sla_deadline) || (!empty($t->resolved_at) && $t->resolved_at->lte($t->sla_deadline));
                }
                return empty($t->sla_deadline) || $t->sla_deadline->gte($now);
            })->count();
            $overdue = $total - $onTime;
            $percent = round(($total / $totalAll) * 100, 1) . '%';

            $rows->push((object)[
                'group' => 'Hãng / Vendor',
                'name' => $sup->name,
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'ontime' => $onTime,
                'overdue' => $overdue,
                'percentage' => $percent,
            ]);
        }

        // Unassigned vendor
        $noVendorTickets = $tickets->whereNull('supplier_id');
        if ($noVendorTickets->count() > 0) {
            $total = $noVendorTickets->count();
            $completed = $noVendorTickets->whereIn('status', ['completed', 'closed'])->count();
            $inProgress = $total - $completed;
            $rows->push((object)[
                'group' => 'Hãng / Vendor',
                'name' => 'Khác / Không chỉ định Vendor',
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'ontime' => '',
                'overdue' => '',
                'percentage' => round(($total / $totalAll) * 100, 1) . '%',
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Phân loại',
            'Hạng mục / Tên Loại hoặc Hãng',
            'Tổng số Ticket',
            'Đã hoàn thành',
            'Đang thực hiện',
            'Kịp hạn SLA',
            'Trễ hạn SLA',
            'Tỷ trọng đóng góp (%)',
        ];
    }

    public function map($row): array
    {
        return [
            $row->group,
            $row->name,
            $row->total,
            $row->completed,
            $row->in_progress,
            $row->ontime,
            $row->overdue,
            $row->percentage,
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
        $query = TechnicalTicket::with(['customer', 'supplier']);

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }
        if (!empty($this->filters['assigned_to'])) {
            $engId = $this->filters['assigned_to'];
            $query->where(function($q) use ($engId) {
                $q->where('assigned_to', $engId)
                  ->orWhereHas('assignedEngineers', function($sq) use ($engId) {
                      $sq->where('users.id', $engId);
                  });
            });
        }
        if (!empty($this->filters['supplier_id'])) {
            $query->where('supplier_id', $this->filters['supplier_id']);
        }
        if (!empty($this->filters['work_type'])) {
            $query->where('work_type', $this->filters['work_type']);
        }

        return $query->get();
    }
}
