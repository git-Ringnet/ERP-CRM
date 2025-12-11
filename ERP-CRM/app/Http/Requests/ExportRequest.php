<?php

namespace App\Http\Requests;

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
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'employee_id' => 'nullable|exists:users,id',
            'note' => 'nullable|string|max:1000',
            
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'nullable|string|max:20',
            'items.*.product_item_ids' => 'nullable|array',
            'items.*.product_item_ids.*' => 'nullable|exists:product_items,id',
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'Vui lòng chọn kho xuất.',
            'date.required' => 'Vui lòng chọn ngày xuất.',
            'items.required' => 'Vui lòng thêm ít nhất một sản phẩm.',
            'items.*.product_id.required' => 'Vui lòng chọn sản phẩm.',
            'items.*.quantity.required' => 'Vui lòng nhập số lượng.',
            'items.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
        ];
    }
}
