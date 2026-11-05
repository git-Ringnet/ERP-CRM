@extends('layouts.app')

@section('title', 'Sửa đơn mua hàng')
@section('page-title', 'Sửa đơn mua hàng: ' . $purchaseOrder->code)

@section('content')
<div class="w-full bg-white rounded-lg shadow-sm">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-900">
            <i class="fas fa-edit text-purple-500 mr-2"></i>Chỉnh sửa đơn mua hàng (PO)
        </h2>
        <a href="{{ route('purchase-orders.index') }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại
        </a>
    </div>
    
    <form action="{{ route('purchase-orders.update', $purchaseOrder) }}" method="POST" class="p-4">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mã PO <span class="text-red-500">*</span></label>
                <input type="text" name="code" id="poCodeInput" value="{{ old('code', $purchaseOrder->code) }}" required maxlength="255" autocomplete="off"
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 @error('code') border-red-500 @enderror">
                <p id="poCodeStatus" class="text-xs mt-1"></p>
                @error('code')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CPQ đơn hàng</label>
                <input type="text" name="cpq_number" value="{{ old('cpq_number', $purchaseOrder->cpq_number) }}"
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nhà cung cấp</label>
                <input type="text" value="{{ $purchaseOrder->supplier->name }}" readonly 
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-gray-50">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ngày giao dự kiến</label>
                <input type="date" name="expected_delivery" value="{{ old('expected_delivery', $purchaseOrder->expected_delivery?->format('Y-m-d')) }}"
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
            </div>
        </div>

        <!-- Tiền tệ & Tỷ giá ẩn (Mặc định USD; PO trong nước định giá VND giữ VND) -->
        @php
            $poCurrencyDisplay = $purchaseOrder->currencyDisplay();
            $selectedCurrencyId = old('currency_id', $poCurrencyDisplay['currency']?->id ?? $baseCurrencyId);
            $selectedRate = old('exchange_rate', $poCurrencyDisplay['rate']);
        @endphp
        <input type="hidden" name="currency_id" id="currencySelect" value="{{ $selectedCurrencyId }}"
            data-is-base="{{ $poCurrencyDisplay['is_foreign'] ? '0' : '1' }}" data-symbol="{{ $poCurrencyDisplay['symbol'] }}">
        <input type="hidden" name="exchange_rate" id="exchangeRateInput" value="{{ $selectedRate }}">
        <script>
            // Ô tiền tệ là input ẩn (không phải select): trả về chính phần tử để đọc dataset
            function poCurrencyOption() {
                const el = document.getElementById('currencySelect');
                if (!el) return null;
                return el.tagName === 'SELECT' ? el.options[el.selectedIndex] : el;
            }
        </script>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Điều khoản thanh toán</label>
                <select name="payment_terms" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                    <option value="immediate" {{ $purchaseOrder->payment_terms == 'immediate' ? 'selected' : '' }}>Thanh toán ngay</option>
                    <option value="cod" {{ $purchaseOrder->payment_terms == 'cod' ? 'selected' : '' }}>COD</option>
                    <option value="net15" {{ $purchaseOrder->payment_terms == 'net15' ? 'selected' : '' }}>Net 15</option>
                    <option value="net30" {{ $purchaseOrder->payment_terms == 'net30' ? 'selected' : '' }}>Net 30</option>
                    <option value="net45" {{ $purchaseOrder->payment_terms == 'net45' ? 'selected' : '' }}>Net 45</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ giao hàng</label>
                <input type="text" name="delivery_address" value="{{ old('delivery_address', $purchaseOrder->delivery_address) }}"
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
            </div>
        </div>

        <!-- Tracking dates -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-calendar-alt text-green-400 mr-1"></i>Ngày dự kiến hàng về
                </label>
                <input type="date" name="expected_arrival_date" 
                    value="{{ old('expected_arrival_date', $purchaseOrder->expected_arrival_date?->format('Y-m-d')) }}"
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-industry text-purple-400 mr-1"></i>Ngày hãng xuất sản phẩm
                </label>
                <input type="date" name="manufacturer_release_date" 
                    value="{{ old('manufacturer_release_date', $purchaseOrder->manufacturer_release_date?->format('Y-m-d')) }}"
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-link text-blue-400 mr-1"></i>Đơn bán hàng liên kết
                </label>
                <select name="sale_id" id="saleSelect" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                    <option value="">-- Không liên kết --</option>
                    @foreach($availableSales ?? [] as $sale)
                        <option value="{{ $sale->id }}" {{ old('sale_id', $purchaseOrder->sale_id) == $sale->id ? 'selected' : '' }}>
                            {{ $sale->code }} - {{ $sale->customer_name }} ({{ number_format($sale->total, 0, ',', '.') }}₫)
                        </option>
                    @endforeach
                </select>
                @if($purchaseOrder->sale)
                    <div class="mt-1">
                        <a href="{{ route('sales.show', $purchaseOrder->sale_id) }}" target="_blank" class="text-xs text-blue-600 hover:underline">
                            <i class="fas fa-external-link-alt mr-1"></i>Xem chi tiết đơn {{ $purchaseOrder->sale->code }}
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
            <textarea name="note" rows="3" 
                class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500" 
                placeholder="Nhập ghi chú cho đơn hàng này...">{{ old('note', $purchaseOrder->note) }}</textarea>
        </div>

        <!-- Danh sách sản phẩm -->
        @php
            $isForeign = $purchaseOrder->currency && !$purchaseOrder->currency->is_base;
            $decimals = $isForeign ? ($purchaseOrder->currency->decimal_places ?? 2) : 0;
        @endphp
        <div class="border-t border-gray-200 pt-4 mb-6">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-900">Chi tiết sản phẩm</h3>
                <button type="button" id="addItem" class="px-4 py-2 text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                    <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
                </button>
            </div>
            <div id="itemsContainer" class="space-y-3">
                @foreach($purchaseOrder->items as $index => $item)
                <div class="item-row grid grid-cols-12 gap-2 items-end p-3 bg-gray-50 rounded-lg border border-gray-200 relative">
                    <div class="col-span-2 relative product-autocomplete">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Sản phẩm</label>
                        <input type="text" name="items[{{ $index }}][product_name]" value="{{ $item->product_name }}"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 product-name-input" autocomplete="off" placeholder="Nhập tên sản phẩm...">
                        <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id ?? '' }}" class="product-id">
                        <input type="hidden" name="items[{{ $index }}][sale_order_request_item_id]" value="{{ $item->sale_order_request_item_id ?? '' }}" class="sale-order-request-item-id">
                        <ul class="absolute z-50 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto hidden suggestions-list top-full left-0 mt-1"></ul>
                    </div>
                    <div class="col-span-1">
                        <label class="block text-xs font-medium text-gray-600 mb-1">SL</label>
                        <input type="number" name="items[{{ $index }}][quantity]" value="{{ $item->quantity }}" min="1" required
                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-qty" oninput="calculateRow(this)">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Giá list ({{ $poCurrencyDisplay['code'] }})</label>
                        <input type="text" name="items[{{ $index }}][warehouse_unit_price]" value="{{ $item->warehouse_unit_price ? (floor($item->warehouse_unit_price) == $item->warehouse_unit_price ? number_format($item->warehouse_unit_price, 0, '.', '') : number_format($item->warehouse_unit_price, 2, '.', '')) : '' }}"
                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-list-price focus:ring-blue-500" oninput="calculateRow(this)">
                    </div>
                    <div class="col-span-1">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Discount (%)</label>
                        <input type="text" name="items[{{ $index }}][discount_percent]" value="{{ floor($item->discount_percent ?? 0) == ($item->discount_percent ?? 0) ? number_format($item->discount_percent ?? 0, 0, '.', '') : number_format($item->discount_percent ?? 0, 2, '.', '') }}"
                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-discount focus:ring-blue-500" oninput="calculateRow(this)">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Giá mua ({{ $poCurrencyDisplay['code'] }})</label>
                        <input type="text" name="items[{{ $index }}][unit_price]" value="{{ floor($item->unit_price) == $item->unit_price ? number_format($item->unit_price, 0, '.', '') : number_format($item->unit_price, 2, '.', '') }}" required
                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-price border-blue-400 focus:ring-blue-500" oninput="calculateRow(this)">
                    </div>
                    <div class="col-span-3">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Thành tiền</label>
                        <input type="text" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded bg-gray-100 item-total" readonly
                            value="{{ floor($item->total) == $item->total ? number_format($item->total, 0, '.', ',') : number_format($item->total, 2, '.', ',') }}">
                    </div>
                    <div class="col-span-1 flex justify-center">
                        <button type="button" class="remove-item w-8 h-8 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center" {{ $loop->count == 1 ? 'style=display:none' : '' }}>
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                <textarea name="note" rows="4" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">{{ old('note', $purchaseOrder->note) }}</textarea>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between items-start">
                    <span class="text-gray-600 text-sm mt-1">Tổng tiền hàng:</span>
                    <div class="text-right">
                        <div id="subtotal" class="font-medium">
                            {{ number_format($purchaseOrder->subtotal, floor($purchaseOrder->subtotal) == $purchaseOrder->subtotal ? 0 : 2, '.', ',') }} $
                        </div>
                        
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 text-sm">Chiết khấu (%):</span>
                    <div class="flex items-center gap-2">
                        <span id="discountAmountDisplay" class="text-sm font-medium text-red-500 {{ $purchaseOrder->discount_percent > 0 ? '' : 'hidden' }}">
                            {{ $purchaseOrder->discount_percent > 0 ? '-' . number_format($purchaseOrder->subtotal * ($purchaseOrder->discount_percent / 100), floor($purchaseOrder->subtotal * ($purchaseOrder->discount_percent / 100)) == ($purchaseOrder->subtotal * ($purchaseOrder->discount_percent / 100)) ? 0 : 2, '.', ',') . ' $' : '' }}
                        </span>
                        <input type="number" step="any" name="discount_percent" value="{{ old('discount_percent', $purchaseOrder->discount_percent !== null ? floatval($purchaseOrder->discount_percent) : 0) }}" min="0" max="100"
                            class="w-20 px-2 py-1 text-sm border border-gray-300 rounded text-right" onchange="calculateTotal()" oninput="calculateTotal()">
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 text-sm">Phí vận chuyển:</span>
                    <input type="number" step="any" name="shipping_cost" value="{{ old('shipping_cost', $purchaseOrder->shipping_cost !== null ? floatval($purchaseOrder->shipping_cost) : 0) }}" min="0"
                        class="w-32 px-2 py-1 text-sm border border-gray-300 rounded text-right" onchange="calculateTotal()" oninput="calculateTotal()">
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 text-sm">Chi phí khác:</span>
                    <input type="number" step="any" name="other_cost" value="{{ old('other_cost', $purchaseOrder->other_cost !== null ? floatval($purchaseOrder->other_cost) : 0) }}" min="0"
                        class="w-32 px-2 py-1 text-sm border border-gray-300 rounded text-right" onchange="calculateTotal()" oninput="calculateTotal()">
                </div>
                <input type="hidden" name="vat_percent" value="0">
                <div class="flex justify-between items-start pt-3 border-t">
                    <span class="text-lg font-semibold mt-1">Tổng cộng:</span>
                    <div class="text-right">
                        <div id="total" class="text-lg font-bold text-blue-600">
                            {{ number_format($purchaseOrder->total_foreign ?? ($purchaseOrder->total / ($purchaseOrder->exchange_rate ?: 1)), floor($purchaseOrder->total_foreign ?? ($purchaseOrder->total / ($purchaseOrder->exchange_rate ?: 1))) == ($purchaseOrder->total_foreign ?? ($purchaseOrder->total / ($purchaseOrder->exchange_rate ?: 1))) ? 0 : 2, '.', ',') }} $
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
            <a href="{{ route('purchase-orders.index') }}" 
               class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                <i class="fas fa-times mr-1"></i> Hủy
            </a>
            <button type="submit" class="px-4 py-2 text-sm text-white bg-blue-500 rounded-lg hover:bg-blue-600">
                <i class="fas fa-save mr-1"></i> Cập nhật
            </button>
        </div>
    </form>
