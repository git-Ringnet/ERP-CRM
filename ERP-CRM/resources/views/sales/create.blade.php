@extends('layouts.app')

@section('title', 'Tạo đơn hàng')
@section('page-title', 'Tạo đơn hàng mới')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <form action="{{ route('sales.store') }}" method="POST" id="saleForm">
        @csrf
        
        <div class="p-4 sm:p-6 space-y-6">
            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Mã đơn hàng <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="code" value="{{ old('code', $code) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('code') border-red-500 @enderror"
                           placeholder="VD: SO-20251205-0001">
                    @error('code')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @else
                        <p class="text-xs text-gray-500 mt-1">Mã tự động: {{ $code }} (có thể sửa nếu cần)</p>
                    @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Loại đơn hàng <span class="text-red-500">*</span>
                    </label>
                    <select name="type" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="retail" {{ old('type') == 'retail' ? 'selected' : '' }}>Bán lẻ</option>
                        <option value="project" {{ old('type') == 'project' ? 'selected' : '' }}>Bán theo dự án</option>
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
                               placeholder="Gõ để tìm khách hàng..." autocomplete="off">
                        <input type="hidden" name="customer_id" required>
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
                    <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ giao hàng</label>
                <textarea name="delivery_address" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">{{ old('delivery_address') }}</textarea>
            </div>

            <!-- Products Section -->
            <div class="border-t pt-4">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Chi tiết sản phẩm</h4>
                
                <div id="productList" class="space-y-3">
                    <div class="product-item bg-gray-50 p-3 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                            <div class="md:col-span-5">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm <span class="text-red-500">*</span></label>
                                <div class="searchable-select product-searchable" data-index="0">
                                    <input type="text" class="searchable-input w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" 
                                           placeholder="Gõ để tìm sản phẩm..." autocomplete="off">
                                    <input type="hidden" name="products[0][product_id]" required class="product-id-input">
                                    <div class="searchable-dropdown hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg max-h-48 overflow-y-auto shadow-lg">
                                        @foreach($products as $product)
                                            <div class="searchable-option px-3 py-2 hover:bg-blue-50 cursor-pointer" 
                                                 data-value="{{ $product->id }}" 
                                                 data-price="{{ $product->price }}"
                                                 data-text="{{ $product->name }}">
                                                {{ $product->name }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng <span class="text-red-500">*</span></label>
                                <input type="number" name="products[0][quantity]" min="1" value="1" required
                                       onchange="calculateRowTotal(0)"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary quantity-input">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Đơn giá <span class="text-red-500">*</span></label>
                                <input type="number" name="products[0][price]" min="0" required
                                       onchange="calculateRowTotal(0)"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary price-input">
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
                    </div>
                </div>

                <button type="button" onclick="addProductRow()" 
                        class="mt-3 inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Thêm sản phẩm
                </button>
            </div>

            <!-- Expenses Section -->
            <div class="border-t pt-4">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Chi phí bán hàng</h4>
                
                <div id="expenseList" class="space-y-3">
                    <div class="expense-item bg-yellow-50 p-3 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Loại chi phí</label>
                                <select name="expenses[0][type]" onchange="calculateExpenses()"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary expense-type">
                                    <option value="">Chọn loại</option>
                                    <option value="shipping">Vận chuyển</option>
                                    <option value="marketing">Marketing</option>
                                    <option value="commission">Hoa hồng</option>
                                    <option value="other">Khác</option>
                                </select>
                            </div>
                            <div class="md:col-span-5">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                                <input type="text" name="expenses[0][description]" placeholder="VD: Chi phí vận chuyển"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền</label>
                                <input type="number" name="expenses[0][amount]" min="0" value="0"
                                       onchange="calculateExpenses()"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary expense-amount">
                            </div>
                            <div class="md:col-span-1 flex items-end">
                                <button type="button" onclick="removeExpenseRow(this)" 
                                        class="w-full px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" onclick="addExpenseRow()" 
                        class="mt-3 inline-flex items-center px-4 py-2 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Thêm chi phí
                </button>

                <div class="mt-4 grid grid-cols-2 md:grid-cols-5 gap-3 p-3 bg-gray-50 rounded-lg">
                    <div>
                        <div class="text-xs text-gray-500">Vận chuyển</div>
                        <div class="text-sm font-medium text-blue-600" id="shippingTotal">0 đ</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Marketing</div>
                        <div class="text-sm font-medium text-orange-600" id="marketingTotal">0 đ</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Hoa hồng</div>
                        <div class="text-sm font-medium text-green-600" id="commissionTotal">0 đ</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Khác</div>
                        <div class="text-sm font-medium text-gray-600" id="otherTotal">0 đ</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Tổng chi phí</div>
                        <div class="text-base font-bold text-red-600" id="totalCost">0 đ</div>
                    </div>
                </div>
            </div>

            <!-- Totals Section -->
            <div class="border-t pt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl ml-auto">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tổng tiền hàng</label>
                        <input type="text" id="subtotal" readonly
                               class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Chiết khấu (%)</label>
                        <input type="number" name="discount" id="discount" value="{{ old('discount', 0) }}" min="0" max="100"
                               onchange="calculateTotal()"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">VAT (%)</label>
                        <input type="number" name="vat" id="vat" value="{{ old('vat', 10) }}" min="0"
                               onchange="calculateTotal()"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><strong>Tổng cộng</strong></label>
                        <input type="text" id="total" readonly
                               class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 font-bold text-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lợi nhuận (Margin)</label>
                        <input type="text" id="margin" readonly
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 font-medium margin-display">
                        <p id="marginWarning" class="text-xs text-red-600 mt-1 hidden">
                            <i class="fas fa-exclamation-triangle"></i> Cảnh báo: Đơn hàng bị lỗ!
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tỷ lệ Margin (%)</label>
                        <input type="text" id="marginPercent" readonly
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 font-medium margin-percent-display">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Đã thanh toán</label>
                        <input type="number" name="paid_amount" id="paid_amount" value="{{ old('paid_amount', 0) }}" min="0"
                               onchange="calculateDebt()"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Công nợ còn lại</label>
                        <input type="text" id="debt" readonly
                               class="w-full border border-gray-200 bg-red-50 rounded-lg px-3 py-2 font-medium text-red-700">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                <textarea name="note" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">{{ old('note') }}</textarea>
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
                <i class="fas fa-save mr-2"></i> Lưu đơn hàng
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
let productIndex = 1;
let expenseIndex = 1;
const products = @json($products);

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
        
        // Show no results message
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

function initAllSearchableSelects() {
    // Customer select
    const customerSelect = document.getElementById('customerSelect');
    if (customerSelect) {
        initSearchableSelect(customerSelect, () => {
            autoCalculateExpenses();
        });
    }
    
    // Product selects
    document.querySelectorAll('.product-searchable').forEach(container => {
        if (!container.dataset.initialized) {
            initSearchableSelect(container, (opt) => {
                const row = container.closest('.product-item');
                const priceInput = row.querySelector('.price-input');
                if (priceInput && opt.dataset.price) {
                    priceInput.value = formatMoney(opt.dataset.price);
                    calculateRowTotal(parseInt(container.dataset.index));
                }
                autoCalculateExpenses();
            });
            container.dataset.initialized = 'true';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initAllSearchableSelects();
    initMoneyInputs();
});

// Format money input
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
    // Apply to price inputs
    document.querySelectorAll('.price-input').forEach(input => {
        if (!input.dataset.moneyInit) {
            setupMoneyInput(input);
            input.dataset.moneyInit = 'true';
        }
    });
    
    // Apply to expense amount inputs
    document.querySelectorAll('.expense-amount').forEach(input => {
        if (!input.dataset.moneyInit) {
            setupMoneyInput(input);
            input.dataset.moneyInit = 'true';
        }
    });
    
    // Apply to paid amount
    const paidAmount = document.getElementById('paid_amount');
    if (paidAmount && !paidAmount.dataset.moneyInit) {
        setupMoneyInput(paidAmount);
        paidAmount.dataset.moneyInit = 'true';
    }
}

function setupMoneyInput(input) {
    // Change type to text for formatting
    input.type = 'text';
    input.inputMode = 'numeric';
    
    // Format existing value
    if (input.value) {
        input.value = formatMoney(input.value);
    }
    
    input.addEventListener('input', function(e) {
        const cursorPos = this.selectionStart;
        const oldLength = this.value.length;
        const oldValue = this.value;
        
        // Get raw number
        const rawValue = unformatMoney(this.value);
        
        // Format and set
        this.value = formatMoney(rawValue);
        
        // Adjust cursor position
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

function addProductRow() {
    const productList = document.getElementById('productList');
    const newRow = document.createElement('div');
    newRow.className = 'product-item bg-gray-50 p-3 rounded-lg';
    newRow.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
            <div class="md:col-span-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm</label>
                <div class="searchable-select product-searchable" data-index="${productIndex}">
                    <input type="text" class="searchable-input w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" 
                           placeholder="Gõ để tìm sản phẩm..." autocomplete="off">
                    <input type="hidden" name="products[${productIndex}][product_id]" required class="product-id-input">
                    <div class="searchable-dropdown hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg max-h-48 overflow-y-auto shadow-lg">
                        ${products.map(p => `<div class="searchable-option px-3 py-2 hover:bg-blue-50 cursor-pointer" data-value="${p.id}" data-price="${p.price}" data-text="${p.name}">${p.name}</div>`).join('')}
                    </div>
                </div>
            </div>
            <div class="md:col-span-2">
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

function updatePrice(select, index) {
    const option = select.options[select.selectedIndex];
    const price = option.dataset.price || 0;
    const row = select.closest('.product-item');
    row.querySelector('.price-input').value = price;
    calculateRowTotal(index);
    
    // Auto-calculate expenses based on formulas
    autoCalculateExpenses();
}

async function autoCalculateExpenses() {
    const customerId = document.querySelector('select[name="customer_id"]').value;
    if (!customerId) return;
    
    // Get all products and calculate total revenue
    let totalRevenue = 0;
    let totalQuantity = 0;
    const productIds = [];
    
    document.querySelectorAll('.product-item').forEach(row => {
        const productId = row.querySelector('.product-select').value;
        const qty = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        
        if (productId) {
            productIds.push(productId);
            totalRevenue += qty * price;
            totalQuantity += qty;
        }
    });
    
    if (productIds.length === 0) return;
    
    try {
        // Call API to get applicable formulas
        const response = await fetch(`/api/cost-formulas/applicable?customer_id=${customerId}&product_id=${productIds[0]}&revenue=${totalRevenue}&quantity=${totalQuantity}`);
        const expenses = await response.json();
        
        // Clear existing expense rows
        const expenseList = document.getElementById('expenseList');
        expenseList.innerHTML = '';
        
        // Add expense rows from formulas
        expenses.forEach((expense, idx) => {
            const newRow = document.createElement('div');
            newRow.className = 'expense-item bg-yellow-50 p-3 rounded-lg';
            newRow.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Loại chi phí</label>
                        <select name="expenses[${idx}][type]" onchange="calculateExpenses()"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary expense-type">
                            <option value="shipping" ${expense.type === 'shipping' ? 'selected' : ''}>Vận chuyển</option>
                            <option value="marketing" ${expense.type === 'marketing' ? 'selected' : ''}>Marketing</option>
                            <option value="commission" ${expense.type === 'commission' ? 'selected' : ''}>Hoa hồng</option>
                            <option value="other" ${expense.type === 'other' ? 'selected' : ''}>Khác</option>
                        </select>
                    </div>
                    <div class="md:col-span-5">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                        <input type="text" name="expenses[${idx}][description]" value="${expense.description}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền</label>
                        <input type="number" name="expenses[${idx}][amount]" min="0" value="${expense.amount}"
                               onchange="calculateExpenses()"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary expense-amount">
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
        });
        
        expenseIndex = expenses.length;
        calculateExpenses();
        
    } catch (error) {
        console.error('Error fetching cost formulas:', error);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener to customer select
    const customerSelect = document.querySelector('select[name="customer_id"]');
    if (customerSelect) {
        customerSelect.addEventListener('change', function() {
            autoCalculateExpenses();
        });
    }
});
function calculateRowTotal(index) {
    const rows = document.querySelectorAll('.product-item');
    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = unformatMoney(row.querySelector('.price-input').value);
        const total = qty * price;
        row.querySelector('.row-total').value = formatMoney(total) + ' đ';
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
    
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const vat = parseFloat(document.getElementById('vat').value) || 0;
    
    const discountAmount = subtotal * discount / 100;
    const afterDiscount = subtotal - discountAmount;
    const vatAmount = afterDiscount * vat / 100;
    const total = afterDiscount + vatAmount;
    
    document.getElementById('subtotal').value = formatMoney(subtotal) + ' đ';
    document.getElementById('total').value = formatMoney(total) + ' đ';
    
    calculateMargin();
    calculateDebt();
}

function calculateMargin() {
    const totalStr = document.getElementById('total').value.replace(/[^\d]/g, '');
    const total = parseFloat(totalStr) || 0;
    const costStr = document.getElementById('totalCost').textContent.replace(/[^\d]/g, '');
    const cost = parseFloat(costStr) || 0;
    const margin = total - cost;
    const marginPercent = total > 0 ? (margin / total * 100).toFixed(2) : 0;
    
    document.getElementById('margin').value = formatMoney(margin) + ' đ';
    document.getElementById('marginPercent').value = marginPercent + '%';
    
    // Update colors based on margin
    const marginInput = document.getElementById('margin');
    const marginPercentInput = document.getElementById('marginPercent');
    const marginWarning = document.getElementById('marginWarning');
    
    // Remove all color classes
    marginInput.classList.remove('bg-green-50', 'text-green-700', 'bg-red-50', 'text-red-700', 'bg-yellow-50', 'text-yellow-700');
    marginPercentInput.classList.remove('bg-green-50', 'text-green-700', 'bg-red-50', 'text-red-700', 'bg-yellow-50', 'text-yellow-700');
    
    if (margin < 0) {
        // Negative margin (loss)
        marginInput.classList.add('bg-red-50', 'text-red-700');
        marginPercentInput.classList.add('bg-red-50', 'text-red-700');
        marginWarning.classList.remove('hidden');
    } else if (marginPercent < 10) {
        // Low margin
        marginInput.classList.add('bg-yellow-50', 'text-yellow-700');
        marginPercentInput.classList.add('bg-yellow-50', 'text-yellow-700');
        marginWarning.classList.add('hidden');
    } else {
        // Good margin
        marginInput.classList.add('bg-green-50', 'text-green-700');
        marginPercentInput.classList.add('bg-green-50', 'text-green-700');
        marginWarning.classList.add('hidden');
    }
}

function calculateDebt() {
    const total = unformatMoney(document.getElementById('total').value);
    const paid = unformatMoney(document.getElementById('paid_amount').value);
    const debt = total - paid;
    
    document.getElementById('debt').value = formatMoney(debt) + ' đ';
}

// Expense functions
function addExpenseRow() {
    const expenseList = document.getElementById('expenseList');
    const newRow = document.createElement('div');
    newRow.className = 'expense-item bg-yellow-50 p-3 rounded-lg';
    newRow.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Loại chi phí</label>
                <select name="expenses[${expenseIndex}][type]" onchange="calculateExpenses()"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary expense-type">
                    <option value="">Chọn loại</option>
                    <option value="shipping">Vận chuyển</option>
                    <option value="marketing">Marketing</option>
                    <option value="commission">Hoa hồng</option>
                    <option value="other">Khác</option>
                </select>
            </div>
            <div class="md:col-span-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                <input type="text" name="expenses[${expenseIndex}][description]" placeholder="VD: Chi phí vận chuyển"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền</label>
                <input type="number" name="expenses[${expenseIndex}][amount]" min="0" value="0"
                       onchange="calculateExpenses()"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary expense-amount">
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
    initMoneyInputs();
}

function removeExpenseRow(btn) {
    const items = document.querySelectorAll('.expense-item');
    if (items.length > 1) {
        btn.closest('.expense-item').remove();
        calculateExpenses();
    }
}

function calculateExpenses() {
    let shipping = 0, marketing = 0, commission = 0, other = 0;
    
    document.querySelectorAll('.expense-item').forEach(row => {
        const type = row.querySelector('.expense-type').value;
        const amount = unformatMoney(row.querySelector('.expense-amount').value);
        
        switch(type) {
            case 'shipping': shipping += amount; break;
            case 'marketing': marketing += amount; break;
            case 'commission': commission += amount; break;
            case 'other': other += amount; break;
        }
    });
    
    const total = shipping + marketing + commission + other;
    
    document.getElementById('shippingTotal').textContent = formatMoney(shipping) + ' đ';
    document.getElementById('marketingTotal').textContent = formatMoney(marketing) + ' đ';
    document.getElementById('commissionTotal').textContent = formatMoney(commission) + ' đ';
    document.getElementById('otherTotal').textContent = formatMoney(other) + ' đ';
    document.getElementById('totalCost').textContent = formatMoney(total) + ' đ';
    
    calculateMargin();
}

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
            if (index === 0 || productInput.value.trim()) {
                errors.push(`Sản phẩm (dòng ${index + 1})`);
                productInput.classList.add('border-red-500');
            }
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
        document.querySelectorAll('.expense-amount').forEach(input => {
            input.value = unformatMoney(input.value);
        });
        const paidAmount = document.getElementById('paid_amount');
        if (paidAmount) {
            paidAmount.value = unformatMoney(paidAmount.value);
        }
        
        document.getElementById('saleForm').submit();
    }
}
</script>
@endpush
