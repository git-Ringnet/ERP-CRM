@extends('layouts.app')

@section('title', 'Chỉnh sửa phiếu nhập')
@section('page-title', 'Chỉnh sửa Phiếu Nhập Kho')

@section('content')
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-edit text-blue-500 mr-2"></i>Chỉnh sửa phiếu nhập: {{ $import->code }}
            </h2>
            <a href="{{ route('imports.show', $import) }}" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
        </div>

        @if($import->status !== 'pending')
            <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400">
                <p class="text-sm text-yellow-700">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Chỉ có thể chỉnh sửa phiếu đang chờ xử lý (pending).
                </p>
            </div>
        @else
            <form action="{{ route('imports.update', $import) }}" method="POST" class="p-4" id="importForm">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mã phiếu nhập</label>
                        <input type="text" value="{{ $import->code }}" readonly
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-gray-50">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Ngày nhập <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="date" value="{{ old('date', $import->date->format('Y-m-d')) }}" required
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg @error('date') border-red-500 @enderror">
                        @error('date')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nhân viên nhập</label>
                        <select name="employee_id" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                            <option value="">-- Chọn nhân viên --</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ $import->employee_id == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nhà cung cấp <span class="text-red-500">*</span>
                        </label>
                        <select name="supplier_id" required id="supplierSelect"
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg @error('supplier_id') border-red-500 @enderror">
                            <option value="">-- Chọn nhà cung cấp --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ $import->supplier_id == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                        <textarea name="note" rows="1" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg"
                            placeholder="Nhập ghi chú về phiếu nhập kho (nếu có)">{{ old('note', $import->note) }}</textarea>
                    </div>
                </div>

                <!-- Chi phí phục vụ nhập hàng -->
                <div class="border-t border-gray-200 pt-4 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">
                        <i class="fas fa-calculator text-orange-500 mr-2"></i>Chi phí phục vụ nhập hàng
                    </h3>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                        <p class="text-sm text-blue-700">
                            <i class="fas fa-info-circle mr-2"></i>
                            Chi phí phục vụ sẽ được phân bổ đều cho tất cả sản phẩm để tính giá kho cuối cùng.
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Chi phí vận chuyển</label>
                            <input type="number" name="shipping_cost" value="{{ old('shipping_cost', $import->shipping_cost) }}" 
                                   min="0" step="0.01" class="service-cost-input w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg"
                                   placeholder="0" onchange="calculateServiceCost()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Chi phí bốc xếp</label>
                            <input type="number" name="loading_cost" value="{{ old('loading_cost', $import->loading_cost) }}" 
                                   min="0" step="0.01" class="service-cost-input w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg"
                                   placeholder="0" onchange="calculateServiceCost()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Chi phí kiểm định</label>
                            <input type="number" name="inspection_cost" value="{{ old('inspection_cost', $import->inspection_cost) }}" 
                                   min="0" step="0.01" class="service-cost-input w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg"
                                   placeholder="0" onchange="calculateServiceCost()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Chi phí khác</label>
                            <input type="number" name="other_cost" value="{{ old('other_cost', $import->other_cost) }}" 
                                   min="0" step="0.01" class="service-cost-input w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg"
                                   placeholder="0" onchange="calculateServiceCost()">
                        </div>
                    </div>
                    <div class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Tổng chi phí phục vụ:</span>
                            <span id="totalServiceCost" class="text-lg font-bold text-orange-600">{{ number_format($import->total_service_cost, 0, ',', '.') }} ₫</span>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-sm text-gray-600">Chi phí phục vụ / đơn vị:</span>
                            <span id="serviceCostPerUnit" class="text-sm font-semibold text-gray-700">{{ number_format($import->getServiceCostPerUnit(), 0, ',', '.') }} ₫</span>
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
                    <a href="{{ route('imports.show', $import) }}"
                        class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                        <i class="fas fa-times mr-1"></i> Hủy
                    </a>
                    <button type="submit" class="px-4 py-2 text-sm text-white bg-blue-500 rounded-lg hover:bg-blue-600">
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
            const warehouses = @json($warehouses);
            const existingItems = @json($existingItems);

            function addItem(existingData = null) {
                const container = document.getElementById('itemsContainer');
                const itemDiv = document.createElement('div');
                itemDiv.className = 'item-card bg-gray-50 rounded-lg p-4 border border-gray-200';
                itemDiv.dataset.index = itemIndex;

                const productOptions = products.map(p =>
                    `<option value="${p.id}" data-code="${p.code}" data-name="${p.name}" data-unit="${p.unit || 'Cái'}" data-cost="${p.default_cost || 0}" ${existingData && existingData.product_id == p.id ? 'selected' : ''}>${p.code} - ${p.name}</option>`
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

                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
                    <div class="md:col-span-1">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Sản phẩm *</label>
                        <select name="items[${itemIndex}][product_id]" required 
                                class="product-select w-full px-2 py-1.5 text-sm border border-gray-300 rounded"
                                onchange="updateProductPrice(${itemIndex}); updateSummary()">
                            <option value="">-- Chọn sản phẩm --</option>
                            ${productOptions}
                        </select>
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
                               placeholder="1" onchange="updateSerialInfo(${itemIndex}); updateSummary();">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Đơn giá nhập</label>
                        <input type="number" name="items[${itemIndex}][cost]" value="${existingData ? existingData.cost : '0'}" 
                               required min="0" step="0.01" class="cost-input w-full px-2 py-1.5 text-sm border border-gray-300 rounded" 
                               placeholder="0" onchange="updateSummary()">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Danh sách Serial</label>
                        <textarea name="items[${itemIndex}][serial_list]" rows="2"
                                  class="serial-textarea w-full px-2 py-1.5 text-sm border border-gray-300 rounded font-mono" 
                                  placeholder="Nhập danh sách số serial, mỗi số serial trên một dòng hoặc ngăn cách bằng dấu phẩy"
                                  onchange="updateSerialInfo(${itemIndex})">${existingData && existingData.serials ? (Array.isArray(existingData.serials) ? existingData.serials.join(', ') : existingData.serials) : ''}</textarea>
                        <p class="text-xs text-gray-400 mt-1">Nhập serial ngăn cách bằng dấu phẩy (,) hoặc xuống dòng. VD: ABC123, DEF456, GHI789</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Ghi chú</label>
                        <textarea name="items[${itemIndex}][comments]" rows="2"
                                  class="comments-textarea w-full px-2 py-1.5 text-sm border border-gray-300 rounded" 
                                  placeholder="Ghi chú cho sản phẩm này (tùy chọn)"
                                  onchange="updateSummary()">${existingData ? existingData.comments || '' : ''}</textarea>
                    </div>
                </div>

                <p id="serialInfo_${itemIndex}" class="text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>Số lượng: 1, Serial đã nhập: 0 → 1 sản phẩm sẽ được tạo mã tạm (NOSKU)
                </p>
            `;

                container.appendChild(itemDiv);
                updateSerialInfo(itemIndex);
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
                return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
            }

            function calculateServiceCost() {
                const serviceCostInputs = document.querySelectorAll('.service-cost-input');
                let totalServiceCost = 0;
                
                serviceCostInputs.forEach(input => {
                    totalServiceCost += parseFloat(input.value) || 0;
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
                    const cost = parseFloat(costInput ? costInput.value : 0) || 0;
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

            // Load existing items on page load
            document.addEventListener('DOMContentLoaded', function () {
                existingItems.forEach(item => addItem(item));
                if (existingItems.length === 0) addItem();
            });
        </script>
    @endpush
@endsection