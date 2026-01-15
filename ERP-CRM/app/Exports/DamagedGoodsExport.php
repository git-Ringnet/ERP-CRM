<?php

namespace App\Exports;

use App\Models\DamagedGood;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DamagedGoodsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = DamagedGood::with(['product', 'discoveredBy'])
            ->orderBy('discovery_date', 'desc');

        // Apply filters
        if (!empty($this->filters['type'])) {
            $query->byType($this->filters['type']);
        }
        if (!empty($this->filters['status'])) {
            $query->byStatus($this->filters['status']);
        }
        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->byDateRange($this->filters['start_date'], $this->filters['end_date']);
        }
        if (!empty($this->filters['product_id'])) {
            $query->where('product_id', $this->filters['product_id']);
        }
        if (!empty($this->filters['search'])) {
            $query->where('code', 'like', '%' . $this->filters['search'] . '%');
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Mã báo cáo',
            'Loại',
            'Sản phẩm',
            'Số lượng',
            'Giá trị gốc (VNĐ)',
            'Thu hồi (VNĐ)',
            'Tổn thất (VNĐ)',
            'Tỷ lệ thu hồi (%)',
            'Ngày phát hiện',
            'Người phát hiện',
            'Lý do',
            'Trạng thái',
            'Giải pháp',
            'Ghi chú',
        ];
    }

    public function map($item): array
    {
        $typeLabels = [
            'damaged' => 'Hàng hư hỏng',
            'liquidation' => 'Thanh lý',
        ];

        $statusLabels = [
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            'processed' => 'Đã xử lý',
        ];

        return [
            $item->code,
            $typeLabels[$item->type] ?? $item->type,
            $item->product->name ?? '',
            $item->quantity,
            number_format($item->original_value, 0, ',', '.'),
            number_format($item->recovery_value, 0, ',', '.'),
            number_format($item->getLossAmount(), 0, ',', '.'),
            number_format($item->getRecoveryRate(), 2, ',', '.'),
            $item->discovery_date->format('d/m/Y'),
            $item->discoveredBy->name ?? '',
            $item->reason ?? '',
            $statusLabels[$item->status] ?? $item->status,
            $item->solution ?? '',
            $item->note ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '3498DB']]],
        ];
    }
}
