@extends('layouts.app')

@section('title', 'Sửa đơn mua hàng')
@section('page-title', 'Sửa đơn mua hàng: ' . $purchaseOrder->code)

@section('content')
<div class="bg-white rounded-lg shadow-sm">
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
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mã PO</label>
                <input type="text" value="{{ $purchaseOrder->code }}" readonly 
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-gray-50">
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

        <!-- Tiền tệ & Tỷ giá -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tiền tệ <span class="text-red-500">*</span></label>
                <select name="currency_id" id="currencySelect" onchange="onCurrencyChange()" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->id }}" 
                            data-is-base="{{ $currency->is_base ? '1' : '0' }}"
                            data-symbol="{{ $currency->symbol }}"
                            {{ old('currency_id', $purchaseOrder->currency_id ?? $baseCurrencyId) == $currency->id ? 'selected' : '' }}>
                            {{ $currency->code }} - {{ $currency->name_vi }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div id="exchangeRateGroup" class="{{ ($purchaseOrder->currency_id && $purchaseOrder->currency_id != $baseCurrencyId) ? '' : 'hidden' }}">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Tỷ giá (1 ngoại tệ = ? VND)
                    <span id="rateSource" class="text-xs text-blue-500 ml-1"></span>
                </label>
                <input type="number" name="exchange_rate" id="exchangeRateInput" step="0.000001" min="0"
                    value="{{ old('exchange_rate', $purchaseOrder->exchange_rate ? floatval($purchaseOrder->exchange_rate) : 1) }}"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    onchange="calculateTotal()">
                <p class="text-xs text-gray-500 mt-1" id="rateHint">Tỷ giá từ lúc tạo đơn</p>
            </div>
            <div id="dualPriceGroup" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Quy đổi sang VND</label>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-1.5 min-h-[38px] flex items-center">
                    <div id="dualPriceDisplay" class="text-sm text-blue-800 font-medium">-</div>
                </div>
            </div>
        </div>

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
                <div class="item-row grid grid-cols-12 gap-3 items-end p-3 bg-gray-50 rounded-lg border border-gray-200 relative">
                    <div class="col-span-4 relative product-autocomplete">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Sản phẩm</label>
                        <input type="text" name="items[{{ $index }}][product_name]" value="{{ $item->product_name }}"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 product-name-input" autocomplete="off" placeholder="Nhập tên sản phẩm...">
                        <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id ?? '' }}" class="product-id">
                        <ul class="absolute z-50 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto hidden suggestions-list top-full left-0 mt-1"></ul>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Số lượng</label>
                        <input type="number" name="items[{{ $index }}][quantity]" value="{{ $item->quantity }}" min="1" required
                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-qty" onchange="calculateRow(this)">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Đơn giá</label>
                        <input type="number" name="items[{{ $index }}][unit_price]" value="{{ number_format($item->unit_price, $decimals, '.', '') }}" min="0" required step="any"
                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-price" onchange="calculateRow(this)">
                    </div>
                    <div class="col-span-3">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Thành tiền</label>
                        <input type="text" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded bg-gray-100 item-total" readonly
                            value="{{ number_format($item->total, $decimals, '.', ',') }}">
                    </div>
                    <div class="col-span-1 flex justify-center">
                        <button type="button" class="remove-item w-8 h-8 bg-red-100 text-red-600 rounded hover:bg-red-200" {{ $loop->count == 1 ? 'style=display:none' : '' }}>
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
                <div class="flex justify-between">
                    <span class="text-gray-600 text-sm">Tổng tiền hàng:</span>
                    <span id="subtotal" class="font-medium">{{ number_format($purchaseOrder->subtotal) }}đ</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 text-sm">Chiết khấu (%):</span>
                    <input type="number" name="discount_percent" value="{{ old('discount_percent', $purchaseOrder->discount_percent) }}" min="0" max="100"
                        class="w-20 px-2 py-1 text-sm border border-gray-300 rounded text-right" onchange="calculateTotal()">
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 text-sm">Phí vận chuyển:</span>
                    <input type="number" name="shipping_cost" value="{{ old('shipping_cost', $purchaseOrder->shipping_cost) }}" min="0"
                        class="w-32 px-2 py-1 text-sm border border-gray-300 rounded text-right" onchange="calculateTotal()">
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 text-sm">Chi phí khác:</span>
                    <input type="number" name="other_cost" value="{{ old('other_cost', $purchaseOrder->other_cost) }}" min="0"
                        class="w-32 px-2 py-1 text-sm border border-gray-300 rounded text-right" onchange="calculateTotal()">
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 text-sm">VAT (%):</span>
                    <input type="number" name="vat_percent" value="{{ old('vat_percent', $purchaseOrder->vat_percent) }}" min="0"
                        class="w-20 px-2 py-1 text-sm border border-gray-300 rounded text-right" onchange="calculateTotal()">
                </div>
                <div class="flex justify-between pt-3 border-t">
                    <span class="text-lg font-semibold">Tổng cộng:</span>
                    <span id="total" class="text-lg font-bold text-blue-600">{{ number_format($purchaseOrder->total) }}đ</span>
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

