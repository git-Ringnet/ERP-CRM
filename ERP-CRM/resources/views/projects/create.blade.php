@extends('layouts.app')

@section('title', 'Thêm dự án mới')
@section('page-title', 'Thêm dự án mới')

@section('content')
    <div class="max-w-8xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <form action="{{ route('projects.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Mã dự án -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Mã dự án <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="code" value="{{ old('code', $code) }}" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('code') border-red-500 @enderror">
                        @error('code')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Trạng thái -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Trạng thái <span class="text-red-500">*</span>
                        </label>
                        <select name="status" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="planning" {{ old('status') == 'planning' ? 'selected' : '' }}>Lên kế hoạch</option>
                            <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>Đang thực hiện
                            </option>
                            <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                            <option value="on_hold" {{ old('status') == 'on_hold' ? 'selected' : '' }}>Tạm dừng</option>
                            <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                        </select>
                    </div>

                    <!-- Tên dự án -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tên dự án <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            placeholder="VD: Dự án cung cấp thiết bị văn phòng ABC"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Khách hàng -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Khách hàng / Chủ đầu tư</label>
                        <select name="customer_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">-- Chọn khách hàng --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Người quản lý -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Người quản lý</label>
                        <select name="manager_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">-- Chọn người quản lý --</option>
                            @foreach($managers as $manager)
                                <option value="{{ $manager->id }}" {{ old('manager_id') == $manager->id ? 'selected' : '' }}>
                                    {{ $manager->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Địa chỉ -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ dự án</label>
                        <input type="text" name="address" value="{{ old('address') }}" placeholder="Địa chỉ thực hiện dự án"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>

                    <!-- Mô tả & Ghi chú -->
                    <div class="bg-white rounded-lg shadow-sm md:col-span-2">
                        <div class="px-4 py-3 border-b border-gray-200">
                            <h2 class="text-base font-semibold text-gray-800">
                                <i class="fas fa-sticky-note mr-2 text-primary"></i>Mô tả & Ghi chú
                            </h2>
                        </div>
                        <div class="p-4 space-y-4">
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Mô tả / Diễn
                                    giải</label>
                                <textarea name="description" id="description" rows="3"
                                    placeholder="Mô tả chi tiết về dự án..."
                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">{{ old('description') }}</textarea>
                            </div>
                            <div>
                                <label for="note" class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                                <textarea name="note" id="note" rows="2" placeholder="Nhập ghi chú nếu có..."
                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">{{ old('note') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4">
                    <!-- Thời gian -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-4 py-3 border-b border-gray-200">
                            <h2 class="text-base font-semibold text-gray-800">
                                <i class="fas fa-calendar-alt mr-2 text-primary"></i>Thời gian
                            </h2>
                        </div>
                        <div class="p-4 space-y-3">
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Ngày bắt
                                    đầu</label>
                                <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}"
                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Ngày kết thúc dự
                                    kiến</label>
                                <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}"
                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                                @error('end_date')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <!-- Ngân sách -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-4 py-3 border-b border-gray-200">
                            <h2 class="text-base font-semibold text-gray-800">
                                <i class="fas fa-money-bill-wave mr-2 text-primary"></i>Ngân sách
                            </h2>
                        </div>
                        <div class="p-4">
                            <label for="budget" class="block text-sm font-medium text-gray-700 mb-1">Dự toán / Ngân sách
                                (VNĐ)</label>
                            <input type="text" id="budget_display" oninput="formatCurrency(this)" value="{{ number_format(old('budget', 0)) }}"
                                class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            <input type="hidden" name="budget" id="budget" value="{{ old('budget', 0) }}">
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <button type="submit"
                            class="w-full px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors font-medium text-sm">
                            <i class="fas fa-save mr-2"></i>Lưu dự án
                        </button>
                        <a href="{{ route('projects.index') }}"
                            class="mt-2 w-full inline-block text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 text-sm">
                            Hủy bỏ
                        </a>
                    </div>
                </div>
        </div>
        </form>
    </div>

    <script>
        function formatCurrency(input) {
            // Remove non-digits
            let value = input.value.replace(/\D/g, '');
            
            // Update hidden input
            document.getElementById('budget').value = value;
            
            // Format display value
            if (value !== '') {
                value = parseInt(value).toLocaleString('en-US');
                input.value = value;
            } else {
                input.value = '';
            }
        }
    </script>
@endsection