@extends('layouts.app')

@section('title', 'Sửa nhân viên')
@section('page-title', 'Chỉnh sửa nhân viên')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <a href="{{ route('employees.index') }}" class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
        <a href="{{ route('employees.show', $employee->id) }}" class="inline-flex items-center px-3 py-1.5 text-sm bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200">
            <i class="fas fa-eye mr-2"></i>Xem chi tiết
        </a>
    </div>

    <form action="{{ route('employees.update', $employee->id) }}" method="POST">
        @csrf
        @method('PUT')
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
                                <input type="text" name="employee_code" id="employee_code" value="{{ old('employee_code', $employee->employee_code) }}" required
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('employee_code') border-red-500 @enderror">
                                @error('employee_code')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Họ và tên <span class="text-red-500">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name', $employee->name) }}" required
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('name') border-red-500 @enderror">
                                @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email', $employee->email) }}" required
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('email') border-red-500 @enderror">
                                @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Điện thoại <span class="text-red-500">*</span></label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone', $employee->phone) }}" required
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('phone') border-red-500 @enderror">
                                @error('phone')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu mới</label>
                                <input type="password" name="password" id="password" placeholder="Để trống nếu không đổi"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('password') border-red-500 @enderror">
                                @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                <p class="mt-1 text-xs text-gray-500">Để trống nếu không muốn đổi mật khẩu (tối thiểu 8 ký tự)</p>
                            </div>
                            <div>
                                <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-1">Ngày sinh</label>
                                <input type="text" name="birth_date" id="birth_date" value="{{ old('birth_date', $employee->birth_date) }}" placeholder="Ngày sinh"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="id_card" class="block text-sm font-medium text-gray-700 mb-1">CMND/CCCD</label>
                                <input type="text" name="id_card" id="id_card" value="{{ old('id_card', $employee->id_card) }}"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div class="md:col-span-2">
                                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ</label>
                                <input type="text" name="address" id="address" value="{{ old('address', $employee->address) }}"
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
                                    <option value="Kinh doanh" {{ old('department', $employee->department) == 'Kinh doanh' ? 'selected' : '' }}>Kinh doanh</option>
                                    <option value="Kỹ thuật" {{ old('department', $employee->department) == 'Kỹ thuật' ? 'selected' : '' }}>Kỹ thuật</option>
                                    <option value="Kế toán" {{ old('department', $employee->department) == 'Kế toán' ? 'selected' : '' }}>Kế toán</option>
                                    <option value="Nhân sự" {{ old('department', $employee->department) == 'Nhân sự' ? 'selected' : '' }}>Nhân sự</option>
                                    <option value="Marketing" {{ old('department', $employee->department) == 'Marketing' ? 'selected' : '' }}>Marketing</option>
                                    <option value="IT" {{ old('department', $employee->department) == 'IT' ? 'selected' : '' }}>IT</option>
                                </select>
                            </div>
                            <div>
                                <label for="position" class="block text-sm font-medium text-gray-700 mb-1">Chức vụ <span class="text-red-500">*</span></label>
                                <input type="text" name="position" id="position" value="{{ old('position', $employee->position) }}" required
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="join_date" class="block text-sm font-medium text-gray-700 mb-1">Ngày vào làm</label>
                                <input type="text" name="join_date" id="join_date" value="{{ old('join_date', $employee->join_date) }}" placeholder="Ngày vào làm"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="salary" class="block text-sm font-medium text-gray-700 mb-1">Lương (VNĐ)</label>
                                <input type="text" name="salary" id="salary" value="{{ old('salary', $employee->salary ? number_format($employee->salary) : '') }}"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary currency-input">
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
                                <input type="text" name="bank_account" id="bank_account" value="{{ old('bank_account', $employee->bank_account) }}"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-1">Ngân hàng</label>
                                <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $employee->bank_name) }}"
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
                        <textarea name="note" id="note" rows="2" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">{{ old('note', $employee->note) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <!-- Loại hình chấm công -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-clock mr-2 text-primary"></i>Chấm công & Địa điểm</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label for="timekeeping_type" class="block text-sm font-medium text-gray-700 mb-1">Loại hình chấm công <span class="text-red-500">*</span></label>
                            <select name="timekeeping_type" id="timekeeping_type" required class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                                <option value="regular" {{ old('timekeeping_type', $employee->timekeeping_type) == 'regular' ? 'selected' : '' }}>Thường xuyên (Cố định)</option>
                                <option value="irregular" {{ old('timekeeping_type', $employee->timekeeping_type) == 'irregular' ? 'selected' : '' }}>Không thường xuyên (Linh hoạt)</option>
                            </select>
                        </div>
                        <div>
                            <label for="work_location_id" class="block text-sm font-medium text-gray-700 mb-1">Địa điểm làm việc chính</label>
                            <select name="work_location_id" id="work_location_id" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                                <option value="">-- Không giới hạn địa điểm --</option>
                                @foreach($workLocations as $location)
                                    <option value="{{ $location->id }}" {{ old('work_location_id', $employee->work_location_id) == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500 italic">Dùng để giới hạn phạm vi chấm công cho nhân viên này.</p>
                        </div>
                    </div>
                </div>

                <!-- Trạng thái -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-toggle-on mr-2 text-primary"></i>Trạng thái</h2>
                    </div>
                    <div class="p-4">
                        <select name="status" id="status" required class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="active" {{ old('status', $employee->status) == 'active' ? 'selected' : '' }}>Đang làm việc</option>
                            <option value="leave" {{ old('status', $employee->status) == 'leave' ? 'selected' : '' }}>Nghỉ phép</option>
                            <option value="resigned" {{ old('status', $employee->status) == 'resigned' ? 'selected' : '' }}>Đã nghỉ việc</option>
                        </select>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors font-medium text-sm">
                        <i class="fas fa-save mr-2"></i>Cập nhật
                    </button>
                    <a href="{{ route('employees.index') }}" class="mt-2 w-full inline-block text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 text-sm">
                        Hủy bỏ
                    </a>
                </div>

                <!-- Thông tin hệ thống -->
                <div class="bg-gray-50 rounded-lg p-3 text-xs text-gray-600">
                    <div class="flex justify-between mb-1">
                        <span>Ngày tạo:</span>
                        <span class="font-medium">{{ \Carbon\Carbon::parse($employee->created_at)->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Cập nhật:</span>
                        <span class="font-medium">{{ \Carbon\Carbon::parse($employee->updated_at)->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('birth_date')) {
        flatpickr("#birth_date", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            locale: "vn"
        });
    }
    if (document.getElementById('join_date')) {
        flatpickr("#join_date", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            locale: "vn"
        });
    }

    // Format currency input
    const currencyInputs = document.querySelectorAll('.currency-input');
    currencyInputs.forEach(input => {
        const formatNumber = (value) => {
            if (!value) return '';
            const cleanValue = value.toString().replace(/\D/g, '');
            if (!cleanValue) return '';
            return parseInt(cleanValue, 10).toLocaleString('en-US');
        };

        input.addEventListener('input', function(e) {
            const cursorPosition = this.selectionStart;
            const originalLength = this.value.length;
            this.value = formatNumber(this.value);
            const newLength = this.value.length;
            const diff = newLength - originalLength;
            let newCursorPos = cursorPosition + diff;
            this.setSelectionRange(newCursorPos, newCursorPos);
        });

        if (input.value) {
            input.value = formatNumber(input.value);
        }
    });
});
</script>
@endpush
@endsection