</div>

@push('styles')
<style>
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
<script>
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
                <div class="font-medium text-sm text-gray-900">${p.name}</div>
                <div class="text-xs text-gray-500">Mã: ${p.code} | ĐVT: ${p.unit || '---'}</div>
            `;
            li.addEventListener('mousedown', (e) => {
                e.preventDefault(); // Prevent blur event
                input.value = p.name;
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
        const exactMatch = products.find(p => p.name.toLowerCase() === val);
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
    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    const itemTotal = Math.round((qty * price) * 100) / 100;
    row.querySelector('.item-total').value = itemTotal.toLocaleString('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    calculateTotal();
}
function calculateTotal() {
    let subtotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        subtotal += Math.round((qty * price) * 100) / 100;
    });

    const discountInput = document.querySelector('input[name="discount_percent"]');
    const discountPercent = discountInput ? (parseFloat(discountInput.value) || 0) : 0;
    const discountAmount = Math.round((subtotal * (discountPercent / 100)) * 100) / 100;
    
    const afterDiscount = subtotal - discountAmount;
    
    const shippingInput = document.querySelector('input[name="shipping_cost"]');
    const shippingCost = shippingInput ? (parseFloat(shippingInput.value) || 0) : 0;
    
    const otherCostInput = document.querySelector('input[name="other_cost"]');
    const otherCost = otherCostInput ? (parseFloat(otherCostInput.value) || 0) : 0;
    
    const beforeVat = afterDiscount + shippingCost + otherCost;

    const vatInput = document.querySelector('input[name="vat_percent"]');
    const vatRate = vatInput ? (parseFloat(vatInput.value) || 0) : 0;
    const vatAmount = Math.round((beforeVat * (vatRate / 100)) * 100) / 100;
    
    const total = Math.round((beforeVat + vatAmount) * 100) / 100;

    document.getElementById('subtotal').textContent = subtotal.toLocaleString('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' đ';
    document.getElementById('total').textContent = total.toLocaleString('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' đ';

    // Cập nhật hiển thị đa tiền tệ
    if (typeof updateDualPriceDisplay === 'function') {
        updateDualPriceDisplay(total);
    }
}

function onCurrencyChange() {
    const select = document.getElementById('currencySelect');
    const option = select.options[select.selectedIndex];
    const isBase = option.dataset.isBase === '1';

    if (isBase) {
        document.getElementById('exchangeRateGroup').classList.add('hidden');
        document.getElementById('dualPriceGroup').classList.add('hidden');
        document.getElementById('exchangeRateInput').value = 1;
    } else {
        document.getElementById('exchangeRateGroup').classList.remove('hidden');
        document.getElementById('dualPriceGroup').classList.remove('hidden');
        // Đối với Edit, không auto-fetch tỷ giá nếu đã có sẵn tỷ giá trong bản ghi
        const currentExchangeRate = {{ $purchaseOrder->exchange_rate ?? 1 }};
        const currentCurrencyId = {{ $purchaseOrder->currency_id ?? 0 }};
        
        if (select.value != currentCurrencyId) {
            fetchExchangeRate(select.value);
        } else {
            document.getElementById('exchangeRateInput').value = currentExchangeRate;
            calculateTotal();
        }
    }
    calculateTotal();
}

async function fetchExchangeRate(currencyId) {
    const dateInput = document.querySelector('input[name="order_date"]'); // PO Edit có thể không có order_date input nếu readonly
    const date = dateInput ? dateInput.value : '{{ $purchaseOrder->order_date->format("Y-m-d") }}';
    
    try {
        const response = await fetch(`{{ route('api.exchange-rate') }}?currency_id=${currencyId}&date=${date}`);
        const data = await response.json();
        
        if (data.rate) {
            document.getElementById('exchangeRateInput').value = data.rate;
            calculateTotal();
        }
    } catch (e) {
        console.error('Failed to fetch exchange rate', e);
    }
}

function updateDualPriceDisplay(total) {
    const select = document.getElementById('currencySelect');
    const option = select.options[select.selectedIndex];
    const isBase = option.dataset.isBase === '1';

    if (isBase) return;

    const rate = parseFloat(document.getElementById('exchangeRateInput').value) || 1;
    const symbol = option.dataset.symbol || '';
    const vndTotal = Math.round(total * rate);

    document.getElementById('dualPriceDisplay').innerHTML = `
        <span class="font-bold">${symbol}${total.toLocaleString('vi-VN')}</span> 
        <span class="mx-1">×</span> 
        <span>${rate.toLocaleString('vi-VN')}</span> 
        <span class="mx-1">=</span> 
        <span class="font-bold text-blue-900">${vndTotal.toLocaleString('vi-VN')} ₫</span>
    `;
}

// Gọi onCurrencyChange khi load trang để set trạng thái ban đầu
document.addEventListener('DOMContentLoaded', function() {
    onCurrencyChange();
});

document.getElementById('addItem').addEventListener('click', function() {
    const container = document.getElementById('itemsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'item-row grid grid-cols-12 gap-3 items-end p-3 bg-gray-50 rounded-lg border border-gray-200 relative';
    newRow.innerHTML = `
        <div class="col-span-4 relative product-autocomplete">
            <label class="block text-xs font-medium text-gray-600 mb-1">Sản phẩm</label>
            <input type="text" name="items[${itemIndex}][product_name]" class="product-name-input w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" autocomplete="off" placeholder="Nhập tên sản phẩm...">
            <input type="hidden" name="items[${itemIndex}][product_id]" class="product-id">
            <ul class="absolute z-50 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto hidden suggestions-list top-full left-0 mt-1"></ul>
        </div>
        <div class="col-span-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">Số lượng</label>
            <input type="number" name="items[${itemIndex}][quantity]" value="1" min="1" required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-qty" onchange="calculateRow(this)">
        </div>
        <div class="col-span-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">Đơn giá</label>
            <input type="number" name="items[${itemIndex}][unit_price]" min="0" required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-price" onchange="calculateRow(this)">
        </div>
        <div class="col-span-3">
            <label class="block text-xs font-medium text-gray-600 mb-1">Thành tiền</label>
            <input type="text" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded bg-gray-100 item-total" readonly value="0">
        </div>
        <div class="col-span-1 flex justify-center">
            <button type="button" class="remove-item w-8 h-8 bg-red-100 text-red-600 rounded hover:bg-red-200"><i class="fas fa-trash"></i></button>
        </div>
    `;
    container.appendChild(newRow);
    setupProductAutocomplete(newRow);
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
@endpush
@endsection
