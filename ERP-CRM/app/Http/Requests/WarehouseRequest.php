<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehouseRequest extends FormRequest
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
        $warehouseId = $this->route('warehouse')?->id ?? $this->route('warehouse');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('warehouses', 'code')->ignore($warehouseId),
            ],
            'name' => 'required|string|max:100',
            'type' => 'required|in:physical,virtual',
            'address' => 'nullable|string|max:500',
            'area' => 'nullable|numeric|min:0|max:999999.99',
            'capacity' => 'nullable|integer|min:0',
            'manager_id' => 'nullable|exists:users,id',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:active,maintenance,inactive',
            'product_type' => 'nullable|string|max:100',
            'has_temperature_control' => 'boolean',
            'has_security_system' => 'boolean',
            'note' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'code' => 'Mã kho',
            'name' => 'Tên kho',
            'type' => 'Loại kho',
            'address' => 'Địa chỉ',
            'area' => 'Diện tích',
            'capacity' => 'Sức chứa',
            'manager_id' => 'Người quản lý',
            'phone' => 'Số điện thoại',
            'status' => 'Trạng thái',
            'product_type' => 'Loại sản phẩm',
            'has_temperature_control' => 'Kiểm soát nhiệt độ',
            'has_security_system' => 'Hệ thống an ninh',
            'note' => 'Ghi chú',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'Mã kho đã tồn tại.',
            'type.in' => 'Loại kho không hợp lệ.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'manager_id.exists' => 'Người quản lý không tồn tại.',
        ];
    }
}
