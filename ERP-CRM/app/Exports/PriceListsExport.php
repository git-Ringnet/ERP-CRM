<?php

namespace App\Exports;

use App\Models\PriceList;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PriceListsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = PriceList::with(['customer', 'items'])->orderBy('priority', 'desc')->orderBy('created_at', 'desc');

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }
        if (!empty($this->filters['type'])) {
            $query->where('type', $this->filters['type']);
        }
        if (!empty($this->filters['status'])) {
            $query->where('is_active', $this->filters['status'] === 'active');
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Mã bảng giá',
            'Tên bảng giá',
            'Loại',
            'Khách hàng',
            'Ngày bắt đầu',
            'Ngày kết thúc',
            'Chiết khấu (%)',
            'Độ ưu tiên',
            'Số sản phẩm',
            'Trạng thái',
            'Mô tả',
        ];
    }

    public function map($priceList): array
    {
        $typeLabels = [
            'standard' => 'Bảng giá chuẩn',
            'customer' => 'Theo khách hàng',
            'promotion' => 'Khuyến mãi',
            'wholesale' => 'Giá sỉ',
        ];

        return [
            $priceList->code,
            $priceList->name,
            $typeLabels[$priceList->type] ?? $priceList->type,
            $priceList->customer->name ?? 'Tất cả',
            $priceList->start_date ? $priceList->start_date->format('d/m/Y') : '',
            $priceList->end_date ? $priceList->end_date->format('d/m/Y') : '',
            $priceList->discount_percent,
            $priceList->priority,
            $priceList->items->count(),
            $priceList->is_active ? 'Hoạt động' : 'Tạm dừng',
            $priceList->description ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F59E0B']]],
        ];
    }
}
