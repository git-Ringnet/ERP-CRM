@extends('layouts.app')

@section('title', 'Thêm tài sản mới')
@section('page-title', 'Thêm Tài sản / Dụng cụ Mới')

@section('content')
<div class="w-full">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex items-center gap-3">
            <a href="{{ route('employee-assets.index') }}" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h2 class="font-semibold text-gray-800">Thêm tài sản mới</h2>
        </div>

        <form action="{{ route('employee-assets.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
            @csrf



            {{-- Thông tin cơ bản --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mã tham chiếu (Optional)</label>
                    <input type="text" name="asset_code" value="{{ old('asset_code') }}" placeholder="VD: Để trống tự sinh mã TS-2026..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('asset_code') border-red-500 @enderror">
                    @error('asset_code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tên tài sản <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="VD: Laptop Dell XPS 15"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('name') border-red-500 @enderror">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Danh mục <span class="text-red-500">*</span></label>
                    <input type="text" name="category" value="{{ old('category') }}"
                        list="category-list" placeholder="VD: Thiết bị IT, Văn phòng..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('category') border-red-500 @enderror">
                    <datalist id="category-list">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">
                        @endforeach
                        <option value="Thiết bị IT">
                        <option value="Văn phòng phẩm">
                        <option value="Dụng cụ sản xuất">
                        <option value="Đồ bảo hộ lao động">
                        <option value="Thiết bị văn phòng">
                    </datalist>
                    @error('category') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hãng / Nhà sản xuất</label>
                    <input type="text" name="brand" value="{{ old('brand') }}" placeholder="VD: Dell, HP, Samsung..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>

            {{-- Số lượng và Serial --}}
            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng <span class="text-red-500">*</span></label>
                        <input type="number" id="quantity_total" name="quantity_total" value="{{ old('quantity_total', 1) }}" min="1"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('quantity_total') border-red-500 @enderror">
                        @error('quantity_total') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-500 mt-2">Tổng số lượng tài sản sẽ được tạo.</p>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nhập danh sách Serial (Mỗi serial là 1 tài sản riêng biệt)</label>
                        <textarea id="serial_list" name="serial_list" rows="3"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg font-mono focus:outline-none focus:ring-2 focus:ring-primary @error('serial_list') border-red-500 @enderror" 
                            placeholder="Nhập mã Serial, ngăn cách bằng dấu phẩy hoặc xuống dòng...">{{ old('serial_list') }}</textarea>
                        @error('serial_list') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        <p id="serial_info" class="text-xs text-gray-500 mt-2 font-medium bg-white px-2 py-1 rounded inline-block border border-gray-200">Đang chờ nhập...</p>
                    </div>
                </div>
            </div>

            {{-- Thông tin mua --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ngày mua</label>
                    <input type="date" name="purchase_date" value="{{ old('purchase_date') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Giá mua (VND)</label>
                    <input type="number" name="purchase_price" value="{{ old('purchase_price') }}" min="0" step="1000"
                        placeholder="0"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hết hạn bảo hành</label>
                    <input type="date" name="warranty_expiry" value="{{ old('warranty_expiry') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>

            {{-- Vị trí & Mô tả --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Vị trí lưu trữ</label>
                <input type="text" name="location" value="{{ old('location') }}" placeholder="VD: Kho A, Phòng IT..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả / Ghi chú</label>
                <textarea name="description" rows="3" placeholder="Mô tả thêm về tài sản..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">{{ old('description') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ảnh tài sản</label>
                <input type="file" name="image" accept="image/*"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm font-medium">
                    <i class="fas fa-save mr-2"></i>Lưu tài sản
                </button>
                <a href="{{ route('employee-assets.index') }}"
                    class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                    Huỷ
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const qtyInput = document.getElementById('quantity_total');
    const serialInput = document.getElementById('serial_list');
    const infoEl = document.getElementById('serial_info');

    function updateSerialInfo() {
        const qty = parseInt(qtyInput.value) || 0;
        const serialText = serialInput.value.trim();

        if (!serialText) {
            infoEl.innerHTML = `Sẽ tạo <strong>1</strong> tài sản tổng với khối lượng <strong>${qty}</strong> (Quản lý theo số lượng).`;
            infoEl.className = 'text-xs mt-2 font-medium px-2 py-1 rounded inline-block bg-gray-100 text-gray-600 border border-gray-200';
            return;
        }

        const serials = serialText.split(/[\n,]/).map(s => s.trim()).filter(s => s);
        const count = serials.length;

        if (count < qty) {
            infoEl.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i>Đã nhập <strong>${count}/${qty}</strong> serial. ${qty - count} tài sản sẽ bị trống mã Serial!`;
            infoEl.className = 'text-xs mt-2 font-medium px-2 py-1 rounded inline-block bg-yellow-50 text-yellow-700 border border-yellow-200';
        } else if (count > qty) {
            infoEl.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i>Đã nhập <strong>${count}</strong> serial. Chỉ <strong>${qty}</strong> serial đầu tiên được lưu.`;
            infoEl.className = 'text-xs mt-2 font-medium px-2 py-1 rounded inline-block bg-orange-50 text-orange-700 border border-orange-200';
        } else {
            infoEl.innerHTML = `<i class="fas fa-check-circle mr-1"></i>Đã nhập đủ mã Serial cho <strong>${qty}</strong> dòng tài sản.`;
            infoEl.className = 'text-xs mt-2 font-medium px-2 py-1 rounded inline-block bg-green-50 text-green-700 border border-green-200';
        }
    }

    qtyInput.addEventListener('input', updateSerialInfo);
    serialInput.addEventListener('input', updateSerialInfo);
    
    // Initial call
    updateSerialInfo();
});
</script>
@endpush
@endsection
