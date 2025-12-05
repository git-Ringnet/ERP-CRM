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
                    <select name="customer_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('customer_id') border-red-500 @enderror">
                        <option value="">Chọn khách hàng</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }} ({{ $customer->code }})
                            </option>
                        @endforeach
                    </select>
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
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm</label>
                                <select name="products[0][product_id]" required onchange="updatePrice(this, 0)"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary product-select">
                                    <option value="">Chọn sản phẩm</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng</label>
                                <input type="number" name="products[0][quantity]" min="1" value="1" required
                                       onchange="calculateRowTotal(0)"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary quantity-input">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Đơn giá</label>
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
                               class="w-full border border-gray-200 bg-green-50 rounded-lg px-3 py-2 font-medium text-green-700">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tỷ lệ Margin (%)</label>
                        <input type="text" id="marginPercent" readonly
                               class="w-full border border-gray-200 bg-green-50 rounded-lg px-3 py-2 font-medium text-green-700">
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

        <!-- Actions -->
        <div class="px-4 sm:px-6 py-4 bg-gray-50 border-t flex flex-col sm:flex-row gap-2 justify-end">
            <a href="{{ route('sales.index') }}" 
               class="inline-flex items-center justify-center px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                <i class="fas fa-times mr-2"></i> Hủy
            </a>
            <button type="submit" 
                    class="inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-save mr-2"></i> Lưu đơn hàng
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
let productIndex = 1;
let expenseIndex = 1;
const products = @json($products);

function addProductRow() {
    const productList = document.getElementById('productList');
    const newRow = document.createElement('div');
    newRow.className = 'product-item bg-gray-50 p-3 rounded-lg';
    newRow.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
            <div class="md:col-span-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm</label>
                <select name="products[${productIndex}][product_id]" required onchange="updatePrice(this, ${productIndex})"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary product-select">
                    <option value="">Chọn sản phẩm</option>
                    ${products.map(p => `<option value="${p.id}" data-price="${p.price}">${p.name}</option>`).join('')}
                </select>
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
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const total = qty * price;
        row.querySelector('.row-total').value = total.toLocaleString('vi-VN') + ' đ';
    });
    calculateTotal();
}

function calculateTotal() {
    let subtotal = 0;
    document.querySelectorAll('.product-item').forEach(row => {
        const qty = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        subtotal += qty * price;
    });
    
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const vat = parseFloat(document.getElementById('vat').value) || 0;
    
    const discountAmount = subtotal * discount / 100;
    const afterDiscount = subtotal - discountAmount;
    const vatAmount = afterDiscount * vat / 100;
    const total = afterDiscount + vatAmount;
    
    document.getElementById('subtotal').value = subtotal.toLocaleString('vi-VN') + ' đ';
    document.getElementById('total').value = total.toLocaleString('vi-VN') + ' đ';
    
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
    
    document.getElementById('margin').value = margin.toLocaleString('vi-VN') + ' đ';
    document.getElementById('marginPercent').value = marginPercent + '%';
}

function calculateDebt() {
    const totalStr = document.getElementById('total').value.replace(/[^\d]/g, '');
    const total = parseFloat(totalStr) || 0;
    const paid = parseFloat(document.getElementById('paid_amount').value) || 0;
    const debt = total - paid;
    
    document.getElementById('debt').value = debt.toLocaleString('vi-VN') + ' đ';
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
        const amount = parseFloat(row.querySelector('.expense-amount').value) || 0;
        
        switch(type) {
            case 'shipping': shipping += amount; break;
            case 'marketing': marketing += amount; break;
            case 'commission': commission += amount; break;
            case 'other': other += amount; break;
        }
    });
    
    const total = shipping + marketing + commission + other;
    
    document.getElementById('shippingTotal').textContent = shipping.toLocaleString('vi-VN') + ' đ';
    document.getElementById('marketingTotal').textContent = marketing.toLocaleString('vi-VN') + ' đ';
    document.getElementById('commissionTotal').textContent = commission.toLocaleString('vi-VN') + ' đ';
    document.getElementById('otherTotal').textContent = other.toLocaleString('vi-VN') + ' đ';
    document.getElementById('totalCost').textContent = total.toLocaleString('vi-VN') + ' đ';
    
    calculateMargin();
}
</script>
@endpush