</div>


@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<style>
    .ts-wrapper.single .ts-control {
        padding: 6px 12px;
        border-radius: 0.5rem;
        border-color: #d1d5db;
        min-height: 38px;
    }
    .ts-dropdown {
        border-radius: 0.5rem;
    }
    .ts-dropdown .option {
        padding: 8px 12px;
    }
    .ts-dropdown .option.active {
        background-color: #eff6ff;
        color: #1e3a8a;
    }
    /* Custom Autocomplete Suggestions */
    .suggestions-list::-webkit-scrollbar {
        width: 6px;
    }
    .suggestions-list::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 3px;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var saleEl = document.getElementById('saleSelect');
    if (saleEl) {
        new TomSelect('#saleSelect', {
            placeholder: '-- Tìm kiếm mã SO hoặc khách hàng --',
            allowEmptyOption: true,
            maxOptions: 300,
            render: {
                no_results: function() {
                    return '<div class="no-results p-3 text-gray-500">Không tìm thấy đơn bán hàng phù hợp</div>';
                }
            }
        });
    }
});
@php
    $productData = $products->map(function ($p) {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'code' => $p->code,
            'unit' => $p->unit
        ];
    });
@endphp
const products = @json($productData);

function setupProductAutocomplete(row) {
    const input = row.querySelector('.product-name-input');
    const idInput = row.querySelector('.product-id');
    const suggestions = row.querySelector('.suggestions-list');

    function renderSuggestions(matches) {
        suggestions.innerHTML = '';
        if (matches.length === 0) {
            suggestions.classList.add('hidden');
            return;
        }
        matches.forEach(p => {
            const li = document.createElement('li');
            li.className = 'px-3 py-2 cursor-pointer hover:bg-blue-50 border-b border-gray-100 last:border-0';
            li.innerHTML = `
                <div class="font-medium text-sm text-gray-900">${p.code}</div>
            `;
            li.addEventListener('mousedown', (e) => {
                e.preventDefault(); // Prevent blur event
                input.value = p.code;
                idInput.value = p.id;
                suggestions.classList.add('hidden');
            });
            suggestions.appendChild(li);
        });
        suggestions.classList.remove('hidden');
    }

    input.addEventListener('input', function() {
        const val = this.value.toLowerCase();
        // Reset ID if input changes
        const exactMatch = products.find(p => p.code.toLowerCase() === val || p.name.toLowerCase() === val);
        idInput.value = exactMatch ? exactMatch.id : '';

        if (val.length < 1) {
            suggestions.classList.add('hidden');
            return;
        }

        const matches = products.filter(p => 
            p.name.toLowerCase().includes(val) || 
            p.code.toLowerCase().includes(val)
        ).slice(0, 20);
        renderSuggestions(matches);
    });

    input.addEventListener('focus', function() {
        if (this.value.trim() === '') {
            renderSuggestions(products.slice(0, 20));
        } else {
             this.dispatchEvent(new Event('input'));
        }
    });

    input.addEventListener('blur', function() {
       setTimeout(() => suggestions.classList.add('hidden'), 200);
    });
}

