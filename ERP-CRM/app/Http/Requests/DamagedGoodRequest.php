<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DamagedGoodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'original_value' => str_replace(',', '', $this->original_value),
            'recovery_value' => str_replace(',', '', $this->recovery_value),
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'type' => 'required|in:damaged,liquidation',
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'product_item_id' => 'nullable|exists:product_items,id', // Legacy/Single
            'product_item_ids' => 'nullable|array',
            'product_item_ids.*' => 'exists:product_items,id',
            'quantity' => 'required|numeric|min:0.01',
            'original_value' => 'required|numeric|min:0',
            'recovery_value' => 'required|numeric|min:0',
            'reason' => 'required|string|max:1000',
            'discovery_date' => 'required|date',
            'discovered_by' => 'required|exists:users,id',
            'solution' => 'nullable|string|max:1000',
            'note' => 'nullable|string|max:500',
        ];

        if ($this->isMethod('post')) {
            $rules['code'] = 'nullable|unique:damaged_goods,code';
        } else {
            $rules['code'] = 'nullable|unique:damaged_goods,code,' . $this->route('damaged_good');
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Vui lòng chọn loại',
            'type.in' => 'Loại không hợp lệ',
            'product_id.required' => 'Vui lòng chọn sản phẩm',
            'product_id.exists' => 'Sản phẩm không tồn tại',
            'quantity.required' => 'Vui lòng nhập số lượng',
            'quantity.numeric' => 'Số lượng phải là số',
            'quantity.min' => 'Số lượng phải lớn hơn 0',
            'original_value.required' => 'Vui lòng nhập giá trị gốc',
            'original_value.numeric' => 'Giá trị gốc phải là số',
            'recovery_value.required' => 'Vui lòng nhập giá trị thu hồi',
            'recovery_value.numeric' => 'Giá trị thu hồi phải là số',
            'reason.required' => 'Vui lòng nhập lý do',
            'discovery_date.required' => 'Vui lòng chọn ngày phát hiện',
            'discovery_date.date' => 'Ngày phát hiện không hợp lệ',
            'discovered_by.required' => 'Vui lòng chọn người phát hiện',
            'discovered_by.exists' => 'Người phát hiện không tồn tại',
        ];
    }
}
