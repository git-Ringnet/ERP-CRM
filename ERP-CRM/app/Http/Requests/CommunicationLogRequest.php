<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommunicationLogRequest extends FormRequest
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
            'type' => ['required', 'in:call,email,meeting,sms,whatsapp,zalo,other'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sentiment' => ['nullable', 'in:positive,neutral,negative'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'occurred_at' => ['required', 'date'],
        ];
    }

    /**
     * Get custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Vui lòng chọn loại giao tiếp.',
            'type.in' => 'Loại giao tiếp không hợp lệ.',
            'subject.required' => 'Vui lòng nhập tiêu đề.',
            'subject.string' => 'Tiêu đề phải là chuỗi ký tự.',
            'subject.max' => 'Tiêu đề không được vượt quá :max ký tự.',
            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'sentiment.in' => 'Cảm xúc không hợp lệ.',
            'duration_minutes.integer' => 'Thời lượng phải là số nguyên.',
            'duration_minutes.min' => 'Thời lượng phải lớn hơn hoặc bằng 0.',
            'occurred_at.required' => 'Vui lòng chọn thời gian.',
            'occurred_at.date' => 'Thời gian không hợp lệ.',
        ];
    }
}
