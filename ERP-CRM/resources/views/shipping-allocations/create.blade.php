@extends('layouts.app')

@section('title', 'Tạo phân bổ chi phí vận chuyển')
@section('page-title', 'Tạo phân bổ chi phí vận chuyển')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <a href="{{ route('shipping-allocations.index') }}" class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
    </div>

    <form action="{{ route('shipping-allocations.store') }}" method="POST" id="allocationForm">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 space-y-4">
                <!-- Basic Info -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-info-circle mr-2 text-primary"></i>Thông tin cơ bản</h2>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Đơn mua hàng <span class="text-red-500">*</span></label>
                                <select name="purchase_order_id" required class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('purchase_order_id') border-red-500 @enderror">
                                    <option value="">Chọn đơn mua hàng</option>
                                    @foreach($purchaseOrders as $po)
                                        <option value="{{ $po->id }}" {{ old('purchase_order_id') == $po->id ? 'selected' : '' }}>
                                            {{ $po->code }} - {{ $po->supplier->name ?? 'N/A' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('purchase_order_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kho nhận hàng <span class="text-red-500">*</span></label>
                                <select name="warehouse_id" required class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('warehouse_id') border-red-500 @enderror">
                                    <option value="">Chọn kho</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                            {{ $warehouse->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('warehouse_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ngày phân bổ <span class="text-red-500">*</span></label>
                                <input type="date" name="allocation_date" value="{{ old('allocation_date', date('Y-m-d')) }}" required
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('allocation_date') border-red-500 @enderror">
                                @error('allocation_date')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phương pháp phân bổ <span class="text-red-500">*</span></label>
                                <select name="allocation_method" id="allocation_method" required onchange="calculateAllocation()"
                                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="value" {{ old('allocation_method', 'value') == 'value' ? 'selected' : '' }}>Theo giá trị hàng hóa</option>
                                    <option value="quantity" {{ old('allocation_method') == 'quantity' ? 'selected' : '' }}>Theo số lượng</option>
                                    <option value="weight" {{ old('allocation_method') == 'weight' ? 'selected' : '' }}>Theo trọng lượng</option>
                                    <option value="volume" {{ old('allocation_method') == 'volume' ? 'selected' : '' }}>Theo thể tích</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tổng chi phí vận chuyển (VNĐ) <span class="text-red-500">*</span></label>
                                <input type="number" name="total_shipping_cost" id="total_shipping_cost" value="{{ old('total_shipping_cost') }}" min="0" required onchange="calculateAllocation()"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('total_shipping_cost') border-red-500 @enderror">
                                @error('total_shipping_cost')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Items -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-boxes mr-2 text-primary"></i>Chi tiết phân bổ theo sản phẩm</h2>
                        <button type="button" onclick="addProductRow()" class="inline-flex items-center px-2 py-1 text-xs bg-green-500 text-white rounded hover:bg-green-600">
                            <i class="fas fa-plus mr-1"></i>Thêm SP
                        </button>
                    </div>
                    <div class="p-4">
                        <div id="productItems">
                            <div class="product-item border border-gray-200 rounded-lg p-3 mb-3" data-index="0">
                                <div class="grid grid-cols-12 gap-2">
                                    <div class="col-span-3">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Sản phẩm</label>
                                        <select name="items[0][product_id]" class="product-select w-full px-2 py-1 text-sm border border-gray-300 rounded-md" required onchange="calculateAllocation()">
                                            <option value="">Chọn SP</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Số lượng</label>
                                        <input type="number" name="items[0][quantity]" class="quantity-input w-full px-2 py-1 text-sm border border-gray-300 rounded-md" min="1" value="1" required onchange="calculateAllocation()">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Đơn giá</label>
                                        <input type="number" name="items[0][unit_value]" class="unit-value-input w-full px-2 py-1 text-sm border border-gray-300 rounded-md" min="0" required onchange="calculateAllocation()">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">TL (kg)</label>
                                        <input type="number" name="items[0][weight]" class="weight-input w-full px-2 py-1 text-sm border border-gray-300 rounded-md" min="0" step="0.001" onchange="calculateAllocation()">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">TT (m³)</label>
                                        <input type="number" name="items[0][volume]" class="volume-input w-full px-2 py-1 text-sm border border-gray-300 rounded-md" min="0" step="0.0001" onchange="calculateAllocation()">
                                    </div>
                                    <div class="col-span-1 flex items-end">
                                        <button type="button" class="remove-btn hidden px-2 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600" onclick="removeProductRow(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-2 mt-2 text-xs">
                                    <div class="text-gray-500">Tổng: <span class="total-value font-bold">0đ</span></div>
                                    <div class="text-green-600">CP phân bổ: <span class="allocated-cost font-bold">0đ</span></div>
                                    <div class="text-blue-600">CP/đơn vị: <span class="cost-per-unit font-bold">0đ</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <!-- Summary -->
                <div class="bg-white rounded-lg shadow-sm border-2 border-green-500">
                    <div class="px-4 py-3 bg-green-500 text-white rounded-t-lg">
                        <h2 class="text-base font-semibold"><i class="fas fa-calculator mr-2"></i>Tổng kết</h2>
                    </div>
                    <div class="p-4 space-y-3">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Tổng giá trị hàng</label>
                            <input type="text" id="summary_total_value" readonly class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-md bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Tổng CP đã phân bổ</label>
                            <input type="text" id="summary_total_allocated" readonly class="w-full px-3 py-1.5 text-sm font-bold text-green-600 border border-green-200 rounded-md bg-green-50">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Chênh lệch</label>
                            <input type="text" id="summary_difference" readonly class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-md">
                        </div>
                    </div>
                </div>

                <!-- Note -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-sticky-note mr-2 text-primary"></i>Ghi chú</h2>
                    </div>
                    <div class="p-4">
                        <textarea name="note" rows="3" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">{{ old('note') }}</textarea>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white rounded-lg shadow-sm p-4 space-y-2">
                    <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors font-medium text-sm">
                        <i class="fas fa-save mr-2"></i>Lưu nháp
                    </button>
                    <button type="submit" name="approve" value="1" class="w-full px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors font-medium text-sm">
                        <i class="fas fa-check mr-2"></i>Lưu và duyệt
                    </button>
                    <a href="{{ route('shipping-allocations.index') }}" class="w-full inline-block text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 text-sm">
                        Hủy
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let productIndex = 1;
const productsData = @json($products);

function addProductRow() {
    const container = document.getElementById('productItems');
    const newRow = document.createElement('div');
    newRow.className = 'product-item border border-gray-200 rounded-lg p-3 mb-3';
    newRow.dataset.index = productIndex;
    
    let productOptions = '<option value="">Chọn SP</option>';
    productsData.forEach(p => {
        productOptions += `<option value="${p.id}">${p.name}</option>`;
    });
    
    newRow.innerHTML = `
        <div class="grid grid-cols-12 gap-2">
            <div class="col-span-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">Sản phẩm</label>
                <select name="items[${productIndex}][product_id]" class="product-select w-full px-2 py-1 text-sm border border-gray-300 rounded-md" required onchange="calculateAllocation()">${productOptions}</select>
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Số lượng</label>
                <input type="number" name="items[${productIndex}][quantity]" class="quantity-input w-full px-2 py-1 text-sm border border-gray-300 rounded-md" min="1" value="1" required onchange="calculateAllocation()">
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Đơn giá</label>
                <input type="number" name="items[${productIndex}][unit_value]" class="unit-value-input w-full px-2 py-1 text-sm border border-gray-300 rounded-md" min="0" required onchange="calculateAllocation()">
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">TL (kg)</label>
                <input type="number" name="items[${productIndex}][weight]" class="weight-input w-full px-2 py-1 text-sm border border-gray-300 rounded-md" min="0" step="0.001" onchange="calculateAllocation()">
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">TT (m³)</label>
                <input type="number" name="items[${productIndex}][volume]" class="volume-input w-full px-2 py-1 text-sm border border-gray-300 rounded-md" min="0" step="0.0001" onchange="calculateAllocation()">
            </div>
            <div class="col-span-1 flex items-end">
                <button type="button" class="remove-btn px-2 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600" onclick="removeProductRow(this)"><i class="fas fa-trash"></i></button>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2 mt-2 text-xs">
            <div class="text-gray-500">Tổng: <span class="total-value font-bold">0đ</span></div>
            <div class="text-green-600">CP phân bổ: <span class="allocated-cost font-bold">0đ</span></div>
            <div class="text-blue-600">CP/đơn vị: <span class="cost-per-unit font-bold">0đ</span></div>
        </div>
    `;
    container.appendChild(newRow);
    productIndex++;
    updateRemoveButtons();
}

function removeProductRow(btn) {
    btn.closest('.product-item').remove();
    calculateAllocation();
    updateRemoveButtons();
}

function updateRemoveButtons() {
    const items = document.querySelectorAll('.product-item');
    items.forEach(item => {
        const btn = item.querySelector('.remove-btn');
        if (btn) btn.classList.toggle('hidden', items.length <= 1);
    });
}

function calculateAllocation() {
    const totalShippingCost = parseFloat(document.getElementById('total_shipping_cost').value) || 0;
    const method = document.getElementById('allocation_method').value;
    const items = document.querySelectorAll('.product-item');
    
    let totalValue = 0, totalQuantity = 0, totalWeight = 0, totalVolume = 0;
    
    items.forEach(item => {
        const quantity = parseFloat(item.querySelector('.quantity-input').value) || 0;
        const unitValue = parseFloat(item.querySelector('.unit-value-input').value) || 0;
        const weight = parseFloat(item.querySelector('.weight-input').value) || 0;
        const volume = parseFloat(item.querySelector('.volume-input').value) || 0;
        const itemTotal = quantity * unitValue;
        item.querySelector('.total-value').textContent = formatNumber(itemTotal) + 'đ';
        totalValue += itemTotal;
        totalQuantity += quantity;
        totalWeight += weight * quantity;
        totalVolume += volume * quantity;
    });
    
    let totalAllocated = 0;
    items.forEach(item => {
        const quantity = parseFloat(item.querySelector('.quantity-input').value) || 0;
        const unitValue = parseFloat(item.querySelector('.unit-value-input').value) || 0;
        const weight = parseFloat(item.querySelector('.weight-input').value) || 0;
        const volume = parseFloat(item.querySelector('.volume-input').value) || 0;
        const itemTotal = quantity * unitValue;
        
        let allocatedCost = 0;
        if (method === 'value') allocatedCost = totalValue > 0 ? (itemTotal / totalValue) * totalShippingCost : 0;
        else if (method === 'quantity') allocatedCost = totalQuantity > 0 ? (quantity / totalQuantity) * totalShippingCost : 0;
        else if (method === 'weight') allocatedCost = totalWeight > 0 ? ((weight * quantity) / totalWeight) * totalShippingCost : 0;
        else if (method === 'volume') allocatedCost = totalVolume > 0 ? ((volume * quantity) / totalVolume) * totalShippingCost : 0;
        
        const costPerUnit = quantity > 0 ? allocatedCost / quantity : 0;
        item.querySelector('.allocated-cost').textContent = formatNumber(allocatedCost) + 'đ';
        item.querySelector('.cost-per-unit').textContent = formatNumber(costPerUnit) + 'đ';
        totalAllocated += allocatedCost;
    });
    
    document.getElementById('summary_total_value').value = formatNumber(totalValue) + 'đ';
    document.getElementById('summary_total_allocated').value = formatNumber(totalAllocated) + 'đ';
    const diff = totalShippingCost - totalAllocated;
    document.getElementById('summary_difference').value = formatNumber(diff) + 'đ';
    document.getElementById('summary_difference').className = 'w-full px-3 py-1.5 text-sm border rounded-md ' + (Math.abs(diff) < 1 ? 'text-green-600 border-green-200 bg-green-50' : 'text-red-600 border-red-200 bg-red-50');
}

function formatNumber(num) { return Math.round(num).toLocaleString('vi-VN'); }

document.addEventListener('DOMContentLoaded', function() { updateRemoveButtons(); calculateAllocation(); });
</script>
@endsection
