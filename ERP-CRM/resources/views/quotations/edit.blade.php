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

            <!-- Products -->
            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold">Chi tiết sản phẩm</h3>
                <button type="button" onclick="addProductRow()"
                    class="inline-flex items-center px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Thêm sản phẩm
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full" id="productsTable">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-600 w-[40%]">Sản phẩm</th>
                            <th class="px-4 py-2 text-center text-sm font-medium text-gray-600 w-[15%]">Số lượng</th>
                            <th class="px-4 py-2 text-right text-sm font-medium text-gray-600 w-[15%]">Đơn giá</th>
                            <th class="px-4 py-2 text-right text-sm font-medium text-gray-600 w-[15%]">Thành tiền</th>
                            <th class="px-4 py-2 w-[5%]"></th>
                        </tr>
                    </thead>
                    <tbody id="productRows">
                        @foreach($quotation->items as $index => $item)
                            <tr class="product-row border-b">
                                <td class="px-4 py-2">
                                    <select name="products[{{ $index }}][product_id]" required
                                        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 product-select">
                                        <option value="">Chọn sản phẩm</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" data-price="{{ $product->price }}" {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                                {{ $product->name }} ({{ $product->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-2">
                                    <input type="number" name="products[{{ $index }}][quantity]" value="{{ $item->quantity }}"
                                        min="1" required onchange="calculateRowTotal({{ $index }})"
                                        class="w-full border border-gray-300 rounded px-3 py-2 text-center">
                                </td>
                                <td class="px-4 py-2">
                                    <input type="text" name="products[{{ $index }}][price]"
                                        value="{{ number_format($item->price, 0, '.', ',') }}" required
                                        oninput="formatCurrency(this)" onchange="calculateRowTotal({{ $index }})"
                                        class="w-full border border-gray-300 rounded px-3 py-2 text-right price-input">
                                </td>
                                <td class="px-4 py-2">
                                    <input type="text" readonly
                                        class="w-full border border-gray-300 rounded px-3 py-2 text-right bg-gray-50 row-total"
                                        value="{{ number_format($item->total, 0, '.', ',') }}">
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <button type="button" onclick="removeProductRow(this)"
                                        class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
                            <span id="subtotal" class="font-medium">0 đ</span>
                        </div>
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-gray-600">Chiết khấu (%):</span>
                            <div class="flex items-center gap-2">
                                <span id="discountAmount" class="text-gray-500 text-sm">0 đ</span>
                                <input type="number" name="discount" id="discount"
                                    value="{{ old('discount', (float)$quotation->discount) }}" min="0" max="100"
                                    onchange="calculateTotal()"
                                    class="w-20 border border-gray-300 rounded px-2 py-1 text-right">
                            </div>
                        </div>
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-gray-600">VAT (%):</span>
                            <div class="flex items-center gap-2">
                                <span id="vatAmount" class="text-gray-500 text-sm">0 đ</span>
                                <input type="number" name="vat" id="vat" value="{{ old('vat', (float)$quotation->vat) }}" min="0"
                                    onchange="calculateTotal()"
                                    class="w-20 border border-gray-300 rounded px-2 py-1 text-right">
                            </div>
                        </div>
                        <hr class="my-3">
                        <div class="flex justify-between">
                            <span class="text-lg font-semibold">Tổng cộng:</span>
                            <span id="total" class="text-lg font-bold text-blue-600">0 đ</span>
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
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        let rowIndex = {{ count($quotation->items) }};
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
                placeholder: "Chọn sản phẩm",
                allowClear: true,
                width: '100%'
            }).on('change', function () {
                // Trigger the updatePrice logic
                const name = $(this).attr('name');
                const match = name.match(/products\[(\d+)\]/);
                if (match) {
                    updatePrice(this, match[1]);
                }
            });
        }

        function formatCurrency(input) {
            // Remove non-numeric chars
            let value = input.value.replace(/\D/g, '');
            if (value === '') {
                input.value = '';
                return;
            }
            // Format with commas
            input.value = new Intl.NumberFormat('en-US').format(parseInt(value));
        }

        function unformatNumber(str) {
            return parseFloat(str.replace(/,/g, '')) || 0;
        }

        function addProductRow() {
            const tbody = document.getElementById('productRows');
            const row = document.createElement('tr');
            row.className = 'product-row border-b';
            row.innerHTML = `
                    <td class="px-4 py-2">
                        <select name="products[${rowIndex}][product_id]" required
                            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 product-select">
                            <option value="">Chọn sản phẩm</option>
                            ${products.map(p => `<option value="${p.id}" data-price="${p.price}">${p.name} (${p.code})</option>`).join('')}
                        </select>
                    </td>
                    <td class="px-4 py-2">
                        <input type="number" name="products[${rowIndex}][quantity]" value="1" min="1" required
                            onchange="calculateRowTotal(${rowIndex})" class="w-full border border-gray-300 rounded px-3 py-2 text-center">
                    </td>
                    <td class="px-4 py-2">
                        <input type="text" name="products[${rowIndex}][price]" value="0" required
                            oninput="formatCurrency(this)" onchange="calculateRowTotal(${rowIndex})" 
                            class="w-full border border-gray-300 rounded px-3 py-2 text-right price-input">
                    </td>
                    <td class="px-4 py-2">
                        <input type="text" readonly class="w-full border border-gray-300 rounded px-3 py-2 text-right bg-gray-50 row-total" value="0">
                    </td>
                    <td class="px-4 py-2 text-center">
                        <button type="button" onclick="removeProductRow(this)" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
            tbody.appendChild(row);

            // Initialize select2 for the new select
            initializeSelect2($(row).find('.product-select'));

            rowIndex++;
        }

        function removeProductRow(btn) {
            const rows = document.querySelectorAll('.product-row');
            if (rows.length > 1) {
                // Destroy select2
                $(btn).closest('tr').find('.product-select').select2('destroy');
                btn.closest('tr').remove();
                calculateTotal();
            }
        }

        function updatePrice(select, index) {
            const option = select.options[select.selectedIndex];
            if (!option) return;

            const price = option.dataset.price || 0;
            document.querySelector(`input[name="products[${index}][price]"]`).value = formatNumber(price);
            calculateRowTotal(index);
        }

        function calculateRowTotal(index) {
            const qtyInput = document.querySelector(`input[name="products[${index}][quantity]"]`);
            const priceInput = document.querySelector(`input[name="products[${index}][price]"]`);

            if (!qtyInput || !priceInput) return;

            const qty = parseFloat(qtyInput.value) || 0;
            const price = unformatNumber(priceInput.value);
            const total = qty * price;

            const row = qtyInput.closest('tr');
            if (row) {
                row.querySelector('.row-total').value = formatNumber(total);
            }

            calculateTotal();
        }

        function calculateTotal() {
            let subtotal = 0;
            document.querySelectorAll('.product-row').forEach(row => {
                const totalInput = row.querySelector('.row-total');
                if (totalInput) {
                    subtotal += unformatNumber(totalInput.value);
                }
            });

            const discountInput = document.getElementById('discount');
            const vatInput = document.getElementById('vat');

            const discount = discountInput ? (parseFloat(discountInput.value) || 0) : 0;
            const vat = vatInput ? (parseFloat(vatInput.value) || 0) : 0;

            const discountAmount = subtotal * discount / 100;
            const afterDiscount = subtotal - discountAmount;
            const vatAmount = afterDiscount * vat / 100;
            const total = afterDiscount + vatAmount;

            const subtotalEl = document.getElementById('subtotal');
            const totalEl = document.getElementById('total');
            const discountAmountEl = document.getElementById('discountAmount');
            const vatAmountEl = document.getElementById('vatAmount');

            if (subtotalEl) subtotalEl.textContent = formatNumber(subtotal) + ' đ';
            if (totalEl) totalEl.textContent = formatNumber(total) + ' đ';
            if (discountAmountEl) discountAmountEl.textContent = formatNumber(discountAmount) + ' đ';
            if (vatAmountEl) vatAmountEl.textContent = formatNumber(vatAmount) + ' đ';
        }

        function formatNumber(num) {
            return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        // Initialize
        calculateTotal();
    </script>
@endpush