document.querySelectorAll('.item-row').forEach(row => setupProductAutocomplete(row));

let itemIndex = {{ $purchaseOrder->items->count() }};

function calculateRow(input) {
    const row = input.closest('.item-row');
    
    const listPriceInput = row.querySelector('.item-list-price');
    const discountInput = row.querySelector('.item-discount');
    const priceInput = row.querySelector('.item-price');
    
    let listPrice = parseFloat(listPriceInput.value) || 0;
    if (listPrice < 0) {
        listPrice = 0;
        listPriceInput.value = "0.00";
    }
    
    let discount = parseFloat(discountInput.value) || 0;
    if (discount < 0) {
        discount = 0;
        discountInput.value = "0.00";
    }
    
    let price = parseFloat(priceInput.value) || 0;
    if (price < 0) {
        price = 0;
        priceInput.value = "0.00";
    }
    
    // If the change came from list price or discount, calculate price
    if (input.classList.contains('item-list-price') || input.classList.contains('item-discount')) {
        price = listPrice * (1 - discount / 100);
        priceInput.value = price % 1 === 0 ? price.toFixed(0) : price.toFixed(2);
    } else if (input.classList.contains('item-price')) {
        // If price is edited directly, recalculate discount percent based on list price
        if (listPrice > 0) {
            discount = Math.max(0, ((listPrice - price) / listPrice) * 100);
            discountInput.value = discount % 1 === 0 ? discount.toFixed(0) : discount.toFixed(2);
        }
    }
    
    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const itemTotal = Math.round((qty * price) * 100) / 100;
    row.querySelector('.item-total').value = itemTotal.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: itemTotal % 1 === 0 ? 0 : 2 });
    
    // Add logic to calculate VND
    const select = document.getElementById('currencySelect');
    const isBase = select ? (poCurrencyOption().dataset.isBase === '1') : true;
    const rate = parseFloat(document.getElementById('exchangeRateInput').value) || 1;
    
    const vndPriceDisplay = row.querySelector('.vnd-unit-price');
    const vndTotalDisplay = row.querySelector('.vnd-item-total');
    if (vndPriceDisplay) vndPriceDisplay.classList.add('hidden');
    if (vndTotalDisplay) vndTotalDisplay.classList.add('hidden');
    calculateTotal();
}

