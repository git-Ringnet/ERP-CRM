<?php

namespace App\Exports;

use App\Models\Quotation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QuotationsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Quotation::with('customer')->orderBy('created_at', 'desc');

        if (!empty($this->filters['search'])) {
            $query->search($this->filters['search']);
        }
        if (!empty($this->filters['status'])) {
            $query->filterByStatus($this->filters['status']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Mã báo giá',
            'Khách hàng',
            'Tiêu đề',
            'Ngày tạo',
            'Hạn báo giá',
            'Tạm tính',
            'Chiết khấu (%)',
            'VAT (%)',
            'Tổng tiền',
            'Trạng thái',
            'Ghi chú',
        ];
    }

    public function map($quotation): array
    {
        $statusLabels = [
            'draft' => 'Nháp',
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            'sent' => 'Đã gửi khách',
            'accepted' => 'Khách chấp nhận',
            'declined' => 'Khách từ chối',
            'converted' => 'Đã chuyển ĐH',
            'expired' => 'Hết hạn',
        ];

        return [
            $quotation->code,
            $quotation->customer_name,
            $quotation->title,
            $quotation->date->format('d/m/Y'),
            $quotation->valid_until->format('d/m/Y'),
            $quotation->subtotal,
            $quotation->discount,
            $quotation->vat,
            $quotation->total,
            $statusLabels[$quotation->status] ?? $quotation->status,
            $quotation->note ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '10B981']]],
        ];
    }
}
