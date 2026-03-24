@extends('layouts.app')

@section('title', 'Tạo báo giá')
@section('page-title', 'Tạo báo giá mới')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: 42px !important;
            border-color: #d1d5db !important;
            border-radius: 0.5rem !important;
            padding-top: 5px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 30px !important;
        }
    </style>
@endpush

@section('content')
    <div class="bg-white rounded-lg shadow-sm">
        <form action="{{ route('quotations.store') }}" method="POST" id="quotationForm">
            @csrf

            <div class="p-4 sm:p-6 space-y-6">
                <!-- Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Mã báo giá <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="code" value="{{ old('code', $code) }}" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('code') border-red-500 @enderror">
                        @error('code')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Khách hàng <span class="text-red-500">*</span>
                        </label>
                        <select name="customer_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('customer_id') border-red-500 @enderror">
                            <option value="">Chọn khách hàng</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ (isset($prefill['customer_id']) && $prefill['customer_id'] == $customer->id) || old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} ({{ $customer->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Tiêu đề báo giá <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" value="{{ isset($prefill['title']) ? 'Báo giá cho ' . $prefill['title'] : old('title') }}" required
                        placeholder="VD: Báo giá cung cấp thiết bị văn phòng"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('title') border-red-500 @enderror">
                    @error('title')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Ngày tạo <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Hạn báo giá <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="valid_until"
                            value="{{ old('valid_until', date('Y-m-d', strtotime('+30 days'))) }}" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('valid_until') border-red-500 @enderror">
                        @error('valid_until')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Currency Selection -->
                <div class="border-t pt-4">
                    <h4 class="text-lg font-medium text-gray-900 mb-3">
                        <i class="fas fa-money-bill-wave text-green-500 mr-2"></i>Tiền tệ báo giá
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Loại tiền tệ</label>
                            <select name="currency_id" id="currencySelect" onchange="onCurrencyChange()"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->id }}"
                                        data-is-base="{{ $currency->is_base ? '1' : '0' }}"
                                        data-code="{{ $currency->code }}"
                                        data-symbol="{{ $currency->symbol }}"
                                        {{ old('currency_id', $baseCurrencyId) == $currency->id ? 'selected' : '' }}>
                                        {{ $currency->code }} - {{ $currency->name_vi }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div id="exchangeRateGroup" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Tỷ giá (1 ngoại tệ = ? VND)
                                <span id="rateSource" class="text-xs text-blue-500 ml-1"></span>
                            </label>
                            <input type="number" name="exchange_rate" id="exchangeRateInput" step="0.000001" min="0"
                                value="{{ old('exchange_rate', 1) }}"
                                onchange="calculateTotal()"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            <p class="text-xs text-gray-500 mt-1" id="rateHint">Tỷ giá sẽ tự động lấy từ Vietcombank</p>
                        </div>
                        <div id="dualPricePlaceholder" class="hidden">
                        <!-- Removed dualPriceGroup as per user request -->
                    </div>
                    </div>
                </div>

                <!-- Products Section -->
                <div class="border-t pt-4">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Chi tiết sản phẩm</h4>

                    <div id="productList" class="space-y-3">
                        <div class="product-item bg-gray-50 p-3 rounded-lg">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                <div class="md:col-span-5">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm <span
                                            class="text-red-500">*</span></label>
                                    <select name="products[0][product_id]" required
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary product-select">
                                        <option value="">Chọn sản phẩm</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng <span
                                            class="text-red-500">*</span></label>
                                    <input type="number" name="products[0][quantity]" min="1" value="1" required
                                        onchange="calculateRowTotal(0)"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary quantity-input">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Đơn giá (<span class="currency-symbol">₫</span>) <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" name="products[0][price]" value="0" required
                                        onchange="calculateRowTotal(0)"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary price-input">
                                    <small class="block text-xs text-gray-500 mt-1 base-price-reference"></small>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Thành tiền (<span class="currency-symbol">₫</span>)</label>
                                    <input type="text" readonly
                                        class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 row-total"
                                        value="0">
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

                <!-- Totals Section -->
                <div class="border-t pt-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Điều khoản thanh toán</label>
                                <textarea name="payment_terms" rows="2"
                                    placeholder="VD: Thanh toán 30% trước, 70% sau khi giao hàng"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">{{ old('payment_terms') }}</textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Thời gian giao hàng</label>
                                <input type="text" name="delivery_time" value="{{ old('delivery_time') }}"
                                    placeholder="VD: 7-10 ngày làm việc"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                                <textarea name="note" rows="2"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">{{ old('note') }}</textarea>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tổng tiền hàng:</span>
                                <span id="subtotal" class="font-medium">0 ₫</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Chiết khấu (%):</span>
                                <div class="flex items-center gap-2">
                                    <span id="discountAmount" class="text-gray-500 text-sm">0 đ</span>
                                    <input type="number" name="discount" id="discount" value="{{ old('discount', 0) }}"
                                        min="0" max="100" onchange="calculateTotal()"
                                        class="w-20 border rounded px-2 py-1 text-right">
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">VAT (%):</span>
                                <div class="flex items-center gap-2">
                                    <span id="vatAmount" class="text-gray-500 text-sm">0 đ</span>
                                    <input type="number" name="vat" id="vat" value="{{ old('vat', 10) }}" min="0"
                                        onchange="calculateTotal()" class="w-20 border rounded px-2 py-1 text-right">
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="flex justify-between items-center pt-2 border-t">
                                <span class="text-lg font-semibold">Tổng cộng:</span>
                                <div class="text-right">
                                    <span id="total" class="text-lg font-bold text-primary">0 ₫</span>
                                    <small id="totalVndReference" class="block text-xs text-gray-500 mt-1"></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="px-4 sm:px-6 py-4 bg-gray-50 border-t flex flex-col sm:flex-row gap-2 justify-end">
                <a href="{{ route('quotations.index') }}"
                    class="inline-flex items-center justify-center px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    <i class="fas fa-times mr-2"></i> Hủy
                </a>
                <button type="submit"
                    class="inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                    <i class="fas fa-save mr-2"></i> Lưu nháp
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        let productIndex = 1;
        const products = @json($products);

        $(document).ready(function () {
            // Initialize Select2 for Customer
            $('select[name="customer_id"]').select2({
                placeholder: "Chọn khách hàng",
                allowClear: true,
                width: '100%'
            });

            // Initialize Select2 for existing Products
            initializeSelect2($('.product-select'));

            // Form submit handler to unformat prices
            $('#quotationForm').on('submit', function () {
                $('.price-input').each(function () {
                    const unformatted = $(this).val().replace(/,/g, '');
                    $(this).val(unformatted);
                });
            });
        });

        function initializeSelect2(elements) {
            elements.select2({
                placeholder: "Tìm mã hoặc tên (Kho/Hãng)...",
                allowClear: true,
                width: '100%',
                ajax: {
                    url: '{{ route("quotations.search-catalog") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return { results: data };
                    },
                    cache: true
                },
                minimumInputLength: 0
            }).on('select2:select', function (e) {
                const data = e.params.data;
                const name = $(this).attr('name');
                const match = name.match(/products\[(\d+)\]/);
                if (match) {
                    const index = match[1];
                    updateProductData(index, data);
                }
            });
        }

        // Format money input (supports decimals for foreign currencies)
        function formatMoney(value) {
            if (value === undefined || value === null || value === '') return '';
            const select = document.getElementById('currencySelect');
            const isVnd = select ? (select.options[select.selectedIndex]?.dataset.isBase === '1') : true;
            const decimals = isVnd ? 0 : 2;
            const num = parseFloat(value.toString().replace(/[^0-9.]/g, ''));
            if (isNaN(num)) return '';
            return num.toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
        }

        function unformatMoney(value) {
            if (value === undefined || value === null || value === '') return 0;
            return parseFloat(value.toString().replace(/[^0-9.]/g, '')) || 0;
        }

        function updateProductData(index, data) {
            const row = document.querySelector(`input[name="products[${index}][quantity]"]`).closest('.product-item');
            const priceInput = row.querySelector('.price-input');
            const basePriceRef = row.querySelector('.base-price-reference');

            if (data.price) {
                const basePriceVnd = parseFloat(data.price);
                
                // Show base price reference
                if (basePriceRef) {
                    basePriceRef.textContent = `Giá gốc kho: ${formatMoney(basePriceVnd)} ₫`;
                }

                const currentRate = parseFloat(document.getElementById('exchangeRateInput').value) || 1;
                // DIVISION logic: Price = VND / Rate
                const priceInCurrency = basePriceVnd / currentRate;
                if (priceInput) priceInput.value = formatMoney(priceInCurrency);
            }
            calculateRowTotal(index);
        }

        function addProductRow() {
            const productList = document.getElementById('productList');
            const newRow = document.createElement('div');
            newRow.className = 'product-item bg-gray-50 p-3 rounded-lg';
            newRow.innerHTML = `
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                <div class="md:col-span-5">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm (<span class="currency-symbol">₫</span>)</label>
                                    <select name="products[${productIndex}][product_id]" required
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary product-select">
                                        <option value="">Chọn sản phẩm</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng</label>
                                    <input type="number" name="products[${productIndex}][quantity]" min="1" value="1" required
                                           onchange="calculateRowTotal(${productIndex})"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary quantity-input">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Đơn giá (<span class="currency-symbol">₫</span>)</label>
                                    <input type="text" name="products[${productIndex}][price]" value="0" required
                                           oninput="formatCurrency(this)" onchange="calculateRowTotal(${productIndex})"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary price-input">
                                    <small class="block text-xs text-gray-500 mt-1 base-price-reference"></small>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Thành tiền (<span class="currency-symbol">₫</span>)</label>
                                    <input type="text" readonly
                                           class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 row-total" value="0">
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

            // Initialize select2 for the new select
            initializeSelect2($(newRow).find('.product-select'));

            productIndex++;
        }

        function removeProductRow(btn) {
            const items = document.querySelectorAll('.product-item');
            if (items.length > 1) {
                // Destroy select2 before removing to prevent leaks (optional but good practice)
                $(btn).closest('.product-item').find('.product-select').select2('destroy');
                btn.closest('.product-item').remove();
                calculateTotal();
            }
        }



        function calculateRowTotal(index) {
            const qtyInput = document.querySelector(`input[name="products[${index}][quantity]"]`);
            const priceInput = document.querySelector(`input[name="products[${index}][price]"]`);
            if (!qtyInput || !priceInput) return;
            const qty = parseFloat(qtyInput.value) || 0;
            const price = unformatMoney(priceInput.value);
            const total = qty * price;
            const row = qtyInput.closest('.product-item');
            if (row) {
                row.querySelector('.row-total').value = formatMoney(total);
            }
            calculateTotal();
        }

        function calculateTotal() {
            const select = document.getElementById('currencySelect');
            const option = select.options[select.selectedIndex];
            const symbol = option.dataset.symbol || '';

            let subtotal = 0;
            document.querySelectorAll('.product-item').forEach(row => {
                const totalInput = row.querySelector('.row-total');
                if (totalInput) {
                    subtotal += unformatMoney(totalInput.value);
                }
            });

            const discountInput = document.getElementById('discount');
            const vatInput = document.getElementById('vat');
            const discount = discountInput ? (parseFloat(discountInput.value) || 0) : 0;
            const vat = vatInput ? (parseFloat(vatInput.value) || 0) : 0;

            const discountAmount = Math.round((subtotal * discount / 100) * 100) / 100;
            const afterDiscount = subtotal - discountAmount;
            const vatAmount = Math.round((afterDiscount * vat / 100) * 100) / 100;
            const total = Math.round((afterDiscount + vatAmount) * 100) / 100;

            const subtotalEl = document.getElementById('subtotal');
            const totalEl = document.getElementById('total');
            const discountAmountEl = document.getElementById('discountAmount');
            const vatAmountEl = document.getElementById('vatAmount');

            if (subtotalEl) subtotalEl.textContent = formatMoney(subtotal) + ' ' + symbol;
            if (totalEl) {
                totalEl.textContent = formatMoney(total) + ' ' + symbol;
                
                // Update VND reference for total
                const totalVndRef = document.getElementById('totalVndReference');
                if (totalVndRef) {
                    if (option.dataset.isBase === '1') {
                        totalVndRef.textContent = '';
                    } else {
                        const exchangeRate = parseFloat(document.getElementById('exchangeRateInput').value) || 1;
                        const vndValue = Math.round(total * exchangeRate);
                        totalVndRef.textContent = `= ${formatMoney(vndValue)} ₫`;
                    }
                }
            }
            if (discountAmountEl) discountAmountEl.textContent = formatMoney(discountAmount) + ' ' + symbol;
            if (vatAmountEl) vatAmountEl.textContent = formatMoney(vatAmount) + ' ' + symbol;

            // Update currency labels
            document.querySelectorAll('.currency-symbol').forEach(el => {
                el.textContent = symbol;
            });
        }

        // Initialize
        calculateTotal();

        // ─── Multi-Currency Functions ───
        const baseCurrencyId = {{ $baseCurrencyId ?? 'null' }};
        let currentExchangeRate = parseFloat(document.getElementById('exchangeRateInput').value) || 1;

        function onCurrencyChange() {
            const select = document.getElementById('currencySelect');
            const option = select.options[select.selectedIndex];
            const isBase = option.dataset.isBase === '1';
            
            const oldRate = currentExchangeRate;

            if (isBase) {
                document.getElementById('exchangeRateGroup').classList.add('hidden');
                document.getElementById('exchangeRateInput').value = 1;
                currentExchangeRate = 1;
            } else {
                document.getElementById('exchangeRateGroup').classList.remove('hidden');
                fetchExchangeRate(select.value).then(() => {
                    const newRate = parseFloat(document.getElementById('exchangeRateInput').value) || 1;
                    recalculateAllPrices(oldRate, newRate);
                    currentExchangeRate = newRate;
                });
                return;
            }
            
            recalculateAllPrices(oldRate, currentExchangeRate);
            calculateTotal();
        }

        function recalculateAllPrices(oldRate, newRate) {
            if (oldRate === newRate) return;
            document.querySelectorAll('.product-item').forEach(row => {
                const priceInput = row.querySelector('.price-input');
                if (priceInput && priceInput.value) {
                    const oldPrice = unformatMoney(priceInput.value);
                    const baseVnd = oldPrice * oldRate;
                    const newPrice = baseVnd / newRate;
                    priceInput.value = formatMoney(newPrice);
                    
                    // Trigger total row recalculation
                    const qty = parseFloat(row.querySelector('.quantity-input').value) || 0;
                    row.querySelector('.row-total').value = formatMoney(qty * newPrice);
                }
            });
            
            // Sync Row Totals and Overall Total
            document.querySelectorAll('.product-item').forEach((_, idx) => {
                calculateRowTotal(idx);
            });
            calculateTotal();
        }

        async function fetchExchangeRate(currencyId) {
            const dateInput = document.querySelector('input[name="date"]');
            const date = dateInput ? dateInput.value : new Date().toISOString().split('T')[0];
            try {
                const response = await fetch(`{{ route('api.exchange-rate') }}?currency_id=${currencyId}&date=${date}`);
                const data = await response.json();
                if (data.rate && !data.is_base) {
                    document.getElementById('exchangeRateInput').value = data.rate;
                    document.getElementById('rateSource').textContent = data.source === 'auto' ? '(Vietcombank)' : '(Thủ công)';
                    document.getElementById('rateHint').textContent = `Ngày: ${data.effective_date || date}`;
                    calculateTotal();
                } else if (!data.rate && !data.is_base) {
                    document.getElementById('rateHint').textContent = '⚠ Chưa có tỷ giá. Nhập thủ công.';
                }
            } catch (e) {
                console.error(e);
            }
        }

        function updateDualPriceDisplay(foreignTotal) {
            const select = document.getElementById('currencySelect');
            const option = select.options[select.selectedIndex];
            if (option.dataset.isBase === '1') return;
            const display = document.getElementById('dualPriceDisplay');
            if (!display) return;
            const rate = parseFloat(document.getElementById('exchangeRateInput').value) || 1;
            const vndTotal = Math.round(foreignTotal * rate);
            display.innerHTML = `<span class="font-semibold">${option.dataset.symbol}${formatNumber(foreignTotal)}</span> × ${formatNumber(rate)} = <span class="font-bold text-blue-900">${formatNumber(vndTotal)} ₫</span>`;
        }

        // Re-fetch rate when date changes
        document.querySelector('input[name="date"]')?.addEventListener('change', function() {
            const select = document.getElementById('currencySelect');
            const option = select.options[select.selectedIndex];
            if (option.dataset.isBase !== '1') fetchExchangeRate(select.value);
        });

        // Init on page load
        onCurrencyChange();
    </script>
@endpush