@extends('layouts.app')

@section('title', 'Sửa tài sản')
@section('page-title', 'Chỉnh sửa Tài sản')

@section('content')
<div class="w-full">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex items-center gap-3">
            <a href="{{ route('employee-assets.show', $employeeAsset) }}" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h2 class="font-semibold text-gray-800">Sửa: {{ $employeeAsset->name }}</h2>
        </div>

        <form action="{{ route('employee-assets.update', $employeeAsset) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
            @csrf @method('PUT')



            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mã tài sản <span class="text-red-500">*</span></label>
                    <input type="text" name="asset_code" value="{{ old('asset_code', $employeeAsset->asset_code) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('asset_code') border-red-500 @enderror">
                    @error('asset_code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tên tài sản <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $employeeAsset->name) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('name') border-red-500 @enderror">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Danh mục <span class="text-red-500">*</span></label>
                    <input type="text" name="category" value="{{ old('category', $employeeAsset->category) }}"
                        list="category-list"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('category') border-red-500 @enderror">
                    <datalist id="category-list">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hãng / Nhà sản xuất</label>
                    <input type="text" name="brand" value="{{ old('brand', $employeeAsset->brand) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>

            @if($employeeAsset->tracking_type === 'serial')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Số Serial / Imei</label>
                    <input type="text" name="serial_number" value="{{ old('serial_number', $employeeAsset->serial_number) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary font-mono"
                        placeholder="Có thể để trống">
                </div>
            @else
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tổng số lượng</label>
                    <input type="number" name="quantity_total" value="{{ old('quantity_total', $employeeAsset->quantity_total) }}" min="1"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <p class="text-xs text-gray-400 mt-1">Hiện còn: {{ $employeeAsset->quantity_available }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ngày mua</label>
                    <input type="date" name="purchase_date" value="{{ old('purchase_date', $employeeAsset->purchase_date?->format('Y-m-d')) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Giá mua (VND)</label>
                    <input type="number" name="purchase_price" value="{{ old('purchase_price', $employeeAsset->purchase_price) }}" min="0"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hết hạn bảo hành</label>
                    <input type="date" name="warranty_expiry" value="{{ old('warranty_expiry', $employeeAsset->warranty_expiry?->format('Y-m-d')) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="available"   {{ $employeeAsset->status === 'available'   ? 'selected' : '' }}>Sẵn sàng</option>
                        <option value="maintenance" {{ $employeeAsset->status === 'maintenance' ? 'selected' : '' }}>Đang bảo trì</option>
                        <option value="disposed"    {{ $employeeAsset->status === 'disposed'    ? 'selected' : '' }}>Thanh lý</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vị trí lưu trữ</label>
                    <input type="text" name="location" value="{{ old('location', $employeeAsset->location) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả / Ghi chú</label>
                <textarea name="description" rows="3"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">{{ old('description', $employeeAsset->description) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ảnh mới (để trống nếu không đổi)</label>
                @if($employeeAsset->image)
                    <img src="{{ Storage::url($employeeAsset->image) }}" alt="Ảnh tài sản" class="h-20 w-20 object-cover rounded-lg mb-2">
                @endif
                <input type="file" name="image" accept="image/*"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm font-medium">
                    <i class="fas fa-save mr-2"></i>Cập nhật
                </button>
                <a href="{{ route('employee-assets.show', $employeeAsset) }}"
                    class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm">Huỷ</a>
            </div>
        </form>
    </div>
</div>
@endsection
