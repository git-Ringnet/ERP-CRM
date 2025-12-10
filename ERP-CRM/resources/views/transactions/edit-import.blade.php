@extends('layouts.app')

@section('title', 'Chỉnh sửa phiếu nhập')
@section('page-title', 'Chỉnh sửa Phiếu Nhập Kho')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-900">Chỉnh sửa phiếu nhập: {{ $transaction->code }}</h2>
        <a href="{{ route('transactions.show', $transaction) }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại
        </a>
    </div>
    
    @if($transaction->status !== 'pending')
    <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400">
        <p class="text-sm text-yellow-700">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            Chỉ có thể chỉnh sửa phiếu đang chờ xử lý (pending).
        </p>
    </div>
    @else
    <form action="{{ route('transactions.update', $transaction) }}" method="POST" class="p-4">
        @csrf
        @method('PUT')
        <input type="hidden" name="type" value="import">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mã phiếu nhập</label>
                <input type="text" value="{{ $transaction->code }}" readonly
                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-gray-50">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Ngày nhập <span class="text-red-500">*</span>
                </label>
                <input type="date" name="date" value="{{ old('date', $transaction->date->format('Y-m-d')) }}" required
                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Kho nhập <span class="text-red-500">*</span>
                </label>
                <select name="warehouse_id" required class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ $transaction->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nhân viên nhập</label>
                <select name="employee_id" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                    <option value="">-- Chọn nhân viên --</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ $transaction->employee_id == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                <textarea name="note" rows="2" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">{{ old('note', $transaction->note) }}</textarea>
            </div>
        </div>

        <div class="border-t border-gray-200 pt-4">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-900">Danh sách sản phẩm</h3>
                <button type="button" onclick="addItem()" class="px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark">
                    <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
                </button>
            </div>

            <div id="itemsContainer" class="space-y-4">
                <!-- Pre-populated items will be added here -->
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
            <a href="{{ route('transactions.show', $transaction) }}" 
               class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                <i class="fas fa-times mr-1"></i> Hủy
            </a>
            <button type="submit" class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:bg-primary-dark">
                <i class="fas fa-save mr-1"></i> Cập nhật phiếu nhập
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

// Reuse the same addItem, removeItem, addSkuField, addPriceTier functions from import.blade.php
// (Copy the JavaScript functions from import view)

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
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
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
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Giá nhập (USD)</label>
                <input type="number" name="items[${itemIndex}][cost_usd]" value="${existingData && existingData.cost_usd ? existingData.cost_usd : ''}" 
                       min="0" step="0.01" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded" placeholder="0.00">
            </div>
            <div>
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-xs font-medium text-gray-600">
                        <i class="fas fa-barcode mr-1"></i>Danh sách SKU
                    </label>
                    <button type="button" onclick="addSkuField(${itemIndex})" 
                            class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                        <i class="fas fa-plus mr-1"></i>Thêm SKU
                    </button>
                </div>
                <div id="skuContainer_${itemIndex}" class="space-y-2"></div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Mô tả</label>
                <input type="text" name="items[${itemIndex}][description]" value="${existingData ? existingData.description || '' : ''}"
                       class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded" placeholder="Mô tả...">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Ghi chú</label>
                <input type="text" name="items[${itemIndex}][comments]" value="${existingData ? existingData.comments || '' : ''}"
                       class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded" placeholder="Ghi chú...">
            </div>
            <div>
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-xs font-medium text-gray-600">
                        <i class="fas fa-tags mr-1"></i>Gói giá
                    </label>
                    <button type="button" onclick="addPriceTier(${itemIndex})" 
                            class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded hover:bg-green-200">
                        <i class="fas fa-plus mr-1"></i>Thêm gói
                    </button>
                </div>
                <div id="priceTiersContainer_${itemIndex}" class="space-y-2"></div>
            </div>
        </div>
    `;
    
    container.appendChild(itemDiv);
    
    // Load existing SKUs if available
    if (existingData && existingData.skus && existingData.skus.length > 0) {
        existingData.skus.forEach(sku => {
            const skuContainer = document.getElementById(`skuContainer_${itemIndex}`);
            const skuDiv = document.createElement('div');
            skuDiv.className = 'sku-field flex gap-2';
            skuDiv.innerHTML = `
                <input type="text" name="items[${itemIndex}][skus][]" value="${sku}"
                       class="flex-1 px-2 py-1.5 text-sm border border-gray-300 rounded" placeholder="SKU">
                <button type="button" onclick="this.parentElement.remove()" 
                        class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200">
                    <i class="fas fa-times"></i>
                </button>
            `;
            skuContainer.appendChild(skuDiv);
        });
    }
    
    // Load existing price tiers if available
    if (existingData && existingData.price_tiers && Array.isArray(existingData.price_tiers)) {
        existingData.price_tiers.forEach((tier, idx) => {
            const tierContainer = document.getElementById(`priceTiersContainer_${itemIndex}`);
            const tierDiv = document.createElement('div');
            tierDiv.className = 'price-tier-field flex gap-2';
            tierDiv.innerHTML = `
                <input type="text" name="items[${itemIndex}][price_tiers][${idx}][name]" value="${tier.name || ''}"
                       class="w-24 px-2 py-1.5 text-sm border border-gray-300 rounded" placeholder="1yr">
                <input type="number" name="items[${itemIndex}][price_tiers][${idx}][price]" value="${tier.price || ''}"
                       min="0" step="0.01" class="flex-1 px-2 py-1.5 text-sm border border-gray-300 rounded" placeholder="Giá USD">
                <button type="button" onclick="this.parentElement.remove()" 
                        class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200">
                    <i class="fas fa-times"></i>
                </button>
            `;
            tierContainer.appendChild(tierDiv);
        });
    }
    
    itemIndex++;
}

function removeItem(index) {
    const item = document.querySelector(`[data-index="${index}"]`);
    if (item) item.remove();
}

function addSkuField(itemIndex) {
    const container = document.getElementById(`skuContainer_${itemIndex}`);
    const skuCount = container.querySelectorAll('.sku-field').length;
    const skuDiv = document.createElement('div');
    skuDiv.className = 'sku-field flex gap-2';
    skuDiv.innerHTML = `
        <input type="text" name="items[${itemIndex}][skus][]" 
               class="flex-1 px-2 py-1.5 text-sm border border-gray-300 rounded" placeholder="SKU #${skuCount + 1}">
        <button type="button" onclick="this.parentElement.remove()" 
                class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(skuDiv);
}

function addPriceTier(itemIndex) {
    const container = document.getElementById(`priceTiersContainer_${itemIndex}`);
    const tierCount = container.querySelectorAll('.price-tier-field').length;
    const tierDiv = document.createElement('div');
    tierDiv.className = 'price-tier-field flex gap-2';
    tierDiv.innerHTML = `
        <input type="text" name="items[${itemIndex}][price_tiers][${tierCount}][name]" 
               class="w-24 px-2 py-1.5 text-sm border border-gray-300 rounded" 
               placeholder="1yr" value="${tierCount + 1}yr">
        <input type="number" name="items[${itemIndex}][price_tiers][${tierCount}][price]" 
               min="0" step="0.01" class="flex-1 px-2 py-1.5 text-sm border border-gray-300 rounded" 
               placeholder="Giá USD">
        <button type="button" onclick="this.parentElement.remove()" 
                class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(tierDiv);
}

// Load existing items on page load
document.addEventListener('DOMContentLoaded', function() {
    existingItems.forEach(item => {
        addItem(item);
    });
    
    // Add one empty item if no existing items
    if (existingItems.length === 0) {
        addItem();
    }
});
</script>
@endpush
@endsection
