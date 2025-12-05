@extends('layouts.app')

@section('title', 'Thêm kho mới')
@section('page-title', 'Thêm kho mới')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Thông tin kho hàng</h2>
        </div>
        
        <form action="{{ route('warehouses.store') }}" method="POST" class="p-4">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Mã kho -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                        Mã kho <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="code" id="code" value="{{ old('code', $code) }}" 
                           class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent @error('code') border-red-500 @enderror">
                    @error('code')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tên kho -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Tên kho <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" 
                           class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Loại kho -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">
                        Loại kho <span class="text-red-500">*</span>
                    </label>
                    <select name="type" id="type" 
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent @error('type') border-red-500 @enderror">
                        <option value="physical" {{ old('type') == 'physical' ? 'selected' : '' }}>Kho vật lý</option>
                        <option value="virtual" {{ old('type') == 'virtual' ? 'selected' : '' }}>Kho ảo</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Trạng thái -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                        Trạng thái <span class="text-red-500">*</span>
                    </label>
                    <select name="status" id="status" 
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent @error('status') border-red-500 @enderror">
                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Đang hoạt động</option>
                        <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>Đang bảo trì</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Ngừng hoạt động</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Người quản lý -->
                <div>
                    <label for="manager_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Người quản lý
                    </label>
                    <select name="manager_id" id="manager_id" 
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">-- Chọn người quản lý --</option>
                        @foreach($managers as $manager)
                            <option value="{{ $manager->id }}" {{ old('manager_id') == $manager->id ? 'selected' : '' }}>
                                {{ $manager->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('manager_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Số điện thoại -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                        Số điện thoại
                    </label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}" 
                           class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    @error('phone')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Diện tích -->
                <div>
                    <label for="area" class="block text-sm font-medium text-gray-700 mb-1">
                        Diện tích (m²)
                    </label>
                    <input type="number" name="area" id="area" value="{{ old('area') }}" step="0.01" min="0"
                           class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    @error('area')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Sức chứa -->
                <div>
                    <label for="capacity" class="block text-sm font-medium text-gray-700 mb-1">
                        Sức chứa
                    </label>
                    <input type="number" name="capacity" id="capacity" value="{{ old('capacity') }}" min="0"
                           class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    @error('capacity')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Loại sản phẩm -->
                <div class="md:col-span-2">
                    <label for="product_type" class="block text-sm font-medium text-gray-700 mb-1">
                        Loại sản phẩm lưu trữ
                    </label>
                    <input type="text" name="product_type" id="product_type" value="{{ old('product_type') }}" 
                           placeholder="VD: Điện tử, Thực phẩm, Hóa chất..."
                           class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    @error('product_type')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Địa chỉ -->
                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">
                        Địa chỉ
                    </label>
                    <textarea name="address" id="address" rows="2"
                              class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">{{ old('address') }}</textarea>
                    @error('address')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tính năng -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tính năng</label>
                    <div class="flex flex-wrap gap-4">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="has_temperature_control" value="1" 
                                   {{ old('has_temperature_control') ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-primary focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Kiểm soát nhiệt độ</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="has_security_system" value="1" 
                                   {{ old('has_security_system') ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-primary focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Hệ thống an ninh</span>
                        </label>
                    </div>
                </div>

                <!-- Ghi chú -->
                <div class="md:col-span-2">
                    <label for="note" class="block text-sm font-medium text-gray-700 mb-1">
                        Ghi chú
                    </label>
                    <textarea name="note" id="note" rows="3"
                              class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">{{ old('note') }}</textarea>
                    @error('note')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                <a href="{{ route('warehouses.index') }}" 
                   class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Hủy
                </a>
                <button type="submit" 
                        class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:bg-primary-dark transition-colors">
                    <i class="fas fa-save mr-2"></i>Lưu
                </button>
            </div>
        </form>
</div>
@endsection
