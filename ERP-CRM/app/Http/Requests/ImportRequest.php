<?php

namespace App\Http\Requests;

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
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'employee_id' => 'nullable|exists:users,id',
            'note' => 'nullable|string|max:1000',
            
            // Items validation
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'nullable|string|max:20',
            'items.*.cost_usd' => 'nullable|numeric|min:0',
            'items.*.description' => 'nullable|string|max:500',
            'items.*.comments' => 'nullable|string|max:500',
            
            // SKU validation
            'items.*.skus' => 'nullable|array',
            'items.*.skus.*' => 'nullable|string|max:100',
            
            // Price tiers validation
            'items.*.price_tiers' => 'nullable|array',
            'items.*.price_tiers.*.name' => 'nullable|string|max:50',
            'items.*.price_tiers.*.price' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'Vui lòng chọn kho nhập.',
            'warehouse_id.exists' => 'Kho không tồn tại.',
            'date.required' => 'Vui lòng chọn ngày nhập.',
            'date.date' => 'Ngày nhập không hợp lệ.',
            'items.required' => 'Vui lòng thêm ít nhất một sản phẩm.',
            'items.min' => 'Vui lòng thêm ít nhất một sản phẩm.',
            'items.*.product_id.required' => 'Vui lòng chọn sản phẩm.',
            'items.*.product_id.exists' => 'Sản phẩm không tồn tại.',
            'items.*.quantity.required' => 'Vui lòng nhập số lượng.',
            'items.*.quantity.integer' => 'Số lượng phải là số nguyên.',
            'items.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
            'items.*.cost_usd.numeric' => 'Giá nhập phải là số.',
            'items.*.cost_usd.min' => 'Giá nhập không được âm.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'warehouse_id' => 'kho nhập',
            'date' => 'ngày nhập',
            'employee_id' => 'nhân viên',
            'note' => 'ghi chú',
            'items.*.product_id' => 'sản phẩm',
            'items.*.quantity' => 'số lượng',
            'items.*.unit' => 'đơn vị',
            'items.*.cost_usd' => 'giá nhập',
            'items.*.description' => 'mô tả',
            'items.*.comments' => 'ghi chú',
        ];
    }
}
