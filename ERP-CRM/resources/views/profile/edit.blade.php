@extends('layouts.app')

@section('title', 'Chỉnh sửa hồ sơ')
@section('page-title', 'Chỉnh sửa hồ sơ')

@section('content')
<div class="w-full h-full mx-auto space-y-6">
    <!-- Profile Information -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-user-circle mr-2 text-primary"></i>Thông tin cá nhân
            </h2>
            <p class="mt-1 text-sm text-gray-500">Cập nhật thông tin tài khoản và email của bạn.</p>
        </div>
        <div class="p-6">
            <form method="post" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('patch')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Họ và tên <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="position" class="block text-sm font-medium text-gray-700 mb-1">Chức vụ</label>
                        <input type="text" name="position" id="position" value="{{ old('position', $user->position) }}" readonly
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
                    </div>

                    <div>
                        <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Phòng ban</label>
                        <input type="text" name="department" id="department" value="{{ old('department', $user->department) }}" readonly
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
                    </div>

                    <div>
                        <label for="employee_code" class="block text-sm font-medium text-gray-700 mb-1">Mã nhân viên</label>
                        <input type="text" id="employee_code" value="{{ $user->employee_code }}" readonly
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
                    </div>
                </div>

                <div class="flex items-center justify-end pt-4 border-t border-gray-200">
                    @if (session('status') === 'profile-updated')
                        <span class="text-sm text-green-600 mr-4">
                            <i class="fas fa-check-circle mr-1"></i>Đã lưu thành công!
                        </span>
                    @endif
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                        <i class="fas fa-save mr-2"></i>Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Password -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-lock mr-2 text-primary"></i>Đổi mật khẩu
            </h2>
            <p class="mt-1 text-sm text-gray-500">Đảm bảo tài khoản của bạn sử dụng mật khẩu mạnh để bảo mật.</p>
        </div>
        <div class="p-6">
            <form method="post" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                @method('put')

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu hiện tại <span class="text-red-500">*</span></label>
                        <input type="password" name="current_password" id="current_password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        @error('current_password', 'updatePassword')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu mới <span class="text-red-500">*</span></label>
                        <input type="password" name="password" id="password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        @error('password', 'updatePassword')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Xác nhận mật khẩu <span class="text-red-500">*</span></label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                </div>

                <div class="flex items-center justify-end pt-4 border-t border-gray-200">
                    @if (session('status') === 'password-updated')
                        <span class="text-sm text-green-600 mr-4">
                            <i class="fas fa-check-circle mr-1"></i>Đã đổi mật khẩu thành công!
                        </span>
                    @endif
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                        <i class="fas fa-key mr-2"></i>Đổi mật khẩu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
