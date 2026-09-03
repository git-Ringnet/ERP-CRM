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

class TechnicalTicketDetailsSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
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
        return 'Chi Tiết Tất Cả Tickets';
    }

    public function collection()
    {
        if ($this->tickets) {
            return $this->tickets;
        }

        $query = TechnicalTicket::with([
            'customer', 'project', 'opportunity', 'sale', 'supplier', 
            'assignedTo', 'creator', 'supportLogs', 'comments', 'assignedEngineers', 'salesOwner'
        ]);

        $currentUserId = auth()->id();
        $isManagerOrAdmin = auth()->user() && auth()->user()->hasAnyRole(['super_admin', 'director', 'sales_manager']);
        $isTechLeadRole = auth()->user() && auth()->user()->hasRole('technical_lead');

        if (!$isManagerOrAdmin && !$isTechLeadRole) {
            $query->where(function ($q) use ($currentUserId) {
                $q->where('created_by', $currentUserId)
                  ->orWhere('sales_owner_id', $currentUserId)
                  ->orWhere('assigned_to', $currentUserId)
                  ->orWhere('team_lead_id', $currentUserId)
                  ->orWhereHas('assignedEngineers', function ($sq) use ($currentUserId) {
                      $sq->where('users.id', $currentUserId);
                  });
            });
        }

        // Apply filters
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['work_type'])) {
            $query->where('work_type', $this->filters['work_type']);
        }

        if (!empty($this->filters['priority'])) {
            $query->where('priority', $this->filters['priority']);
        }

        if (!empty($this->filters['assigned_to'])) {
            $engId = $this->filters['assigned_to'];
            $query->where(function ($q) use ($engId) {
                $q->where('assigned_to', $engId)
                  ->orWhereHas('assignedEngineers', function ($sq) use ($engId) {
                      $sq->where('users.id', $engId);
                  });
            });
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

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        if (isset($this->filters['sla_status'])) {
            $sla = $this->filters['sla_status'];
            $now = Carbon::now();
            if ($sla === 'overdue') {
                $query->where(function ($q) use ($now) {
                    $q->where(function ($sq) use ($now) {
                        $sq->whereIn('status', ['completed', 'closed'])
                           ->whereColumn('resolved_at', '>', 'sla_deadline');
                    })->orWhere(function ($sq) use ($now) {
                        $sq->whereNotIn('status', ['completed', 'closed'])
                           ->whereNotNull('sla_deadline')
                           ->where('sla_deadline', '<', $now);
                    });
                });
            } elseif ($sla === 'ontime') {
                $query->where(function ($q) use ($now) {
                    $q->where(function ($sq) use ($now) {
                        $sq->whereIn('status', ['completed', 'closed'])
                           ->whereColumn('resolved_at', '<=', 'sla_deadline');
                    })->orWhere(function ($sq) use ($now) {
                        $sq->whereNotIn('status', ['completed', 'closed'])
                           ->where(function ($tsq) use ($now) {
                               $tsq->whereNull('sla_deadline')
                                   ->orWhere('sla_deadline', '>=', $now);
                           });
                    });
                });
            }
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Mã Ticket',
            'Tiêu đề công việc',
            'Trạng thái',
            'Loại việc (Work Type)',
            'Độ ưu tiên',
            'Khách hàng',
            'Dự án (System Project)',
            'Dự án/Partner (Nhập tay)',
            'Cơ hội',
            'Đơn hàng bán',
            'Hãng / Nhà cung cấp (Vendor)',
            'Kỹ sư thực hiện',
            'Người tạo (Sales)',
            'Sales phụ trách',
            'Bộ phận yêu cầu',
            'Hạn SLA (Due Date)',
            'Thời gian hoàn thành',
            'Trạng thái SLA',
            'Thời gian xử lý (Giờ Workload)',
            'Số lượt Log hỗ trợ',
            'Số tin nhắn trao đổi',
        ];
    }

    public function map($ticket): array
    {
        $processingTime = '';
        if ($ticket->resolved_at) {
            $processingTime = round($ticket->created_at->diffInMinutes($ticket->resolved_at) / 60, 2);
        } elseif ($ticket->status !== 'completed' && $ticket->status !== 'closed') {
            $processingTime = round($ticket->created_at->diffInMinutes(Carbon::now()) / 60, 2);
        }

        $slaStatus = 'Kịp hạn';
        if ($ticket->is_overdue) {
            $slaStatus = 'Trễ hạn';
        } elseif (!$ticket->sla_deadline) {
            $slaStatus = 'Không áp dụng';
        }

        $engineersNames = $ticket->assignedEngineers->pluck('name')->join(', ') ?: ($ticket->assignedTo->name ?? 'Chưa phân công');

        $customerName = $ticket->customer->name 
            ?? $ticket->project->customer->name 
            ?? $ticket->project->customer_name 
            ?? '';

        return [
            $ticket->code,
            $ticket->title,
            $ticket->status_label,
            $ticket->work_type_label,
            $ticket->priority_label,
            $customerName,
            $ticket->project->name ?? '',
            $ticket->project_name ?? '',
            $ticket->opportunity->name ?? '',
            $ticket->sale->code ?? '',
            $ticket->supplier->name ?? '',
            $engineersNames,
            $ticket->creator->name ?? '',
            $ticket->salesOwner->name ?? '',
            $ticket->department ?? '',
            $ticket->sla_deadline ? $ticket->sla_deadline->format('d/m/Y H:i') : '',
            $ticket->resolved_at ? $ticket->resolved_at->format('d/m/Y H:i') : '',
            $slaStatus,
            $processingTime,
            $ticket->supportLogs->count(),
            $ticket->comments->count(),
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
                    'startColor' => ['rgb' => '2563EB'] // Primary Blue
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ]
            ],
        ];
    }
}
