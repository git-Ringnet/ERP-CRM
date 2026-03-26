@extends('layouts.app')

@section('title', 'Tạo phiếu nhập kho')
@section('page-title', 'Tạo Phiếu Nhập Kho')

@push('styles')
<style>
    /* Prevent select dropdowns from overflowing */
    .product-select, .warehouse-select {
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .product-select option, .warehouse-select option {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Searchable Select Styles */
    .searchable-select {
        position: relative;
    }
    .searchable-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 50;
        background: white;
        border: 1px solid #d1d5db;
        border-top: none;
        border-radius: 0 0 0.5rem 0.5rem;
        max-height: 200px;
        overflow-y: auto;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .searchable-option {
        padding: 0.5rem 0.75rem;
        cursor: pointer;
        font-size: 0.875rem;
    }
    .searchable-option:hover {
        background-color: #f3f4f6;
    }
    .searchable-option.selected {
        background-color: #eff6ff;
        color: #1d4ed8;
    }
</style>
@endpush

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

        <form action="{{ route('imports.store') }}" method="POST" class="p-4" id="importForm">
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
                    <input type="text" name="date" id="date_picker" value="{{ old('date', date('Y-m-d')) }}" required
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg @error('date') border-red-500 @enderror">
                    @error('date')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nhân viên nhập</label>
                    <div class="searchable-select" id="employeeSelectable">
                        <input type="text" class="searchable-input w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" 
                               placeholder="Gõ để tìm nhân viên..." autocomplete="off">
                        <input type="hidden" name="employee_id" value="{{ old('employee_id') }}">
                        <div class="searchable-dropdown">
                            <div class="searchable-option" data-value="">-- Chọn nhân viên --</div>
                            @foreach($employees as $employee)
                                <div class="searchable-option" data-value="{{ $employee->id }}" data-text="{{ $employee->name }}">
                                    {{ $employee->name }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nhà cung cấp <span class="text-red-500">*</span>
                    </label>
                    <div class="searchable-select" id="supplierSelectable">
                        <input type="text" class="searchable-input w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary @error('supplier_id') border-red-500 @enderror" 
                               placeholder="Gõ để tìm nhà cung cấp..." autocomplete="off" required>
                        <input type="hidden" name="supplier_id" id="supplierSelect" value="{{ old('supplier_id') }}">
                        <div class="searchable-dropdown">
                            <div class="searchable-option" data-value="">-- Chọn nhà cung cấp --</div>
                            @foreach($suppliers as $supplier)
                                <div class="searchable-option" data-value="{{ $supplier->id }}" data-text="{{ $supplier->name }}">
                                    {{ $supplier->name }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @error('supplier_id')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                    <textarea name="note" rows="1" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg"
                        placeholder="Nhập ghi chú về phiếu nhập kho (nếu có)">{{ old('note') }}</textarea>
                </div>
            </div>

            <!-- Chi phí phục vụ nhập hàng -->
            <div class="border-t border-gray-200 pt-4 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">
                    <i class="fas fa-calculator text-orange-500 mr-2"></i>Chi phí phục vụ nhập hàng
                </h3>
                
                <!-- Shipping Allocation Option -->
                @if(!empty($shippingAllocations) && $shippingAllocations->count() > 0)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="useShippingAllocation" class="mr-2" onchange="toggleShippingAllocation()">
                        <span class="text-sm font-medium text-blue-700">
                            <i class="fas fa-truck mr-1"></i>
                            Sử dụng phân bổ chi phí vận chuyển (Shipping Allocation)
                        </span>
                    </label>
                </div>

                <div id="shippingAllocationSection" class="hidden mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Chọn phiếu phân bổ</label>
                    <select name="shipping_allocation_id" id="shippingAllocationSelect" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                        <option value="">-- Chọn phiếu phân bổ --</option>
                        @foreach($shippingAllocations as $allocation)
                            <option value="{{ $allocation->id }}" 
                                    data-method="{{ $allocation->allocation_method }}"
                                    data-cost="{{ $allocation->total_shipping_cost }}"
                                    data-po="{{ $allocation->purchaseOrder->code ?? 'N/A' }}">
                                {{ $allocation->code }} - {{ $allocation->purchaseOrder->code ?? 'N/A' }} 
                                ({{ number_format($allocation->total_shipping_cost, 0, ',', '.') }}đ - {{ $allocation->method_label }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        Chi phí sẽ được phân bổ theo phương pháp đã cấu hình trong phiếu phân bổ
                    </p>
                </div>
                @endif

                <div id="manualServiceCostSection">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                        <p class="text-sm text-blue-700">
                            <i class="fas fa-info-circle mr-2"></i>
                            Chi phí phục vụ sẽ được phân bổ đều cho tất cả sản phẩm để tính giá kho cuối cùng.
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Chi phí vận chuyển</label>
                            <input type="text" name="shipping_cost" value="{{ old('shipping_cost', 0) }}" 
                                   class="service-cost-input w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg"
                                   placeholder="0" oninput="formatNumber(this); calculateServiceCost()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Chi phí bốc xếp</label>
                            <input type="text" name="loading_cost" value="{{ old('loading_cost', 0) }}" 
                                   class="service-cost-input w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg"
                                   placeholder="0" oninput="formatNumber(this); calculateServiceCost()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Chi phí kiểm định</label>
                            <input type="text" name="inspection_cost" value="{{ old('inspection_cost', 0) }}" 
                                   class="service-cost-input w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg"
                                   placeholder="0" oninput="formatNumber(this); calculateServiceCost()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Chi phí khác</label>
                            <input type="text" name="other_cost" value="{{ old('other_cost', 0) }}" 
                                   class="service-cost-input w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg"
                                   placeholder="0" oninput="formatNumber(this); calculateServiceCost()">
                        </div>
                    </div>
                    <div class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Tổng chi phí phục vụ:</span>
                            <span id="totalServiceCost" class="text-lg font-bold text-orange-600">0 ₫</span>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-sm text-gray-600">Chi phí phục vụ / đơn vị:</span>
                            <span id="serviceCostPerUnit" class="text-sm font-semibold text-gray-700">0 ₫</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-4">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-lg font-semibold text-gray-900">Danh sách nhập kho</h3>
                    <button type="button" onclick="addItem()"
                        class="px-4 py-2 text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                        <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
                    </button>
                </div>

                <div id="itemsContainer" class="space-y-4">
                    <!-- Items will be added here -->
                </div>
            </div>

            <!-- Bảng tổng hợp sản phẩm đã thêm -->
            <div class="border-t border-gray-200 pt-4 mt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Tổng hợp sản phẩm đã thêm</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="summaryTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã - Tên sản
                                    phẩm</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho nhập</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Đơn vị</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Đơn giá</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thành tiền</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="summaryBody">
                            <tr id="emptyRow">
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    Chưa có sản phẩm nào được thêm
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
            const warehouses = @json($warehouses);

            function addItem(existingData = null) {
                const container = document.getElementById('itemsContainer');
                const itemDiv = document.createElement('div');
                itemDiv.className = 'item-card bg-gray-50 rounded-lg p-4 border border-gray-200';
                itemDiv.dataset.index = itemIndex;

                const productOptions = products.map(p => {
                    const displayName = p.name.length > 50 ? p.name.substring(0, 47) + '...' : p.name;
                    return `<div class="searchable-option" data-value="${p.id}" data-text="${p.code} - ${p.name}" data-code="${p.code}" data-name="${p.name}" data-unit="${p.unit || 'Cái'}" data-cost="${p.default_cost || 0}">${p.code} - ${displayName}</div>`;
                }).join('');

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

                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
                    <div class="md:col-span-1">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Sản phẩm *</label>
                        <div class="searchable-select product-searchable" data-index="${itemIndex}">
                            <input type="text" class="searchable-input w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-primary focus:border-primary" 
                                   placeholder="Tìm sản phẩm..." autocomplete="off">
                            <input type="hidden" name="items[${itemIndex}][product_id]" class="product-id-input" value="${existingData ? existingData.product_id : ''}" required>
                            <div class="searchable-dropdown">
                                <div class="searchable-option" data-value="">-- Chọn sản phẩm --</div>
                                ${productOptions}
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Kho nhập *</label>
                        <select name="items[${itemIndex}][warehouse_id]" required 
                                class="warehouse-select w-full px-2 py-1.5 text-sm border border-gray-300 rounded"
                                onchange="updateSummary()">
                            <option value="">-- Chọn kho nhập --</option>
                            ${warehouseOptions}
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Số lượng *</label>
                        <input type="number" name="items[${itemIndex}][quantity]" value="${existingData ? existingData.quantity : '1'}" 
                               required min="1" step="1" class="quantity-input w-full px-2 py-1.5 text-sm border border-gray-300 rounded" 
                               placeholder="1" oninput="updateSerialInfo(${itemIndex}); updateSummary();">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Đơn giá nhập</label>
                        <input type="text" name="items[${itemIndex}][cost]" value="${existingData ? formatNumberValue(existingData.cost) : '0'}" 
                               class="cost-input w-full px-2 py-1.5 text-sm border border-gray-300 rounded" 
                               placeholder="0" oninput="formatNumber(this); updateSummary()">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Danh sách Serial</label>
                        <textarea name="items[${itemIndex}][serial_list]" rows="2"
                                  class="serial-textarea w-full px-2 py-1.5 text-sm border border-gray-300 rounded font-mono" 
                                  placeholder="Nhập danh sách số serial, mỗi số serial trên một dòng hoặc ngăn cách bằng dấu phẩy"
                                  oninput="updateSerialInfo(${itemIndex})">${existingData && existingData.serials ? existingData.serials.join(', ') : ''}</textarea>
                        <p class="text-xs text-gray-400 mt-1">Nhập serial ngăn cách bằng dấu phẩy (,) hoặc xuống dòng. VD: ABC123, DEF456, GHI789</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Ghi chú</label>
                        <textarea name="items[${itemIndex}][comments]" rows="2"
                                  class="comments-textarea w-full px-2 py-1.5 text-sm border border-gray-300 rounded" 
                                  placeholder="Ghi chú cho sản phẩm này (tùy chọn)"
                                  oninput="updateSummary()">${existingData ? existingData.comments || '' : ''}</textarea>
                    </div>
                </div>

                <p id="serialInfo_${itemIndex}" class="text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>Số lượng: 1, Serial đã nhập: 0 → 1 sản phẩm sẽ được tạo mã tạm (NOSKU)
                </p>
            `;

                container.appendChild(itemDiv);
                initSearchableSelect(itemDiv.querySelector('.product-searchable'), (val, option) => {
                    updateProductPrice(itemDiv.dataset.index, option);
                    updateSummary();
                });
                itemIndex++;
                updateSummary();
            }

                container.appendChild(itemDiv);
                itemIndex++;
                updateSummary();
            }

            function removeItem(index) {
                const item = document.querySelector(`[data-index="${index}"]`);
                if (item) {
                    item.remove();
                    updateSummary();
                }
            }

            function updateSerialInfo(itemIdx) {
                const itemCard = document.querySelector(`[data-index="${itemIdx}"]`);
                if (!itemCard) return;

                const qtyInput = itemCard.querySelector('.quantity-input');
                const serialTextarea = itemCard.querySelector('.serial-textarea');
                const infoEl = document.getElementById(`serialInfo_${itemIdx}`);

                if (!qtyInput || !serialTextarea || !infoEl) return;

                const qty = parseInt(qtyInput.value) || 1;
                const serialText = serialTextarea.value.trim();
                let filledSerials = 0;

                if (serialText) {
                    // Split by newline or comma
                    const serials = serialText.split(/[\n,]/).map(s => s.trim()).filter(s => s);
                    filledSerials = serials.length;
                }

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

            function updateProductPrice(index) {
                const itemCard = document.querySelector(`[data-index="${index}"]`);
                if (!itemCard) return;

                const productSelect = itemCard.querySelector('.product-select');
                const costInput = itemCard.querySelector('.cost-input');

                if (productSelect && costInput) {
                    const selectedOption = productSelect.options[productSelect.selectedIndex];
                    const cost = selectedOption.dataset.cost;
                    if (cost && !costInput.value || costInput.value == '0') {
                        costInput.value = cost;
                    }
                }
            }

            function formatCurrency(amount) {
                if (amount === 0) return '0 ₫';
                // Use the desired format 1,000,000 and don't show .00 for integers
                return new Intl.NumberFormat('en-US').format(amount) + ' ₫';
            }

            function formatNumber(input) {
                let value = input.value.replace(/\D/g, '');
                if (value === '') {
                    input.value = '';
                    return;
                }
                input.value = new Intl.NumberFormat('en-US').format(parseInt(value));
            }

            function formatNumberValue(value) {
                if (!value) return '0';
                return new Intl.NumberFormat('en-US').format(value);
            }

            function unformatNumber(value) {
                if (typeof value !== 'string') return value;
                return parseFloat(value.replace(/,/g, '')) || 0;
            }

            function calculateServiceCost() {
                const serviceCostInputs = document.querySelectorAll('.service-cost-input');
                let totalServiceCost = 0;
                
                serviceCostInputs.forEach(input => {
                    totalServiceCost += unformatNumber(input.value);
                });

                document.getElementById('totalServiceCost').textContent = formatCurrency(totalServiceCost);

                // Calculate per unit cost
                const itemCards = document.querySelectorAll('.item-card');
                let totalQty = 0;
                
                itemCards.forEach(card => {
                    const qtyInput = card.querySelector('.quantity-input');
                    if (qtyInput && qtyInput.value) {
                        totalQty += parseFloat(qtyInput.value) || 0;
                    }
                });

                const serviceCostPerUnit = totalQty > 0 ? totalServiceCost / totalQty : 0;
                document.getElementById('serviceCostPerUnit').textContent = formatCurrency(serviceCostPerUnit);
            }

            function updateSummary() {
                const summaryBody = document.getElementById('summaryBody');
                const emptyRow = document.getElementById('emptyRow');
                const itemCards = document.querySelectorAll('.item-card');

                // Clear existing rows except empty row
                summaryBody.querySelectorAll('tr:not(#emptyRow)').forEach(row => row.remove());

                let hasValidItems = false;
                let stt = 1;

                itemCards.forEach((card, idx) => {
                    const productSelect = card.querySelector('.product-select');
                    const warehouseSelect = card.querySelector('.warehouse-select');
                    const qtyInput = card.querySelector('.quantity-input');
                    const costInput = card.querySelector('.cost-input');
                    const commentsTextarea = card.querySelector('.comments-textarea');

                    if (!productSelect || !productSelect.value) return;

                    const selectedOption = productSelect.options[productSelect.selectedIndex];
                    const productCode = selectedOption.dataset.code || '';
                    const productName = selectedOption.dataset.name || '';
                    const productUnit = selectedOption.dataset.unit || 'Cái';

                    const warehouseName = warehouseSelect && warehouseSelect.value
                        ? warehouseSelect.options[warehouseSelect.selectedIndex].text
                        : '<span class="text-red-500">Chưa chọn</span>';

                    const qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
                    const cost = unformatNumber(costInput ? costInput.value : 0);
                    const total = qty * cost;
                    const comments = commentsTextarea ? commentsTextarea.value : '';

                    hasValidItems = true;

                    const row = document.createElement('tr');
                    row.className = 'hover:bg-gray-50';
                    row.innerHTML = `
                    <td class="px-4 py-3 text-sm text-gray-900">${stt}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">${productCode} - ${productName}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">${warehouseName}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">${productUnit}</td>
                    <td class="px-4 py-3 text-sm text-gray-900 font-medium">${qty}</td>
                    <td class="px-4 py-3 text-sm text-right text-gray-900">${formatCurrency(cost)}</td>
                    <td class="px-4 py-3 text-sm text-right text-gray-900 font-semibold">${formatCurrency(total)}</td>
                    <td class="px-4 py-3 text-sm text-gray-500">${comments || '-'}</td>
                `;
                    summaryBody.appendChild(row);
                    stt++;
                });

                if (emptyRow) {
                    emptyRow.style.display = hasValidItems ? 'none' : '';
                }

                // Recalculate service cost when items change
                calculateServiceCost();
            }

            function initSearchableSelect(container, onSelectCallback) {
                const input = container.querySelector('.searchable-input');
                const hiddenInput = container.querySelector('input[type="hidden"]');
                const dropdown = container.querySelector('.searchable-dropdown');
                const options = container.querySelectorAll('.searchable-option');

                // Set initial value if hidden input has value
                if (hiddenInput.value) {
                    const selectedOption = Array.from(options).find(opt => opt.dataset.value == hiddenInput.value);
                    if (selectedOption) {
                        input.value = selectedOption.dataset.text || selectedOption.textContent.trim();
                        selectedOption.classList.add('selected');
                    }
                }

                input.addEventListener('focus', () => {
                    dropdown.style.display = 'block';
                });

                document.addEventListener('click', (e) => {
                    if (!container.contains(e.target)) {
                        dropdown.style.display = 'none';
                    }
                });

                input.addEventListener('input', () => {
                    const filter = input.value.toLowerCase();
                    let hasVisible = false;
                    options.forEach(opt => {
                        const text = opt.textContent.toLowerCase();
                        if (text.includes(filter)) {
                            opt.style.display = 'block';
                            hasVisible = true;
                        } else {
                            opt.style.display = 'none';
                        }
                    });
                    dropdown.style.display = hasVisible ? 'block' : 'none';
                });

                options.forEach(opt => {
                    opt.addEventListener('click', () => {
                        input.value = opt.dataset.text || opt.textContent.trim();
                        hiddenInput.value = opt.dataset.value;
                        dropdown.style.display = 'none';
                        
                        options.forEach(o => o.classList.remove('selected'));
                        opt.classList.add('selected');

                        if (onSelectCallback) onSelectCallback(opt.dataset.value, opt);
                    });
                });
            }

            function updateProductPrice(index, selectedOption) {
                const itemCard = document.querySelector(`[data-index="${index}"]`);
                if (!itemCard) return;

                const costInput = itemCard.querySelector('.cost-input');

                if (selectedOption && costInput) {
                    const cost = selectedOption.dataset.cost;
                    if (cost && (!costInput.value || costInput.value == '0')) {
                        costInput.value = formatNumberValue(cost);
                    }
                }
            }

            // Remove accents for Vietnamese search
            function removeAccents(str) {
                return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'D');
            }

            // Enhanced initSearchableSelect with accent-insensitive search
            function initSearchableSelectEnhanced(container, onSelectCallback) {
                const input = container.querySelector('.searchable-input');
                const hiddenInput = container.querySelector('input[type="hidden"]');
                const dropdown = container.querySelector('.searchable-dropdown');
                const options = container.querySelectorAll('.searchable-option');

                if (hiddenInput.value) {
                    const selectedOption = Array.from(options).find(opt => opt.dataset.value == hiddenInput.value);
                    if (selectedOption) {
                        input.value = selectedOption.dataset.text || selectedOption.textContent.trim();
                        selectedOption.classList.add('selected');
                    }
                }

                input.addEventListener('focus', () => {
                    dropdown.style.display = 'block';
                });

                document.addEventListener('click', (e) => {
                    if (!container.contains(e.target)) {
                        dropdown.style.display = 'none';
                    }
                });

                input.addEventListener('input', () => {
                    const filter = removeAccents(input.value.toLowerCase());
                    let hasVisible = false;
                    options.forEach(opt => {
                        const text = removeAccents(opt.textContent.toLowerCase());
                        if (text.includes(filter)) {
                            opt.style.display = 'block';
                            hasVisible = true;
                        } else {
                            opt.style.display = 'none';
                        }
                    });
                    dropdown.style.display = hasVisible ? 'block' : 'none';
                });

                options.forEach(opt => {
                    opt.addEventListener('click', () => {
                        input.value = opt.dataset.text || opt.textContent.trim();
                        hiddenInput.value = opt.dataset.value;
                        dropdown.style.display = 'none';
                        
                        options.forEach(o => o.classList.remove('selected'));
                        opt.classList.add('selected');

                        if (onSelectCallback) onSelectCallback(opt.dataset.value, opt);
                    });
                });
            }

            // Add first item on page load
            document.addEventListener('DOMContentLoaded', function () {
                // Initialize static searchable selects
                initSearchableSelectEnhanced(document.getElementById('employeeSelectable'));
                initSearchableSelectEnhanced(document.getElementById('supplierSelectable'));

                // Initialize date picker
                flatpickr("#date_picker", {
                    altInput: true,
                    altFormat: "d/m/Y",
                    dateFormat: "Y-m-d",
                    locale: "vn"
                });

                addItem();

                // Strip commas before form submisssion
                document.getElementById('importForm').addEventListener('submit', function() {
                    document.querySelectorAll('.service-cost-input, .cost-input').forEach(input => {
                        input.value = unformatNumber(input.value);
                    });
                });
            });
        </script>
    @endpush
@endsection


<script>
function toggleShippingAllocation() {
    const checkbox = document.getElementById('useShippingAllocation');
    const allocationSection = document.getElementById('shippingAllocationSection');
    const manualSection = document.getElementById('manualServiceCostSection');
    const allocationSelect = document.getElementById('shippingAllocationSelect');

    if (checkbox && checkbox.checked) {
        if (allocationSection) allocationSection.classList.remove('hidden');
        if (manualSection) manualSection.classList.add('hidden');
        // Disable manual cost inputs
        document.querySelectorAll('.service-cost-input').forEach(input => {
            input.disabled = true;
            input.value = 0;
        });
    } else {
        if (allocationSection) allocationSection.classList.add('hidden');
        if (manualSection) manualSection.classList.remove('hidden');
        if (allocationSelect) allocationSelect.value = '';
        // Enable manual cost inputs
        document.querySelectorAll('.service-cost-input').forEach(input => {
            input.disabled = false;
        });
    }
    if (typeof calculateServiceCost === 'function') {
        calculateServiceCost();
    }
}
</script>
