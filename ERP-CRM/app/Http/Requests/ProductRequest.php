<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('products')->ignore($this->product)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'max_stock' => ['nullable', 'integer', 'min:0'],
            'management_type' => ['required', 'in:normal,serial,lot'],
            'auto_generate_serial' => ['nullable', 'boolean'],
            'serial_prefix' => ['nullable', 'string', 'max:20'],
            'expiry_months' => ['nullable', 'integer', 'min:0'],
            'track_expiry' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Mã sản phẩm là bắt buộc.',
            'code.unique' => 'Mã sản phẩm đã tồn tại trong hệ thống.',
            'name.required' => 'Tên sản phẩm là bắt buộc.',
            'unit.required' => 'Đơn vị tính là bắt buộc.',
            'price.required' => 'Giá bán là bắt buộc.',
            'price.numeric' => 'Giá bán phải là số.',
            'price.min' => 'Giá bán phải lớn hơn hoặc bằng 0.',
            'cost.required' => 'Giá vốn là bắt buộc.',
            'cost.numeric' => 'Giá vốn phải là số.',
            'cost.min' => 'Giá vốn phải lớn hơn hoặc bằng 0.',
            'management_type.required' => 'Loại quản lý là bắt buộc.',
            'management_type.in' => 'Loại quản lý phải là Normal, Serial hoặc Lot.',
        ];
    }
}
