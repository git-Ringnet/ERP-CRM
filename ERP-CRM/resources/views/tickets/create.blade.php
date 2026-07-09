@extends('layouts.app')

@section('title', 'Tạo yêu cầu mới')
@section('page-title', 'Tạo yêu cầu mới')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center gap-2">
            <a href="{{ route('tickets.index') }}"
                class="inline-flex items-center text-sm font-semibold text-gray-500 hover:text-gray-700">
                <i class="fas fa-chevron-left mr-1"></i> Quay lại danh sách
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-md font-bold text-gray-800">Thông tin phiếu yêu cầu</h3>
            </div>
            <form method="POST" action="{{ route('tickets.store') }}" id="ticketForm" class="p-6 space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Type Selection -->
                    <div>
                        <label for="type_select" class="block text-sm font-semibold text-gray-700 mb-1.5">Loại yêu cầu <span
                                class="text-red-500">*</span></label>
                        <select name="type" id="type_select" onchange="toggleTypeFields()"
                            class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                            <option value="preload">Đặt hàng preload (Chưa có EU)</option>
                            <option value="borrow">Mượn hàng</option>
                        </select>
                    </div>
                </div>

                <!-- Borrow target section (Only visible when borrow is selected) -->
                <div id="borrow_fields" class="hidden bg-gray-50 p-4 rounded-lg border border-gray-200 space-y-4">
                    <h4 class="text-sm font-bold text-gray-700"><i
                            class="fas fa-people-arrows text-primary mr-1.5"></i>Thông tin mượn hàng</h4>

                    <div class="product-search-container relative">
                        <label for="product_search" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Chọn
                            sản phẩm cần mượn <span class="text-red-500">*</span></label>
                        <input type="text" id="product_search" autocomplete="off"
                            value="{{ $preselectedProductCode ?? '' }}"
                            placeholder="Nhập part (mã) để tìm kiếm..."
                            class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                            oninput="searchProducts(this, 'product_select', 'product_search_results', loadProductHolders)">
                        <input type="hidden" id="product_select" value="{{ $preselectedProductId ?? '' }}">

                        <div id="product_search_results"
                            class="search-results-dropdown absolute z-50 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto hidden">
                        </div>
                    </div>

                    <!-- Holder Options Loading State -->
                    <div id="holders_loading" class="hidden text-sm text-gray-500 py-2">
                        <i class="fas fa-spinner fa-spin mr-1 text-primary"></i> Đang tải thông tin tồn kho và người giữ
                        hàng...
                    </div>

                    <!-- Holders Selection Container -->
                    <div id="holders_container" class="hidden space-y-2">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Mượn từ đâu/ai? <span
                                class="text-red-500">*</span></label>

                        <div id="holders_options" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <!-- Options generated dynamically -->
                        </div>
                    </div>

                    <!-- Serial Selection Container -->
                    <div id="serial_selection_container" class="hidden space-y-2 bg-white border border-gray-200 rounded-lg p-4">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-2"><i class="fas fa-barcode text-primary mr-1"></i>Chọn số Serial muốn mượn <span class="text-red-500">*</span></label>
                        <div id="serial_checkboxes" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                            <!-- Checkboxes generated dynamically -->
                        </div>
                    </div>

                    <div id="borrow_qty_container" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="borrow_qty" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Số
                                lượng mượn <span class="text-red-500">*</span></label>
                            <input type="number" id="borrow_qty" min="1"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                oninput="validateBorrowQty()">
                            <span id="max_qty_warning" class="text-xs text-red-500 font-medium hidden">Số lượng vượt quá tồn
                                kho khả dụng!</span>
                            <span id="self_borrow_warning" class="text-xs text-amber-600 font-bold hidden block mt-1"><i class="fas fa-exclamation-triangle"></i> Chú ý: Bạn đang chọn mượn hàng từ chính mình!</span>
                        </div>
                    </div>
                </div>

                <!-- Items section for Preload (Dynamic adding) -->
                <div id="preload_items" class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h4 class="text-sm font-bold text-gray-700"><i class="fas fa-list text-primary mr-1.5"></i>Danh sách
                            đặt hàng</h4>
                        <button type="button" onclick="addPreloadRow()"
                            class="px-3 py-1.5 bg-emerald-50 text-emerald-600 border border-emerald-200 text-xs font-bold rounded-lg hover:bg-emerald-100 transition-colors">
                            <i class="fas fa-plus mr-1"></i> Thêm sản phẩm
                        </button>
                    </div>

                    <div class="border border-gray-200 rounded-lg">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-semibold">
                                <tr class="divide-x divide-gray-100 border-b border-gray-200">
                                    <th class="px-4 py-2.5">Sản phẩm</th>
                                    <th class="px-4 py-2.5 w-32">Số lượng</th>
                                    <th class="px-4 py-2.5 w-16 text-center">Xóa</th>
                                </tr>
                            </thead>
                            <tbody id="preload_tbody" class="divide-y divide-gray-200">
                                <!-- Rows added dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Hidden input fields mapping dynamically structured items for form submission -->
                <div id="hidden_inputs"></div>

                <div>
                    <label for="note" class="block text-sm font-semibold text-gray-700 mb-1.5">Ghi chú lý do yêu cầu</label>
                    <textarea name="note" id="note" rows="3" placeholder="Nhập lý do đặt hàng hoặc mượn hàng..."
                        class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"></textarea>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                    <a href="{{ route('tickets.index') }}"
                        class="px-4 py-2 border border-gray-200 text-gray-600 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors">
                        Hủy bỏ
                    </a>
                    <button type="button" onclick="submitTicketForm()"
                        class="px-4 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/90 transition-colors shadow-sm">
                        Gửi yêu cầu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const currentUserId = {{ auth()->id() }};
        const currentUserName = "{{ auth()->user()->name }}";
        let preloadRowCount = 0;

        document.addEventListener('DOMContentLoaded', function() {
            const preselectedId = "{{ $preselectedProductId ?? '' }}";
            if (preselectedId) {
                // Switch default request type to 'borrow' and load holders
                document.getElementById('type_select').value = 'borrow';
                toggleTypeFields();
                loadProductHolders();
            }
        });

        function toggleTypeFields() {
            const type = document.getElementById('type_select').value;
            const borrowFields = document.getElementById('borrow_fields');
            const preloadItems = document.getElementById('preload_items');

            if (type === 'borrow') {
                borrowFields.classList.remove('hidden');
                preloadItems.classList.add('hidden');
            } else {
                borrowFields.classList.add('hidden');
                preloadItems.classList.remove('hidden');
            }
        }

        // --- Search products autocomplete logic ---
        function searchProducts(input, hiddenId, resultsId, onSelectCallback = null) {
            const query = input.value.trim();
            const resultsDiv = document.getElementById(resultsId);
            const hiddenInput = document.getElementById(hiddenId);

            hiddenInput.value = '';

            if (query.length < 1) {
                resultsDiv.classList.add('hidden');
                resultsDiv.innerHTML = '';
                if (onSelectCallback) onSelectCallback();
                return;
            }

            fetch(`/tickets/search-products?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(products => {
                    resultsDiv.innerHTML = '';
                    if (products.length === 0) {
                        resultsDiv.innerHTML = '<div class="p-3 text-xs text-gray-500 italic">Không tìm thấy sản phẩm nào</div>';
                    } else {
                        products.forEach(prod => {
                            const option = document.createElement('div');
                            option.className = 'px-4 py-2 text-sm text-gray-700 hover:bg-primary hover:text-white cursor-pointer transition-colors';
                            option.innerText = prod.code;
                            option.onclick = () => {
                                input.value = prod.code;
                                hiddenInput.value = prod.id;
                                resultsDiv.classList.add('hidden');
                                if (onSelectCallback) {
                                    onSelectCallback(prod.id);
                                }
                            };
                            resultsDiv.appendChild(option);
                        });
                    }
                    resultsDiv.classList.remove('hidden');
                })
                .catch(err => {
                    console.error(err);
                });
        }

        // Close search results dropdowns when clicking outside
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.product-search-container')) {
                document.querySelectorAll('.search-results-dropdown').forEach(div => {
                    div.classList.add('hidden');
                });
            }
        });

        // --- Preload logic ---
        function addPreloadRow() {
            preloadRowCount++;
            const tbody = document.getElementById('preload_tbody');
            const row = document.createElement('tr');
            row.id = `preload_row_${preloadRowCount}`;
            row.className = 'divide-x divide-gray-50';

            row.innerHTML = `
                    <td class="px-4 py-2 relative product-search-container">
                        <input type="text" autocomplete="off" placeholder="Nhập part (mã) để tìm..." 
                               class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary product-search-input"
                               onfocus="this.select()"
                               oninput="searchProducts(this, 'preload_product_id_${preloadRowCount}', 'preload_results_${preloadRowCount}')">
                        <input type="hidden" class="product-input" id="preload_product_id_${preloadRowCount}">
                        <div id="preload_results_${preloadRowCount}" class="search-results-dropdown absolute z-50 w-11/12 bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto hidden">
                        </div>
                    </td>
                    <td class="px-4 py-2">
                        <input type="number" min="1" value="1" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary qty-input">
                    </td>
                    <td class="px-4 py-2 text-center">
                        <button type="button" onclick="removePreloadRow(${preloadRowCount})" class="text-red-500 hover:text-red-700">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                `;
            tbody.appendChild(row);
        }

        function removePreloadRow(id) {
            const row = document.getElementById(`preload_row_${id}`);
            if (row) {
                row.remove();
            }
        }

        // --- Borrow logic ---
        let selectedMaxQty = 0;

        function loadProductHolders() {
            const productId = document.getElementById('product_select').value;
            const container = document.getElementById('holders_container');
            const optionsDiv = document.getElementById('holders_options');
            const loading = document.getElementById('holders_loading');
            const qtyContainer = document.getElementById('borrow_qty_container');

            if (!productId) {
                container.classList.add('hidden');
                qtyContainer.classList.add('hidden');
                return;
            }

            loading.classList.remove('hidden');
            container.classList.add('hidden');
            qtyContainer.classList.add('hidden');

            fetch(`/tickets/holders?product_id=${productId}`)
                .then(res => res.json())
                .then(data => {
                    window.holdersData = data;
                    loading.classList.add('hidden');
                    optionsDiv.innerHTML = '';

                    if (!data.success) {
                        alert('Lỗi tải dữ liệu');
                        return;
                    }

                    let hasStock = false;

                    // Warehouses general stock options
                    if (data.warehouses && data.warehouses.length > 0) {
                        data.warehouses.forEach(wh => {
                            if (wh.qty > 0) {
                                hasStock = true;
                                optionsDiv.innerHTML += `
                                        <label class="relative flex flex-col p-4 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-primary/50 transition-all select-none">
                                            <div class="flex items-center gap-2">
                                                <input type="radio" name="borrow_source_select" value="warehouse_${wh.warehouse_id}" data-qty="${wh.qty}" onclick="selectBorrowSource('warehouse', null, ${wh.qty}, false, ${wh.warehouse_id})" class="text-primary focus:ring-primary">
                                                <span class="text-sm font-bold text-gray-800">${wh.name}</span>
                                            </div>
                                            <span class="text-xs text-gray-500 mt-1">Còn trống: <strong class="text-teal-600 font-semibold">${wh.qty} thiết bị</strong></span>
                                        </label>
                                    `;
                            }
                        });
                    }

                    // Salesperson allocation options
                    if (data.sales && data.sales.length > 0) {
                        data.sales.forEach(sale => {
                            if (sale.qty > 0) {
                                hasStock = true;
                                const isSelf = (sale.user_id && (parseInt(sale.user_id) === currentUserId)) || (sale.name === currentUserName);
                                const selfBadge = isSelf ? ' <span class="bg-amber-100 text-amber-800 text-[10px] px-1.5 py-0.5 rounded font-bold">Của bạn</span>' : '';
                                optionsDiv.innerHTML += `
                                        <label class="relative flex flex-col p-4 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-primary/50 transition-all select-none">
                                            <div class="flex items-center gap-2">
                                                <input type="radio" name="borrow_source_select" value="sales_${sale.user_id || sale.name}" data-qty="${sale.qty}" onclick="selectBorrowSource('sales', '${sale.user_id || ''}', ${sale.qty}, ${isSelf}, null, '${sale.name}')" class="text-primary focus:ring-primary">
                                                <span class="text-sm font-bold text-gray-800">${sale.name}${selfBadge}</span>
                                            </div>
                                            <span class="text-xs text-gray-500 mt-1">Đang giữ: <strong class="text-orange-600 font-semibold">${sale.qty} thiết bị</strong></span>
                                        </label>
                                    `;
                            }
                        });
                    }

                    if (!hasStock) {
                        optionsDiv.innerHTML = `
                                <div class="col-span-full bg-red-50 text-red-700 p-3 rounded-lg border border-red-100 text-sm">
                                    <i class="fas fa-exclamation-circle mr-1.5"></i> Thiết bị này hiện không có sẵn trong bất kỳ kho nào và không có ai đang giữ.
                                </div>
                            `;
                    }

                    container.classList.remove('hidden');
                })
                .catch(err => {
                    loading.classList.add('hidden');
                    console.error(err);
                    alert('Có lỗi xảy ra khi tải danh sách người giữ hàng.');
                });
        }

        let borrowSource = '';
        let targetUserId = '';
        let isSelfBorrow = false;

        function selectBorrowSource(source, targetUser, maxQty, isSelf = false, warehouseId = null, salesName = null) {
            borrowSource = source;
            targetUserId = targetUser;
            selectedMaxQty = maxQty;
            isSelfBorrow = isSelf;

            const qtyContainer = document.getElementById('borrow_qty_container');
            qtyContainer.classList.remove('hidden');

            const selfWarning = document.getElementById('self_borrow_warning');
            if (isSelf) {
                selfWarning.classList.remove('hidden');
            } else {
                selfWarning.classList.add('hidden');
            }

            // Load serial number options if product has real serials
            let sourceItems = [];
            if (window.holdersData) {
                if (source === 'warehouse') {
                    const wh = window.holdersData.warehouses.find(w => w.warehouse_id == warehouseId);
                    sourceItems = wh ? wh.items : [];
                } else {
                    const sl = window.holdersData.sales.find(s => (targetUser && s.user_id == targetUser) || (salesName && s.name == salesName));
                    sourceItems = sl ? sl.items : [];
                }
            }

            const realSerials = sourceItems.filter(item => !item.is_placeholder);
            const serialContainer = document.getElementById('serial_selection_container');
            const serialCheckboxes = document.getElementById('serial_checkboxes');
            const qtyInput = document.getElementById('borrow_qty');

            if (realSerials.length > 0) {
                serialCheckboxes.innerHTML = '';
                realSerials.forEach(item => {
                    serialCheckboxes.innerHTML += `
                        <label class="flex items-center gap-2 p-2 border border-gray-200 rounded-lg hover:bg-teal-50/50 cursor-pointer select-none transition-colors">
                            <input type="checkbox" name="selected_serial" value="${item.id}" onchange="updateBorrowQtyFromSerials()" class="rounded text-primary focus:ring-primary">
                            <span class="text-sm font-mono text-gray-800">${item.sku}</span>
                        </label>
                    `;
                });
                serialContainer.classList.remove('hidden');
                qtyInput.value = 0;
                qtyInput.readOnly = true;
            } else {
                serialContainer.classList.add('hidden');
                qtyInput.value = 1;
                qtyInput.readOnly = false;
            }

            qtyInput.max = maxQty;
            validateBorrowQty();
        }

        function updateBorrowQtyFromSerials() {
            const checked = document.querySelectorAll('input[name="selected_serial"]:checked');
            const qtyInput = document.getElementById('borrow_qty');
            qtyInput.value = checked.length;
            validateBorrowQty();
        }

        function validateBorrowQty() {
            const qtyInput = document.getElementById('borrow_qty');
            const qtyVal = parseInt(qtyInput.value) || 0;
            const warning = document.getElementById('max_qty_warning');

            if (qtyVal > selectedMaxQty) {
                warning.classList.remove('hidden');
                return false;
            } else {
                warning.classList.add('hidden');
                return true;
            }
        }

        // --- Submit form builder ---
        function submitTicketForm() {
            const type = document.getElementById('type_select').value;
            const hiddenDiv = document.getElementById('hidden_inputs');
            hiddenDiv.innerHTML = ''; // clear

            if (type === 'preload') {
                // Validate preload rows
                const rows = document.querySelectorAll('#preload_tbody tr');
                if (rows.length === 0) {
                    alert('Vui lòng thêm ít nhất một sản phẩm cần đặt hàng.');
                    return;
                }

                let valid = true;
                rows.forEach((row, index) => {
                    const prodId = row.querySelector('.product-input').value;
                    const qty = parseInt(row.querySelector('.qty-input').value) || 0;

                    if (!prodId) {
                        alert('Vui lòng chọn sản phẩm ở tất cả các dòng.');
                        valid = false;
                        return;
                    }
                    if (qty <= 0) {
                        alert('Số lượng sản phẩm đặt hàng phải lớn hơn 0.');
                        valid = false;
                        return;
                    }

                    // Add to hidden input
                    hiddenDiv.innerHTML += `
                            <input type="hidden" name="items[${index}][product_id]" value="${prodId}">
                            <input type="hidden" name="items[${index}][quantity]" value="${qty}">
                        `;
                });

                if (!valid) return;

            } else {
                // Borrow validation
                const prodId = document.getElementById('product_select').value;
                const qty = parseInt(document.getElementById('borrow_qty').value) || 0;

                if (!prodId) {
                    alert('Vui lòng chọn sản phẩm mượn.');
                    return;
                }

                const checkedRadio = document.querySelector('input[name="borrow_source_select"]:checked');
                if (!checkedRadio) {
                    alert('Vui lòng chọn nguồn mượn (Kho hoặc một Salesperson).');
                    return;
                }

                const serialContainer = document.getElementById('serial_selection_container');
                const checkedSerials = document.querySelectorAll('input[name="selected_serial"]:checked');
                if (!serialContainer.classList.contains('hidden')) {
                    if (checkedSerials.length === 0) {
                        alert('Vui lòng chọn ít nhất một số Serial muốn mượn.');
                        return;
                    }
                }

                if (qty <= 0) {
                    alert('Số lượng mượn phải lớn hơn 0.');
                    return;
                }

                if (!validateBorrowQty()) {
                    alert('Số lượng mượn vượt quá giới hạn tồn kho khả dụng.');
                    return;
                }

                if (isSelfBorrow) {
                    if (!confirm('Bạn đang chọn mượn hàng từ chính mình. Bạn có chắc chắn muốn tiếp tục không?')) {
                        return;
                    }
                }

                hiddenDiv.innerHTML += `
                        <input type="hidden" name="source" value="${borrowSource}">
                        <input type="hidden" name="target_user_id" value="${targetUserId || ''}">
                        <input type="hidden" name="items[0][product_id]" value="${prodId}">
                        <input type="hidden" name="items[0][quantity]" value="${qty}">
                    `;

                checkedSerials.forEach((checkbox, idx) => {
                    hiddenDiv.innerHTML += `
                        <input type="hidden" name="items[0][selected_serial_ids][${idx}]" value="${checkbox.value}">
                    `;
                });
            }

            document.getElementById('ticketForm').submit();
        }

        // Initialize fields
        toggleTypeFields();
        addPreloadRow(); // add first row by default for preload
    </script>
@endsection