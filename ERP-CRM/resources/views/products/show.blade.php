@extends('layouts.app')

@section('title', 'Chi tiết sản phẩm')
@section('page-title', 'Chi tiết sản phẩm')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <a href="{{ route('products.index') }}" 
           class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
        <div class="flex gap-2">
            <a href="{{ route('products.edit', $product->id) }}" 
               class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-edit mr-2"></i>Chỉnh sửa
            </a>
            <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="inline delete-form">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-danger text-white rounded-lg hover:bg-red-700 transition-colors delete-btn"
                        data-name="{{ $product->name }}">
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
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-box mr-2 text-primary"></i>Thông tin cơ bản
                    </h2>
                    @if($product->management_type == 'serial')
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">
                            <i class="fas fa-barcode mr-1"></i>Serial
                        </span>
                    @elseif($product->management_type == 'lot')
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-purple-100 text-purple-800">
                            <i class="fas fa-layer-group mr-1"></i>Lot
                        </span>
                    @else
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">
                            <i class="fas fa-box mr-1"></i>Thường
                        </span>
                    @endif
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Mã sản phẩm</label>
                            <p class="text-base font-semibold text-gray-900">{{ $product->code }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Tên sản phẩm</label>
                            <p class="text-base font-semibold text-gray-900">{{ $product->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Danh mục</label>
                            <p class="text-base text-gray-900">{{ $product->category ?: 'Chưa phân loại' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Đơn vị</label>
                            <p class="text-base text-gray-900">{{ $product->unit }}</p>
                        </div>
                        @if($product->description)
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-500 mb-1">Mô tả</label>
                            <p class="text-base text-gray-700 whitespace-pre-line">{{ $product->description }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Thông tin giá -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-dollar-sign mr-2 text-primary"></i>Thông tin giá
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                            <label class="block text-sm font-medium text-green-700 mb-1">Giá bán</label>
                            <p class="text-2xl font-bold text-green-900">{{ number_format($product->price) }} đ</p>
                        </div>
                        <div class="bg-orange-50 rounded-lg p-4 border border-orange-200">
                            <label class="block text-sm font-medium text-orange-700 mb-1">Giá vốn</label>
                            <p class="text-2xl font-bold text-orange-900">{{ number_format($product->cost) }} đ</p>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                            <label class="block text-sm font-medium text-blue-700 mb-1">Lợi nhuận</label>
                            <p class="text-2xl font-bold text-blue-900">{{ number_format($product->price - $product->cost) }} đ</p>
                            <p class="text-sm text-blue-600">
                                ({{ $product->cost > 0 ? number_format((($product->price - $product->cost) / $product->cost) * 100, 1) : 0 }}%)
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ghi chú -->
            @if($product->note)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-sticky-note mr-2 text-primary"></i>Ghi chú
                    </h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-700 whitespace-pre-line">{{ $product->note }}</p>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Tồn kho -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-warehouse mr-2 text-primary"></i>Tồn kho
                    </h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tồn kho hiện tại</label>
                        <p class="text-2xl font-bold {{ $product->stock <= $product->min_stock ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $product->stock }} {{ $product->unit }}
                        </p>
                        @if($product->stock <= $product->min_stock)
                            <p class="text-sm text-red-600 mt-1">
                                <i class="fas fa-exclamation-triangle mr-1"></i>Dưới mức tối thiểu
                            </p>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-200">
                            <label class="block text-xs font-medium text-yellow-700 mb-1">Tối thiểu</label>
                            <p class="text-lg font-bold text-yellow-900">{{ $product->min_stock }}</p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-3 border border-green-200">
                            <label class="block text-xs font-medium text-green-700 mb-1">Tối đa</label>
                            <p class="text-lg font-bold text-green-900">{{ $product->max_stock }}</p>
                        </div>
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
                        <span class="text-sm font-medium text-gray-900">{{ $product->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Cập nhật lần cuối</span>
                        <span class="text-sm font-medium text-gray-900">{{ $product->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Thao tác nhanh</h3>
                <div class="space-y-2">
                    <a href="{{ route('products.edit', $product->id) }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                        <i class="fas fa-edit mr-2"></i>Chỉnh sửa
                    </a>
                    <a href="{{ route('products.index') }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-list mr-2"></i>Danh sách
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