function calculateTotal() {
    let subtotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const priceStr = String(row.querySelector('.item-price').value).replace(/\s+/g, '').replace(',', '.');
        const price = parseFloat(priceStr) || 0;
        subtotal += Math.round((qty * price) * 100) / 100;
    });

    const parseVal = (sel) => {
        const el = document.querySelector(sel);
        if (!el) return 0;
        return parseFloat(String(el.value).replace(/\s+/g, '').replace(',', '.')) || 0;
    };

    const discountPercent = parseVal('input[name="discount_percent"]');
    const discountAmount = Math.round((subtotal * (discountPercent / 100)) * 100) / 100;
    
    const afterDiscount = subtotal - discountAmount;
    
    const shippingCost = parseVal('input[name="shipping_cost"]');
    const otherCost = parseVal('input[name="other_cost"]');
    
    const beforeVat = afterDiscount + shippingCost + otherCost;

    const total = Math.round((beforeVat) * 100) / 100;

    const select = document.getElementById('currencySelect');
    const option = select ? poCurrencyOption() : null;
    const symbol = option ? (option.dataset.symbol || '$') : '$';
    const isBase = option ? option.dataset.isBase === '1' : false;
    const rate = parseFloat(String(document.getElementById('exchangeRateInput').value).replace(',', '.')) || 1;

    document.getElementById('subtotal').innerHTML = subtotal.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: subtotal % 1 === 0 ? 0 : 2 }) + ' ' + symbol;
    document.getElementById('total').innerHTML = total.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: total % 1 === 0 ? 0 : 2 }) + ' ' + symbol;

    const discDisplay = document.getElementById('discountAmountDisplay');
    if (discDisplay) {
        discDisplay.textContent = discountAmount > 0 ? ('-' + discountAmount.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: discountAmount % 1 === 0 ? 0 : 2 }) + ' ' + symbol) : '';
        discDisplay.classList.toggle('hidden', discountAmount <= 0);
    }
    
    const vatDisplay = document.getElementById('vatAmountDisplay');
    if (vatDisplay) {
        vatDisplay.textContent = vatAmount > 0 ? ('+' + vatAmount.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: vatAmount % 1 === 0 ? 0 : 2 }) + ' $') : '';
        vatDisplay.classList.toggle('hidden', vatAmount <= 0);
    }
    
    document.querySelectorAll('.currency-symbol').forEach(el => el.textContent = symbol);

    const subtotalVnd = document.getElementById('subtotalVnd');
    const totalVnd = document.getElementById('totalVndReference');
    
    if (isBase) {
        if (subtotalVnd) subtotalVnd.classList.add('hidden');
        if (totalVnd) totalVnd.classList.add('hidden');
    } else {
        if (subtotalVnd) {
            subtotalVnd.textContent = Math.round(subtotal * rate).toLocaleString('en-US') + ' đ';
            subtotalVnd.classList.remove('hidden');
        }
        if (totalVnd) {
            totalVnd.textContent = Math.round(total * rate).toLocaleString('en-US') + ' đ';
            totalVnd.classList.remove('hidden');
        }
    }
}

