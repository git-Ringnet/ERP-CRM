<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\ProductItem;
use Illuminate\Foundation\Http\FormRequest;

/**
 * ExportRequest - Validation for export transactions
 * Requirements: 2.5
 */
class ExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'employee_id' => 'nullable|exists:users,id',
            'project_id' => 'nullable|exists:projects,id',
            'customer_id' => 'nullable|exists:customers,id',
            'note' => 'nullable|string|max:1000',

            // Items validation - warehouse_id per item
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.warehouse_id' => 'required|exists:warehouses,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.requested_quantity' => 'nullable|integer|min:1',
            'items.*.comments' => 'nullable|string|max:500',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.total' => 'nullable|numeric|min:0',
            'items.*.serial_list' => 'nullable|string',
            'items.*.product_item_ids' => 'nullable|array',
            'items.*.product_item_ids.*' => 'nullable|exists:product_items,id',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $items = $this->input('items', []);
        $modified = false;

        foreach ($items as $index => $item) {
            if (isset($item['serial_list'])) {
                $serialList = $item['serial_list'];
                $serials = preg_split('/[\n,]+/', $serialList);
                $serials = array_values(array_filter(array_map('trim', $serials)));

                $productId = $item['product_id'] ?? null;
                $warehouseId = $item['warehouse_id'] ?? null;

                if (!empty($serials) && $productId && $warehouseId) {
                    // Find product item IDs that match these serials (SKUs) and are in stock
                    $productItemIds = \App\Models\ProductItem::where('product_id', $productId)
                        ->where('warehouse_id', $warehouseId)
                        ->where('status', \App\Models\ProductItem::STATUS_IN_STOCK)
                        ->whereIn('sku', $serials)
                        ->pluck('id')
                        ->toArray();

                    $items[$index]['product_item_ids'] = $productItemIds;
                    $modified = true;
                } else if (empty($serials)) {
                    $items[$index]['product_item_ids'] = [];
                    $modified = true;
                }
            }
        }

        if ($modified) {
            $this->merge(['items' => $items]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateStockAndSerials($validator);
            $this->validateProjectWarehouseAuthorization($validator);
        });
    }

    /**
     * Validate stock availability and serial requirements
     */
    protected function validateStockAndSerials($validator)
    {
        $items = $this->input('items', []);

        foreach ($items as $index => $item) {
            $productId = $item['product_id'] ?? null;
            $warehouseId = $item['warehouse_id'] ?? null;
            $quantity = (int) ($item['quantity'] ?? 0);
            $selectedSerials = $item['product_item_ids'] ?? [];

            if (!$productId || !$warehouseId || $quantity <= 0) {
                continue;
            }

            // Filter out empty values
            $selectedSerials = array_filter($selectedSerials, fn($id) => !empty($id));
            $selectedCount = count($selectedSerials);

            // Get stock info
            $serialItems = ProductItem::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->where('status', ProductItem::STATUS_IN_STOCK)
                ->where('quantity', '>', 0)
                ->hasSerial()
                ->get();

            $noSkuCount = ProductItem::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->where('status', ProductItem::STATUS_IN_STOCK)
                ->where('quantity', '>', 0)
                ->noSerial()
                ->count();

            $totalStock = $serialItems->count() + $noSkuCount;
            $product = Product::find($productId);
            $productName = $product ? $product->name : "ID: {$productId}";

            // Check if quantity exceeds stock
            if ($quantity > $totalStock) {
                $validator->errors()->add(
                    "items.{$index}.quantity",
                    "Sản phẩm '{$productName}': Số lượng ({$quantity}) vượt quá tồn kho ({$totalStock})"
                );
                continue;
            }

            // Validate serial_list input details if provided
            if (!empty($item['serial_list'])) {
                $serials = preg_split('/[\n,]+/', $item['serial_list']);
                $serials = array_values(array_filter(array_map('trim', $serials)));

                // 1. Check for duplicates in list
                if (count($serials) !== count(array_unique($serials))) {
                    $validator->errors()->add(
                        "items.{$index}.serial_list",
                        "Sản phẩm '{$productName}': Có số serial bị trùng lặp trong danh sách quét."
                    );
                }

                // 2. Check each serial validity
                foreach ($serials as $sku) {
                    $productItem = ProductItem::where('product_id', $productId)
                        ->where('sku', $sku)
                        ->first();

                    if (!$productItem) {
                        $validator->errors()->add(
                            "items.{$index}.serial_list",
                            "Sản phẩm '{$productName}': Số serial '{$sku}' không tồn tại trong hệ thống."
                        );
                    } else {
                        if ((int)$productItem->warehouse_id !== (int)$warehouseId) {
                            $whName = $productItem->warehouse->name ?? "ID: {$productItem->warehouse_id}";
                            $validator->errors()->add(
                                "items.{$index}.serial_list",
                                "Sản phẩm '{$productName}': Số serial '{$sku}' đang nằm ở kho khác ({$whName}) chứ không phải kho xuất đã chọn."
                            );
                        }

                        if ($productItem->status !== ProductItem::STATUS_IN_STOCK) {
                            $statusLabel = $productItem->status;
                            $validator->errors()->add(
                                "items.{$index}.serial_list",
                                "Sản phẩm '{$productName}': Số serial '{$sku}' không khả dụng (Trạng thái hiện tại: {$statusLabel})."
                            );
                        }
                    }
                }
            }

            // Check if need more serials
            $remainingQty = $quantity - $selectedCount;
            if ($remainingQty > $noSkuCount && $serialItems->count() > 0) {
                $needSerials = $remainingQty - $noSkuCount;
                $validator->errors()->add(
                    "items.{$index}.product_item_ids",
                    "Sản phẩm '{$productName}': Cần chọn thêm {$needSerials} serial (chỉ có {$noSkuCount} sản phẩm không serial)"
                );
            }

            // Check if selected serials exceed quantity
            if ($selectedCount > $quantity) {
                $validator->errors()->add(
                    "items.{$index}.product_item_ids",
                    "Sản phẩm '{$productName}': Đã chọn {$selectedCount} serial nhưng số lượng chỉ là {$quantity}"
                );
            }

            // Check for duplicate serials within same item
            if (count($selectedSerials) !== count(array_unique($selectedSerials))) {
                $validator->errors()->add(
                    "items.{$index}.product_item_ids",
                    "Sản phẩm '{$productName}': Không được chọn serial trùng nhau"
                );
            }
        }

        // Check for duplicate serials across all items
        $allSelectedSerials = [];
        foreach ($items as $index => $item) {
            $selectedSerials = $item['product_item_ids'] ?? [];
            $selectedSerials = array_filter($selectedSerials, fn ($id) => !empty($id));
            
            foreach ($selectedSerials as $serialId) {
                if (in_array($serialId, $allSelectedSerials)) {
                    $product = Product::find($item['product_id'] ?? 0);
                    $productName = $product ? $product->name : "ID: " . ($item['product_id'] ?? '?');
                    $validator->errors()->add(
                        "items.{$index}.product_item_ids",
                        "Sản phẩm '{$productName}': Serial đã được chọn ở sản phẩm khác"
                    );
                    break;
                }
                $allSelectedSerials[] = $serialId;
            }
        }
    }

    protected function validateProjectWarehouseAuthorization($validator)
    {
        $items = $this->input('items', []);
        $projectWarehouse = \App\Models\Warehouse::where('code', 'WH_PROJECT')->first();
        $projectWarehouseId = $projectWarehouse ? $projectWarehouse->id : null;
        
        if (!$projectWarehouseId) {
            return;
        }

        $hasProjectItem = false;
        foreach ($items as $item) {
            if (isset($item['warehouse_id']) && (int)$item['warehouse_id'] === (int)$projectWarehouseId) {
                $hasProjectItem = true;
                break;
            }
        }

        if ($hasProjectItem) {
            $user = auth()->user();
            $isWarehouseOrAdmin = $user && $user->hasAnyRole(['super_admin', 'warehouse_manager', 'warehouse_staff']);
            
            if (!$isWarehouseOrAdmin) {
                $export = $this->route('export');
                if (!$export) {
                    $validator->errors()->add(
                        'items',
                        'Chỉ thủ kho hoặc người quản lý mới có quyền tạo phiếu xuất kho trực tiếp từ Kho dự án.'
                    );
                } else {
                    if ($export->reference_type !== 'sale' || !$export->reference_id) {
                        $validator->errors()->add(
                            'items',
                            'Phiếu xuất kho dự án này không liên kết với đơn hàng bán (SO) hợp lệ.'
                        );
                    } else {
                        $sale = \App\Models\Sale::find($export->reference_id);
                        if (!$sale || (int)$sale->user_id !== (int)$user->id) {
                            $validator->errors()->add(
                                'items',
                                'Bạn không có quyền yêu cầu xuất hàng cho dự án này (chỉ chủ sở hữu đơn hàng bán mới có quyền).'
                            );
                        }
                    }
                }
            }
        }
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Vui lòng chọn ngày xuất.',
            'items.required' => 'Vui lòng thêm ít nhất một sản phẩm.',
            'items.*.product_id.required' => 'Vui lòng chọn sản phẩm.',
            'items.*.warehouse_id.required' => 'Vui lòng chọn kho xuất.',
            'items.*.quantity.required' => 'Vui lòng nhập số lượng.',
            'items.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
        ];
    }
}
