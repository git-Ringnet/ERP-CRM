<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use Illuminate\Support\Collection;

class TransactionExporter
{
    /**
     * Export transactions to CSV format
     */
    public function exportToCsv(Collection $transactions): string
    {
        $csv = [];
        
        // Header row
        $csv[] = [
            'Mã giao dịch',
            'Loại',
            'Kho nguồn',
            'Kho đích',
            'Ngày',
            'Nhân viên',
            'Tổng số lượng',
            'Trạng thái',
            'Ghi chú',
            'Sản phẩm',
            'Số lượng SP',
            'Đơn vị',
        ];

        // Data rows
        foreach ($transactions as $transaction) {
            foreach ($transaction->items as $item) {
                $csv[] = [
                    $transaction->code,
                    $transaction->type,
                    $transaction->warehouse->name,
                    $transaction->toWarehouse ? $transaction->toWarehouse->name : '',
                    $transaction->date->format('Y-m-d'),
                    $transaction->employee->name,
                    $transaction->total_qty,
                    $transaction->status,
                    $transaction->note ?? '',
                    $item->product->name,
                    $item->quantity,
                    $item->unit,
                ];
            }
        }

        return $this->arrayToCsv($csv);
    }

    /**
     * Export transactions to JSON format
     */
    public function exportToJson(Collection $transactions): string
    {
        $data = $transactions->map(function ($transaction) {
            return [
                'code' => $transaction->code,
                'type' => $transaction->type,
                'warehouse' => [
                    'id' => $transaction->warehouse_id,
                    'name' => $transaction->warehouse->name,
                    'code' => $transaction->warehouse->code,
                ],
                'to_warehouse' => $transaction->toWarehouse ? [
                    'id' => $transaction->to_warehouse_id,
                    'name' => $transaction->toWarehouse->name,
                    'code' => $transaction->toWarehouse->code,
                ] : null,
                'date' => $transaction->date->format('Y-m-d'),
                'employee' => [
                    'id' => $transaction->employee_id,
                    'name' => $transaction->employee->name,
                    'code' => $transaction->employee->employee_code,
                ],
                'total_qty' => $transaction->total_qty,
                'status' => $transaction->status,
                'note' => $transaction->note,
                'items' => $transaction->items->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'product_code' => $item->product->code,
                        'quantity' => $item->quantity,
                        'unit' => $item->unit,
                        'serial_number' => $item->serial_number,
                    ];
                })->toArray(),
                'created_at' => $transaction->created_at->toIso8601String(),
            ];
        });

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Export transactions summary to CSV
     */
    public function exportSummaryToCsv(Collection $transactions): string
    {
        $csv = [];
        
        // Header row
        $csv[] = [
            'Mã giao dịch',
            'Loại',
            'Kho nguồn',
            'Kho đích',
            'Ngày',
            'Nhân viên',
            'Tổng số lượng',
            'Số mặt hàng',
            'Trạng thái',
            'Ghi chú',
        ];

        // Data rows
        foreach ($transactions as $transaction) {
            $csv[] = [
                $transaction->code,
                $transaction->getTypeLabel(),
                $transaction->warehouse->name,
                $transaction->toWarehouse ? $transaction->toWarehouse->name : '',
                $transaction->date->format('d/m/Y'),
                $transaction->employee->name,
                $transaction->total_qty,
                $transaction->items->count(),
                $transaction->getStatusLabel(),
                $transaction->note ?? '',
            ];
        }

        return $this->arrayToCsv($csv);
    }

    /**
     * Convert array to CSV string
     */
    private function arrayToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Get filename for export
     */
    public function getFilename(string $format = 'csv', string $type = 'transactions'): string
    {
        $timestamp = now()->format('Y-m-d_His');
        return "inventory_{$type}_{$timestamp}.{$format}";
    }
}
