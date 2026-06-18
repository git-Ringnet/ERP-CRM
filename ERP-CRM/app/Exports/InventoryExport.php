<?php
 
namespace App\Exports;
 
use App\Models\ProductItem;
use App\Models\InventoryCustomColumn;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;
 
class InventoryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filters;
    protected $columns;
 
    public function __construct($filters = [])
    {
        $this->filters = $filters;
         
        $tab = $filters['tab'] ?? 'stocking';
        // Load custom columns for the active tab
        $this->columns = InventoryCustomColumn::where('tab', $tab)->get();
    }
 
    public function collection()
    {
        $tab = $this->filters['tab'] ?? 'stocking';
 
        $query = ProductItem::with([
            'product', 
            'warehouse', 
            'import.purchaseOrder.items.saleOrderRequestItem.saleOrderRequest.creator', 
            'import.purchaseOrder.sale.project'
        ])
        ->select(
            'product_id',
            'import_id',
            'warehouse_id',
            'borrower',
            'comments',
            'custom_fields',
            DB::raw('SUM(quantity) as quantity'),
            DB::raw('GROUP_CONCAT(sku ORDER BY sku SEPARATOR ", ") as sku'),
            DB::raw('GROUP_CONCAT(id) as item_ids'),
            DB::raw('MAX(updated_at) as updated_at')
        )
        ->where('status', ProductItem::STATUS_IN_STOCK)
        ->groupBy('product_id', 'import_id', 'warehouse_id', 'borrower', 'comments', 'custom_fields');
 
        // Apply warehouse filter
        if (!empty($this->filters['warehouse_id'])) {
            $query->where('warehouse_id', $this->filters['warehouse_id']);
        }
 
        // Apply search filter (same logic as controller)
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($pQ) use ($search) {
                    $pQ->where('name', 'like', "%{$search}%")
                       ->orWhere('code', 'like', "%{$search}%");
                })
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('borrower', 'like', "%{$search}%")
                ->orWhere('comments', 'like', "%{$search}%")
                ->orWhereHas('import.purchaseOrder', function ($poQ) use ($search) {
                    $poQ->where('code', 'like', "%{$search}%")
                        ->orWhereHas('sale', function ($sQ) use ($search) {
                            $sQ->where('customer_name', 'like', "%{$search}%")
                               ->orWhereHas('project', function ($projQ) use ($search) {
                                   $projQ->where('name', 'like', "%{$search}%");
                               });
                        })
                        ->orWhereHas('creator', function ($uQ) use ($search) {
                            $uQ->where('name', 'like', "%{$search}%");
                        });
                })
                ->orWhereHas('import.purchaseOrder.items.saleOrderRequestItem', function ($soriQ) use ($search) {
                    $soriQ->where('eu_name_mst', 'like', "%{$search}%")
                          ->orWhereHas('saleOrderRequest.creator', function ($uQ) use ($search) {
                              $uQ->where('name', 'like', "%{$search}%");
                          });
                });
            });
        }
 
        // Identify project POs and stocking POs
        $projectPoIds = DB::table(DB::raw("(
            SELECT id AS purchase_order_id, sale_id
            FROM purchase_orders
            WHERE sale_id IS NOT NULL
            
            UNION
            
            SELECT poi.purchase_order_id, sor.sale_id
            FROM purchase_order_items poi
            JOIN sale_order_request_items sori ON poi.sale_order_request_item_id = sori.id
            JOIN sale_order_requests sor ON sori.sale_order_request_id = sor.id
            WHERE sor.sale_id IS NOT NULL
        ) as po_sos"))
        ->groupBy('purchase_order_id')
        ->having(DB::raw('COUNT(DISTINCT sale_id)'), '=', 1)
        ->pluck('purchase_order_id')
        ->toArray();
 
        // Apply Tab filters
        if ($tab === 'rmodel') {
            $query->whereHas('product', function($q) {
                $q->where('code', 'like', '%R')
                  ->orWhere('code', 'like', '%NFR');
            });
        } elseif ($tab === 'project') {
            $query->whereHas('product', function($q) {
                $q->where('code', 'not like', '%R')
                  ->where('code', 'not like', '%NFR');
            })->whereHas('import', function($impQ) use ($projectPoIds) {
                $impQ->where('reference_type', 'purchase_order')
                     ->whereIn('reference_id', $projectPoIds);
            });
        } else { // default 'stocking'
            $query->whereHas('product', function($q) {
                $q->where('code', 'not like', '%R')
                  ->where('code', 'not like', '%NFR');
            })->where(function($q) use ($projectPoIds) {
                $q->whereNull('import_id')
                  ->orWhereHas('import', function($impQ) use ($projectPoIds) {
                      $impQ->where(function($subQ) use ($projectPoIds) {
                          $subQ->where('reference_type', '!=', 'purchase_order')
                               ->orWhereNull('reference_id')
                               ->orWhereNotIn('reference_id', $projectPoIds);
                      });
                  });
            });
        }
 
        return $query->orderBy('updated_at', 'desc')->get();
    }
 
    public function headings(): array
    {
        $tab = $this->filters['tab'] ?? 'stocking';
 
        if ($tab === 'rmodel') {
            $headers = [
                'Mã thiết bị',
                'Tên thiết bị',
                'Số Serial (S/N)',
                'Số lượng',
                'Người đặt hàng / Thông tin đơn',
                'Người mượn thiết bị',
                'Ghi chú',
            ];
        } else {
            $headers = [
                'Mã thiết bị',
                'Tên thiết bị',
                'Số Serial (S/N)',
                'Số lượng',
                'Người đặt hàng',
                'Số PO',
                'Dự án / End User',
                'Người mượn thiết bị',
                'Ghi chú',
            ];
        }
 
        // Add custom columns to headers
        foreach ($this->columns as $col) {
            $headers[] = $col->name;
        }
 
        return $headers;
    }
 
    public function map($item): array
    {
        $tab = $this->filters['tab'] ?? 'stocking';
         
        $serial = '';
        if (!$item->isNoSku()) {
            $serial = $item->sku;
        } else {
            $serial = 'Không serial';
        }
 
        if ($tab === 'rmodel') {
            $row = [
                $item->product->code ?? '',
                $item->product->name ?? '',
                $serial,
                $item->quantity,
                $item->r_model_orderer_info ?? '',
                $item->borrower ?? '',
                $item->comments ?? '',
            ];
        } else {
            $row = [
                $item->product->code ?? '',
                $item->product->name ?? '',
                $serial,
                $item->quantity,
                $item->order_creator_name ?? '',
                $item->purchase_order_code ?? '',
                $item->project_name ?? '',
                $item->borrower ?? '',
                $item->comments ?? '',
            ];
        }
 
        // Add custom fields values
        foreach ($this->columns as $col) {
            $row[] = $item->custom_fields[$col->key] ?? '';
        }
 
        return $row;
    }
 
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '27ae60']]],
        ];
    }
}
