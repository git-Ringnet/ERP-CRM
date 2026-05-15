<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SinglePurchaseOrderExport implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths
{
    protected PurchaseOrder $po;

    public function __construct(PurchaseOrder $po)
    {
        $this->po = $po;
        $this->po->load(['supplier', 'items.product', 'items.saleOrderRequestItem.saleItem', 'currency']);
    }

    public function title(): string
    {
        return $this->po->code;
    }

    public function headings(): array
    {
        return [
            '#',
            'Sản phẩm',
            'Mã SO',
            'SL',
            'ĐVT',
            'Giá nhập kho (USD)',
            'Giá mua thực tế (USD)',
            'Thành tiền (USD)',
            'Trạng thái',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $isForeign = $this->po->currency && !$this->po->currency->is_base;
        $rate = $this->po->exchange_rate ?: 1;

        foreach ($this->po->items as $index => $item) {
            // Giá nhập kho từ PNL SaleItem
            $warehousePriceUsd = '-';
            if ($item->saleOrderRequestItem && $item->saleOrderRequestItem->saleItem) {
                $estCost = $item->saleOrderRequestItem->saleItem->estimated_cost_usd;
                if ($estCost > 0) {
                    $warehousePriceUsd = number_format($estCost, 2);
                }
            }

            // Sale code
            $saleCode = '-';
            if ($item->saleOrderRequestItem && $item->saleOrderRequestItem->saleOrderRequest && $item->saleOrderRequestItem->saleOrderRequest->sale) {
                $saleCode = $item->saleOrderRequestItem->saleOrderRequest->sale->code;
            }

            $statusLabels = [
                'ordered' => 'Chờ hàng',
                'shipping' => 'Đang về',
                'received' => 'Đã về',
                'cancelled' => 'Hủy',
            ];

            $rows[] = [
                $index + 1,
                $item->product_name . ($item->unit ? " (ĐVT: {$item->unit})" : ''),
                $saleCode,
                $item->quantity,
                $item->unit ?? '',
                $warehousePriceUsd,
                number_format($item->unit_price, 2),
                number_format($item->total, 2),
                $statusLabels[$item->status] ?? 'Chờ hàng',
            ];
        }

        // Summary rows
        $rows[] = [];
        $rows[] = ['', '', '', '', '', '', 'Tổng tiền hàng:', number_format($this->po->subtotal, 2)];
        if ($this->po->discount_percent > 0) {
            $rows[] = ['', '', '', '', '', '', "Chiết khấu ({$this->po->discount_percent}%):", number_format($this->po->discount_amount, 2)];
        }
        if ($this->po->shipping_cost > 0) {
            $rows[] = ['', '', '', '', '', '', 'Phí vận chuyển:', number_format($this->po->shipping_cost, 2)];
        }
        $rows[] = ['', '', '', '', '', '', 'Tổng cộng:', number_format($this->po->total_foreign ?? $this->po->total, 2)];

        // PO Info
        $rows[] = [];
        $rows[] = ['Thông tin PO'];
        $rows[] = ['Mã PO:', $this->po->code];
        $rows[] = ['Nhà cung cấp:', $this->po->supplier->name ?? ''];
        $rows[] = ['Ngày tạo:', $this->po->order_date->format('d/m/Y')];
        $rows[] = ['Ngày giao dự kiến:', $this->po->expected_delivery ? $this->po->expected_delivery->format('d/m/Y') : '-'];

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 35,
            'C' => 18,
            'D' => 8,
            'E' => 8,
            'F' => 20,
            'G' => 22,
            'H' => 20,
            'I' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '3B82F6']],
            ],
        ];
    }
}
