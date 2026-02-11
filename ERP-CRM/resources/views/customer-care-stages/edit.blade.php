@extends('layouts.app')

@section('title', 'Sửa giai đoạn chăm sóc')
@section('page-title', 'Sửa giai đoạn chăm sóc khách hàng')

@section('content')
<div class="#">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6">
            <form action="{{ route('customer-care-stages.update', $customerCareStage) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Customer -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Khách hàng <span class="text-red-500">*</span>
                        </label>
                        <select name="customer_id" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('customer_id') border-red-500 @enderror">
                            <option value="">-- Chọn khách hàng --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id', $customerCareStage->customer_id) == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->code }} - {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Customer Context Card (Auto-loaded with current customer) -->
                    <div id="customerContextCard" class="md:col-span-2">
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4 border-l-4 border-blue-500">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-blue-900 mb-3">
                                        <i class="fas fa-user-circle mr-2"></i>Thông tin khách hàng
                                    </h4>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <p class="text-xs text-blue-700 mb-1">Mã khách hàng</p>
                                            <p class="font-medium text-gray-900" id="customer-code">{{ $customerCareStage->customer->code ?? '-' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-blue-700 mb-1">Email</p>
                                            <p class="text-sm text-gray-900" id="customer-email">{{ $customerCareStage->customer->email ?? '-' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-blue-700 mb-1">Điện thoại</p>
                                            <p class="text-sm text-gray-900" id="customer-phone">{{ $customerCareStage->customer->phone ?? '-' }}</p>
                                        </div>
                                        <div class="md:col-span-2">
                                            <p class="text-xs text-blue-700 mb-1">Địa chỉ</p>
                                            <p class="text-sm text-gray-900" id="customer-address">{{ $customerCareStage->customer->address ?? '-' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-blue-700 mb-1">Lịch sử chăm sóc</p>
                                            <p class="text-sm font-semibold text-gray-900">
                                                <span id="customer-care-count">{{ $customerCareStage->customer->careStages->count() ?? 0 }}</span> giai đoạn
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3 pt-3 border-t border-blue-200">
                                        <p class="text-xs text-blue-700">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            <span id="customer-status-text">
                                                @if($customerCareStage->customer->careStages->count() > 1)
                                                    Khách hàng đã có {{ $customerCareStage->customer->careStages->count() }} giai đoạn chăm sóc. Đây là giai đoạn hiện tại.
                                                @else
                                                    Đây là giai đoạn chăm sóc đầu tiên của khách hàng.
                                                @endif
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="ml-4">
                                    <div id="customer-loading" class="hidden">
                                        <i class="fas fa-spinner fa-spin text-blue-600 text-xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stage -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Giai đoạn <span class="text-red-500">*</span>
                        </label>
                        <select name="stage" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('stage') border-red-500 @enderror">
                            <option value="new" {{ old('stage', $customerCareStage->stage) == 'new' ? 'selected' : '' }}>Khách hàng mới</option>
                            <option value="onboarding" {{ old('stage', $customerCareStage->stage) == 'onboarding' ? 'selected' : '' }}>Đang tiếp nhận</option>
                            <option value="active" {{ old('stage', $customerCareStage->stage) == 'active' ? 'selected' : '' }}>Chăm sóc tích cực</option>
                            <option value="follow_up" {{ old('stage', $customerCareStage->stage) == 'follow_up' ? 'selected' : '' }}>Theo dõi</option>
                            <option value="retention" {{ old('stage', $customerCareStage->stage) == 'retention' ? 'selected' : '' }}>Duy trì</option>
                            <option value="at_risk" {{ old('stage', $customerCareStage->stage) == 'at_risk' ? 'selected' : '' }}>Có nguy cơ</option>
                            <option value="inactive" {{ old('stage', $customerCareStage->stage) == 'inactive' ? 'selected' : '' }}>Không hoạt động</option>
                        </select>
                        @error('stage')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Trạng thái <span class="text-red-500">*</span>
                        </label>
                        <select name="status" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('status') border-red-500 @enderror">
                            <option value="not_started" {{ old('status', $customerCareStage->status) == 'not_started' ? 'selected' : '' }}>Chưa bắt đầu</option>
                            <option value="in_progress" {{ old('status', $customerCareStage->status) == 'in_progress' ? 'selected' : '' }}>Đang thực hiện</option>
                            <option value="completed" {{ old('status', $customerCareStage->status) == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                            <option value="on_hold" {{ old('status', $customerCareStage->status) == 'on_hold' ? 'selected' : '' }}>Tạm dừng</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Priority -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Mức độ ưu tiên <span class="text-red-500">*</span>
                        </label>
                        <select name="priority" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('priority') border-red-500 @enderror">
                            <option value="low" {{ old('priority', $customerCareStage->priority) == 'low' ? 'selected' : '' }}>Thấp</option>
                            <option value="medium" {{ old('priority', $customerCareStage->priority) == 'medium' ? 'selected' : '' }}>Trung bình</option>
                            <option value="high" {{ old('priority', $customerCareStage->priority) == 'high' ? 'selected' : '' }}>Cao</option>
                            <option value="urgent" {{ old('priority', $customerCareStage->priority) == 'urgent' ? 'selected' : '' }}>Khẩn cấp</option>
                        </select>
                        @error('priority')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Assigned To -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Người phụ trách
                        </label>
                        <select name="assigned_to"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('assigned_to') border-red-500 @enderror">
                            <option value="">-- Chưa phân công --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('assigned_to', $customerCareStage->assigned_to) == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_to')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Ngày bắt đầu <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="start_date" value="{{ old('start_date', $customerCareStage->start_date->format('Y-m-d')) }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('start_date') border-red-500 @enderror">
                        @error('start_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Target Completion Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Ngày hoàn thành dự kiến
                        </label>
                        <input type="date" name="target_completion_date" value="{{ old('target_completion_date', $customerCareStage->target_completion_date?->format('Y-m-d')) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('target_completion_date') border-red-500 @enderror">
                        @error('target_completion_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Completion Percentage -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Phần trăm hoàn thành (%)
                        </label>
                        <input type="number" name="completion_percentage" value="{{ old('completion_percentage', $customerCareStage->completion_percentage) }}" min="0" max="100"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('completion_percentage') border-red-500 @enderror">
                        @error('completion_percentage')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Notes -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Ghi chú
                        </label>
                        <textarea name="notes" rows="4"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('notes') border-red-500 @enderror">{{ old('notes', $customerCareStage->notes) }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200">
                    <a href="{{ route('customer-care-stages.show', $customerCareStage) }}" 
                       class="px-6 py-2 text-sm text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                        <i class="fas fa-times mr-1"></i> Hủy
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 text-sm text-white bg-primary rounded-lg hover:bg-primary-dark">
                        <i class="fas fa-save mr-1"></i> Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const customerSelect = document.querySelector('select[name="customer_id"]');
    const contextCard = document.getElementById('customerContextCard');
    const loadingSpinner = document.getElementById('customer-loading');
    
    if (customerSelect) {
        customerSelect.addEventListener('change', function() {
            const customerId = this.value;
            
            if (!customerId) {
                return;
            }
            
            // Show loading
            loadingSpinner.classList.remove('hidden');
            
            // AJAX call to get customer details
            fetch(`/api/customers/${customerId}/details`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                // Update card with new customer data
                document.getElementById('customer-code').textContent = data.code || '-';
                document.getElementById('customer-email').textContent = data.email || '-';
                document.getElementById('customer-phone').textContent = data.phone || '-';
                document.getElementById('customer-address').textContent = data.address || '-';
                document.getElementById('customer-care-count').textContent = data.care_history_count || 0;
                
                // Update status text
                const statusText = document.getElementById('customer-status-text');
                if (data.care_history_count > 0) {
                    statusText.textContent = `Khách hàng đã có ${data.care_history_count} giai đoạn chăm sóc trước đó.`;
                } else {
                    statusText.textContent = 'Đây là giai đoạn chăm sóc đầu tiên của khách hàng.';
                }
                
                loadingSpinner.classList.add('hidden');
            })
            .catch(error => {
                console.error('Error loading customer details:', error);
                loadingSpinner.classList.add('hidden');
            });
        });
    }
});
</script>
@endpush
@endsection
