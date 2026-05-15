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
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Resolve debt_limit_value from the type-specific input
        $type = $this->input('debt_limit_type', 'amount');

        if ($type === 'percent') {
            $value = $this->input('debt_limit_value_percent', $this->input('debt_limit_value', 0));
        } else {
            // Strip comma-formatted amount string (e.g. "50,000,000" -> "50000000")
            $raw = $this->input('debt_limit_value_amount', $this->input('debt_limit_value', 0));
            $value = is_string($raw) ? str_replace(',', '', $raw) : $raw;
        }

        $this->merge([
            'debt_limit_value' => $value ?: 0,
        ]);
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
            'abv_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'type' => ['required', 'in:normal,vip'],
            'tax_code' => ['required', 'string', 'max:50', Rule::unique('customers')->ignore($this->customer)],
            'website' => ['nullable', 'url', 'max:255'],
            'debt_limit' => ['nullable', 'numeric', 'min:0'],
            'debt_days' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string'],
            'am' => ['nullable', 'string', 'max:255'],
            'debt_limit_type' => ['nullable', 'string', 'in:amount,percent'],
            'debt_limit_value' => ['required', 'numeric', 'min:0'],
            'payment_terms' => ['nullable', 'array'],
            'contacts' => ['nullable', 'array'],
            'contacts.*.first_name' => ['required', 'string', 'max:255'],
            'contacts.*.last_name' => ['nullable', 'string', 'max:255'],
            'contacts.*.title' => ['nullable', 'string', 'max:50'],
            'contacts.*.name' => ['nullable', 'string', 'max:500'], // Full name
            'contacts.*.position' => ['required', 'string', 'max:255'],
            'contacts.*.phone' => ['required', 'string', 'max:20'],
            'contacts.*.email' => ['required', 'email', 'max:255'],
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
