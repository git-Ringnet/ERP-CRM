<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Sale::with(['project', 'customer'])->orderBy('date', 'desc');

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        if (!empty($this->filters['type'])) {
            $query->where('type', $this->filters['type']);
        }
        if (!empty($this->filters['project_id'])) {
            $query->where('project_id', $this->filters['project_id']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Mã đơn',
            'Loại',
            'Dự án',
            'Khách hàng',
            'Ngày tạo',
            'Tạm tính',
            'Chiết khấu (%)',
            'VAT (%)',
            'Tổng tiền',
            'Giá vốn',
            'Lợi nhuận',
            'Margin (%)',
            'Đã thanh toán',
            'Còn nợ',
            'TT Thanh toán',
            'Trạng thái',
            'Ghi chú',
        ];
    }

    public function map($sale): array
    {
        $typeLabels = [
            'retail' => 'Bán lẻ',
            'project' => 'Bán theo dự án',
        ];

        $statusLabels = [
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'shipping' => 'Đang giao',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
        ];

        $paymentLabels = [
            'unpaid' => 'Chưa thanh toán',
            'partial' => 'Thanh toán một phần',
            'paid' => 'Đã thanh toán',
        ];

        return [
            $sale->code,
            $typeLabels[$sale->type] ?? $sale->type,
            $sale->project ? $sale->project->code : '',
            $sale->customer_name,
            $sale->date->format('d/m/Y'),
            $sale->subtotal,
            $sale->discount,
            $sale->vat,
            $sale->total,
            $sale->cost,
            $sale->margin,
            $sale->margin_percent,
            $sale->paid_amount,
            $sale->debt_amount,
            $paymentLabels[$sale->payment_status] ?? $sale->payment_status,
            $statusLabels[$sale->status] ?? $sale->status,
            $sale->note ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '10B981']]],
        ];
    }
}
