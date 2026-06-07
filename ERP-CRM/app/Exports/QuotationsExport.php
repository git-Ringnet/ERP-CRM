<?php

namespace App\Exports;

use App\Models\Quotation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Maatwebsite\Excel\Concerns\WithColumnWidths;

class QuotationsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
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
            'Ghi chú / Cảnh báo',
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
            'accepted' => 'KH chốt giá',
            'declined' => 'Khách từ chối',
            'converted' => 'KH chốt giá - Đã chuyển ĐH',
            'expired' => 'Hết hạn',
        ];

        $noteParts = [];
        if (!empty($quotation->note_array)) {
            $noteParts[] = "Ghi chú:\n" . implode("\n", array_map(function($val, $key) {
                return "(" . ($key + 1) . ") " . $val;
            }, $quotation->note_array, array_keys($quotation->note_array)));
        }
        if (!empty($quotation->disclaimer_array)) {
            $noteParts[] = "Cảnh báo / Lưu ý:\n" . implode("\n", array_map(function($val, $key) {
                return "(" . ($key + 1) . ") " . $val;
            }, $quotation->disclaimer_array, array_keys($quotation->disclaimer_array)));
        }
        $notesFormatted = implode("\n\n", $noteParts);

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
            $notesFormatted,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Mã báo giá
            'B' => 25, // Khách hàng
            'C' => 30, // Tiêu đề
            'D' => 15, // Ngày tạo
            'E' => 15, // Hạn báo giá
            'F' => 15, // Tạm tính
            'G' => 15, // Chiết khấu
            'H' => 12, // VAT
            'I' => 18, // Tổng tiền
            'J' => 15, // Trạng thái
            'K' => 45, // Ghi chú / Cảnh báo
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:K' . $highestRow)
            ->getAlignment()
            ->setWrapText(true);

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '10B981']]
            ],
        ];
    }
}
