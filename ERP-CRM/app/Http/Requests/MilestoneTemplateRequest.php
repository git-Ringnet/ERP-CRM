<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MilestoneTemplateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'stage_type' => ['required', 'in:new,onboarding,active,follow_up,retention,at_risk,inactive'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên template.',
            'name.string' => 'Tên template phải là chuỗi ký tự.',
            'name.max' => 'Tên template không được vượt quá :max ký tự.',
            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'stage_type.required' => 'Vui lòng chọn loại giai đoạn.',
            'stage_type.in' => 'Loại giai đoạn không hợp lệ.',
            'is_default.boolean' => 'Giá trị mặc định phải là boolean.',
        ];
    }
}
