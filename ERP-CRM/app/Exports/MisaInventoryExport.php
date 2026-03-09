<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;

class MisaInventoryExport implements FromView, WithStyles
{
    protected $collection;
    protected $type; // 'import' or 'export'

    public function __construct($collection, $type)
    {
        $this->collection = $collection;
        $this->type = $type;
    }

    public function view(): View
    {
        // Nhóm các items theo phiếu (import_id hoặc export_id)
        $groupedData = $this->collection->groupBy(function($item) {
            return $this->type === 'import' ? $item->import_id : $item->export_id;
        });

        $vouchers = [];
        foreach ($groupedData as $id => $items) {
            $parent = $this->type === 'import' ? $items->first()->import : $items->first()->export;
            // Eager load product for items
            if (!$items->first()->relationLoaded('product')) {
                $items->load('product');
            }
            
            $vouchers[] = [
                'parent' => $parent,
                'items' => $items,
                'type' => $this->type
            ];
        }

        return view('exports.misa-inventory', [
            'vouchers' => $vouchers,
            'type' => $this->type,
            'isSingle' => count($vouchers) === 1
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Các định dạng nâng cao sẽ được xử lý trong Blade hoặc ở đây nếu cần
        ];
    }
}
