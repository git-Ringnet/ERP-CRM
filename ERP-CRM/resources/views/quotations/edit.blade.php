@extends('layouts.app')

@section('title', 'Sửa báo giá - ' . $quotation->code)
@section('page-title', 'Sửa báo giá: ' . $quotation->code)

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

        .suggestions-list::-webkit-scrollbar {
            width: 6px;
        }
        .suggestions-list::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 3px;
        }
    </style>
@endpush

@section('content')
    <div class="bg-white rounded-lg shadow-sm">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <span
                    class="px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">{{ $quotation->code }}</span>
                <span class="text-gray-500">{{ $quotation->customer->name ?? '' }}</span>
            </div>
            <a href="{{ route('quotations.show', $quotation) }}"
                class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Quay lại
            </a>
        </div>

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded m-4">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('quotations.update', $quotation) }}" method="POST" id="quotationForm">
            @csrf
            @method('PUT')
            <div class="p-6 border-b border-gray-200">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mã báo giá <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $quotation->code) }}" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('code') border-red-500 @enderror">
                        @error('code')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Khách hàng <span
                                class="text-red-500">*</span></label>
                        <select name="customer_id" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Chọn khách hàng</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id', $quotation->customer_id) == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} ({{ $customer->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề báo giá <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $quotation->title) }}" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ngày tạo <span
                                class="text-red-500">*</span></label>
                        <input type="date" name="date" value="{{ old('date', $quotation->date->format('Y-m-d')) }}" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hạn báo giá <span
                                class="text-red-500">*</span></label>
                        <input type="date" name="valid_until"
                            value="{{ old('valid_until', $quotation->valid_until->format('Y-m-d')) }}" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('valid_until') border-red-500 @enderror">
                        @error('valid_until')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Currency Section -->
            @php
                $isForeign = $quotation->currency && !$quotation->currency->is_base;
                $decimals = $isForeign ? ($quotation->currency->decimal_places ?? 2) : 0;
            @endphp
            <div class="p-6 border-b border-gray-200">
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
                                    {{ old('currency_id', $quotation->currency_id ?? $baseCurrencyId) == $currency->id ? 'selected' : '' }}>
                                    {{ $currency->code }} - {{ $currency->name_vi }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="exchangeRateGroup" class="{{ ($quotation->currency_id && $quotation->currency_id != $baseCurrencyId) ? '' : 'hidden' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tỷ giá (1 ngoại tệ = ? VND)
                            <span id="rateSource" class="text-xs text-blue-500 ml-1"></span>
                        </label>
                        <input type="number" name="exchange_rate" id="exchangeRateInput" step="0.000001" min="0"
                            value="{{ old('exchange_rate', $quotation->exchange_rate ? floatval($quotation->exchange_rate) : 1) }}"
                            onchange="calculateTotal()"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <p class="text-xs text-gray-500 mt-1" id="rateHint">Tỷ giá từ lúc tạo báo giá</p>
                    </div>
                    <div id="dualPricePlaceholder" class="hidden">
                        <!-- Removed dualPriceGroup as per user request -->
                    </div>
                </div>
            </div>

            <!-- Products -->
            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold">Chi tiết sản phẩm</h3>
                <button type="button" onclick="addProductRow()"
                    class="inline-flex items-center px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Thêm sản phẩm
                </button>
            </div>

            <div id="productList" class="p-4 space-y-3">
                @foreach($quotation->items as $index => $item)
                    <div class="product-item bg-gray-50 p-3 rounded-lg border border-gray-100 mb-3" data-index="{{ $index }}">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                            <div class="md:col-span-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm <span class="text-red-500">*</span></label>
                                <select name="products[{{ $index }}][product_id]" class="w-full product-select" data-placeholder="Tìm hoặc nhập tên sản phẩm...">
                                    @if($item->product_id)
                                        <option value="p-{{ $item->product_id }}" selected>[KHO] {{ $item->product_code }} - {{ $item->product_name }}</option>
                                    @else
                                        <option value="{{ $item->product_name }}" selected>{{ $item->product_name }}</option>
                                    @endif
                                </select>
                                <input type="hidden" name="products[{{ $index }}][product_name]" value="{{ $item->product_name }}" class="product-name-hidden">
                            </div>
                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">SL</label>
                                <input type="number" name="products[{{ $index }}][quantity]"
                                    value="{{ $item->quantity }}" min="1" required
                                    onchange="calculateRowTotal({{ $index }})"
                                    class="w-full border border-gray-300 rounded-lg px-2 py-2 focus:outline-none focus:ring-2 focus:ring-primary quantity-input">
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Đơn giá (<span class="currency-symbol">₫</span>)</label>
                                <input type="text" name="products[{{ $index }}][price]"
                                    value="{{ number_format($item->price, $decimals, '.', ',') }}" required
                                    onchange="calculateRowTotal({{ $index }})"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-right price-input">
                                <small class="block text-[10px] text-gray-400 mt-1 base-price-reference leading-none">
                                    @if($item->product)
                                        Giá gốc kho: {{ number_format($item->product->calculated_selling_price ?? $item->product->price, 0, '.', ',') }} ₫
                                    @endif
                                </small>
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Thành tiền (<span class="currency-symbol">₫</span>)</label>
                                <input type="text" readonly
                                    class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 text-right row-total"
                                    value="{{ number_format($item->total, $decimals, '.', ',') }}">
                            </div>
                            <div class="md:col-span-1 pt-7">
                                <button type="button" onclick="removeProductRow(this)"
                                    class="w-full h-[42px] flex items-center justify-center bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors"
                                    title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Totals -->
            <div class="p-6 border-b border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Điều khoản thanh toán</label>
                            <textarea name="payment_terms" rows="2"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('payment_terms', $quotation->payment_terms) }}</textarea>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Thời gian giao hàng</label>
                            <input type="text" name="delivery_time"
                                value="{{ old('delivery_time', $quotation->delivery_time) }}"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                            <textarea name="note" rows="2"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('note', $quotation->note) }}</textarea>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex justify-between mb-3">
                            <span class="text-gray-600">Tổng tiền hàng:</span>
                            <span id="subtotal" class="font-medium text-lg">0 ₫</span>
                        </div>
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-gray-600">Chiết khấu (%):</span>
                            <div class="flex items-center gap-2">
                                <span id="discountAmount" class="text-gray-500 text-sm">0 đ</span>
                                <input type="number" name="discount" id="discount"
                                    value="{{ old('discount', (float)$quotation->discount) }}" min="0" max="100"
                                    onchange="calculateTotal()"
                                    class="w-20 border border-gray-300 rounded px-2 py-1 text-right focus:ring-1 focus:ring-primary">
                            </div>
                        </div>
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-gray-600">VAT (%):</span>
                            <div class="flex items-center gap-2">
                                <span id="vatAmount" class="text-gray-500 text-sm">0 đ</span>
                                <input type="number" name="vat" id="vat" value="{{ old('vat', (float)$quotation->vat) }}" min="0"
                                    onchange="calculateTotal()"
                                    class="w-20 border border-gray-300 rounded px-2 py-1 text-right focus:ring-1 focus:ring-primary">
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="flex justify-between items-center pt-3 border-t">
                            <span class="text-lg font-semibold">Tổng cộng:</span>
                            <div class="text-right">
                                <span id="total" class="text-2xl font-bold text-primary">0 ₫</span>
                                <small id="totalVndReference" class="block text-sm text-gray-500 mt-1 text-right"></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-4 p-4">
                <a href="{{ route('quotations.show', $quotation) }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                    <i class="fas fa-times mr-2"></i> Hủy
                </a>
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                    <i class="fas fa-save mr-2"></i> Lưu thay đổi
                </button>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Select2 (only for customer) -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        let rowIndex = {{ count($quotation->items) }};

        $(document).ready(function () {
            // Initialize Select2 for Customer
            $('select[name="customer_id"]').select2({
                placeholder: "Chọn khách hàng",
                allowClear: true,
                width: '100%'
            });

            // Initialize product selects for existing rows
            $('.product-select').each(function() {
                initProductSelect($(this));
            });

            // Form submit handler to unformat prices
            $('#quotationForm').on('submit', function () {
                $('.price-input').each(function () {
                    const unformatted = $(this).val().replace(/,/g, '');
                    $(this).val(unformatted);
                });
            });

            calculateTotal();
        });

        function initProductSelect(element) {
            const row = element.closest('.product-item');
            
            element.select2({
                placeholder: "Tìm hoặc nhập tên sản phẩm...",
                allowClear: true,
                tags: true,
                width: '100%',
                ajax: {
                    url: "{{ route('quotations.search-catalog') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { search: params.term };
                    },
                    processResults: function (data) {
                        return {
                            results: data.map(item => ({
                                id: item.id,
                                text: item.text,
                                price: item.price,
                                unit: item.unit,
                                name: item.name
                            }))
                        };
                    },
                    cache: true,
                    error: function(xhr, status, error) {
                        console.error('Select2 AJAX error:', error);
                    }
                },
                createTag: function (params) {
                    const term = $.trim(params.term);
                    if (term === '') return null;
                    return { id: term, text: term, isNew: true };
                }
            }).on('select2:select', function (e) {
                const data = e.params.data;
                const hiddenName = row.find('.product-name-hidden');
                
                // If it's a tag or doesn't have our prefixes (p- or c-)
                const isManual = data.isNew || (typeof data.id === 'string' && !data.id.startsWith('p-') && !data.id.startsWith('c-'));
                
                if (isManual) {
                    hiddenName.val(data.text);
                    row.find('.base-price-reference').text('');
                } else {
                    hiddenName.val(data.name || data.text);
                    if (data.price) {
                        const basePriceVnd = parseFloat(data.price);
                        row.find('.base-price-reference').text(`Giá gốc: ${formatMoney(basePriceVnd)} ₫`);
                        
                        const rate = parseFloat($('#exchangeRateInput').val()) || 1;
                        row.find('.price-input').val(formatMoney(basePriceVnd / rate));
                    }
                }
                const qtyInput = row.find('.quantity-input');
                const nameAttr = qtyInput.attr('name');
                const match = nameAttr.match(/products\[(\d+)\]/);
                if (match) calculateRowTotal(match[1]);
            });
        }

        function formatMoney(value) {
            if (value === undefined || value === null || value === '') return '';
            const select = document.getElementById('currencySelect');
            const isVnd = select ? (select.options[select.selectedIndex]?.dataset.isBase === '1') : true;
            const decimals = isVnd ? 0 : 2;
            const num = parseFloat(value.toString().replace(/[^0-9.]/g, ''));
            if (isNaN(num)) return '';
            return num.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
        }

        function unformatMoney(value) {
            if (value === undefined || value === null || value === '') return 0;
            return parseFloat(value.toString().replace(/[^0-9.]/g, '')) || 0;
        }

        function addProductRow() {
            const productList = document.getElementById('productList');
            const newRow = document.createElement('div');
            newRow.className = 'product-item bg-gray-50 p-3 rounded-lg border border-gray-100';
            newRow.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-12 relative">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm <span class="text-red-500">*</span></label>
                        <select name="products[${rowIndex}][product_id]" class="w-full product-select" data-placeholder="Tìm hoặc nhập tên sản phẩm...">
                            <option value=""></option>
                        </select>
                        <input type="hidden" name="products[${rowIndex}][product_name]" class="product-name-hidden">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng</label>
                        <input type="number" name="products[${rowIndex}][quantity]" min="1" value="1" required
                               onchange="calculateRowTotal(${rowIndex})"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-center quantity-input">
                    </div>
                    <div class="md:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Đơn giá (<span class="currency-symbol">₫</span>)</label>
                        <input type="text" name="products[${rowIndex}][price]" value="0" required
                               onchange="calculateRowTotal(${rowIndex})"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-right price-input">
                        <small class="block text-xs text-gray-500 mt-1 base-price-reference"></small>
                    </div>
                    <div class="md:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Thành tiền (<span class="currency-symbol">₫</span>)</label>
                        <input type="text" readonly
                               class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 text-right row-total" value="0">
                    </div>
                    <div class="md:col-span-2 flex items-end">
                        <button type="button" onclick="removeProductRow(this)"
                                class="w-full px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors">
                            <i class="fas fa-trash mr-2"></i> Xóa
                        </button>
                    </div>
                </div>`;
            productList.appendChild(newRow);
            initProductSelect($(newRow).find('.product-select'));
            rowIndex++;
        }

        function removeProductRow(btn) {
            const items = document.querySelectorAll('.product-item');
            if (items.length > 1) {
                btn.closest('.product-item').remove();
                calculateTotal();
            }
        }

        function calculateRowTotal(index) {
            const row = $(`.quantity-input[name="products[${index}][quantity]"]`).closest('.product-item');
            const qty = parseFloat(row.find('.quantity-input').val()) || 0;
            const price = unformatMoney(row.find('.price-input').val());
            const total = qty * price;
            row.find('.row-total').val(formatMoney(total));
            calculateTotal();
        }

        function calculateTotal() {
            const option = $('#currencySelect option:selected');
            const symbol = option.data('symbol') || '';
            let subtotal = 0;
            $('.row-total').each(function() { subtotal += unformatMoney($(this).val()); });
            const discount = parseFloat($('#discount').val()) || 0;
            const vat = parseFloat($('#vat').val()) || 0;
            const discountAmount = subtotal * discount / 100;
            const afterDiscount = subtotal - discountAmount;
            const vatAmount = afterDiscount * vat / 100;
            const total = afterDiscount + vatAmount;
            $('#subtotal').text(formatMoney(subtotal) + ' ' + symbol);
            $('#discountAmount').text(formatMoney(discountAmount) + ' ' + symbol);
            $('#vatAmount').text(formatMoney(vatAmount) + ' ' + symbol);
            $('#total').text(formatMoney(total) + ' ' + symbol);
            const vndRef = $('#totalVndReference');
            if (option.data('is-base') === 1) { vndRef.text(''); } else {
                const rate = parseFloat($('#exchangeRateInput').val()) || 1;
                vndRef.text(`= ${formatMoney(total * rate)} ₫`);
            }
            $('.currency-symbol').text(symbol);
        }

        let currentExchangeRate = parseFloat($('#exchangeRateInput').val()) || 1;
        function onCurrencyChange() {
            const option = $('#currencySelect option:selected');
            if (option.data('is-base') === 1) {
                $('#exchangeRateGroup').addClass('hidden');
                $('#exchangeRateInput').val(1);
                updatePricesAfterRateChange(1);
            } else {
                $('#exchangeRateGroup').removeClass('hidden');
                fetchExchangeRate(option.val());
            }
        }

        async function fetchExchangeRate(currencyId) {
            const date = $('input[name="date"]').val();
            try {
                const response = await fetch(`{{ route('api.exchange-rate') }}?currency_id=${currencyId}&date=${date}`);
                const data = await response.json();
                if (data.rate) {
                    $('#exchangeRateInput').val(data.rate);
                    $('#rateSource').text(data.source === 'auto' ? '(Vietcombank)' : '(Thủ công)');
                    $('#rateHint').text(`Ngày: ${data.effective_date || date}`);
                    updatePricesAfterRateChange(data.rate);
                }
            } catch (e) { console.error(e); }
        }

        function updatePricesAfterRateChange(newRate) {
            const oldRate = currentExchangeRate;
            if (oldRate === newRate) return;
            $('.product-item').each(function() {
                const priceInput = $(this).find('.price-input');
                const oldPrice = unformatMoney(priceInput.val());
                const baseVnd = oldPrice * oldRate;
                const newPrice = baseVnd / newRate;
                priceInput.val(formatMoney(newPrice));
                
                const qtyStr = $(this).find('.quantity-input').val();
                const qty = parseFloat(qtyStr) || 0;
                $(this).find('.row-total').val(formatMoney(qty * newPrice));
            });
            currentExchangeRate = newRate;
            calculateTotal();
        }

        $('input[name="date"]').on('change', function() {
            const option = $('#currencySelect option:selected');
            if (option.data('is-base') !== 1) fetchExchangeRate(option.val());
        });
        $('#exchangeRateInput').on('change', function() { updatePricesAfterRateChange(parseFloat($(this).val()) || 1); });
    </script>
@endpush