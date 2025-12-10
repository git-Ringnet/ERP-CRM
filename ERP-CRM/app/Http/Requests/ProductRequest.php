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
     * Requirements: 1.3, 2.2
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('products')->ignore($this->product)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'size:1', 'regex:/^[A-Z]$/'],
            'unit' => ['required', 'string', 'max:50'],
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
            'category.size' => 'Danh mục phải là một ký tự.',
            'category.regex' => 'Danh mục phải là một chữ cái in hoa (A-Z).',
        ];
    }
}
