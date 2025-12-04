@extends('layouts.app')

@section('title', 'Chi tiết nhà cung cấp')
@section('page-title', 'Chi tiết nhà cung cấp')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <a href="{{ route('suppliers.index') }}" 
           class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
        <div class="flex gap-2">
            <a href="{{ route('suppliers.edit', $supplier->id) }}" 
               class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-edit mr-2"></i>Chỉnh sửa
            </a>
            <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" class="inline delete-form">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-danger text-white rounded-lg hover:bg-red-700 transition-colors delete-btn"
                        data-name="{{ $supplier->name }}">
                    <i class="fas fa-trash mr-2"></i>Xóa
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Thông tin cơ bản -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-truck mr-2 text-primary"></i>Thông tin cơ bản
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Mã NCC</label>
                            <p class="text-base font-semibold text-gray-900">{{ $supplier->code }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Tên nhà cung cấp</label>
                            <p class="text-base font-semibold text-gray-900">{{ $supplier->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Email</label>
                            <p class="text-base text-gray-900">
                                <a href="mailto:{{ $supplier->email }}" class="text-primary hover:underline">
                                    <i class="fas fa-envelope mr-1 text-gray-400"></i>{{ $supplier->email }}
                                </a>
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Điện thoại</label>
                            <p class="text-base text-gray-900">
                                <a href="tel:{{ $supplier->phone }}" class="text-primary hover:underline">
                                    <i class="fas fa-phone mr-1 text-gray-400"></i>{{ $supplier->phone }}
                                </a>
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Người liên hệ</label>
                            <p class="text-base text-gray-900">{{ $supplier->contact_person ?: 'Chưa cập nhật' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Mã số thuế</label>
                            <p class="text-base text-gray-900">{{ $supplier->tax_code ?: 'Chưa cập nhật' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-500 mb-1">Địa chỉ</label>
                            <p class="text-base text-gray-900">
                                <i class="fas fa-map-marker-alt mr-1 text-gray-400"></i>
                                {{ $supplier->address ?: 'Chưa cập nhật' }}
                            </p>
                        </div>
                        @if($supplier->website)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Website</label>
                            <p class="text-base text-gray-900">
                                <a href="{{ $supplier->website }}" target="_blank" class="text-primary hover:underline">
                                    <i class="fas fa-globe mr-1 text-gray-400"></i>{{ $supplier->website }}
                                </a>
                            </p>
                        </div>
                        @endif
                        @if($supplier->product_type)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Loại sản phẩm</label>
                            <p class="text-base text-gray-900">{{ $supplier->product_type }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Ghi chú -->
            @if($supplier->note)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-sticky-note mr-2 text-primary"></i>Ghi chú
                    </h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-700 whitespace-pre-line">{{ $supplier->note }}</p>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Điều khoản thanh toán -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-credit-card mr-2 text-primary"></i>Thanh toán
                    </h2>
                </div>
                <div class="p-6">
                    <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                        <label class="block text-sm font-medium text-blue-700 mb-1">Điều khoản thanh toán</label>
                        <p class="text-2xl font-bold text-blue-900">{{ $supplier->payment_terms }} ngày</p>
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
                        <span class="text-sm text-gray-500">Ngày tạo</span>
                        <span class="text-sm font-medium text-gray-900">{{ $supplier->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Cập nhật lần cuối</span>
                        <span class="text-sm font-medium text-gray-900">{{ $supplier->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Thao tác nhanh</h3>
                <div class="space-y-2">
                    <a href="{{ route('suppliers.edit', $supplier->id) }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                        <i class="fas fa-edit mr-2"></i>Chỉnh sửa
                    </a>
                    <a href="{{ route('suppliers.index') }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-list mr-2"></i>Danh sách
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
