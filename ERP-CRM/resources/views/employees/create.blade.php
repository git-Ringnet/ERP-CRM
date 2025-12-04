@extends('layouts.app')

@section('title', 'Thêm nhân viên')
@section('page-title', 'Thêm nhân viên mới')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <a href="{{ route('employees.index') }}" class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
    </div>

    <form action="{{ route('employees.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 space-y-4">
                <!-- Thông tin cá nhân -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-user mr-2 text-primary"></i>Thông tin cá nhân</h2>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="employee_code" class="block text-sm font-medium text-gray-700 mb-1">Mã nhân viên <span class="text-red-500">*</span></label>
                                <input type="text" name="employee_code" id="employee_code" value="{{ old('employee_code') }}" required placeholder="VD: NV001"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('employee_code') border-red-500 @enderror">
                                @error('employee_code')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Họ và tên <span class="text-red-500">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="Nhập họ và tên"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('name') border-red-500 @enderror">
                                @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required placeholder="email@example.com"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('email') border-red-500 @enderror">
                                @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Điện thoại <span class="text-red-500">*</span></label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required placeholder="0123456789"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('phone') border-red-500 @enderror">
                                @error('phone')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-1">Ngày sinh</label>
                                <input type="date" name="birth_date" id="birth_date" value="{{ old('birth_date') }}"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="id_card" class="block text-sm font-medium text-gray-700 mb-1">CMND/CCCD</label>
                                <input type="text" name="id_card" id="id_card" value="{{ old('id_card') }}" placeholder="Số CMND/CCCD"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div class="md:col-span-2">
                                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ</label>
                                <input type="text" name="address" id="address" value="{{ old('address') }}" placeholder="Nhập địa chỉ"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thông tin công việc -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-briefcase mr-2 text-primary"></i>Thông tin công việc</h2>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Phòng ban <span class="text-red-500">*</span></label>
                                <select name="department" id="department" required class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="">-- Chọn phòng ban --</option>
                                    <option value="Kinh doanh" {{ old('department') == 'Kinh doanh' ? 'selected' : '' }}>Kinh doanh</option>
                                    <option value="Kỹ thuật" {{ old('department') == 'Kỹ thuật' ? 'selected' : '' }}>Kỹ thuật</option>
                                    <option value="Kế toán" {{ old('department') == 'Kế toán' ? 'selected' : '' }}>Kế toán</option>
                                    <option value="Nhân sự" {{ old('department') == 'Nhân sự' ? 'selected' : '' }}>Nhân sự</option>
                                    <option value="Marketing" {{ old('department') == 'Marketing' ? 'selected' : '' }}>Marketing</option>
                                    <option value="IT" {{ old('department') == 'IT' ? 'selected' : '' }}>IT</option>
                                </select>
                            </div>
                            <div>
                                <label for="position" class="block text-sm font-medium text-gray-700 mb-1">Chức vụ <span class="text-red-500">*</span></label>
                                <input type="text" name="position" id="position" value="{{ old('position') }}" required placeholder="VD: Nhân viên, Trưởng phòng..."
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="join_date" class="block text-sm font-medium text-gray-700 mb-1">Ngày vào làm</label>
                                <input type="date" name="join_date" id="join_date" value="{{ old('join_date') }}"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="salary" class="block text-sm font-medium text-gray-700 mb-1">Lương (VNĐ)</label>
                                <input type="number" name="salary" id="salary" value="{{ old('salary', 0) }}" min="0" step="100000"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thông tin ngân hàng -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-university mr-2 text-primary"></i>Thông tin ngân hàng</h2>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="bank_account" class="block text-sm font-medium text-gray-700 mb-1">Số tài khoản</label>
                                <input type="text" name="bank_account" id="bank_account" value="{{ old('bank_account') }}" placeholder="Số tài khoản"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-1">Ngân hàng</label>
                                <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name') }}" placeholder="VD: Vietcombank..."
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ghi chú -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-sticky-note mr-2 text-primary"></i>Ghi chú</h2>
                    </div>
                    <div class="p-4">
                        <textarea name="note" id="note" rows="2" placeholder="Nhập ghi chú nếu có..."
                                  class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">{{ old('note') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <!-- Trạng thái -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-toggle-on mr-2 text-primary"></i>Trạng thái</h2>
                    </div>
                    <div class="p-4">
                        <select name="status" id="status" required class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Đang làm việc</option>
                            <option value="leave" {{ old('status') == 'leave' ? 'selected' : '' }}>Nghỉ phép</option>
                            <option value="resigned" {{ old('status') == 'resigned' ? 'selected' : '' }}>Đã nghỉ việc</option>
                        </select>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors font-medium text-sm">
                        <i class="fas fa-save mr-2"></i>Lưu nhân viên
                    </button>
                    <a href="{{ route('employees.index') }}" class="mt-2 w-full inline-block text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 text-sm">
                        Hủy bỏ
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
