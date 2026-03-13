@extends('layouts.app')

@section('title', 'Tạo Bảng lương')
@section('page-title', 'Tổng hợp Bảng lương tháng')

@section('content')
<div class="">
    <div class="bg-white rounded-lg shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-gray-50/50">
            <h2 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-magic text-primary mr-2"></i>Tạo và Tính toán Bảng lương
            </h2>
            <a href="{{ route('payrolls.index') }}" class="text-gray-500 hover:text-gray-700 flex items-center text-sm font-medium">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại
            </a>
        </div>

        <div class="p-6">
            <div class="bg-blue-50 border border-blue-100 p-4 rounded-lg mb-6 flex gap-3 text-sm text-blue-800">
                <i class="fas fa-info-circle text-blue-500 mt-0.5 text-lg"></i>
                <p>Hệ thống sẽ <strong>tự động đếm ngày công</strong> thực tế làm việc trong tháng từ module Chấm công, áp dụng mức lương cơ bản, các khoản phụ cấp và ghi nhận thưởng doanh số của từng nhân viên để tính ra Lương Thực Nhận một cách chính xác nhất.</p>
            </div>

            <form action="{{ route('payrolls.store') }}" method="POST">
                @csrf
                <div class="space-y-6">
                    <!-- Tiêu đề -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề bảng lương <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title', 'Bảng lương tháng ' . $currentMonth . ' năm ' . $currentYear) }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary @error('title') border-red-500 @enderror">
                        @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tháng & Năm -->
                        <div class="flex gap-4">
                            <div class="w-1/2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tháng <span class="text-red-500">*</span></label>
                                <select name="month" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                    @for($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ old('month', $currentMonth) == $m ? 'selected' : '' }}>Tháng {{ $m }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="w-1/2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Năm <span class="text-red-500">*</span></label>
                                <select name="year" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                    @for($y = $currentYear - 2; $y <= $currentYear + 1; $y++)
                                        <option value="{{ $y }}" {{ old('year', $currentYear) == $y ? 'selected' : '' }}>Năm {{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <!-- Số ngày công chuẩn -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Số ngày công chuẩn của tháng <span class="text-red-500">*</span></label>
                            <input type="number" step="1" name="standard_working_days" value="{{ old('standard_working_days', 26) }}" required min="1" max="31"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            <p class="mt-1 text-[11px] text-gray-500">Dùng làm mẫu số để chia lương cơ bản. VD: Tháng 3 có 26 ngày công chuẩn.</p>
                            @error('standard_working_days')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end pt-5 border-t border-gray-200 mt-6">
                        <a href="{{ route('payrolls.index') }}" class="mr-4 text-sm font-medium text-gray-600 hover:text-gray-900 border border-gray-300 px-4 py-2 rounded-lg bg-white hover:bg-gray-50">Hủy khai báo</a>
                        <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark font-medium flex items-center focus:ring-4 focus:ring-primary/30 transition-all">
                            <i class="fas fa-cogs mr-2"></i>Bắt đầu Tổng hợp lương
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
