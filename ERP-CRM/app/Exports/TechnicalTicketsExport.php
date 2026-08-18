<?php

namespace App\Exports;

use App\Models\TechnicalTicket;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class TechnicalTicketsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Return the collection of tickets based on filters.
     */
    public function collection()
    {
        $query = TechnicalTicket::with([
            'customer', 'project', 'opportunity', 'sale', 'supplier', 'assignedTo', 'creator', 'supportLogs'
        ]);

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
            $query->where('assigned_to', $this->filters['assigned_to']);
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

    /**
     * Define Excel Headers.
     */
    public function headings(): array
    {
        return [
            'Mã Ticket',
            'Tiêu đề',
            'Trạng thái',
            'Loại công việc',
            'Độ ưu tiên',
            'Khách hàng',
            'Dự án',
            'Cơ hội',
            'Đơn hàng bán',
            'Hãng / Nhà cung cấp',
            'Kỹ sư phụ trách',
            'Người tạo (Sales)',
            'Hạn SLA',
            'Thời gian hoàn thành',
            'SLA Status',
            'Thời gian xử lý (Giờ)',
            'Workload (Số lần log hỗ trợ)',
        ];
    }

    /**
     * Map each ticket row.
     */
    public function map($ticket): array
    {
        // Processing Time (Hours)
        $processingTime = '';
        if ($ticket->resolved_at) {
            $processingTime = round($ticket->created_at->diffInMinutes($ticket->resolved_at) / 60, 2);
        } elseif ($ticket->status !== 'completed' && $ticket->status !== 'closed') {
            $processingTime = round($ticket->created_at->diffInMinutes(Carbon::now()) / 60, 2);
        }

        // SLA Status
        $slaStatus = 'Kịp hạn';
        if ($ticket->is_overdue) {
            $slaStatus = 'Trễ hạn';
        } elseif (!$ticket->sla_deadline) {
            $slaStatus = 'Không áp dụng';
        }

        return [
            $ticket->code,
            $ticket->title,
            $ticket->status_label,
            $ticket->work_type_label,
            $ticket->priority_label,
            $ticket->customer->name ?? '',
            $ticket->project->name ?? '',
            $ticket->opportunity->name ?? '',
            $ticket->sale->code ?? '',
            $ticket->supplier->name ?? '',
            $ticket->assignedTo->name ?? 'Chưa phân công',
            $ticket->creator->name ?? '',
            $ticket->sla_deadline ? $ticket->sla_deadline->format('d/m/Y H:i') : '',
            $ticket->resolved_at ? $ticket->resolved_at->format('d/m/Y H:i') : '',
            $slaStatus,
            $processingTime,
            $ticket->supportLogs->count(),
        ];
    }
}