function onCurrencyChange() {
    const select = document.getElementById('currencySelect');
    if (select && select.tagName === 'SELECT' && select.options) {
        const option = poCurrencyOption();
        const isBase = option.dataset.isBase === '1';
        const exGroup = document.getElementById('exchangeRateGroup');
        if (exGroup) {
            if (isBase) {
                exGroup.classList.add('hidden');
                document.getElementById('exchangeRateInput').value = 1;
            } else {
                exGroup.classList.remove('hidden');
            }
        }
    }
    document.querySelectorAll('.item-qty').forEach(input => calculateRow(input));
    calculateTotal();
}

async function fetchExchangeRate(currencyId) {
    const dateInput = document.querySelector('input[name="order_date"]');
    const date = dateInput ? dateInput.value : '{{ $purchaseOrder->order_date->format("Y-m-d") }}';
    
    try {
        const response = await fetch(`{{ route('api.exchange-rate') }}?currency_id=${currencyId}&date=${date}`);
        const data = await response.json();
        
        if (data.rate) {
            document.getElementById('exchangeRateInput').value = data.rate;
            document.querySelectorAll('.item-qty').forEach(input => calculateRow(input));
            calculateTotal();
        }
    } catch (e) {
        console.error('Failed to fetch exchange rate', e);
    }
}

