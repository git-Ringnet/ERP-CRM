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

        $customers = Customer::orderBy('name')->get();

        foreach ($customers as $cust) {
            $custTickets = $tickets->where('customer_id', $cust->id);
            $total = $custTickets->count();
            if ($total === 0 && !empty($this->filters['customer_id']) && $this->filters['customer_id'] != $cust->id) {
                continue;
            }
            if ($total === 0 && empty($this->filters['customer_id'])) {
                continue;
            }

            $completed = $custTickets->whereIn('status', ['completed', 'closed'])->count();
            $inProgress = $custTickets->whereIn('status', ['assigned', 'in_progress', 'waiting', 'pending', 'escalate'])->count();

            $rows->push((object)[
                'stt' => $stt++,
                'name' => $cust->name,
                'email' => $cust->email ?? '',
                'phone' => $cust->phone ?? '',
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
        $query = TechnicalTicket::with(['customer']);

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
