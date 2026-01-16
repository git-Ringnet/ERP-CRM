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
    
    <form action="{{ route('exports.store') }}" method="POST" class="p-4" id="exportForm" onsubmit="return validateForm()">
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

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dự án</label>
                <select name="project_id" id="project_id" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg" onchange="toggleExportType()">
                    <option value="">-- Không chọn dự án --</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                            {{ $project->code }} - {{ $project->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Khách hàng</label>
                <select name="customer_id" id="customer_id" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg" onchange="toggleExportType()">
                    <option value="">-- Không chọn khách hàng --</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                            {{ $customer->code }} - {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-3">
                <p class="text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Chọn <strong>Dự án</strong> nếu xuất cho dự án, hoặc <strong>Khách hàng</strong> nếu xuất bán/giao hàng cho khách hàng. Có thể để trống cả hai.
                </p>
            </div>

            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                <textarea name="note" rows="1" 
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
// Toggle between project and customer selection
function toggleExportType() {
    const projectSelect = document.getElementById('project_id');
    const customerSelect = document.getElementById('customer_id');
    
    if (projectSelect && customerSelect) {
        // If project is selected, disable customer
        if (projectSelect.value) {
            customerSelect.value = '';
            customerSelect.disabled = true;
            customerSelect.classList.add('bg-gray-100');
        } else {
            customerSelect.disabled = false;
            customerSelect.classList.remove('bg-gray-100');
        }
        
        // If customer is selected, disable project
        if (customerSelect.value) {
            projectSelect.value = '';
            projectSelect.disabled = true;
            projectSelect.classList.add('bg-gray-100');
        } else {
            projectSelect.disabled = false;
            projectSelect.classList.remove('bg-gray-100');
        }
    }
}

let itemIndex = 0;
const products = @json($products);
const warehouses = @json($warehouses);
let stockCache = {};

function addItem(existingData = null) {
    const container = document.getElementById('itemsContainer');
    const itemDiv = document.createElement('div');
    itemDiv.className = 'item-card bg-gray-50 rounded-lg p-4 border border-gray-200';
    itemDiv.dataset.index = itemIndex;
    
    const productOptions = products.map(p => 
        `<option value="${p.id}" ${existingData && existingData.product_id == p.id ? 'selected' : ''}>${p.code} - ${p.name}</option>`
    ).join('');
    
    const warehouseOptions = warehouses.map(w => 
        `<option value="${w.id}" ${existingData && existingData.warehouse_id == w.id ? 'selected' : ''}>${w.name}</option>`
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
            <div class="md:col-span-4">
                <label class="block text-xs font-medium text-gray-600 mb-1">Mã sản phẩm *</label>
                <select name="items[${itemIndex}][product_id]" required 
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded"
                        onchange="loadStockInfo(${itemIndex})">
                    <option value="">-- Chọn sản phẩm --</option>
                    ${productOptions}
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">Kho xuất *</label>
                <select name="items[${itemIndex}][warehouse_id]" required 
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded"
                        onchange="loadStockInfo(${itemIndex})">
                    <option value="">-- Chọn kho --</option>
                    ${warehouseOptions}
                </select>
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
        
        <!-- Stock Info -->
        <div id="stockInfo_${itemIndex}" class="mb-3 hidden">
            <div class="flex items-center justify-between p-2 bg-white rounded border border-gray-200">
                <span class="text-xs font-medium text-gray-600">
                    <i class="fas fa-warehouse mr-1"></i>Tồn kho:
                </span>
                <span id="stockSummary_${itemIndex}" class="text-sm font-medium"></span>
            </div>
        </div>
        
        <!-- Serial Selection -->
        <div id="serialSection_${itemIndex}" class="hidden">
            <div class="flex justify-between items-center mb-2">
                <label class="block text-xs font-medium text-gray-600">
                    <i class="fas fa-barcode mr-1"></i>Chọn Serial xuất
                </label>
                <button type="button" onclick="addSerialSelect(${itemIndex})" id="addSerialBtn_${itemIndex}"
                        class="px-2 py-1 text-xs bg-orange-100 text-orange-700 rounded hover:bg-orange-200">
                    <i class="fas fa-plus mr-1"></i>Thêm Serial
                </button>
            </div>
            <div id="serialContainer_${itemIndex}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
            </div>
            <p id="serialWarning_${itemIndex}" class="text-xs mt-2 hidden"></p>
        </div>
    `;
    
    container.appendChild(itemDiv);
    itemIndex++;
}

function removeItem(index) {
    const item = document.querySelector(`[data-index="${index}"]`);
    if (item) item.remove();
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
            const response = await fetch(`/exports/available-items?product_id=${productId}&warehouse_id=${warehouseId}`);
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
    // Clear excess serial selects if quantity decreased
    const qtyInput = document.querySelector(`[name="items[${itemIdx}][quantity]"]`);
    const container = document.getElementById(`serialContainer_${itemIdx}`);
    const qty = parseInt(qtyInput.value) || 1;
    const selects = container.querySelectorAll('.serial-select');
    
    // Remove excess selects
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
    
    // Check if already at max
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
    
    // Update add button state
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
        warningEl.innerHTML = `<i class="fas fa-check-circle mr-1"></i>Sẽ xuất ${qty} sản phẩm không serial`;
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
        const productSelect = document.querySelector(`[name="items[${itemIndex}][product_id]"]`);
        const qtyInput = document.querySelector(`[name="items[${itemIndex}][quantity]"]`);
        
        if (!warehouseSelect || !productSelect || !qtyInput) return;
        
        const warehouseId = warehouseSelect.value;
        const productId = productSelect.value;
        const qty = parseInt(qtyInput.value) || 0;
        
        if (!warehouseId || !productId || qty <= 0) return;
        
        const cacheKey = `${productId}_${warehouseId}`;
        const data = stockCache[cacheKey] || { items: [], noSkuCount: 0 };
        const serialItems = data.items || [];
        const noSkuCount = data.noSkuCount || 0;
        const totalStock = serialItems.length + noSkuCount;
        
        const selectedSerials = getSelectedSerials(itemIndex);
        const selectedCount = selectedSerials.length;
        const remainingQty = qty - selectedCount;
        
        // Check if quantity exceeds stock
        if (qty > totalStock) {
            hasError = true;
            const productName = productSelect.options[productSelect.selectedIndex].text;
            errorMessages.push(`Sản phẩm "${productName}": Số lượng (${qty}) vượt quá tồn kho (${totalStock})`);
        }
        // Check if need more serials
        else if (remainingQty > noSkuCount && serialItems.length > 0) {
            hasError = true;
            const productName = productSelect.options[productSelect.selectedIndex].text;
            const needSerials = remainingQty - noSkuCount;
            errorMessages.push(`Sản phẩm "${productName}": Cần chọn thêm ${needSerials} serial (chỉ có ${noSkuCount} sản phẩm không serial)`);
        }
    });
    
    if (hasError) {
        alert('Không thể lưu phiếu xuất:\n\n' + errorMessages.join('\n'));
        return false;
    }
    
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    addItem();
});
</script>
@endpush
@endsection