// Gọi onCurrencyChange khi load trang để set trạng thái ban đầu
document.addEventListener('DOMContentLoaded', function() {
    onCurrencyChange();
});

document.getElementById('addItem').addEventListener('click', function() {
    const container = document.getElementById('itemsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'item-row grid grid-cols-12 gap-2 items-end p-3 bg-gray-50 rounded-lg border border-gray-200 relative';
    newRow.innerHTML = `
        <div class="col-span-2 relative product-autocomplete">
            <label class="block text-xs font-medium text-gray-600 mb-1">Sản phẩm</label>
            <input type="text" name="items[${itemIndex}][product_name]" class="product-name-input w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" autocomplete="off" placeholder="Nhập tên sản phẩm...">
            <input type="hidden" name="items[${itemIndex}][product_id]" class="product-id">
            <input type="hidden" name="items[${itemIndex}][sale_order_request_item_id]" value="" class="sale-order-request-item-id">
            <ul class="absolute z-50 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto hidden suggestions-list top-full left-0 mt-1"></ul>
        </div>
        <div class="col-span-1">
            <label class="block text-xs font-medium text-gray-600 mb-1">SL</label>
            <input type="number" name="items[${itemIndex}][quantity]" value="1" min="1" required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-qty" oninput="calculateRow(this)">
        </div>
        <div class="col-span-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">Giá list ({{ $poCurrencyDisplay['code'] }})</label>
            <input type="text" name="items[${itemIndex}][warehouse_unit_price]" value="0.00" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-list-price focus:ring-blue-500" oninput="calculateRow(this)">
        </div>
        <div class="col-span-1">
            <label class="block text-xs font-medium text-gray-600 mb-1">Discount (%)</label>
            <input type="text" name="items[${itemIndex}][discount_percent]" value="0.00" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-discount focus:ring-blue-500" oninput="calculateRow(this)">
        </div>
        <div class="col-span-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">Giá mua ({{ $poCurrencyDisplay['code'] }})</label>
            <input type="text" name="items[${itemIndex}][unit_price]" value="0.00" required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-price border-blue-400 focus:ring-blue-500" oninput="calculateRow(this)">
        </div>
        <div class="col-span-3">
            <label class="block text-xs font-medium text-gray-600 mb-1">Thành tiền</label>
            <input type="text" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded bg-gray-100 item-total" readonly value="0.00">
        </div>
        <div class="col-span-1 flex justify-center">
            <button type="button" class="remove-item w-8 h-8 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center"><i class="fas fa-trash"></i></button>
        </div>
    `;
    container.appendChild(newRow);
    setupProductAutocomplete(newRow);
    
    // Set currency symbol for new row elements
    const select = document.getElementById('currencySelect');
    const option = select ? poCurrencyOption() : null;
    const symbol = option ? (option.dataset.symbol || 'đ') : 'đ';
    newRow.querySelectorAll('.currency-symbol').forEach(el => el.textContent = symbol);
    
    itemIndex++;
    updateRemoveButtons();
});

document.getElementById('itemsContainer').addEventListener('click', function(e) {
    if (e.target.closest('.remove-item')) {
        e.target.closest('.item-row').remove();
        updateRemoveButtons();
        calculateTotal();
    }
});

function updateRemoveButtons() {
    const rows = document.querySelectorAll('.item-row');
    rows.forEach(row => {
        row.querySelector('.remove-item').style.display = rows.length > 1 ? 'flex' : 'none';
    });
}
</script>
@include('purchase-orders.partials.code-check-script', ['ignoreId' => $purchaseOrder->id])
@endpush
@endsection
