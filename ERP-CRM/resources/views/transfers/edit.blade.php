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
    <form action="{{ route('transfers.update', $transfer) }}" method="POST" class="p-4" onsubmit="return validateForm()">
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

            <div class="md:col-span-3">
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
const warehouses = @json($warehouses);
const existingItems = @json($existingItems);
const defaultWarehouseId = {{ $transfer->from_warehouse_id ?? 'null' }};
const defaultToWarehouseId = {{ $transfer->to_warehouse_id ?? 'null' }};
let stockCache = {};

function addItem(existingData = null) {
    const container = document.getElementById('itemsContainer');
    const itemDiv = document.createElement('div');
    itemDiv.className = 'item-card bg-gray-50 rounded-lg p-4 border border-gray-200';
    itemDiv.dataset.index = itemIndex;
    
    const selectedWarehouse = existingData?.warehouse_id || defaultWarehouseId;
    const selectedToWarehouse = existingData?.to_warehouse_id || defaultToWarehouseId;
    
    const warehouseOptions = warehouses.map(w => 
        `<option value="${w.id}" ${w.id == selectedWarehouse ? 'selected' : ''}>${w.name}</option>`
    ).join('');
    
    const toWarehouseOptions = warehouses.map(w => 
        `<option value="${w.id}" ${w.id == selectedToWarehouse ? 'selected' : ''}>${w.name}</option>`
    ).join('');
    
    // Pre-fill product text if existing
    const selectedProduct = existingData ? products.find(p => p.id == existingData.product_id) : null;
    const productText = selectedProduct ? `${selectedProduct.code} - ${selectedProduct.name}` : '';
    
    itemDiv.innerHTML = `
        <div class="flex justify-between items-center mb-3">
            <h4 class="font-medium text-gray-700">Sản phẩm #${itemIndex + 1}</h4>
            <button type="button" onclick="removeItem(${itemIndex})" 
                    class="px-2 py-1 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200">
                <i class="fas fa-trash mr-1"></i>Xóa
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-3">
            <div class="md:col-span-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">Sản phẩm *</label>
                <div class="searchable-select product-searchable" data-index="${itemIndex}">
                    <input type="text" class="searchable-input w-full px-2 py-1.5 text-sm border border-gray-300 rounded" 
                           placeholder="Gõ để tìm sản phẩm..." autocomplete="off" value="${productText}">
                    <input type="hidden" name="items[${itemIndex}][product_id]" required class="product-id-input" value="${existingData ? existingData.product_id || '' : ''}">
                    <div class="searchable-dropdown hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg max-h-48 overflow-y-auto shadow-lg">
                        ${products.map(p => `
                            <div class="searchable-option px-3 py-2 hover:bg-blue-50 cursor-pointer" 
                                 data-value="${p.id}" 
                                 data-text="${p.code} - ${p.name}">
                                ${p.code} - ${p.name}
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Kho nguồn *</label>
                <select name="items[${itemIndex}][warehouse_id]" required 
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded warehouse-from-select"
                        onchange="loadStockInfo(${itemIndex}); validateWarehouses(${itemIndex})">
                    <option value="">-- Chọn kho --</option>
                    ${warehouseOptions}
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Kho đích *</label>
                <select name="items[${itemIndex}][to_warehouse_id]" required 
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded warehouse-to-select"
                        onchange="validateWarehouses(${itemIndex})">
                    <option value="">-- Chọn kho --</option>
                    ${toWarehouseOptions}
                </select>
                <p id="warehouseWarning_${itemIndex}" class="text-xs text-red-600 mt-1 hidden">
                    <i class="fas fa-exclamation-triangle mr-1"></i>Kho nguồn và kho đích không được trùng nhau!
                </p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Số lượng *</label>
                <input type="number" name="items[${itemIndex}][quantity]" value="${existingData ? existingData.quantity : '1'}" 
                       required min="1" step="1" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded" 
                       placeholder="1" onchange="onQuantityChange(${itemIndex})">
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">Ghi chú</label>
                <input type="text" name="items[${itemIndex}][comments]" value="${existingData ? existingData.comments || '' : ''}"
                       class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded" placeholder="Ghi chú...">
            </div>
        </div>
        
        <div id="stockInfo_${itemIndex}" class="mb-3 hidden">
            <div class="flex items-center justify-between p-2 bg-white rounded border border-gray-200">
                <span class="text-xs font-medium text-gray-600"><i class="fas fa-warehouse mr-1"></i>Tồn kho nguồn:</span>
                <span id="stockSummary_${itemIndex}" class="text-sm font-medium"></span>
            </div>
        </div>
        
        <div id="serialSection_${itemIndex}" class="hidden">
            <div class="flex justify-between items-center mb-2">
                <label class="block text-xs font-medium text-gray-600">
                    <i class="fas fa-barcode mr-1"></i>Chọn Serial chuyển
                </label>
                <button type="button" onclick="addSerialSelect(${itemIndex})" id="addSerialBtn_${itemIndex}"
                        class="px-2 py-1 text-xs bg-purple-100 text-purple-700 rounded hover:bg-purple-200">
                    <i class="fas fa-plus mr-1"></i>Thêm Serial
                </button>
            </div>
            <div id="serialContainer_${itemIndex}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2"></div>
            <p id="serialWarning_${itemIndex}" class="text-xs mt-2 hidden"></p>
        </div>
    `;
    
    container.appendChild(itemDiv);
    
    const currentIdx = itemIndex;
    itemIndex++;
    
    // Initialize searchable select for this item
    setTimeout(() => {
        const searchable = document.querySelector(`[data-index="${currentIdx}"] .product-searchable`);
        if (searchable && !searchable.dataset.initialized) {
            initSearchableSelect(searchable);
            searchable.dataset.initialized = 'true';
        }
    }, 0);
    
    if (existingData && existingData.product_id) {
        setTimeout(async () => {
            await loadStockInfo(currentIdx);
            if (existingData.product_item_ids && existingData.product_item_ids.length > 0) {
                for (const itemId of existingData.product_item_ids) {
                    // Add serial selects for existing items
                    const cacheKey = `${existingData.product_id}_${existingData.warehouse_id}`;
                    const data = stockCache[cacheKey] || { items: [], noSkuCount: 0 };
                    const serialItems = data.items || [];
                    
                    if (serialItems.length > 0) {
                        const serial = serialItems.find(s => s.id == itemId);
                        if (serial) {
                            addSerialSelect(currentIdx);
                            // Set the value after adding
                            setTimeout(() => {
                                const selects = document.querySelectorAll(`[name="items[${currentIdx}][product_item_ids][]"]`);
                                const lastSelect = selects[selects.length - 1];
                                if (lastSelect) {
                                    lastSelect.value = itemId;
                                    onSerialChange(currentIdx);
                                }
                            }, 50);
                        }
                    }
                }
            }
        }, 100);
    }
}

function removeItem(index) {
    const item = document.querySelector(`[data-index="${index}"]`);
    if (item) item.remove();
}

function validateWarehouses(itemIdx) {
    const warehouseFromSelect = document.querySelector(`[name="items[${itemIdx}][warehouse_id]"]`);
    const warehouseToSelect = document.querySelector(`[name="items[${itemIdx}][to_warehouse_id]"]`);
    const warningEl = document.getElementById(`warehouseWarning_${itemIdx}`);
    
    if (!warehouseFromSelect || !warehouseToSelect || !warningEl) return;
    
    const fromWarehouse = warehouseFromSelect.value;
    const toWarehouse = warehouseToSelect.value;
    
    if (fromWarehouse && toWarehouse && fromWarehouse === toWarehouse) {
        // Show warning
        warningEl.classList.remove('hidden');
        warehouseToSelect.classList.add('border-red-500');
        
        // Reset kho đích
        warehouseToSelect.value = '';
        
        // Show alert
        alert('Kho nguồn và kho đích không được trùng nhau! Vui lòng chọn kho đích khác.');
    } else {
        // Hide warning
        warningEl.classList.add('hidden');
        warehouseToSelect.classList.remove('border-red-500');
    }
}

async function loadStockInfo(itemIdx) {
    const warehouseSelect = document.querySelector(`[name="items[${itemIdx}][warehouse_id]"]`);
    const productSelect = document.querySelector(`[name="items[${itemIdx}][product_id]"]`);
    const warehouseId = warehouseSelect.value;
    const productId = productSelect.value;
    const stockInfoDiv = document.getElementById(`stockInfo_${itemIdx}`);
    const stockSummary = document.getElementById(`stockSummary_${itemIdx}`);
    const serialSection = document.getElementById(`serialSection_${itemIdx}`);
    const serialContainer = document.getElementById(`serialContainer_${itemIdx}`);
    
    if (!warehouseId || !productId) {
        stockInfoDiv.classList.add('hidden');
        serialSection.classList.add('hidden');
        return;
    }
    
    const cacheKey = `${productId}_${warehouseId}`;
    
    if (!stockCache[cacheKey]) {
        try {
            const response = await fetch(`/transfers/available-items?product_id=${productId}&warehouse_id=${warehouseId}`);
            stockCache[cacheKey] = await response.json();
        } catch (e) {
            stockCache[cacheKey] = { items: [], noSkuCount: 0 };
        }
    }
    
    const data = stockCache[cacheKey];
    const serialItems = data.items || [];
    const noSkuCount = data.noSkuCount || 0;
    const totalStock = serialItems.length + noSkuCount;
    
    stockInfoDiv.classList.remove('hidden');
    
    if (totalStock === 0) {
        stockSummary.innerHTML = `<span class="text-red-600">Hết hàng</span>`;
        serialSection.classList.add('hidden');
    } else {
        let summaryHtml = `<span class="text-green-600">${totalStock} sản phẩm</span>`;
        if (serialItems.length > 0) summaryHtml += ` (<span class="text-blue-600">${serialItems.length} có serial</span>`;
        if (noSkuCount > 0) summaryHtml += serialItems.length > 0 ? `, ` : ` (`;
        if (noSkuCount > 0) summaryHtml += `<span class="text-gray-600">${noSkuCount} không serial</span>`;
        if (serialItems.length > 0 || noSkuCount > 0) summaryHtml += `)`;
        stockSummary.innerHTML = summaryHtml;
        
        if (serialItems.length > 0) {
            serialSection.classList.remove('hidden');
            serialContainer.innerHTML = '';
        } else {
            serialSection.classList.add('hidden');
        }
    }
    
    validateQuantity(itemIdx);
}

function onQuantityChange(itemIdx) {
    const qtyInput = document.querySelector(`[name="items[${itemIdx}][quantity]"]`);
    const container = document.getElementById(`serialContainer_${itemIdx}`);
    const qty = parseInt(qtyInput.value) || 1;
    const selects = container.querySelectorAll('.serial-select');
    
    while (selects.length > qty) {
        selects[selects.length - 1].remove();
    }
    
    validateQuantity(itemIdx);
}

function addSerialSelect(itemIdx) {
    const warehouseSelect = document.querySelector(`[name="items[${itemIdx}][warehouse_id]"]`);
    const productSelect = document.querySelector(`[name="items[${itemIdx}][product_id]"]`);
    const qtyInput = document.querySelector(`[name="items[${itemIdx}][quantity]"]`);
    const warehouseId = warehouseSelect.value;
    const productId = productSelect.value;
    const container = document.getElementById(`serialContainer_${itemIdx}`);
    const qty = parseInt(qtyInput.value) || 1;
    
    if (!warehouseId || !productId) return;
    
    const currentSelects = container.querySelectorAll('.serial-select').length;
    if (currentSelects >= qty) {
        alert(`Số lượng là ${qty}, chỉ được chọn tối đa ${qty} serial!`);
        return;
    }
    
    const cacheKey = `${productId}_${warehouseId}`;
    const data = stockCache[cacheKey] || { items: [], noSkuCount: 0 };
    const serialItems = data.items || [];
    
    if (serialItems.length === 0) return;
    
    const selectedSerials = getSelectedSerials(itemIdx);
    const availableSerials = serialItems.filter(item => !selectedSerials.includes(item.id.toString()));
    
    if (availableSerials.length === 0) {
        alert('Đã chọn hết serial có sẵn!');
        return;
    }
    
    const selectCount = container.querySelectorAll('.serial-select').length;
    const selectDiv = document.createElement('div');
    selectDiv.className = 'serial-select flex gap-1';
    
    const options = availableSerials.map(item => `<option value="${item.id}">${item.sku}</option>`).join('');
    
    selectDiv.innerHTML = `
        <select name="items[${itemIdx}][product_item_ids][]" 
                class="flex-1 px-2 py-1.5 text-sm border border-gray-300 rounded font-mono"
                onchange="onSerialChange(${itemIdx})">
            <option value="">-- Chọn serial #${selectCount + 1} --</option>
            ${options}
        </select>
        <button type="button" onclick="removeSerialSelect(this, ${itemIdx})" 
                class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(selectDiv);
    validateQuantity(itemIdx);
}

function addSerialSelectWithValue(itemIdx, selectedItemId) {
    const warehouseSelect = document.querySelector(`[name="items[${itemIdx}][warehouse_id]"]`);
    const productSelect = document.querySelector(`[name="items[${itemIdx}][product_id]"]`);
    const warehouseId = warehouseSelect.value;
    const productId = productSelect.value;
    const container = document.getElementById(`serialContainer_${itemIdx}`);
    
    if (!warehouseId || !productId) return;
    
    const cacheKey = `${productId}_${warehouseId}`;
    const data = stockCache[cacheKey] || { items: [], noSkuCount: 0 };
    const serialItems = data.items || [];
    
    if (serialItems.length === 0) return;
    
    const selectCount = container.querySelectorAll('.serial-select').length;
    const selectDiv = document.createElement('div');
    selectDiv.className = 'serial-select flex gap-1';
    
    const selectedSerials = getSelectedSerials(itemIdx);
    const availableSerials = serialItems.filter(item => 
        !selectedSerials.includes(item.id.toString()) || item.id == selectedItemId
    );
    
    const options = availableSerials.map(item => 
        `<option value="${item.id}" ${item.id == selectedItemId ? 'selected' : ''}>${item.sku}</option>`
    ).join('');
    
    selectDiv.innerHTML = `
        <select name="items[${itemIdx}][product_item_ids][]" 
                class="flex-1 px-2 py-1.5 text-sm border border-gray-300 rounded font-mono"
                onchange="onSerialChange(${itemIdx})">
            <option value="">-- Chọn serial #${selectCount + 1} --</option>
            ${options}
        </select>
        <button type="button" onclick="removeSerialSelect(this, ${itemIdx})" 
                class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(selectDiv);
    updateSerialOptions(itemIdx);
    validateQuantity(itemIdx);
}

function onSerialChange(itemIdx) {
    updateSerialOptions(itemIdx);
    validateQuantity(itemIdx);
}

function updateSerialOptions(itemIdx) {
    const warehouseSelect = document.querySelector(`[name="items[${itemIdx}][warehouse_id]"]`);
    const productSelect = document.querySelector(`[name="items[${itemIdx}][product_id]"]`);
    const container = document.getElementById(`serialContainer_${itemIdx}`);
    
    if (!warehouseSelect || !productSelect) return;
    
    const warehouseId = warehouseSelect.value;
    const productId = productSelect.value;
    
    if (!warehouseId || !productId) return;
    
    const cacheKey = `${productId}_${warehouseId}`;
    const data = stockCache[cacheKey] || { items: [], noSkuCount: 0 };
    const serialItems = data.items || [];
    
    const selectedSerials = getSelectedSerials(itemIdx);
    const selects = container.querySelectorAll('select');
    
    selects.forEach(select => {
        const currentValue = select.value;
        const otherSelected = selectedSerials.filter(id => id !== currentValue);
        
        // Rebuild options
        let optionsHtml = '<option value="">-- Chọn serial --</option>';
        serialItems.forEach(item => {
            const isSelected = item.id.toString() === currentValue;
            const isUsed = otherSelected.includes(item.id.toString());
            if (!isUsed || isSelected) {
                optionsHtml += `<option value="${item.id}" ${isSelected ? 'selected' : ''}>${item.sku}</option>`;
            }
        });
        select.innerHTML = optionsHtml;
    });
}

function removeSerialSelect(btn, itemIdx) {
    btn.parentElement.remove();
    updateSerialOptions(itemIdx);
    validateQuantity(itemIdx);
}

function getSelectedSerials(itemIdx) {
    const container = document.getElementById(`serialContainer_${itemIdx}`);
    const selects = container.querySelectorAll('select');
    const selected = [];
    selects.forEach(select => { if (select.value) selected.push(select.value); });
    return selected;
}

function validateQuantity(itemIdx) {
    const warehouseSelect = document.querySelector(`[name="items[${itemIdx}][warehouse_id]"]`);
    const productSelect = document.querySelector(`[name="items[${itemIdx}][product_id]"]`);
    const qtyInput = document.querySelector(`[name="items[${itemIdx}][quantity]"]`);
    const warningEl = document.getElementById(`serialWarning_${itemIdx}`);
    const addBtn = document.getElementById(`addSerialBtn_${itemIdx}`);
    
    if (!warehouseSelect || !productSelect || !qtyInput || !warningEl) return;
    
    const warehouseId = warehouseSelect.value;
    const productId = productSelect.value;
    
    if (!warehouseId || !productId) return;
    
    const qty = parseInt(qtyInput.value) || 1;
    const cacheKey = `${productId}_${warehouseId}`;
    const data = stockCache[cacheKey] || { items: [], noSkuCount: 0 };
    const serialItems = data.items || [];
    const noSkuCount = data.noSkuCount || 0;
    const totalStock = serialItems.length + noSkuCount;
    
    const selectedSerials = getSelectedSerials(itemIdx);
    const selectedCount = selectedSerials.length;
    const remainingQty = qty - selectedCount;
    
    if (addBtn) {
        if (selectedCount >= qty) {
            addBtn.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            addBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
    
    warningEl.classList.remove('hidden');
    
    if (qty > totalStock) {
        warningEl.className = 'text-xs mt-2 text-red-600';
        warningEl.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i>Số lượng (${qty}) vượt quá tồn kho (${totalStock})!`;
        qtyInput.classList.add('border-red-500');
    } else if (remainingQty > noSkuCount && serialItems.length > 0) {
        const needSerials = remainingQty - noSkuCount;
        warningEl.className = 'text-xs mt-2 text-yellow-600';
        warningEl.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i>Cần chọn thêm ${needSerials} serial (chỉ có ${noSkuCount} sản phẩm không serial)`;
        qtyInput.classList.remove('border-red-500');
    } else if (selectedCount > 0) {
        warningEl.className = 'text-xs mt-2 text-green-600';
        warningEl.innerHTML = `<i class="fas fa-check-circle mr-1"></i>Đã chọn ${selectedCount} serial${remainingQty > 0 ? ', ' + remainingQty + ' không serial' : ''}, đủ số lượng`;
        qtyInput.classList.remove('border-red-500');
    } else if (qty <= noSkuCount) {
        warningEl.className = 'text-xs mt-2 text-green-600';
        warningEl.innerHTML = `<i class="fas fa-check-circle mr-1"></i>Sẽ chuyển ${qty} sản phẩm không serial`;
        qtyInput.classList.remove('border-red-500');
    } else {
        warningEl.classList.add('hidden');
        qtyInput.classList.remove('border-red-500');
    }
}

function validateForm() {
    const items = document.querySelectorAll('.item-card');
    let hasError = false;
    let errorMessages = [];
    
    items.forEach((item, idx) => {
        const itemIndex = item.dataset.index;
        const warehouseSelect = document.querySelector(`[name="items[${itemIndex}][warehouse_id]"]`);
        const toWarehouseSelect = document.querySelector(`[name="items[${itemIndex}][to_warehouse_id]"]`);
        const productSelect = document.querySelector(`[name="items[${itemIndex}][product_id]"]`);
        const qtyInput = document.querySelector(`[name="items[${itemIndex}][quantity]"]`);
        
        if (!warehouseSelect || !toWarehouseSelect || !productSelect || !qtyInput) return;
        
        const warehouseId = warehouseSelect.value;
        const toWarehouseId = toWarehouseSelect.value;
        const productId = productSelect.value;
        const qty = parseInt(qtyInput.value) || 0;
        
        if (!warehouseId || !toWarehouseId || !productId || qty <= 0) return;
        
        if (warehouseId === toWarehouseId) {
            hasError = true;
            const productName = productSelect.options[productSelect.selectedIndex].text;
            errorMessages.push(`Sản phẩm "${productName}": Kho nguồn và kho đích phải khác nhau`);
            return;
        }
        
        const cacheKey = `${productId}_${warehouseId}`;
        const data = stockCache[cacheKey] || { items: [], noSkuCount: 0 };
        const serialItems = data.items || [];
        const noSkuCount = data.noSkuCount || 0;
        const totalStock = serialItems.length + noSkuCount;
        
        const selectedSerials = getSelectedSerials(itemIndex);
        const selectedCount = selectedSerials.length;
        const remainingQty = qty - selectedCount;
        
        if (qty > totalStock) {
            hasError = true;
            const productName = productSelect.options[productSelect.selectedIndex].text;
            errorMessages.push(`Sản phẩm "${productName}": Số lượng (${qty}) vượt quá tồn kho (${totalStock})`);
        }
        else if (remainingQty > noSkuCount && serialItems.length > 0) {
            hasError = true;
            const productName = productSelect.options[productSelect.selectedIndex].text;
            const needSerials = remainingQty - noSkuCount;
            errorMessages.push(`Sản phẩm "${productName}": Cần chọn thêm ${needSerials} serial (chỉ có ${noSkuCount} sản phẩm không serial)`);
        }
    });
    
    if (hasError) {
        alert('Không thể lưu phiếu chuyển:\n\n' + errorMessages.join('\n'));
        return false;
    }
    
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    existingItems.forEach(item => addItem(item));
    if (existingItems.length === 0) addItem();
});

// Searchable Select Functions
function initSearchableSelect(container) {
    const input = container.querySelector('.searchable-input');
    const hiddenInput = container.querySelector('input[type="hidden"]');
    const dropdown = container.querySelector('.searchable-dropdown');
    const options = dropdown.querySelectorAll('.searchable-option');
    const itemIdx = parseInt(container.dataset.index);
    
    input.addEventListener('focus', () => {
        dropdown.classList.remove('hidden');
        filterOptions('');
    });
    
    input.addEventListener('input', (e) => {
        filterOptions(e.target.value);
    });
    
    function filterOptions(query) {
        const q = query.toLowerCase();
        let hasResults = false;
        options.forEach(opt => {
            const text = opt.dataset.text.toLowerCase();
            if (text.includes(q)) {
                opt.classList.remove('hidden');
                hasResults = true;
            } else {
                opt.classList.add('hidden');
            }
        });
        
        // Show no results message
        let noResults = dropdown.querySelector('.no-results');
        if (!hasResults) {
            if (!noResults) {
                noResults = document.createElement('div');
                noResults.className = 'no-results px-3 py-2 text-gray-500 italic text-sm';
                noResults.textContent = 'Không tìm thấy sản phẩm';
                dropdown.appendChild(noResults);
            }
            noResults.classList.remove('hidden');
        } else if (noResults) {
            noResults.classList.add('hidden');
        }
    }
    
    options.forEach(opt => {
        opt.addEventListener('click', () => {
            input.value = opt.dataset.text;
            hiddenInput.value = opt.dataset.value;
            dropdown.classList.add('hidden');
            loadStockInfo(itemIdx);
        });
    });
    
    // Keyboard navigation
    input.addEventListener('keydown', (e) => {
        const visibleOptions = [...options].filter(o => !o.classList.contains('hidden'));
        const highlighted = dropdown.querySelector('.searchable-option.highlighted');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (!highlighted && visibleOptions.length) {
                visibleOptions[0].classList.add('highlighted');
            } else if (highlighted) {
                const idx = visibleOptions.indexOf(highlighted);
                if (idx < visibleOptions.length - 1) {
                    highlighted.classList.remove('highlighted');
                    visibleOptions[idx + 1].classList.add('highlighted');
                    visibleOptions[idx + 1].scrollIntoView({ block: 'nearest' });
                }
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (highlighted) {
                const idx = visibleOptions.indexOf(highlighted);
                if (idx > 0) {
                    highlighted.classList.remove('highlighted');
                    visibleOptions[idx - 1].classList.add('highlighted');
                    visibleOptions[idx - 1].scrollIntoView({ block: 'nearest' });
                }
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (highlighted) highlighted.click();
        } else if (e.key === 'Escape') {
            dropdown.classList.add('hidden');
        }
    });
    
    // Close on click outside
    document.addEventListener('click', (e) => {
        if (!container.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
}
</script>
@endpush

@push('styles')
<style>
.searchable-select {
    position: relative;
}
.searchable-dropdown {
    top: 100%;
    left: 0;
    right: 0;
}
.searchable-option.highlighted {
    background-color: #dbeafe;
}
</style>
@endpush
@endsection
