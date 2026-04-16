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
                    @if(old('products'))
                        @foreach(old('products') as $index => $item)
                        <div class="product-item bg-gray-50 p-3 rounded-lg border border-gray-100 mb-4" data-index="{{ $index }}">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                                <div class="md:col-span-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm <span class="text-red-500">*</span></label>
                                    <select name="products[{{ $index }}][product_id]" class="w-full product-select" data-placeholder="Tìm hoặc nhập tên sản phẩm...">
                                        @if(isset($item['product_id']) && $item['product_id'])
                                            <option value="{{ $item['product_id'] }}" selected>{{ $item['product_name'] }}</option>
                                        @elseif($item['product_name'])
                                            <option value="{{ $item['product_name'] }}" selected>{{ $item['product_name'] }}</option>
                                        @endif
                                    </select>
                                    <input type="hidden" name="products[{{ $index }}][product_name]" value="{{ $item['product_name'] }}" class="product-name-hidden">
                                </div>
                                <div class="md:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">SL</label>
                                    <input type="number" name="products[{{ $index }}][quantity]"
                                           value="{{ $item['quantity'] }}" min="1" required
                                           onchange="calculateRowTotal({{ $index }})"
                                           class="w-full border border-gray-300 rounded-lg px-2 py-2 focus:outline-none focus:ring-2 focus:ring-primary quantity-input">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Đơn giá (<span class="currency-symbol">₫</span>)</label>
                                    <input type="text" name="products[{{ $index }}][price]"
                                           value="{{ $item['price'] }}" required
                                           onchange="calculateRowTotal({{ $index }})"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary price-input">
                                    <small class="block text-[10px] text-gray-400 mt-1 base-price-reference leading-none"></small>
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Thành tiền (<span class="currency-symbol">₫</span>)</label>
                                    <input type="text" readonly
                                           class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 row-total" value="0">
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
                    @else
                        <div class="product-item bg-gray-50 p-3 rounded-lg border border-gray-100" data-index="0">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                                <div class="md:col-span-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm <span class="text-red-500">*</span></label>
                                    <select name="products[0][product_id]" class="w-full product-select" data-placeholder="Tìm hoặc nhập tên sản phẩm...">
                                        <option value=""></option>
                                    </select>
                                    <input type="hidden" name="products[0][product_name]" class="product-name-hidden">
                                </div>
                                <div class="md:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">SL</label>
                                    <input type="number" name="products[0][quantity]" value="1" min="1" required
                                           onchange="calculateRowTotal(0)"
                                           class="w-full border border-gray-300 rounded-lg px-2 py-2 focus:outline-none focus:ring-2 focus:ring-primary quantity-input">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Đơn giá (<span class="currency-symbol">₫</span>)</label>
                                    <input type="text" name="products[0][price]" value="0" required
                                           onchange="calculateRowTotal(0)"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary price-input">
                                    <small class="block text-[10px] text-gray-400 mt-1 base-price-reference leading-none"></small>
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Thành tiền (<span class="currency-symbol">₫</span>)</label>
                                    <input type="text" readonly
                                           class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 row-total" value="0">
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
                    @endif
                    </div>

                    <button type="button" onclick="addProductRow()"
                        class="mt-3 inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="fas fa-plus mr-2"></i> Thêm hàng hóa/dịch vụ
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
                                <span id="subtotal" class="font-medium text-lg">0 ₫</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Chiết khấu (%):</span>
                                <div class="flex items-center gap-2">
                                    <span id="discountAmount" class="text-gray-500 text-sm">0 đ</span>
                                    <input type="number" name="discount" id="discount" value="{{ old('discount', 0) }}"
                                        min="0" max="100" onchange="calculateTotal()"
                                        class="w-20 border rounded px-2 py-1 text-right focus:ring-1 focus:ring-primary">
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">VAT (%):</span>
                                <div class="flex items-center gap-2">
                                    <span id="vatAmount" class="text-gray-500 text-sm">0 đ</span>
                                    <input type="number" name="vat" id="vat" value="{{ old('vat', 10) }}" min="0"
                                        onchange="calculateTotal()" class="w-20 border rounded px-2 py-1 text-right focus:ring-1 focus:ring-primary">
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="flex justify-between items-center pt-2 border-t">
                                <span class="text-lg font-semibold">Tổng cộng:</span>
                                <div class="text-right">
                                    <span id="total" class="text-2xl font-bold text-primary">0 ₫</span>
                                    <small id="totalVndReference" class="block text-sm text-gray-500 mt-1"></small>
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
    <!-- Select2 (only for customer) -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        let productIndex = 1;

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
                    return {
                        id: term,
                        text: term,
                        isNew: true
                    };
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
                const idxRef = qtyInput.attr('name').match(/products\[(\d+)\]/)[1];
                calculateRowTotal(idxRef);
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
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                    <div class="md:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm <span class="text-red-500">*</span></label>
                        <select name="products[${productIndex}][product_id]" class="w-full product-select" data-placeholder="Tìm hoặc nhập tên sản phẩm...">
                            <option value=""></option>
                        </select>
                        <input type="hidden" name="products[${productIndex}][product_name]" class="product-name-hidden">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">SL</label>
                        <input type="number" name="products[${productIndex}][quantity]" min="1" value="1" required
                               onchange="calculateRowTotal(${productIndex})"
                               class="w-full border border-gray-300 rounded-lg px-2 py-2 focus:outline-none focus:ring-2 focus:ring-primary quantity-input">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Đơn giá (<span class="currency-symbol">₫</span>)</label>
                        <input type="text" name="products[${productIndex}][price]" value="0" required
                               onchange="calculateRowTotal(${productIndex})"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary price-input">
                        <small class="block text-[10px] text-gray-400 mt-1 base-price-reference leading-none"></small>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Thành tiền (<span class="currency-symbol">₫</span>)</label>
                        <input type="text" readonly
                               class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 row-total" value="0">
                    </div>
                    <div class="md:col-span-1 pt-7">
                        <button type="button" onclick="removeProductRow(this)"
                                class="w-full h-[42px] flex items-center justify-center bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors"
                                title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>`;
            productList.appendChild(newRow);
            initProductSelect($(newRow).find('.product-select'));
            productIndex++;
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