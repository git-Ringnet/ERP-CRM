@extends('layouts.app')

@section('title', 'Chi tiết nhân viên')
@section('page-title', 'Chi tiết nhân viên')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <a href="{{ route('employees.index') }}" 
           class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
        <div class="flex gap-2">
            <a href="{{ route('employees.edit', $employee->id) }}" 
               class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-edit mr-2"></i>Chỉnh sửa
            </a>
            <form action="{{ route('employees.destroy', $employee->id) }}" method="POST" class="inline delete-form">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-danger text-white rounded-lg hover:bg-red-700 transition-colors delete-btn"
                        data-name="{{ $employee->name }}">
                    <i class="fas fa-trash mr-2"></i>Xóa
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Thông tin cá nhân -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-user mr-2 text-primary"></i>Thông tin cá nhân
                    </h2>
                    @if($employee->status == 'active')
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>Đang làm việc
                        </span>
                    @elseif($employee->status == 'leave')
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            <i class="fas fa-clock mr-1"></i>Nghỉ phép
                        </span>
                    @else
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i>Đã nghỉ việc
                        </span>
                    @endif
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Mã nhân viên</label>
                            <p class="text-base font-semibold text-gray-900">{{ $employee->employee_code }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Họ và tên</label>
                            <p class="text-base font-semibold text-gray-900">{{ $employee->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Email</label>
                            <p class="text-base text-gray-900">
                                <a href="mailto:{{ $employee->email }}" class="text-primary hover:underline">
                                    <i class="fas fa-envelope mr-1 text-gray-400"></i>{{ $employee->email }}
                                </a>
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Điện thoại</label>
                            <p class="text-base text-gray-900">
                                <a href="tel:{{ $employee->phone }}" class="text-primary hover:underline">
                                    <i class="fas fa-phone mr-1 text-gray-400"></i>{{ $employee->phone }}
                                </a>
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Ngày sinh</label>
                            <p class="text-base text-gray-900">
                                {{ $employee->birth_date ? \Carbon\Carbon::parse($employee->birth_date)->format('d/m/Y') : 'Chưa cập nhật' }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">CMND/CCCD</label>
                            <p class="text-base text-gray-900">{{ $employee->id_card ?: 'Chưa cập nhật' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-500 mb-1">Địa chỉ</label>
                            <p class="text-base text-gray-900">
                                <i class="fas fa-map-marker-alt mr-1 text-gray-400"></i>
                                {{ $employee->address ?: 'Chưa cập nhật' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thông tin công việc -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-briefcase mr-2 text-primary"></i>Thông tin công việc
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Phòng ban</label>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                                <i class="fas fa-building mr-1"></i>{{ $employee->department }}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Chức vụ</label>
                            <p class="text-base font-semibold text-gray-900">{{ $employee->position }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Ngày vào làm</label>
                            <p class="text-base text-gray-900">
                                {{ $employee->join_date ? \Carbon\Carbon::parse($employee->join_date)->format('d/m/Y') : 'Chưa cập nhật' }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Thời gian làm việc</label>
                            <p class="text-base text-gray-900">
                                @if($employee->join_date)
                                    {{ \Carbon\Carbon::parse($employee->join_date)->diffForHumans(null, true) }}
                                @else
                                    Chưa cập nhật
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thông tin ngân hàng -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-university mr-2 text-primary"></i>Thông tin ngân hàng
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Số tài khoản</label>
                            <p class="text-base text-gray-900">{{ $employee->bank_account ?: 'Chưa cập nhật' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Ngân hàng</label>
                            <p class="text-base text-gray-900">{{ $employee->bank_name ?: 'Chưa cập nhật' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ghi chú -->
            @if($employee->note)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-sticky-note mr-2 text-primary"></i>Ghi chú
                    </h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-700 whitespace-pre-line">{{ $employee->note }}</p>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Lương -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-money-bill-wave mr-2 text-primary"></i>Lương
                    </h2>
                </div>
                <div class="p-6">
                    <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                        <label class="block text-sm font-medium text-green-700 mb-1">Mức lương</label>
                        <p class="text-2xl font-bold text-green-900">{{ number_format($employee->salary) }} đ</p>
                    </div>
                </div>
            </div>

            <!-- Thông tin hệ thống -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-info-circle mr-2 text-primary"></i>Thông tin hệ thống
                    </h2>
                </div>
                <div class="p-6 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Trạng thái tài khoản</span>
                        @if($employee->is_locked)
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                <i class="fas fa-lock mr-1"></i>Đã khóa
                            </span>
                        @else
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-unlock mr-1"></i>Đang mở
                            </span>
                        @endif
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Ngày tạo</span>
                        <span class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($employee->created_at)->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Cập nhật lần cuối</span>
                        <span class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($employee->updated_at)->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Thao tác nhanh</h3>
                <div class="space-y-2">
                    <!-- <a href="{{ route('employees.edit', $employee->id) }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                        <i class="fas fa-edit mr-2"></i>Chỉnh sửa
                    </a> -->
                    <form action="{{ route('employees.toggle-lock', $employee->id) }}" method="POST" class="w-full">
                        @csrf
                        <button type="submit" 
                                class="w-full inline-flex items-center justify-center px-4 py-2 {{ $employee->is_locked ? 'bg-green-600 hover:bg-green-700' : 'bg-orange-600 hover:bg-orange-700' }} text-white rounded-lg transition-colors">
                            <i class="fas fa-{{ $employee->is_locked ? 'unlock' : 'lock' }} mr-2"></i>
                            {{ $employee->is_locked ? 'Mở khóa tài khoản' : 'Khóa tài khoản' }}
                        </button>
                    </form>
                    <a href="{{ route('employees.index') }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-list mr-2"></i>Danh sách
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
