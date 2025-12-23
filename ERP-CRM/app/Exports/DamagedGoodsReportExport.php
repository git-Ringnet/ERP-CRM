<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DamagedGoodsReportExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $damagedGoods;

    public function __construct(Collection $damagedGoods)
    {
        $this->damagedGoods = $damagedGoods;
    }

    public function collection()
    {
        return $this->damagedGoods->map(function ($item, $index) {
            $typeLabel = $item->type === 'damaged' ? 'Hàng hư hỏng' : 'Thanh lý';
            
            $statusLabel = match($item->status) {
                'pending' => 'Chờ duyệt',
                'approved' => 'Đã duyệt',
                'rejected' => 'Từ chối',
                'processed' => 'Đã xử lý',
                default => $item->status,
            };

            return [
                'stt' => $index + 1,
                'product' => $item->product->name ?? '',
                'type' => $typeLabel,
                'quantity' => $item->quantity,
                'original_value' => $item->original_value,
                'recovery_value' => $item->recovery_value,
                'loss' => $item->original_value - $item->recovery_value,
                'discovery_date' => $item->discovery_date->format('d/m/Y'),
                'discovered_by' => $item->discoveredBy->name ?? '',
                'status' => $statusLabel,
                'reason' => $item->reason ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'STT',
            'Sản phẩm',
            'Loại',
            'Số lượng',
            'Giá trị gốc',
            'Thu hồi',
            'Tổn thất',
            'Ngày phát hiện',
            'Người phát hiện',
            'Trạng thái',
            'Lý do',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4472C4']]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 30,
            'C' => 15,
            'D' => 10,
            'E' => 15,
            'F' => 12,
            'G' => 12,
            'H' => 15,
            'I' => 20,
            'J' => 12,
            'K' => 30,
        ];
    }
}
