@extends('layouts.app')

@section('title', 'Chỉnh sửa phiếu chuyển')
@section('page-title', 'Chỉnh sửa Phiếu Chuyển Kho')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-900">Chỉnh sửa phiếu chuyển: {{ $transfer->code }}</h2>
        <a href="{{ route('transfers.show', $transfer) }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại
        </a>
    </div>
    
    @if($transfer->status !== 'pending')
    <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400">
        <p class="text-sm text-yellow-700">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            Chỉ có thể chỉnh sửa phiếu đang chờ xử lý.
        </p>
    </div>
    @else
    <form action="{{ route('transfers.update', $transfer) }}" method="POST" class="p-4">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mã phiếu chuyển</label>
                <input type="text" value="{{ $transfer->code }}" readonly
                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-gray-50">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Ngày chuyển <span class="text-red-500">*</span>
                </label>
                <input type="date" name="date" value="{{ old('date', $transfer->date->format('Y-m-d')) }}" required
                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nhân viên</label>
                <select name="employee_id" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                    <option value="">-- Chọn nhân viên --</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ $transfer->employee_id == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Kho nguồn <span class="text-red-500">*</span>
                </label>
                <select name="warehouse_id" required class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ $transfer->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Kho đích <span class="text-red-500">*</span>
                </label>
                <select name="to_warehouse_id" required class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ $transfer->to_warehouse_id == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                <textarea name="note" rows="2" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">{{ old('note', $transfer->note) }}</textarea>
            </div>
        </div>

        <div class="border-t border-gray-200 pt-4">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-900">Danh sách sản phẩm</h3>
                <button type="button" onclick="addItem()" class="px-4 py-2 text-sm bg-purple-500 text-white rounded-lg hover:bg-purple-600">
                    <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
                </button>
            </div>

            <div id="itemsContainer" class="space-y-4"></div>
        </div>

        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
            <a href="{{ route('transfers.show', $transfer) }}" 
               class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                <i class="fas fa-times mr-1"></i> Hủy
            </a>
            <button type="submit" class="px-4 py-2 text-sm text-white bg-purple-500 rounded-lg hover:bg-purple-600">
                <i class="fas fa-save mr-1"></i> Cập nhật phiếu chuyển
            </button>
        </div>
    </form>
    @endif
</div>

@push('scripts')
<script>
let itemIndex = 0;
const products = @json($products);
const existingItems = @json($existingItems);

function addItem(existingData = null) {
    const container = document.getElementById('itemsContainer');
    const itemDiv = document.createElement('div');
    itemDiv.className = 'item-card bg-gray-50 rounded-lg p-4 border border-gray-200';
    itemDiv.dataset.index = itemIndex;
    
    const productOptions = products.map(p => 
        `<option value="${p.id}" ${existingData && existingData.product_id == p.id ? 'selected' : ''}>${p.name} (${p.code})</option>`
    ).join('');
    
    itemDiv.innerHTML = `
        <div class="flex justify-between items-center mb-3">
            <h4 class="font-medium text-gray-700">Sản phẩm #${itemIndex + 1}</h4>
            <button type="button" onclick="removeItem(${itemIndex})" 
                    class="px-2 py-1 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200">
                <i class="fas fa-trash mr-1"></i>Xóa
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Sản phẩm *</label>
                <select name="items[${itemIndex}][product_id]" required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded">
                    <option value="">-- Chọn sản phẩm --</option>
                    ${productOptions}
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Số lượng *</label>
                <input type="number" name="items[${itemIndex}][quantity]" value="${existingData ? existingData.quantity : ''}" 
                       required min="1" step="1" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded" placeholder="0">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Đơn vị</label>
                <input type="text" name="items[${itemIndex}][unit]" value="${existingData ? existingData.unit || '' : ''}"
                       class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded" placeholder="Cái, Hộp...">
            </div>
        </div>
    `;
    
    container.appendChild(itemDiv);
    itemIndex++;
}

function removeItem(index) {
    const item = document.querySelector(`[data-index="${index}"]`);
    if (item) item.remove();
}

document.addEventListener('DOMContentLoaded', function() {
    existingItems.forEach(item => addItem(item));
    if (existingItems.length === 0) addItem();
});
</script>
@endpush
@endsection
