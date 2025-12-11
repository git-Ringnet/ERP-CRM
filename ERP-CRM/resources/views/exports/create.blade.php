@extends('layouts.app')

@section('title', 'Tạo phiếu xuất kho')
@section('page-title', 'Tạo Phiếu Xuất Kho')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-900">
            <i class="fas fa-arrow-up text-orange-500 mr-2"></i>Thông tin phiếu xuất
        </h2>
        <a href="{{ route('exports.index') }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại
        </a>
    </div>
    
    <form action="{{ route('exports.store') }}" method="POST" class="p-4">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mã phiếu xuất</label>
                <input type="text" value="{{ $code }}" readonly
                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-gray-50">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Ngày xuất <span class="text-red-500">*</span>
                </label>
                <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Kho xuất <span class="text-red-500">*</span>
                </label>
                <select name="warehouse_id" id="warehouseSelect" required 
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                    <option value="">-- Chọn kho --</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nhân viên xuất</label>
                <select name="employee_id" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                    <option value="">-- Chọn nhân viên --</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                <textarea name="note" rows="2" 
                          class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">{{ old('note') }}</textarea>
            </div>
        </div>

        <div class="border-t border-gray-200 pt-4">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-900">Danh sách sản phẩm xuất</h3>
                <button type="button" onclick="addItem()" 
                        class="px-4 py-2 text-sm bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                    <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
                </button>
            </div>

            <div id="itemsContainer" class="space-y-4"></div>
        </div>

        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
            <a href="{{ route('exports.index') }}" 
               class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                <i class="fas fa-times mr-1"></i> Hủy
            </a>
            <button type="submit" class="px-4 py-2 text-sm text-white bg-orange-500 rounded-lg hover:bg-orange-600">
                <i class="fas fa-save mr-1"></i> Lưu phiếu xuất
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
let itemIndex = 0;
const products = @json($products);

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
                <select name="items[${itemIndex}][product_id]" required 
                        onchange="checkStock(${itemIndex})"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded">
                    <option value="">-- Chọn sản phẩm --</option>
                    ${productOptions}
                </select>
                <p id="stockInfo_${itemIndex}" class="text-xs text-gray-500 mt-1"></p>
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

function checkStock(itemIdx) {
    const warehouseId = document.getElementById('warehouseSelect').value;
    const productSelect = document.querySelector(`[name="items[${itemIdx}][product_id]"]`);
    const productId = productSelect.value;
    const stockInfo = document.getElementById(`stockInfo_${itemIdx}`);
    
    if (!warehouseId || !productId) {
        stockInfo.textContent = '';
        return;
    }
    
    // Fetch available stock
    fetch(`/exports/available-items?product_id=${productId}&warehouse_id=${warehouseId}`)
        .then(response => response.json())
        .then(items => {
            stockInfo.textContent = `Tồn kho: ${items.length} SKU`;
            stockInfo.className = items.length > 0 ? 'text-xs text-green-600 mt-1' : 'text-xs text-red-600 mt-1';
        })
        .catch(() => {
            stockInfo.textContent = '';
        });
}

document.addEventListener('DOMContentLoaded', function() {
    addItem();
});
</script>
@endpush
@endsection
