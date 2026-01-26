@extends('layouts.app')

@section('title', 'Nhập báo giá NCC')
@section('page-title', 'Nhập báo giá từ nhà cung cấp')

@section('content')
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-file-invoice-dollar text-green-500 mr-2"></i>Thông tin báo giá NCC
            </h2>
            <a href="{{ route('supplier-quotations.index') }}" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
        </div>

        <form action="{{ route('supplier-quotations.store') }}" method="POST" class="p-4">
            @csrf

            @if($selectedRequest)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p class="text-blue-800"><i class="fas fa-info-circle mr-2"></i> Nhập báo giá cho yêu cầu:
                        <strong>{{ $selectedRequest->code }}</strong> - {{ $selectedRequest->title }}</p>
                    <input type="hidden" name="purchase_request_id" value="{{ $selectedRequest->id }}">
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mã báo giá <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code', $code) }}" required readonly
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nhà cung cấp <span
                            class="text-red-500">*</span></label>
                    <select name="supplier_id" id="supplierSelect" required
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                        <option value="">Tìm và chọn nhà cung cấp...</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" data-email="{{ $supplier->email }}"
                                data-phone="{{ $supplier->phone }}">{{ $supplier->code }} - {{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if(!$selectedRequest)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Từ yêu cầu báo giá</label>
                        <select name="purchase_request_id" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                            <option value="">-- Không liên kết --</option>
                            @foreach($purchaseRequests as $request)
                                <option value="{{ $request->id }}">{{ $request->code }} - {{ $request->title }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ngày báo giá <span
                            class="text-red-500">*</span></label>
                    <input type="date" name="quotation_date" value="{{ old('quotation_date', now()->format('Y-m-d')) }}"
                        required class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hiệu lực đến <span
                            class="text-red-500">*</span></label>
                    <input type="date" name="valid_until"
                        value="{{ old('valid_until', now()->addDays(30)->format('Y-m-d')) }}" required
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Thời gian giao (ngày)</label>
                    <input type="number" name="delivery_days" value="{{ old('delivery_days', 7) }}" min="1"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bảo hành</label>
                    <input type="text" name="warranty" value="{{ old('warranty') }}" placeholder="VD: 12 tháng"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                </div>
            </div>

            <!-- Danh sách sản phẩm -->
            <div class="border-t border-gray-200 pt-4 mb-6">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-lg font-semibold text-gray-900">Chi tiết sản phẩm</h3>
                    <button type="button" id="addItem"
                        class="px-4 py-2 text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                        <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
                    </button>
                </div>
                <div id="itemsContainer" class="space-y-3">
                    @if($selectedRequest && $selectedRequest->items->count() > 0)
                        @foreach($selectedRequest->items as $index => $item)
                            <div class="item-row grid grid-cols-12 gap-3 items-end p-3 bg-gray-50 rounded-lg border border-gray-200 relative">
                                <div class="col-span-4 relative product-autocomplete">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Sản phẩm</label>
                                    <input type="text" name="items[{{ $index }}][product_name]" value="{{ $item->product_name }}"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 product-name-input place-holder-gray-400" autocomplete="off" placeholder="Nhập tên sản phẩm...">
                                    <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id ?? '' }}" class="product-id">
                                    <ul class="absolute z-50 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto hidden suggestions-list top-full left-0 mt-1"></ul>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Số lượng</label>
                                    <input type="number" name="items[{{ $index }}][quantity]" value="{{ $item->quantity }}" min="1"
                                        required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-qty"
                                        onchange="calculateRow(this)">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Đơn giá</label>
                                    <input type="number" name="items[{{ $index }}][unit_price]" min="0" required placeholder="0"
                                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-price"
                                        onchange="calculateRow(this)">
                                </div>
                                <div class="col-span-3">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Thành tiền</label>
                                    <input type="text"
                                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded bg-gray-100 item-total"
                                        readonly value="0">
                                </div>
                                <div class="col-span-1 flex justify-center">
                                    <button type="button"
                                        class="remove-item w-8 h-8 bg-red-100 text-red-600 rounded hover:bg-red-200" {{ $loop->count == 1 ? 'style=display:none' : '' }}>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="item-row grid grid-cols-12 gap-3 items-end p-3 bg-gray-50 rounded-lg border border-gray-200 relative">
                            <div class="col-span-4 relative product-autocomplete">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Sản phẩm</label>
                                <input type="text" name="items[0][product_name]" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 product-name-input" autocomplete="off" placeholder="Nhập tên sản phẩm...">
                                <input type="hidden" name="items[0][product_id]" class="product-id">
                                <ul class="absolute z-50 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto hidden suggestions-list top-full left-0 mt-1"></ul>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Số lượng</label>
                                <input type="number" name="items[0][quantity]" value="1" min="1" required
                                    class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-qty"
                                    onchange="calculateRow(this)">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Đơn giá</label>
                                <input type="number" name="items[0][unit_price]" min="0" required placeholder="0"
                                    class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-price"
                                    onchange="calculateRow(this)">
                            </div>
                            <div class="col-span-3">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Thành tiền</label>
                                <input type="text"
                                    class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded bg-gray-100 item-total"
                                    readonly value="0">
                            </div>
                            <div class="col-span-1 flex justify-center">
                                <button type="button"
                                    class="remove-item w-8 h-8 bg-red-100 text-red-600 rounded hover:bg-red-200"
                                    style="display:none;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Điều khoản thanh toán</label>
                    <input type="text" name="payment_terms" value="{{ old('payment_terms') }}" placeholder="VD: Net 30"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 mb-1 mt-3">Ghi chú</label>
                    <textarea name="note" rows="3"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">{{ old('note') }}</textarea>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600 text-sm">Tổng tiền hàng:</span>
                        <span id="subtotal" class="font-medium">0đ</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 text-sm">Chiết khấu (%):</span>
                        <input type="number" name="discount_percent" value="0" min="0" max="100"
                            class="w-20 px-2 py-1 text-sm border border-gray-300 rounded text-right"
                            onchange="calculateTotal()">
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 text-sm">Phí vận chuyển:</span>
                        <input type="number" name="shipping_cost" value="0" min="0"
                            class="w-32 px-2 py-1 text-sm border border-gray-300 rounded text-right"
                            onchange="calculateTotal()">
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 text-sm">VAT (%):</span>
                        <input type="number" name="vat_percent" value="10" min="0"
                            class="w-20 px-2 py-1 text-sm border border-gray-300 rounded text-right"
                            onchange="calculateTotal()">
                    </div>
                    <div class="flex justify-between pt-3 border-t">
                        <span class="text-lg font-semibold">Tổng cộng:</span>
                        <span id="total" class="text-lg font-bold text-blue-600">0đ</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('supplier-quotations.index') }}"
                    class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                    <i class="fas fa-times mr-1"></i> Hủy
                </a>
                <button type="submit" class="px-4 py-2 text-sm text-white bg-blue-500 rounded-lg hover:bg-blue-600">
                    <i class="fas fa-save mr-1"></i> Lưu báo giá
                </button>
            </div>
        </form>
    </div>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
        <style>
            .ts-wrapper.single .ts-control {
                padding: 8px 12px;
                border-radius: 0.5rem;
                border-color: #d1d5db;
                min-height: 42px;
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

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
        <script>
            // TomSelect for Supplier only
            document.addEventListener('DOMContentLoaded', function () {
                new TomSelect('#supplierSelect', {
                    placeholder: 'Tìm và chọn nhà cung cấp...',
                    allowEmptyOption: true,
                    maxOptions: 100
                });
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
                        // Show recent or top products if empty? Or show nothing?
                        // User request: "click vào input cho hiện dropdown"
                        // So show all (sliced)
                        renderSuggestions(products.slice(0, 20));
                    } else {
                        // Trigger search logic
                         this.dispatchEvent(new Event('input'));
                    }
                });

                input.addEventListener('blur', function() {
                    // Delay hiding to allow click
                   setTimeout(() => suggestions.classList.add('hidden'), 200);
                });
            }

            // Init existing rows
            document.querySelectorAll('.item-row').forEach(row => setupProductAutocomplete(row));

            let itemIndex = {{ $selectedRequest ? $selectedRequest->items->count() : 1 }};

            function calculateRow(input) {
                const row = input.closest('.item-row');
                const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
                const price = parseFloat(row.querySelector('.item-price').value) || 0;
                row.querySelector('.item-total').value = (qty * price).toLocaleString('vi-VN');
                calculateTotal();
            }

            function calculateTotal() {
                let subtotal = 0;
                document.querySelectorAll('.item-row').forEach(row => {
                    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
                    const price = parseFloat(row.querySelector('.item-price').value) || 0;
                    subtotal += qty * price;
                });

                const discount = parseFloat(document.querySelector('[name="discount_percent"]').value) || 0;
                const shipping = parseFloat(document.querySelector('[name="shipping_cost"]').value) || 0;
                const vat = parseFloat(document.querySelector('[name="vat_percent"]').value) || 0;

                const discountAmount = subtotal * (discount / 100);
                const afterDiscount = subtotal - discountAmount;
                const beforeVat = afterDiscount + shipping;
                const vatAmount = beforeVat * (vat / 100);
                const total = beforeVat + vatAmount;

                document.getElementById('subtotal').textContent = subtotal.toLocaleString('vi-VN') + 'đ';
                document.getElementById('total').textContent = Math.round(total).toLocaleString('vi-VN') + 'đ';
            }

            document.getElementById('addItem').addEventListener('click', function () {
                const container = document.getElementById('itemsContainer');
                const newRow = document.createElement('div');
                newRow.className = 'item-row grid grid-cols-12 gap-3 items-end p-3 bg-gray-50 rounded-lg border border-gray-200 relative';
                newRow.innerHTML = `
                <div class="col-span-4 relative product-autocomplete">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Sản phẩm</label>
                    <input type="text" name="items[${itemIndex}][product_name]" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 product-name-input" autocomplete="off" placeholder="Nhập tên sản phẩm...">
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

            document.getElementById('itemsContainer').addEventListener('click', function (e) {
                if (e.target.closest('.remove-item')) {
                    e.target.closest('.item-row').remove();
                    updateRemoveButtons();
                    calculateTotal();
                }
            });

            function updateRemoveButtons() {
                const rows = document.querySelectorAll('.item-row');
                rows.forEach(row => {
                    row.querySelector('.remove-item').style.display = rows.length > 1 ? 'block' : 'none';
                });
            }
        </script>
    @endpush
@endsection