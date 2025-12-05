<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryTransactionRequest extends FormRequest
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
        $rules = [
            'type' => 'required|in:import,export,transfer',
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'employee_id' => 'nullable|exists:users,id',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'nullable|string|max:20',
            'items.*.serial_number' => 'nullable|string|max:100',
            'items.*.cost' => 'nullable|numeric|min:0',
        ];

        // Add to_warehouse_id validation for transfer type
        if ($this->input('type') === 'transfer') {
            $rules['to_warehouse_id'] = 'required|exists:warehouses,id|different:warehouse_id';
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'type' => 'Loại giao dịch',
            'warehouse_id' => 'Kho',
            'to_warehouse_id' => 'Kho đích',
            'date' => 'Ngày giao dịch',
            'employee_id' => 'Nhân viên',
            'note' => 'Ghi chú',
            'items' => 'Danh sách sản phẩm',
            'items.*.product_id' => 'Sản phẩm',
            'items.*.quantity' => 'Số lượng',
            'items.*.unit' => 'Đơn vị',
            'items.*.serial_number' => 'Số serial',
            'items.*.cost' => 'Giá vốn',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Loại giao dịch không hợp lệ.',
            'to_warehouse_id.different' => 'Kho đích phải khác kho nguồn.',
            'items.required' => 'Phải có ít nhất một sản phẩm.',
            'items.min' => 'Phải có ít nhất một sản phẩm.',
        ];
    }
}
