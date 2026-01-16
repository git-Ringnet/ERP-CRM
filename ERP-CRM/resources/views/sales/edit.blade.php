@extends('layouts.app')

@section('title', 'Sửa đơn hàng')
@section('page-title', 'Sửa đơn hàng: ' . $sale->code)

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <form action="{{ route('sales.update', $sale->id) }}" method="POST" id="saleForm">
        @csrf
        @method('PUT')
        
        <div class="p-4 sm:p-6 space-y-6">
            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Mã đơn hàng <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="code" value="{{ old('code', $sale->code) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('code') border-red-500 @enderror">
                    @error('code')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @else
                        <p class="text-xs text-gray-500 mt-1">Có thể sửa mã đơn hàng nếu cần</p>
                    @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Loại đơn hàng <span class="text-red-500">*</span>
                    </label>
                    <select name="type" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="retail" {{ old('type', $sale->type) == 'retail' ? 'selected' : '' }}>Bán lẻ</option>
                        <option value="project" {{ old('type', $sale->type) == 'project' ? 'selected' : '' }}>Bán theo dự án</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Khách hàng <span class="text-red-500">*</span>
                    </label>
                    <div class="searchable-select" id="customerSelect">
                        <input type="text" class="searchable-input w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('customer_id') border-red-500 @enderror" 
                               placeholder="Gõ để tìm khách hàng..." autocomplete="off"
                               value="{{ $sale->customer->name }} ({{ $sale->customer->code }})">
                        <input type="hidden" name="customer_id" required value="{{ $sale->customer_id }}">
                        <div class="searchable-dropdown hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg max-h-48 overflow-y-auto shadow-lg">
                            @foreach($customers as $customer)
                                <div class="searchable-option px-3 py-2 hover:bg-blue-50 cursor-pointer" 
                                     data-value="{{ $customer->id }}" 
                                     data-text="{{ $customer->name }} ({{ $customer->code }})">
                                    {{ $customer->name }} ({{ $customer->code }})
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @error('customer_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Ngày tạo <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="date" value="{{ old('date', $sale->date->format('Y-m-d')) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ giao hàng</label>
                    <textarea name="delivery_address" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">{{ old('delivery_address', $sale->delivery_address) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Trạng thái <span class="text-red-500">*</span>
                    </label>
                    <select name="status" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="pending" {{ old('status', $sale->status) == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                        <option value="approved" {{ old('status', $sale->status) == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                        <option value="shipping" {{ old('status', $sale->status) == 'shipping' ? 'selected' : '' }}>Đang giao</option>
                        <option value="completed" {{ old('status', $sale->status) == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                        <option value="cancelled" {{ old('status', $sale->status) == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                    </select>
                </div>
            </div>

            <!-- Products Section -->
            <div class="border-t pt-4">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Chi tiết sản phẩm</h4>
                
                <div id="productList" class="space-y-3">
                    @foreach($sale->items as $index => $item)
                    <div class="product-item bg-gray-50 p-3 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                            <div class="md:col-span-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm <span class="text-red-500">*</span></label>
                                <div class="searchable-select product-searchable" data-index="{{ $index }}">
                                    <input type="text" class="searchable-input w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" 
                                           placeholder="Gõ để tìm sản phẩm..." autocomplete="off"
                                           value="{{ $item->product->name }}">
                                    <input type="hidden" name="products[{{ $index }}][product_id]" required class="product-id-input" value="{{ $item->product_id }}">
                                    <div class="searchable-dropdown hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg max-h-48 overflow-y-auto shadow-lg">
                                        @foreach($products as $product)
                                            <div class="searchable-option px-3 py-2 hover:bg-blue-50 cursor-pointer" 
                                                 data-value="{{ $product['id'] }}" 
                                                 data-price="{{ $product['price'] }}"
                                                 data-is-liquidation="{{ $product['is_liquidation'] }}"
                                                 data-warranty="{{ $product['warranty_months'] ?? 0 }}"
                                                 data-text="{{ $product['name'] }}">
                                                {{ $product['name'] }}
                                                @if(isset($product['liquidation_count']) && $product['liquidation_count'] > 0 && !$product['is_liquidation'])
                                                    <span class="text-orange-600 italic text-xs ml-1">(Có {{ $product['liquidation_count'] }} sẵn)</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng <span class="text-red-500">*</span></label>
                                <input type="number" name="products[{{ $index }}][quantity]" min="1" value="{{ $item->quantity }}" required
                                       onchange="calculateRowTotal({{ $index }})"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary quantity-input">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Đơn giá <span class="text-red-500">*</span></label>
                                <input type="text" name="products[{{ $index }}][price]" min="0" value="{{ number_format((int)$item->price, 0, '.', ',') }}" required
                                       onchange="calculateRowTotal({{ $index }})"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary price-input">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Bảo hành (tháng)</label>
                                <input type="number" name="products[{{ $index }}][warranty_months]" min="0" max="120" value="{{ $item->warranty_months ?? '' }}"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary warranty-input"
                                       placeholder="0">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Thành tiền</label>
                                <input type="text" readonly value="{{ number_format($item->total, 0, '.', ',') }}"
                                       class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 row-total">
                            </div>
                            <div class="md:col-span-1 flex items-end">
                                <button type="button" onclick="removeProductRow(this)" 
                                        class="w-full px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <button type="button" onclick="addProductRow()" 
                        class="mt-3 inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Thêm sản phẩm
                </button>
            </div>

            <!-- Expenses Section (Internal Costs) -->
            <div class="border-t pt-4">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-lg font-medium text-gray-900">Chi phí nội bộ (Không hiển thị trên hóa đơn)</h4>
                    <button type="button" onclick="calculateExpenses()" 
                            class="inline-flex items-center px-3 py-1.5 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition-colors text-sm font-medium">
                        <i class="fas fa-calculator mr-1.5"></i> Tính chi phí tự động
                    </button>
                </div>
                
                <div id="expenseList" class="space-y-3">
                    @if($sale->expenses && count($sale->expenses) > 0)
                        @foreach($sale->expenses as $index => $expense)
                        <div class="expense-item bg-gray-50 p-3 rounded-lg">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Loại chi phí</label>
                                    <select name="expenses[{{ $index }}][type]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary expense-type">
                                        <option value="shipping" {{ $expense->type == 'shipping' ? 'selected' : '' }}>Vận chuyển</option>
                                        <option value="marketing" {{ $expense->type == 'marketing' ? 'selected' : '' }}>Marketing</option>
                                        <option value="commission" {{ $expense->type == 'commission' ? 'selected' : '' }}>Hoa hồng</option>
                                        <option value="other" {{ $expense->type == 'other' ? 'selected' : '' }}>Khác</option>
                                    </select>
                                </div>
                                <div class="md:col-span-5">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                                    <input type="text" name="expenses[{{ $index }}][description]" value="{{ $expense->description }}"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary"
                                           placeholder="Chi tiết chi phí...">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền (VNĐ)</label>
                                    <input type="text" name="expenses[{{ $index }}][amount]" value="{{ number_format((int)$expense->amount, 0, '.', ',') }}"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary price-input expense-amount">
                                </div>
                                <div class="md:col-span-1 flex items-end">
                                    <button type="button" onclick="removeExpenseRow(this)" 
                                            class="w-full px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>

                <button type="button" onclick="addExpenseRow()" 
                        class="mt-3 inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Thêm chi phí
                </button>
            </div>
            <div class="border-t pt-4">
                <div class="space-y-3 max-w-md ml-auto">
                    <div class="flex justify-between items-center">
                        <label class="text-sm font-medium text-gray-700">Tổng tiền hàng</label>
                        <input type="text" id="subtotal" readonly value="{{ number_format($sale->subtotal, 0, '.', ',') }}"
                               class="w-48 text-right border border-gray-200 bg-gray-100 rounded-lg px-3 py-2">
                    </div>
                    <div class="flex justify-between items-center">
                        <label class="text-sm font-medium text-gray-700">Chiết khấu (%)</label>
                        <div class="flex gap-2 items-center">
                            <input type="number" name="discount" id="discount" value="{{ old('discount', $sale->discount ? (int)$sale->discount : '') }}" min="0" max="100" step="1"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')" onchange="calculateTotal()"
                                   class="w-16 text-center border border-gray-300 rounded-lg px-2 py-2 focus:outline-none focus:ring-2 focus:ring-primary"
                                   placeholder="0">
                            <input type="text" id="discountAmount" readonly
                                   class="w-32 text-right border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 text-red-600">
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <label class="text-sm font-medium text-gray-700">Thuế GTGT (%)</label>
                        <div class="flex gap-2 items-center">
                            <input type="number" name="vat" id="vat" value="{{ old('vat', $sale->vat ? (int)$sale->vat : '') }}" min="0" max="100" step="1"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')" onchange="calculateTotal()"
                                   class="w-16 text-center border border-gray-300 rounded-lg px-2 py-2 focus:outline-none focus:ring-2 focus:ring-primary"
                                   placeholder="0">
                            <input type="text" id="vatAmount" readonly
                                   class="w-32 text-right border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 text-blue-600">
                        </div>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t">
                        <label class="text-base font-bold text-gray-900">Tổng cộng</label>
                        <input type="text" id="total" readonly value="{{ number_format($sale->total, 0, '.', ',') }}"
                               class="w-48 text-right border border-gray-200 bg-primary/10 rounded-lg px-3 py-2 font-bold text-lg text-primary">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                <textarea name="note" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">{{ old('note', $sale->note) }}</textarea>
            </div>
        </div>

        <!-- Validation Error Message -->
        <div id="validationErrors" class="hidden px-4 sm:px-6 py-3 bg-red-50 border-t border-red-200">
            <div class="flex items-start">
                <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-2"></i>
                <div>
                    <p class="text-sm font-medium text-red-800">Vui lòng điền đầy đủ các trường bắt buộc:</p>
                    <ul id="errorList" class="mt-1 text-sm text-red-700 list-disc list-inside"></ul>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="px-4 sm:px-6 py-4 bg-gray-50 border-t flex flex-col sm:flex-row gap-2 justify-end">
            <a href="{{ route('sales.index') }}" 
               class="inline-flex items-center justify-center px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                <i class="fas fa-times mr-2"></i> Hủy
            </a>
            <button type="button" onclick="validateAndSubmit()"
                    class="inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-save mr-2"></i> Cập nhật
            </button>
        </div>
    </form>
</div>
@endsection

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
.no-results {
    padding: 8px 12px;
    color: #6b7280;
    font-style: italic;
}
</style>
@endpush

@push('scripts')
<script>
let productIndex = {{ count($sale->items) }};
let isSubmitting = false;
const products = @json($products);

// Prevent "Leave site?" warning when submitting form
window.addEventListener('beforeunload', function(e) {
    if (isSubmitting) {
        return undefined;
    }
});

// Searchable Select Functions
function initSearchableSelect(container, onSelect) {
    const input = container.querySelector('.searchable-input');
    const hiddenInput = container.querySelector('input[type="hidden"]');
    const dropdown = container.querySelector('.searchable-dropdown');
    const options = dropdown.querySelectorAll('.searchable-option');
    
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
        
        let noResults = dropdown.querySelector('.no-results');
        if (!hasResults) {
            if (!noResults) {
                noResults = document.createElement('div');
                noResults.className = 'no-results';
                noResults.textContent = 'Không tìm thấy kết quả';
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
            if (onSelect) onSelect(opt);
        });
    });
    
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
    
    document.addEventListener('click', (e) => {
        if (!container.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
}

// Format money input (dùng dấu phẩy phân cách hàng nghìn)
function formatMoney(value) {
    if (!value) return '';
    const num = value.toString().replace(/[^\d]/g, '');
    return num.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function unformatMoney(value) {
    if (!value) return 0;
    return parseInt(value.toString().replace(/[^\d]/g, '')) || 0;
}

function initMoneyInputs() {
    document.querySelectorAll('.price-input').forEach(input => {
        if (!input.dataset.moneyInit) {
            setupMoneyInput(input);
            input.dataset.moneyInit = 'true';
        }
    });
}

function setupMoneyInput(input) {
    input.type = 'text';
    input.inputMode = 'numeric';
    
    // Don't re-format if already formatted (contains comma)
    // Don't re-format if already formatted (contains comma as thousand separator)
    if (input.value && !input.value.includes(',')) {
        // Convert decimal to integer before formatting
        const numValue = parseInt(parseFloat(input.value));
        input.value = formatMoney(numValue);
    }
    
    input.addEventListener('input', function(e) {
        const cursorPos = this.selectionStart;
        const oldLength = this.value.length;
        const rawValue = unformatMoney(this.value);
        this.value = formatMoney(rawValue);
        const newLength = this.value.length;
        const diff = newLength - oldLength;
        this.setSelectionRange(cursorPos + diff, cursorPos + diff);
    });
    
    input.addEventListener('blur', function() {
        if (this.value) {
            this.value = formatMoney(unformatMoney(this.value));
        }
    });
}

function initAllSearchableSelects() {
    const customerSelect = document.getElementById('customerSelect');
    if (customerSelect && !customerSelect.dataset.initialized) {
        initSearchableSelect(customerSelect);
        customerSelect.dataset.initialized = 'true';
    }
    
    document.querySelectorAll('.product-searchable').forEach(container => {
        if (!container.dataset.initialized) {
            initSearchableSelect(container, (opt) => {
                const row = container.closest('.product-item');
                const priceInput = row.querySelector('.price-input');
                const warrantyInput = row.querySelector('.warranty-input');
                if (priceInput && opt.dataset.price) {
                    priceInput.value = formatMoney(opt.dataset.price);
                    calculateRowTotal(parseInt(container.dataset.index));
                }
                const isLiquidationInput = row.querySelector('.is-liquidation-input');
                if (isLiquidationInput && opt.dataset.isLiquidation) {
                    isLiquidationInput.value = opt.dataset.isLiquidation;
                }
                // Auto-fill warranty from product
                if (warrantyInput && opt.dataset.warranty) {
                    const warrantyMonths = parseInt(opt.dataset.warranty) || 0;
                    warrantyInput.value = warrantyMonths > 0 ? warrantyMonths : '';
                }
            });
            container.dataset.initialized = 'true';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initAllSearchableSelects();
    initMoneyInputs();
});

function addProductRow() {
    const productList = document.getElementById('productList');
    const newRow = document.createElement('div');
    newRow.className = 'product-item bg-gray-50 p-3 rounded-lg';
    newRow.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
            <div class="md:col-span-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm</label>
                <div class="searchable-select product-searchable" data-index="${productIndex}">
                    <input type="text" class="searchable-input w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" 
                           placeholder="Gõ để tìm sản phẩm..." autocomplete="off">
                    <input type="hidden" name="products[${productIndex}][product_id]" required class="product-id-input">
                    <div class="searchable-dropdown hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg max-h-48 overflow-y-auto shadow-lg">
                        ${products.map(p => `<div class="searchable-option px-3 py-2 hover:bg-blue-50 cursor-pointer" data-value="${p.id}" data-price="${p.price}" data-is-liquidation="${p.is_liquidation}" data-warranty="${p.warranty_months || 0}" data-text="${p.name}">${p.name}${p.liquidation_count > 0 ? ` <span class="text-orange-600 italic text-xs ml-1">(Có ${p.liquidation_count} sẵn)</span>` : ''}</div>`).join('')}
                    </div>
                </div>
            </div>
            <div class="md:col-span-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng</label>
                <input type="number" name="products[${productIndex}][quantity]" min="1" value="1" required
                       onchange="calculateRowTotal(${productIndex})"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary quantity-input">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Đơn giá</label>
                <input type="number" name="products[${productIndex}][price]" min="0" required
                       onchange="calculateRowTotal(${productIndex})"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary price-input">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Bảo hành (tháng)</label>
                <input type="number" name="products[${productIndex}][warranty_months]" min="0" max="120" value=""
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary warranty-input"
                       placeholder="0">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Thành tiền</label>
                <input type="text" readonly
                       class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 row-total">
            </div>
            <div class="md:col-span-1 flex items-end">
                <button type="button" onclick="removeProductRow(this)" 
                        class="w-full px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    productList.appendChild(newRow);
    productIndex++;
    
    // Initialize searchable select and money inputs for new row
    initAllSearchableSelects();
    initMoneyInputs();
}

function removeProductRow(btn) {
    const items = document.querySelectorAll('.product-item');
    if (items.length > 1) {
        btn.closest('.product-item').remove();
        calculateTotal();
    }
}

function calculateRowTotal(index) {
    const rows = document.querySelectorAll('.product-item');
    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = unformatMoney(row.querySelector('.price-input').value);
        const total = qty * price;
        row.querySelector('.row-total').value = formatMoney(total);
    });
    calculateTotal();
}

function calculateTotal() {
    let subtotal = 0;
    document.querySelectorAll('.product-item').forEach(row => {
        const qty = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = unformatMoney(row.querySelector('.price-input').value);
        subtotal += qty * price;
    });
    
    const discount = parseInt(document.getElementById('discount').value) || 0;
    const vat = parseInt(document.getElementById('vat').value) || 0;
    
    const discountAmount = subtotal * discount / 100;
    const afterDiscount = subtotal - discountAmount;
    const vatAmount = afterDiscount * vat / 100;
    const total = afterDiscount + vatAmount;
    
    document.getElementById('subtotal').value = formatMoney(subtotal);
    document.getElementById('discountAmount').value = discountAmount > 0 ? formatMoney(discountAmount) : '0';
    document.getElementById('vatAmount').value = vatAmount > 0 ? formatMoney(vatAmount) : '0';
    document.getElementById('total').value = formatMoney(total);
}

// Calculate on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateTotal();
});

// Validation function
function validateAndSubmit() {
    const errors = [];
    const errorContainer = document.getElementById('validationErrors');
    const errorList = document.getElementById('errorList');
    
    // Reset error styles
    document.querySelectorAll('.border-red-500').forEach(el => {
        el.classList.remove('border-red-500');
    });
    
    // Check required fields
    const code = document.querySelector('input[name="code"]');
    if (!code.value.trim()) {
        errors.push('Mã đơn hàng');
        code.classList.add('border-red-500');
    }
    
    const customerId = document.querySelector('input[name="customer_id"]');
    const customerInput = document.querySelector('#customerSelect .searchable-input');
    if (!customerId.value) {
        errors.push('Khách hàng');
        customerInput.classList.add('border-red-500');
    }
    
    const date = document.querySelector('input[name="date"]');
    if (!date.value) {
        errors.push('Ngày tạo');
        date.classList.add('border-red-500');
    }
    
    // Check products
    let hasValidProduct = false;
    document.querySelectorAll('.product-item').forEach((row, index) => {
        const productId = row.querySelector('.product-id-input');
        const productInput = row.querySelector('.searchable-input');
        const quantity = row.querySelector('.quantity-input');
        const price = row.querySelector('.price-input');
        
        if (!productId.value) {
            errors.push(`Sản phẩm (dòng ${index + 1})`);
            productInput.classList.add('border-red-500');
        } else {
            hasValidProduct = true;
        }
        
        if (productId.value) {
            if (!quantity.value || parseFloat(quantity.value) < 1) {
                errors.push(`Số lượng (dòng ${index + 1})`);
                quantity.classList.add('border-red-500');
            }
            const priceValue = unformatMoney(price.value);
            if (!price.value || priceValue < 0) {
                errors.push(`Đơn giá (dòng ${index + 1})`);
                price.classList.add('border-red-500');
            }
        }
    });
    
    if (!hasValidProduct) {
        errors.push('Cần ít nhất 1 sản phẩm');
    }
    
    // Show errors or submit
    if (errors.length > 0) {
        errorList.innerHTML = errors.map(e => `<li>${e}</li>`).join('');
        errorContainer.classList.remove('hidden');
        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
        errorContainer.classList.add('hidden');
        
        // Unformat money values before submit
        document.querySelectorAll('.price-input').forEach(input => {
            input.value = unformatMoney(input.value);
        });
        
        // Set flag to prevent "Leave site?" warning
        isSubmitting = true;
        document.getElementById('saleForm').submit();
    }
}

// Expense Management
let expenseIndex = {{ count($sale->expenses) }};

function addExpenseRow(data = null) {
    const expenseList = document.getElementById('expenseList');
    const newRow = document.createElement('div');
    newRow.className = 'expense-item bg-gray-50 p-3 rounded-lg';
    
    // Default values
    const type = data ? data.type : 'other';
    const description = data ? data.description : '';
    const amount = data ? formatMoney(data.amount) : '';
    
    newRow.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Loại chi phí</label>
                <select name="expenses[${expenseIndex}][type]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary expense-type">
                    <option value="shipping" ${type === 'shipping' ? 'selected' : ''}>Vận chuyển</option>
                    <option value="marketing" ${type === 'marketing' ? 'selected' : ''}>Marketing</option>
                    <option value="commission" ${type === 'commission' ? 'selected' : ''}>Hoa hồng</option>
                    <option value="other" ${type === 'other' ? 'selected' : ''}>Khác</option>
                </select>
            </div>
            <div class="md:col-span-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                <input type="text" name="expenses[${expenseIndex}][description]" value="${description}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary"
                       placeholder="Chi tiết chi phí...">
            </div>
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền (VNĐ)</label>
                <input type="text" name="expenses[${expenseIndex}][amount]" value="${amount}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary price-input expense-amount">
            </div>
            <div class="md:col-span-1 flex items-end">
                <button type="button" onclick="removeExpenseRow(this)" 
                        class="w-full px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    expenseList.appendChild(newRow);
    expenseIndex++;
    
    // Initialize money input
    const newPriceInput = newRow.querySelector('.price-input');
    setupMoneyInput(newPriceInput);
}

function removeExpenseRow(btn) {
    btn.closest('.expense-item').remove();
}

async function calculateExpenses() {
    // Collect data
    const customerId = document.querySelector('input[name="customer_id"]').value;
    const items = [];
    document.querySelectorAll('.product-item').forEach(row => {
        const productId = row.querySelector('.product-id-input').value;
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = unformatMoney(row.querySelector('.price-input').value) || 0;
        
        if (productId && quantity > 0) {
            items.push({
                product_id: productId,
                quantity: quantity,
                price: price
            });
        }
    });
    
    if (items.length === 0) {
        alert('Vui lòng thêm sản phẩm trước khi tính chi phí.');
        return;
    }
    
    // Show loading
    const btn = document.querySelector('button[onclick="calculateExpenses()"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Đang tính...';
    btn.disabled = true;
    
    try {
        const response = await fetch('{{ route("cost-formulas.calculate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                customer_id: customerId,
                items: items
            })
        });
        
        const data = await response.json();
        
        if (data && data.length > 0) {
            // Confirm clear existing? No, just append or maybe optional.
            // For now, let's just append new suggestions.
            let count = 0;
            data.forEach(expense => {
                addExpenseRow({
                    type: expense.type,
                    description: expense.description + ' (Tự động)',
                    amount: expense.amount
                });
                count++;
            });
            alert(`Đã tìm thấy và thêm ${count} mục chi phí phù hợp.`);
        } else {
            alert('Không tìm thấy công thức chi phí phù hợp.');
        }
        
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi tính chi phí.');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}
</script>
@endpush
