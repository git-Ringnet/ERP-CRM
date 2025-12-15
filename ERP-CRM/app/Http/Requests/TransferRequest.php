<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * TransferRequest - Validation for transfer transactions
 * Requirements: 3.5
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
            'warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:warehouse_id',
            'date' => 'required|date',
            'employee_id' => 'nullable|exists:users,id',
            'note' => 'nullable|string|max:1000',
            
            // Items validation - simplified: only product, quantity, serial, comments
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.serial' => 'nullable|string|max:100',
            'items.*.comments' => 'nullable|string|max:500',
            'items.*.product_item_ids' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'Vui lòng chọn kho nguồn.',
            'to_warehouse_id.required' => 'Vui lòng chọn kho đích.',
            'to_warehouse_id.different' => 'Kho đích phải khác kho nguồn.',
            'date.required' => 'Vui lòng chọn ngày chuyển.',
            'items.required' => 'Vui lòng thêm ít nhất một sản phẩm.',
            'items.*.product_id.required' => 'Vui lòng chọn sản phẩm.',
            'items.*.quantity.required' => 'Vui lòng nhập số lượng.',
            'items.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
        ];
    }
}
