<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\ProductItem;
use Illuminate\Foundation\Http\FormRequest;

/**
 * ImportRequest - Validation for import transactions
 * Requirements: 1.5
 */
class ImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'supplier_id' => 'required|exists:suppliers,id',
            'date' => 'required|date',
            'employee_id' => 'nullable|exists:users,id',
            'note' => 'nullable|string|max:1000',
            'reference_code' => 'nullable|string|max:100',
            
            // Shipping allocation
            'shipping_allocation_id' => 'nullable|exists:shipping_allocations,id',

            // Service costs
            'shipping_cost' => 'nullable|numeric|min:0',
            'loading_cost' => 'nullable|numeric|min:0',
            'inspection_cost' => 'nullable|numeric|min:0',
            'other_cost' => 'nullable|numeric|min:0',

            // Items validation
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.warehouse_id' => 'required|exists:warehouses,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.cost' => 'nullable|numeric|min:0',
            'items.*.comments' => 'nullable|string|max:500',

            // Support for serial list (textarea)
            'items.*.serial_list' => 'nullable|string',
            // Support for multiple serials (array)
            'items.*.serials' => 'nullable|array',
            'items.*.serials.*' => 'nullable|string|max:100',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateDuplicateSerials($validator);
        });
    }

    /**
     * Check for duplicate serials in database and within the request
     */
    protected function validateDuplicateSerials($validator)
    {
        $items = $this->input('items', []);
        $allSerials = [];
        $duplicatesInRequest = [];
        $duplicatesInDb = [];

        foreach ($items as $index => $item) {
            $productId = $item['product_id'] ?? null;

            // Get serials from array or from serial_list textarea
            $serials = $item['serials'] ?? [];
            if (empty($serials) && !empty($item['serial_list'])) {
                // Parse serial_list (newline or comma separated)
                $serials = preg_split('/[\n,]+/', $item['serial_list']);
            }

            if (empty($productId) || empty($serials)) {
                continue;
            }

            // Filter out empty serials
            $serials = array_filter(array_map('trim', $serials), fn($s) => !empty($s));

            foreach ($serials as $serial) {
                $serial = trim($serial);
                $key = "{$productId}:{$serial}";

                // Check duplicate within request
                if (isset($allSerials[$key])) {
                    $duplicatesInRequest[$key] = $serial;
                } else {
                    $allSerials[$key] = $serial;
                }
            }

            // Check duplicate in database
            if (!empty($serials)) {
                $existingSerials = ProductItem::where('product_id', $productId)
                    ->whereIn('sku', $serials)
                    ->pluck('sku')
                    ->toArray();

                if (!empty($existingSerials)) {
                    $product = Product::find($productId);
                    $productName = $product ? $product->name : "ID: {$productId}";
                    foreach ($existingSerials as $existingSerial) {
                        $duplicatesInDb["{$productId}:{$existingSerial}"] = [
                            'serial' => $existingSerial,
                            'product' => $productName,
                        ];
                    }
                }
            }
        }

        // Add errors for duplicates within request
        if (!empty($duplicatesInRequest)) {
            $serialList = implode(', ', array_values($duplicatesInRequest));
            $validator->errors()->add('items', "Serial bị trùng trong phiếu nhập: {$serialList}");
        }

        // Add errors for duplicates in database
        if (!empty($duplicatesInDb)) {
            foreach ($duplicatesInDb as $dup) {
                $validator->errors()->add('items', "Serial '{$dup['serial']}' đã tồn tại trong hệ thống cho sản phẩm '{$dup['product']}'");
            }
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Vui lòng chọn nhà cung cấp.',
            'supplier_id.exists' => 'Nhà cung cấp không tồn tại.',
            'date.required' => 'Vui lòng chọn ngày nhập.',
            'date.date' => 'Ngày nhập không hợp lệ.',
            'items.required' => 'Vui lòng thêm ít nhất một sản phẩm.',
            'items.min' => 'Vui lòng thêm ít nhất một sản phẩm.',
            'items.*.product_id.required' => 'Vui lòng chọn sản phẩm.',
            'items.*.product_id.exists' => 'Sản phẩm không tồn tại.',
            'items.*.warehouse_id.required' => 'Vui lòng chọn kho nhập.',
            'items.*.warehouse_id.exists' => 'Kho không tồn tại.',
            'items.*.quantity.required' => 'Vui lòng nhập số lượng.',
            'items.*.quantity.integer' => 'Số lượng phải là số nguyên.',
            'items.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'supplier_id' => 'nhà cung cấp',
            'date' => 'ngày nhập',
            'employee_id' => 'nhân viên',
            'note' => 'ghi chú',
            'items.*.product_id' => 'sản phẩm',
            'items.*.warehouse_id' => 'kho nhập',
            'items.*.quantity' => 'số lượng',
            'items.*.serial' => 'serial',
            'items.*.comments' => 'ghi chú',
        ];
    }
}
