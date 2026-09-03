<?php

namespace App\Exports\Sheets;

use App\Models\TechnicalTicket;
use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TechnicalCustomerSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
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
        return 'Theo Khách Hàng';
    }

    public function collection()
    {
        $tickets = $this->tickets ?: $this->getFilteredTickets();
        $rows = collect();
        $stt = 1;

        // Group tickets by resolved customer name across all sources
        $customerGroups = [];
        $unassignedTickets = collect();

        foreach ($tickets as $ticket) {
            $customerName = $ticket->customer->name 
                ?? $ticket->project->customer->name 
                ?? $ticket->project->customer_name 
                ?? $ticket->opportunity->customer->name 
                ?? $ticket->opportunity->customer_name 
                ?? $ticket->sale->customer->name 
                ?? $ticket->sale->customer_name 
                ?? $ticket->supportLogs->pluck('customer_info')->filter()->first()
                ?? null;

            if (!empty($customerName)) {
                $customerGroups[$customerName][] = $ticket;
            } else {
                $unassignedTickets->push($ticket);
            }
        }

        // Preload Customer models for contact details
        $customersByName = Customer::whereIn('name', array_keys($customerGroups))->get()->keyBy('name');

        foreach ($customerGroups as $name => $cTickets) {
            $cTicketCollection = collect($cTickets);
            $total = $cTicketCollection->count();
            $completed = $cTicketCollection->whereIn('status', ['completed', 'closed'])->count();
            $inProgress = $total - $completed;

            $custModel = $customersByName->get($name);
            $email = $custModel->email ?? '';
            $phone = $custModel->phone ?? '';

            $rows->push((object)[
                'stt' => $stt++,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'total' => $total,
                'in_progress' => $inProgress,
                'completed' => $completed,
            ]);
        }

        // Append unassigned if any
        if ($unassignedTickets->count() > 0 && empty($this->filters['customer_id'])) {
            $total = $unassignedTickets->count();
            $completed = $unassignedTickets->whereIn('status', ['completed', 'closed'])->count();
            $inProgress = $unassignedTickets->whereIn('status', ['assigned', 'in_progress', 'waiting', 'pending', 'escalate'])->count();

            $rows->push((object)[
                'stt' => $stt++,
                'name' => 'Khác / Chưa xác định Khách hàng',
                'email' => '',
                'phone' => '',
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
            'Tên Khách Hàng',
            'Email',
            'Số điện thoại',
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
            $row->email,
            $row->phone,
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
                    'startColor' => ['rgb' => '475569'] // Slate
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
        $query = TechnicalTicket::with([
            'customer', 'project.customer', 'opportunity.customer', 'sale.customer', 'supportLogs'
        ]);

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }
        if (!empty($this->filters['customer_id'])) {
            $query->where('customer_id', $this->filters['customer_id']);
        }

        return $query->get();
    }
}
