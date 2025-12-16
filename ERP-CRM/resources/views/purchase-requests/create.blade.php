@extends('layouts.app')

@section('title', 'Tạo yêu cầu báo giá')
@section('page-title', 'Tạo yêu cầu báo giá mới')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-900">
            <i class="fas fa-file-invoice text-orange-500 mr-2"></i>Thông tin yêu cầu báo giá
        </h2>
        <a href="{{ route('purchase-requests.index') }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại
        </a>
    </div>
    
    <form action="{{ route('purchase-requests.store') }}" method="POST" id="requestForm" class="p-4">
        @csrf
        
        <!-- Thông tin chung -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mã yêu cầu <span class="text-red-500">*</span></label>
                <input type="text" name="code" value="{{ old('code', $code) }}" required readonly
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg bg-gray-50">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" required placeholder="VD: Yêu cầu báo giá thiết bị văn phòng"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hạn báo giá <span class="text-red-500">*</span></label>
                <input type="date" name="deadline" value="{{ old('deadline', now()->addDays(7)->format('Y-m-d')) }}" required
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mức ưu tiên</label>
                <select name="priority" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                    <option value="normal">Bình thường</option>
                    <option value="high">Cao</option>
                    <option value="urgent">Khẩn cấp</option>
                </select>
            </div>
        </div>

        <!-- Chọn NCC -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Chọn nhà cung cấp gửi yêu cầu <span class="text-red-500">*</span></label>
            <select name="suppliers[]" id="suppliersSelect" multiple required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->code }} - {{ $supplier->name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">Có thể chọn nhiều nhà cung cấp để gửi yêu cầu báo giá</p>
            @error('suppliers')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Danh sách sản phẩm -->
        <div class="border-t border-gray-200 pt-4 mb-6">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-900">Danh sách sản phẩm cần báo giá</h3>
                <button type="button" id="addItem" class="px-4 py-2 text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                    <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
                </button>
            </div>
            <div id="itemsContainer" class="space-y-3">
                <div class="item-row grid grid-cols-12 gap-3 items-end p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="col-span-4">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Tên sản phẩm</label>
                        <input type="text" name="items[0][product_name]" required placeholder="Nhập tên sản phẩm"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Số lượng</label>
                        <input type="number" name="items[0][quantity]" value="1" min="1" required
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Đơn vị</label>
                        <input type="text" name="items[0][unit]" value="Cái"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="col-span-3">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Quy cách/Mô tả</label>
                        <input type="text" name="items[0][specifications]" placeholder="Quy cách kỹ thuật"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="col-span-1 flex justify-center">
                        <button type="button" class="remove-item w-8 h-8 bg-red-100 text-red-600 rounded-lg hover:bg-red-200" style="display:none;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Yêu cầu đặc biệt -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Yêu cầu đặc biệt</label>
            <textarea name="requirements" rows="3" placeholder="VD: Yêu cầu về bảo hành, giao hàng, thanh toán..."
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">{{ old('requirements') }}</textarea>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
            <a href="{{ route('purchase-requests.index') }}" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">Hủy</a>
            <button type="submit" class="px-4 py-2 text-sm bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                <i class="fas fa-save mr-2"></i> Lưu nháp
            </button>
            <button type="submit" name="send" value="1" class="px-4 py-2 text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                <i class="fas fa-paper-plane mr-2"></i> Lưu và gửi NCC
            </button>
        </div>
    </form>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<style>
    .ts-wrapper.multi .ts-control {
        padding: 6px 10px;
        border-radius: 0.5rem;
        border-color: #d1d5db;
        min-height: 42px;
    }
    .ts-wrapper .ts-control .item {
        background-color: #3b82f6;
        color: white;
        border-radius: 4px;
        padding: 2px 8px;
    }
    .ts-dropdown {
        border-radius: 0.5rem;
    }
    .ts-dropdown .option {
        padding: 10px 12px;
    }
    .ts-dropdown .option.active {
        background-color: #3b82f6;
        color: white;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new TomSelect('#suppliersSelect', {
        plugins: ['remove_button'],
        placeholder: 'Tìm và chọn nhà cung cấp...',
        allowEmptyOption: false,
        maxOptions: 100,
        render: {
            no_results: function() {
                return '<div class="no-results p-3 text-gray-500">Không tìm thấy nhà cung cấp</div>';
            }
        }
    });
});

let itemIndex = 1;

document.getElementById('addItem').addEventListener('click', function() {
    const container = document.getElementById('itemsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'item-row grid grid-cols-12 gap-3 items-end p-3 bg-gray-50 rounded-lg border border-gray-200';
    newRow.innerHTML = `
        <div class="col-span-4">
            <label class="block text-xs font-medium text-gray-500 mb-1">Tên sản phẩm</label>
            <input type="text" name="items[${itemIndex}][product_name]" required placeholder="Nhập tên sản phẩm"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div class="col-span-2">
            <label class="block text-xs font-medium text-gray-500 mb-1">Số lượng</label>
            <input type="number" name="items[${itemIndex}][quantity]" value="1" min="1" required
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div class="col-span-2">
            <label class="block text-xs font-medium text-gray-500 mb-1">Đơn vị</label>
            <input type="text" name="items[${itemIndex}][unit]" value="Cái"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div class="col-span-3">
            <label class="block text-xs font-medium text-gray-500 mb-1">Quy cách/Mô tả</label>
            <input type="text" name="items[${itemIndex}][specifications]" placeholder="Quy cách kỹ thuật"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div class="col-span-1 flex justify-center">
            <button type="button" class="remove-item w-8 h-8 bg-red-100 text-red-600 rounded-lg hover:bg-red-200">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(newRow);
    itemIndex++;
    updateRemoveButtons();
});

document.getElementById('itemsContainer').addEventListener('click', function(e) {
    if (e.target.closest('.remove-item')) {
        e.target.closest('.item-row').remove();
        updateRemoveButtons();
    }
});

function updateRemoveButtons() {
    const rows = document.querySelectorAll('.item-row');
    rows.forEach((row, index) => {
        const btn = row.querySelector('.remove-item');
        btn.style.display = rows.length > 1 ? 'block' : 'none';
    });
}
</script>
@endpush
@endsection
