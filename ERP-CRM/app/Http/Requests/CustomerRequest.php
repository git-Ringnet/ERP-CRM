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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'type' => ['required', 'in:normal,vip'],
            'tax_code' => ['required', 'string', 'max:50', Rule::unique('customers')->ignore($this->customer)],
            'website' => ['nullable', 'url', 'max:255'],
            'debt_limit' => ['nullable', 'numeric', 'min:0'],
            'debt_days' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string'],
            'contacts' => ['nullable', 'array'],
            'contacts.*.name' => ['required', 'string', 'max:255'],
            'contacts.*.position' => ['nullable', 'string', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:20'],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
            'contacts.*.is_primary' => ['nullable', 'boolean'],
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
            'name.required' => 'Tên khách hàng là bắt buộc.',
            'email.required' => 'Email công ty là bắt buộc.',
            'email.email' => 'Email công ty không đúng định dạng.',
            'phone.required' => 'Số điện thoại công ty là bắt buộc.',
            'type.required' => 'Loại khách hàng là bắt buộc.',
            'type.in' => 'Loại khách hàng phải là Normal hoặc VIP.',
            'tax_code.required' => 'Mã số thuế là bắt buộc.',
            'tax_code.unique' => 'Mã số thuế này đã tồn tại trong hệ thống.',
            'contacts.*.name.required' => 'Tên người liên hệ là bắt buộc.',
        ];
    }
}
