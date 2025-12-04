@extends('layouts.app')

@section('title', 'Sửa sản phẩm')
@section('page-title', 'Chỉnh sửa sản phẩm')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <a href="{{ route('products.index') }}" class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
        <a href="{{ route('products.show', $product->id) }}" class="inline-flex items-center px-3 py-1.5 text-sm bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200">
            <i class="fas fa-eye mr-2"></i>Xem chi tiết
        </a>
    </div>

    <form action="{{ route('products.update', $product->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 space-y-4">
                <!-- Thông tin cơ bản -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-box mr-2 text-primary"></i>Thông tin cơ bản</h2>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Mã sản phẩm <span class="text-red-500">*</span></label>
                                <input type="text" name="code" id="code" value="{{ old('code', $product->code) }}" required
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('code') border-red-500 @enderror">
                                @error('code')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Tên sản phẩm <span class="text-red-500">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" required
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('name') border-red-500 @enderror">
                                @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Danh mục</label>
                                <input type="text" name="category" id="category" value="{{ old('category', $product->category) }}"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="unit" class="block text-sm font-medium text-gray-700 mb-1">Đơn vị <span class="text-red-500">*</span></label>
                                <input type="text" name="unit" id="unit" value="{{ old('unit', $product->unit) }}" required
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div class="md:col-span-2">
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                                <textarea name="description" id="description" rows="2"
                                          class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">{{ old('description', $product->description) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Giá -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-dollar-sign mr-2 text-primary"></i>Thông tin giá</h2>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Giá bán (VNĐ) <span class="text-red-500">*</span></label>
                                <input type="number" name="price" id="price" value="{{ old('price', $product->price) }}" required min="0" step="1000"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="cost" class="block text-sm font-medium text-gray-700 mb-1">Giá vốn (VNĐ) <span class="text-red-500">*</span></label>
                                <input type="number" name="cost" id="cost" value="{{ old('cost', $product->cost) }}" required min="0" step="1000"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tồn kho -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-warehouse mr-2 text-primary"></i>Tồn kho</h2>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="min_stock" class="block text-sm font-medium text-gray-700 mb-1">Tồn kho tối thiểu</label>
                                <input type="number" name="min_stock" id="min_stock" value="{{ old('min_stock', $product->min_stock) }}" min="0"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="max_stock" class="block text-sm font-medium text-gray-700 mb-1">Tồn kho tối đa</label>
                                <input type="number" name="max_stock" id="max_stock" value="{{ old('max_stock', $product->max_stock) }}" min="0"
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
                        <textarea name="note" id="note" rows="2" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">{{ old('note', $product->note) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <!-- Loại quản lý -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-cog mr-2 text-primary"></i>Loại quản lý</h2>
                    </div>
                    <div class="p-4">
                        <select name="management_type" id="management_type" required class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="normal" {{ old('management_type', $product->management_type) == 'normal' ? 'selected' : '' }}>Thường</option>
                            <option value="serial" {{ old('management_type', $product->management_type) == 'serial' ? 'selected' : '' }}>Serial Number</option>
                            <option value="lot" {{ old('management_type', $product->management_type) == 'lot' ? 'selected' : '' }}>Lot Number</option>
                        </select>
                    </div>
                </div>

                <!-- Serial Config -->
                <div id="serial-config" class="bg-white rounded-lg shadow-sm hidden">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-barcode mr-2 text-primary"></i>Cấu hình Serial</h2>
                    </div>
                    <div class="p-4 space-y-3">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="auto_generate_serial" value="1" {{ old('auto_generate_serial', $product->auto_generate_serial) ? 'checked' : '' }}
                                   class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Tự động tạo Serial</span>
                        </label>
                        <div>
                            <label for="serial_prefix" class="block text-sm font-medium text-gray-700 mb-1">Tiền tố Serial</label>
                            <input type="text" name="serial_prefix" id="serial_prefix" value="{{ old('serial_prefix', $product->serial_prefix) }}"
                                   class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                    </div>
                </div>

                <!-- Lot Config -->
                <div id="lot-config" class="bg-white rounded-lg shadow-sm hidden">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-layer-group mr-2 text-primary"></i>Cấu hình Lot</h2>
                    </div>
                    <div class="p-4 space-y-3">
                        <div>
                            <label for="expiry_months" class="block text-sm font-medium text-gray-700 mb-1">Số tháng hết hạn</label>
                            <input type="number" name="expiry_months" id="expiry_months" value="{{ old('expiry_months', $product->expiry_months) }}" min="0"
                                   class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="track_expiry" value="1" {{ old('track_expiry', $product->track_expiry) ? 'checked' : '' }}
                                   class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Theo dõi hạn sử dụng</span>
                        </label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors font-medium text-sm">
                        <i class="fas fa-save mr-2"></i>Cập nhật
                    </button>
                    <a href="{{ route('products.index') }}" class="mt-2 w-full inline-block text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 text-sm">
                        Hủy bỏ
                    </a>
                </div>

                <!-- Thông tin hệ thống -->
                <div class="bg-gray-50 rounded-lg p-3 text-xs text-gray-600">
                    <div class="flex justify-between mb-1">
                        <span>Ngày tạo:</span>
                        <span class="font-medium">{{ $product->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Cập nhật:</span>
                        <span class="font-medium">{{ $product->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const managementType = document.getElementById('management_type');
    const serialConfig = document.getElementById('serial-config');
    const lotConfig = document.getElementById('lot-config');

    function toggleConfig() {
        serialConfig.classList.add('hidden');
        lotConfig.classList.add('hidden');
        if (managementType.value === 'serial') serialConfig.classList.remove('hidden');
        else if (managementType.value === 'lot') lotConfig.classList.remove('hidden');
    }

    toggleConfig();
    managementType.addEventListener('change', toggleConfig);
});
</script>
@endsection
