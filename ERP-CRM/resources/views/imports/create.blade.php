@extends('layouts.app')

@section('title', 'Tạo phiếu nhập kho')
@section('page-title', 'Tạo Phiếu Nhập Kho')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-900">
            <i class="fas fa-arrow-down text-blue-500 mr-2"></i>Thông tin phiếu nhập
        </h2>
        <a href="{{ route('imports.index') }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại
        </a>
    </div>
    
    <form action="{{ route('imports.store') }}" method="POST" class="p-4">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mã phiếu nhập</label>
                <input type="text" value="{{ $code }}" readonly
                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-gray-50">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Ngày nhập <span class="text-red-500">*</span>
                </label>
                <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg @error('date') border-red-500 @enderror">
                @error('date')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Kho nhập <span class="text-red-500">*</span>
                </label>
                <select name="warehouse_id" required 
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg @error('warehouse_id') border-red-500 @enderror">
                    <option value="">-- Chọn kho --</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
                @error('warehouse_id')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nhân viên nhập</label>
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú phiếu</label>
                <textarea name="note" rows="2" 
                          class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">{{ old('note') }}</textarea>
            </div>
        </div>

        <div class="border-t border-gray-200 pt-4">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-900">Danh sách sản phẩm nhập</h3>
                <button type="button" onclick="addItem()" 
                        class="px-4 py-2 text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                    <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
                </button>
            </div>

            <div id="itemsContainer" class="space-y-4">
                <!-- Items will be added here -->
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
            <a href="{{ route('imports.index') }}" 
               class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                <i class="fas fa-times mr-1"></i> Hủy
            </a>
            <button type="submit" class="px-4 py-2 text-sm text-white bg-blue-500 rounded-lg hover:bg-blue-600">
                <i class="fas fa-save mr-1"></i> Lưu phiếu nhập
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
        `<option value="${p.id}" ${existingData && existingData.product_id == p.id ? 'selected' : ''}>${p.code} - ${p.name}</option>`
    ).join('');
    
    itemDiv.innerHTML = `
        <div class="flex justify-between items-center mb-3">
            <h4 class="font-medium text-gray-700">Sản phẩm #${itemIndex + 1}</h4>
            <button type="button" onclick="removeItem(${itemIndex})" 
                    class="px-2 py-1 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200">
                <i class="fas fa-trash mr-1"></i>Xóa
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-3">
            <div class="md:col-span-5">
                <label class="block text-xs font-medium text-gray-600 mb-1">Mã sản phẩm / Part Number *</label>
                <select name="items[${itemIndex}][product_id]" required 
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded">
                    <option value="">-- Chọn sản phẩm --</option>
                    ${productOptions}
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Số lượng *</label>
                <input type="number" name="items[${itemIndex}][quantity]" value="${existingData ? existingData.quantity : '1'}" 
                       required min="1" step="1" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded" 
                       placeholder="1" onchange="updateSerialInfo(${itemIndex})">
            </div>
            <div class="md:col-span-5">
                <label class="block text-xs font-medium text-gray-600 mb-1">Ghi chú</label>
                <input type="text" name="items[${itemIndex}][comments]" value="${existingData ? existingData.comments || '' : ''}"
                       class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded" placeholder="Ghi chú...">
            </div>
        </div>
        
        <div>
            <div class="flex justify-between items-center mb-2">
                <label class="block text-xs font-medium text-gray-600">
                    <i class="fas fa-barcode mr-1"></i>Danh sách Serial 
                    <span class="text-gray-400">(mỗi serial = 1 sản phẩm, phần còn lại sẽ tự tạo mã tạm)</span>
                </label>
                <button type="button" onclick="addSerialField(${itemIndex})" 
                        class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded hover:bg-green-200">
                    <i class="fas fa-plus mr-1"></i>Thêm Serial
                </button>
            </div>
            <div id="serialContainer_${itemIndex}" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
                <!-- Serial fields will be added here -->
            </div>
            <p id="serialInfo_${itemIndex}" class="text-xs text-gray-500 mt-2">
                <i class="fas fa-info-circle mr-1"></i>Số lượng: 1, Serial đã nhập: 0 → 1 sản phẩm sẽ được tạo mã tạm (NOSKU)
            </p>
        </div>
    `;
    
    container.appendChild(itemDiv);
    itemIndex++;
}

function removeItem(index) {
    const item = document.querySelector(`[data-index="${index}"]`);
    if (item) item.remove();
}

function addSerialField(itemIdx) {
    const container = document.getElementById(`serialContainer_${itemIdx}`);
    const serialCount = container.querySelectorAll('.serial-field').length;
    const serialDiv = document.createElement('div');
    serialDiv.className = 'serial-field flex gap-1';
    serialDiv.innerHTML = `
        <input type="text" name="items[${itemIdx}][serials][]" 
               class="flex-1 px-2 py-1.5 text-sm border border-gray-300 rounded font-mono" 
               placeholder="Serial #${serialCount + 1}" onchange="updateSerialInfo(${itemIdx})">
        <button type="button" onclick="removeSerialField(this, ${itemIdx})" 
                class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(serialDiv);
    updateSerialInfo(itemIdx);
}

function removeSerialField(btn, itemIdx) {
    btn.parentElement.remove();
    updateSerialInfo(itemIdx);
}

function updateSerialInfo(itemIdx) {
    const qtyInput = document.querySelector(`[name="items[${itemIdx}][quantity]"]`);
    const container = document.getElementById(`serialContainer_${itemIdx}`);
    const infoEl = document.getElementById(`serialInfo_${itemIdx}`);
    
    if (!qtyInput || !container || !infoEl) return;
    
    const qty = parseInt(qtyInput.value) || 1;
    const serialInputs = container.querySelectorAll('input[type="text"]');
    let filledSerials = 0;
    serialInputs.forEach(input => {
        if (input.value.trim()) filledSerials++;
    });
    
    const noSkuCount = Math.max(0, qty - filledSerials);
    
    if (filledSerials > qty) {
        infoEl.innerHTML = `<i class="fas fa-exclamation-triangle mr-1 text-yellow-600"></i>
            <span class="text-yellow-600">Cảnh báo: Số serial (${filledSerials}) nhiều hơn số lượng (${qty}). Chỉ ${qty} serial đầu tiên sẽ được sử dụng.</span>`;
    } else if (noSkuCount > 0) {
        infoEl.innerHTML = `<i class="fas fa-info-circle mr-1"></i>
            Số lượng: ${qty}, Serial đã nhập: ${filledSerials} → <span class="text-blue-600 font-medium">${noSkuCount} sản phẩm sẽ được tạo mã tạm (NOSKU)</span>`;
    } else {
        infoEl.innerHTML = `<i class="fas fa-check-circle mr-1 text-green-600"></i>
            <span class="text-green-600">Đủ serial cho ${qty} sản phẩm</span>`;
    }
}

// Add first item on page load
document.addEventListener('DOMContentLoaded', function() {
    addItem();
});
</script>
@endpush
@endsection
