@extends('layouts.app')

@section('title', 'Thêm sản phẩm')
@section('page-title', 'Thêm sản phẩm mới')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <a href="{{ route('products.index') }}" class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
    </div>

    <form action="{{ route('products.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 space-y-4">
                <!-- Thông tin cơ bản -->
                <!-- Requirements: 1.3, 2.1 -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-box mr-2 text-primary"></i>Thông tin cơ bản</h2>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Mã sản phẩm <span class="text-red-500">*</span></label>
                                <input type="text" name="code" id="code" value="{{ old('code') }}" required placeholder="VD: SP001"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('code') border-red-500 @enderror">
                                @error('code')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Tên sản phẩm <span class="text-red-500">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="Nhập tên sản phẩm"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('name') border-red-500 @enderror">
                                @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Danh mục</label>
                                <select name="category" id="category" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="">-- Chọn danh mục --</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}" {{ old('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                    @endforeach
                                </select>
                                @error('category')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="unit" class="block text-sm font-medium text-gray-700 mb-1">Đơn vị <span class="text-red-500">*</span></label>
                                <input type="text" name="unit" id="unit" value="{{ old('unit', 'Cái') }}" required placeholder="VD: Cái, Hộp, Kg..."
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div class="md:col-span-2">
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                                <textarea name="description" id="description" rows="3" placeholder="Mô tả chi tiết sản phẩm..."
                                          class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">{{ old('description') }}</textarea>
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
                        <textarea name="note" id="note" rows="3" placeholder="Nhập ghi chú nếu có..."
                                  class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">{{ old('note') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <!-- Thông tin bổ sung -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-info-circle mr-2 text-primary"></i>Lưu ý</h2>
                    </div>
                    <div class="p-4">
                        <div class="text-sm text-gray-600 space-y-2">
                            <p><i class="fas fa-check-circle text-green-500 mr-2"></i>Sản phẩm chỉ chứa thông tin cơ bản</p>
                            <p><i class="fas fa-check-circle text-green-500 mr-2"></i>SKU và giá được quản lý khi nhập kho</p>
                            <p><i class="fas fa-check-circle text-green-500 mr-2"></i>Danh mục sử dụng ký tự A-Z</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors font-medium text-sm">
                        <i class="fas fa-save mr-2"></i>Lưu sản phẩm
                    </button>
                    <a href="{{ route('products.index') }}" class="mt-2 w-full inline-block text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 text-sm">
                        Hủy bỏ
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
