<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionReportExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $transactions;

    public function __construct(Collection $transactions)
    {
        $this->transactions = $transactions;
    }

    public function collection()
    {
        return $this->transactions->map(function ($transaction, $index) {
            $typeLabel = match($transaction->type) {
                'import' => 'Nhập kho',
                'export' => 'Xuất kho',
                'transfer' => 'Chuyển kho',
                default => $transaction->type,
            };
            
            $statusLabel = match($transaction->status) {
                'completed' => 'Hoàn thành',
                'pending' => 'Chờ xử lý',
                'cancelled' => 'Đã hủy',
                default => $transaction->status,
            };

            $warehouse = $transaction->warehouse->name ?? '';
            if ($transaction->type === 'transfer' && $transaction->toWarehouse) {
                $warehouse .= ' → ' . $transaction->toWarehouse->name;
            }

            return [
                'stt' => $index + 1,
                'code' => $transaction->code,
                'type' => $typeLabel,
                'warehouse' => $warehouse,
                'date' => $transaction->date->format('d/m/Y'),
                'total_qty' => $transaction->total_qty,
                'employee' => $transaction->employee->name ?? '',
                'status' => $statusLabel,
                'note' => $transaction->note ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'STT',
            'Mã phiếu',
            'Loại',
            'Kho',
            'Ngày',
            'Số lượng',
            'Nhân viên',
            'Trạng thái',
            'Ghi chú',
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
            'B' => 15,
            'C' => 12,
            'D' => 30,
            'E' => 12,
            'F' => 10,
            'G' => 20,
            'H' => 12,
            'I' => 30,
        ];
    }
}
