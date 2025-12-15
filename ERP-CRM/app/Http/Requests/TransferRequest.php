<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\ProductItem;
use Illuminate\Foundation\Http\FormRequest;

/**
 * TransferRequest - Validation for transfer transactions
 */
class TransferRequest extends FormRequest
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
            'note' => 'nullable|string|max:1000',

            // Items validation - warehouse per item
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.warehouse_id' => 'required|exists:warehouses,id',
            'items.*.to_warehouse_id' => 'required|exists:warehouses,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.comments' => 'nullable|string|max:500',
            'items.*.product_item_ids' => 'nullable|array',
            'items.*.product_item_ids.*' => 'nullable|exists:product_items,id',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateStockAndSerials($validator);
        });
    }

    /**
     * Validate stock availability and serial requirements
     */
    protected function validateStockAndSerials($validator)
    {
        $items = $this->input('items', []);

        // Validate all items have same source and destination warehouse
        $firstWarehouseId = null;
        $firstToWarehouseId = null;
        foreach ($items as $index => $item) {
            $warehouseId = $item['warehouse_id'] ?? null;
            $toWarehouseId = $item['to_warehouse_id'] ?? null;

            if ($firstWarehouseId === null) {
                $firstWarehouseId = $warehouseId;
                $firstToWarehouseId = $toWarehouseId;
            } else {
                if ($warehouseId != $firstWarehouseId) {
                    $validator->errors()->add(
                        "items.{$index}.warehouse_id",
                        'Tất cả sản phẩm trong phiếu chuyển phải có cùng kho nguồn'
                    );
                }
                if ($toWarehouseId != $firstToWarehouseId) {
                    $validator->errors()->add(
                        "items.{$index}.to_warehouse_id",
                        'Tất cả sản phẩm trong phiếu chuyển phải có cùng kho đích'
                    );
                }
            }
        }

        foreach ($items as $index => $item) {
            $productId = $item['product_id'] ?? null;
            $warehouseId = $item['warehouse_id'] ?? null;
            $toWarehouseId = $item['to_warehouse_id'] ?? null;
            $quantity = (int) ($item['quantity'] ?? 0);
            $selectedSerials = $item['product_item_ids'] ?? [];

            if (!$productId || !$warehouseId || !$toWarehouseId || $quantity <= 0) {
                continue;
            }

            // Check same warehouse
            if ($warehouseId == $toWarehouseId) {
                $validator->errors()->add(
                    "items.{$index}.to_warehouse_id",
                    'Kho nguồn và kho đích phải khác nhau'
                );
                continue;
            }

            // Filter out empty values
            $selectedSerials = array_filter($selectedSerials, fn ($id) => !empty($id));
            $selectedCount = count($selectedSerials);

            // Get stock info
            $serialItems = ProductItem::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->where('status', ProductItem::STATUS_IN_STOCK)
                ->where('quantity', '>', 0)
                ->whereRaw("sku NOT LIKE 'NOSKU%'")
                ->get();

            $noSkuCount = ProductItem::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->where('status', ProductItem::STATUS_IN_STOCK)
                ->where('quantity', '>', 0)
                ->whereRaw("sku LIKE 'NOSKU%'")
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

    public function messages(): array
    {
        return [
            'date.required' => 'Vui lòng chọn ngày chuyển.',
            'items.required' => 'Vui lòng thêm ít nhất một sản phẩm.',
            'items.*.product_id.required' => 'Vui lòng chọn sản phẩm.',
            'items.*.warehouse_id.required' => 'Vui lòng chọn kho nguồn.',
            'items.*.to_warehouse_id.required' => 'Vui lòng chọn kho đích.',
            'items.*.quantity.required' => 'Vui lòng nhập số lượng.',
            'items.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
        ];
    }
}
