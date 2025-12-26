@extends('layouts.app')

@section('title', 'Sửa dự án')
@section('page-title', 'Sửa dự án: ' . $project->code)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="{{ route('projects.update', $project->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Mã dự án -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Mã dự án <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="code" value="{{ old('code', $project->code) }}" required
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
                        <option value="planning" {{ old('status', $project->status) == 'planning' ? 'selected' : '' }}>Lên kế hoạch</option>
                        <option value="in_progress" {{ old('status', $project->status) == 'in_progress' ? 'selected' : '' }}>Đang thực hiện</option>
                        <option value="completed" {{ old('status', $project->status) == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                        <option value="on_hold" {{ old('status', $project->status) == 'on_hold' ? 'selected' : '' }}>Tạm dừng</option>
                        <option value="cancelled" {{ old('status', $project->status) == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                    </select>
                </div>

                <!-- Tên dự án -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Tên dự án <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $project->name) }}" required
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
                        <option value="{{ $customer->id }}" {{ old('customer_id', $project->customer_id) == $customer->id ? 'selected' : '' }}>
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
                        <option value="{{ $manager->id }}" {{ old('manager_id', $project->manager_id) == $manager->id ? 'selected' : '' }}>
                            {{ $manager->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Địa chỉ -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ dự án</label>
                    <input type="text" name="address" value="{{ old('address', $project->address) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Mô tả & Ghi chú -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800">
                            <i class="fas fa-sticky-note mr-2 text-primary"></i>Mô tả & Ghi chú
                        </h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Mô tả / Diễn giải</label>
                            <textarea name="description" id="description" rows="3"
                                      placeholder="Mô tả chi tiết về dự án..."
                                      class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">{{ old('description', $project->description) }}</textarea>
                        </div>
                        <div>
                            <label for="note" class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                            <textarea name="note" id="note" rows="2"
                                      placeholder="Nhập ghi chú nếu có..."
                                      class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">{{ old('note', $project->note) }}</textarea>
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
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Ngày bắt đầu</label>
                            <input type="date" name="start_date" id="start_date" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}"
                                   class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Ngày kết thúc dự kiến</label>
                            <input type="date" name="end_date" id="end_date" value="{{ old('end_date', $project->end_date?->format('Y-m-d')) }}"
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
                        <label for="budget" class="block text-sm font-medium text-gray-700 mb-1">Dự toán / Ngân sách (VNĐ)</label>
                        <input type="number" name="budget" id="budget" value="{{ old('budget', $project->budget) }}" min="0" step="1000"
                               class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors font-medium text-sm">
                        <i class="fas fa-save mr-2"></i>Cập nhật dự án
                    </button>
                    <a href="{{ route('projects.index') }}" class="mt-2 w-full inline-block text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 text-sm">
                        Hủy bỏ
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
