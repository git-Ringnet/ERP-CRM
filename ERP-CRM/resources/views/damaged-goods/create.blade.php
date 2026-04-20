@extends('layouts.app')

@section('title', 'Tạo báo cáo hư hỏng')
@section('page-title', 'Tạo Báo Cáo Hàng Hư Hỏng / Thanh Lý')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: 38px !important;
            border-color: #d1d5db !important;
            border-radius: 0.5rem !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px !important;
            padding-left: 12px !important;
            color: #374151 !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }
        .select2-dropdown {
            border-color: #d1d5db !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
            z-index: 9999;
        }
        .select2-results__option {
            padding: 8px 12px !important;
            font-size: 0.875rem !important;
            word-wrap: break-word !important;
            white-space: normal !important;
        }
        /* Fix overflow for very long names */
        .select2-container {
            max-width: 100% !important;
        }
    </style>
@endpush

@section('content')
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Thông tin báo cáo</h2>
            <a href="{{ route('damaged-goods.index') }}" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
        </div>

        <form action="{{ route('damaged-goods.store') }}" method="POST" class="p-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Loại <span
                            class="text-red-500">*</span></label>
                    <select name="type" id="type"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('type') border-red-500 @enderror"
                        required>
                        <option value="">-- Chọn loại --</option>
                        <option value="damaged" {{ old('type') == 'damaged' ? 'selected' : '' }}>Hàng hư hỏng</option>
                        <option value="liquidation" {{ old('type') == 'liquidation' ? 'selected' : '' }}>Thanh lý</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm <span
                            class="text-red-500">*</span></label>
                    <select name="product_id" id="product_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('product_id') border-red-500 @enderror"
                        required>
                        <option value="">-- Chọn sản phẩm --</option>
                    </select>
                    @error('product_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="warehouse_id" class="block text-sm font-medium text-gray-700 mb-1">Kho hàng <span
                            class="text-red-500">*</span> <span id="warehouse_stock_info"
                            class="text-blue-600 ml-2 font-normal"></span></label>
                    <select name="warehouse_id" id="warehouse_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('warehouse_id') border-red-500 @enderror"
                        required onchange="loadProductItems()">
                        <option value="">-- Chọn kho hàng --</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('warehouse_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div id="product_item_container" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Chi tiết (Serial/Mã nhập)</label>
                    <div class="border border-gray-300 rounded-lg p-2 max-h-48 overflow-y-auto bg-gray-50" id="item_list">
                        <!-- Checkboxes will be injected here -->
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Chọn nhiều serial nếu cần. Số lượng sẽ tự động cập nhật theo số
                        serial đã chọn.</p>
                </div>

                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Số lượng <span
                            class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="quantity" id="quantity" value="{{ old('quantity') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('quantity') border-red-500 @enderror"
                        required>
                    @error('quantity')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="original_value" class="block text-sm font-medium text-gray-700 mb-1">Giá trị gốc <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="original_value" id="original_value" value="{{ old('original_value') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('original_value') border-red-500 @enderror"
                        required>
                    @error('original_value')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="recovery_value" class="block text-sm font-medium text-gray-700 mb-1">Giá trị thu hồi <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="recovery_value" id="recovery_value" value="{{ old('recovery_value', 0) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('recovery_value') border-red-500 @enderror"
                        required>
                    @error('recovery_value')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="discovery_date" class="block text-sm font-medium text-gray-700 mb-1">Ngày phát hiện <span
                            class="text-red-500">*</span></label>
                    <input type="date" name="discovery_date" id="discovery_date"
                        value="{{ old('discovery_date', date('Y-m-d')) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('discovery_date') border-red-500 @enderror"
                        required>
                    @error('discovery_date')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="discovered_by" class="block text-sm font-medium text-gray-700 mb-1">Người phát hiện <span
                            class="text-red-500">*</span></label>
                    <select name="discovered_by" id="discovered_by"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('discovered_by') border-red-500 @enderror"
                        required>
                        <option value="">-- Chọn người phát hiện --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('discovered_by') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('discovered_by')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">Lý do <span
                            class="text-red-500">*</span></label>
                    <textarea name="reason" id="reason" rows="3"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('reason') border-red-500 @enderror"
                        required>{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="solution" class="block text-sm font-medium text-gray-700 mb-1">Giải pháp xử lý</label>
                    <textarea name="solution" id="solution" rows="3"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('solution') border-red-500 @enderror">{{ old('solution') }}</textarea>
                    @error('solution')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="note" class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                    <textarea name="note" id="note" rows="2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('note') border-red-500 @enderror">{{ old('note') }}</textarea>
                    @error('note')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex gap-2">
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-sm">
                    <i class="fas fa-save mr-1"></i> Lưu
                </button>
                <a href="{{ route('damaged-goods.index') }}"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
                    <i class="fas fa-times mr-1"></i> Hủy
                </a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            const $productSelect = $('#product_id');
            const $warehouseSelect = $('#warehouse_id');
            
            $productSelect.select2({
                placeholder: "-- Chọn sản phẩm --",
                allowClear: true,
                width: '100%',
                ajax: {
                    url: "{{ route('products.ajax-search') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return {
                            results: data.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.name + ' (' + item.code + ')'
                                };
                            })
                        };
                    },
                    cache: true
                }
            });

            // Trigger load on any change
            $productSelect.on('change select2:select select2:unselect select2:clear', function() {
                loadProductItems();
            });

            $warehouseSelect.on('change', function() {
                loadProductItems();
            });

            // Initial load
            loadProductItems();
        });

        function loadProductItems() {
            const productId = $('#product_id').val();
            const warehouseId = $('#warehouse_id').val();
            const $container = $('#product_item_container');
            const $itemList = $('#item_list');
            const $quantityInput = $('#quantity');
            const $stockInfo = $('#warehouse_stock_info');

            console.log('Loading items for Product:', productId, 'Warehouse:', warehouseId);

            if (!productId || !warehouseId) {
                $container.addClass('hidden');
                return;
            }

            // Show loading state
            $itemList.html('<div class="p-2 text-sm text-gray-500"><i class="fas fa-spinner fa-spin mr-1"></i> Đang tải danh sách...</div>');
            $container.removeClass('hidden');

            $.get("{{ route('damaged-goods.items') }}", {
                product_id: productId,
                warehouse_id: warehouseId
            }, function(data) {
                console.log('Data received:', data);
                $itemList.empty();
                
                const items = data.items || [];
                const totalStock = data.total_stock || 0;

                if ($stockInfo.length) {
                    $stockInfo.text(`(Tồn: ${totalStock})`);
                    $stockInfo.attr('class', totalStock > 0 ? 'text-blue-600 ml-2 font-normal' : 'text-red-500 ml-2 font-normal');
                }

                if (items.length > 0) {
                    items.forEach(item => {
                        const div = `
                            <div class="flex items-center space-x-2 p-1 hover:bg-gray-100 rounded">
                                <input type="checkbox" name="product_item_ids[]" value="${item.id}"
                                    class="item-checkbox rounded border-gray-300 text-primary focus:ring-primary"
                                    onchange="updateQuantity()">
                                <span class="text-sm text-gray-700">${item.sku} (SL: ${item.quantity})</span>
                            </div>`;
                        $itemList.append(div);
                    });
                } else {
                    $itemList.html('<span class="text-sm text-gray-500 p-2">Không có sản phẩm này trong kho đã chọn.</span>');
                }
                $container.removeClass('hidden');
                updateQuantity();
            }).fail(function(xhr) {
                console.error('Error loading items:', xhr);
                $itemList.html('<span class="text-sm text-red-500 p-2">Lỗi khi tải danh sách sản phẩm.</span>');
            });
        }

        function updateQuantity() {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            const quantityInput = document.getElementById('quantity');

            if (checkboxes.length > 0) {
                quantityInput.value = checkboxes.length;
                quantityInput.readOnly = true;
                quantityInput.classList.add('bg-gray-100');
            } else {
                quantityInput.readOnly = false;
                quantityInput.classList.remove('bg-gray-100');
                quantityInput.value = ''; // Clear quantity if no items are selected
            }
        }

        // Currency Formatting
        const currencyInputs = ['original_value', 'recovery_value'];

        function formatCurrency(input) {
            // Keep only digits
            let value = input.value.replace(/\D/g, '');
            if (value === '') return;
            // Format with commas: 1,000,000
            input.value = new Intl.NumberFormat('en-US').format(value);
        }

        currencyInputs.forEach(id => {
            const input = document.getElementById(id);

            // Format on load if value exists
            if (input.value) formatCurrency(input);

            input.addEventListener('input', function (e) {
                // Save cursor position logic is tricky with changing separators, 
                // simplifying to just format for now as 'en-US' usually adds length.
                // But let's keep it simple: just format.
                formatCurrency(this);
            });

            // Clean before form submit
            input.closest('form').addEventListener('submit', function () {
                // Remove commas to get raw number
                input.value = input.value.replace(/,/g, '');
            });
        });
    </script>
@endpush