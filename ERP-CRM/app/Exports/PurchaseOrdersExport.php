<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseOrdersExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = PurchaseOrder::with('supplier')->orderBy('created_at', 'desc');

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('supplier', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        if (!empty($this->filters['supplier_id'])) {
            $query->where('supplier_id', $this->filters['supplier_id']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Mã PO',
            'Nhà cung cấp',
            'Ngày tạo',
            'Ngày giao dự kiến',
            'Tạm tính',
            'Chiết khấu',
            'Phí vận chuyển',
            'VAT',
            'Tổng tiền',
            'Trạng thái',
            'Ghi chú',
        ];
    }

    public function map($order): array
    {
        $statusLabels = [
            'draft' => 'Nháp',
            'pending_approval' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'sent' => 'Đã gửi NCC',
            'confirmed' => 'NCC xác nhận',
            'shipping' => 'Đang giao',
            'partial_received' => 'Nhận một phần',
            'received' => 'Đã nhận hàng',
            'cancelled' => 'Đã hủy',
        ];

        return [
            $order->code,
            $order->supplier->name ?? '',
            $order->order_date->format('d/m/Y'),
            $order->expected_delivery ? $order->expected_delivery->format('d/m/Y') : '',
            $order->subtotal,
            $order->discount_amount,
            $order->shipping_cost,
            $order->vat_amount,
            $order->total,
            $statusLabels[$order->status] ?? $order->status,
            $order->note ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '3B82F6']]],
        ];
    }
}
