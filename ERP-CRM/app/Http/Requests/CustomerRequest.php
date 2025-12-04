<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50', Rule::unique('customers')->ignore($this->customer)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'type' => ['required', 'in:normal,vip'],
            'tax_code' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'debt_limit' => ['nullable', 'numeric', 'min:0'],
            'debt_days' => ['nullable', 'integer', 'min:0'],
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
            'code.required' => 'Mã khách hàng là bắt buộc.',
            'code.unique' => 'Mã khách hàng đã tồn tại trong hệ thống.',
            'name.required' => 'Tên khách hàng là bắt buộc.',
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không đúng định dạng.',
            'phone.required' => 'Số điện thoại là bắt buộc.',
            'type.required' => 'Loại khách hàng là bắt buộc.',
            'type.in' => 'Loại khách hàng phải là Normal hoặc VIP.',
        ];
    }
}
