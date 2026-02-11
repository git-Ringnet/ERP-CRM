<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerCareStageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'stage' => ['required', 'in:new,onboarding,active,follow_up,retention,at_risk,inactive'],
            'status' => ['required', 'in:not_started,in_progress,completed,on_hold'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'start_date' => ['required', 'date'],
            'target_completion_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'actual_completion_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'completion_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Vui lòng chọn khách hàng.',
            'customer_id.exists' => 'Khách hàng không tồn tại.',
            'stage.required' => 'Vui lòng chọn giai đoạn chăm sóc.',
            'stage.in' => 'Giai đoạn chăm sóc không hợp lệ.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'priority.required' => 'Vui lòng chọn mức độ ưu tiên.',
            'priority.in' => 'Mức độ ưu tiên không hợp lệ.',
            'assigned_to.exists' => 'Người được phân công không tồn tại.',
            'start_date.required' => 'Vui lòng chọn ngày bắt đầu.',
            'start_date.date' => 'Ngày bắt đầu không hợp lệ.',
            'target_completion_date.date' => 'Ngày hoàn thành dự kiến không hợp lệ.',
            'target_completion_date.after_or_equal' => 'Ngày hoàn thành dự kiến phải sau hoặc bằng ngày bắt đầu.',
            'actual_completion_date.date' => 'Ngày hoàn thành thực tế không hợp lệ.',
            'actual_completion_date.after_or_equal' => 'Ngày hoàn thành thực tế phải sau hoặc bằng ngày bắt đầu.',
            'completion_percentage.integer' => 'Phần trăm hoàn thành phải là số nguyên.',
            'completion_percentage.min' => 'Phần trăm hoàn thành phải từ 0 đến 100.',
            'completion_percentage.max' => 'Phần trăm hoàn thành phải từ 0 đến 100.',
        ];
    }
}
