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

                    <!-- Borrower Selection for Warehouse Manager / Admin / BOD -->
                    <div id="borrower_selection_wrapper" class="hidden">
                        @if(!empty($canChooseBorrower))
                            <label for="borrower_user_id" class="block text-sm font-semibold text-gray-700 mb-1.5">
                                Người mượn hàng <span class="text-red-500">*</span>
                                <span class="ml-1 text-[11px] font-normal text-purple-700 bg-purple-100 px-2 py-0.5 rounded-full">
                                    <i class="fas fa-shield-alt mr-1"></i>Admin / BOD / Quản lý kho
                                </span>
                            </label>
                            <select name="borrower_user_id" id="borrower_user_id"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ $u->id === auth()->id() ? 'selected' : '' }}>
                                        {{ $u->name }} ({{ $u->department ?: 'N/A' }}) {{ $u->id === auth()->id() ? '★ [Chính bạn]' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">
                                * Có thể chọn nhân viên khác để tạo phiếu mượn hộ.
                            </p>
                        @else
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Người mượn hàng</label>
                            <input type="text" readonly value="{{ auth()->user()->name }} ({{ auth()->user()->department ?: 'N/A' }})"
                                class="w-full bg-gray-50 border-gray-200 rounded-lg text-sm text-gray-600 cursor-not-allowed">
                            <input type="hidden" name="borrower_user_id" id="borrower_user_id" value="{{ auth()->id() }}">
                        @endif
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- BORROW FIELDS (Multi-product support)                                     -->
                <!-- ========================================================================= -->
                <div id="borrow_fields" class="hidden bg-gray-50 p-5 rounded-lg border border-gray-200 space-y-5">
                    <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                        <h4 class="text-sm font-bold text-gray-800 flex items-center">
                            <i class="fas fa-people-arrows text-primary mr-2 text-base"></i>Thông tin mượn hàng
                        </h4>
                        <span class="text-xs text-gray-500 italic">Hỗ trợ mượn một hoặc nhiều sản phẩm trong một phiếu</span>
                    </div>

                    <!-- Source Selection -->
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-gray-600 uppercase">
                            Nguồn mượn hàng <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="relative flex items-center gap-3 p-3.5 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-primary transition-all select-none shadow-xs" id="label_source_warehouse">
                                <input type="radio" name="source_type" value="warehouse" checked onchange="onSourceTypeChange()" class="text-primary focus:ring-primary">
                                <div>
                                    <div class="text-sm font-bold text-gray-800 flex items-center gap-1.5">
                                        <i class="fas fa-warehouse text-teal-600"></i> Mượn từ Kho hàng (Runrate)
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5">Mượn trực tiếp từ tồn kho runrate khả dụng</div>
                                </div>
                            </label>
                            <label class="relative flex items-center gap-3 p-3.5 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-primary transition-all select-none shadow-xs" id="label_source_sales">
                                <input type="radio" name="source_type" value="sales" onchange="onSourceTypeChange()" class="text-primary focus:ring-primary">
                                <div>
                                    <div class="text-sm font-bold text-gray-800 flex items-center gap-1.5">
                                        <i class="fas fa-user-friends text-orange-600"></i> Mượn từ Nhân viên khác
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5">Mượn từ hàng nhân viên khác đang tạm giữ</div>
                                </div>
                            </label>
                        </div>

                        <!-- Target Sales Select (Only visible when 'sales' is chosen) -->
                        <div id="target_user_container" class="hidden p-3.5 bg-white rounded-lg border border-orange-200 space-y-1.5">
                            <label for="target_user_id" class="block text-xs font-bold text-orange-800 uppercase">
                                Chọn nhân viên muốn mượn hàng <span class="text-red-500">*</span>
                            </label>
                            <select id="target_user_id" onchange="onTargetUserChange()"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500">
                                <option value="">-- Chọn nhân viên đang giữ hàng --</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->department ?: 'N/A' }} - {{ $u->email }})</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-gray-500">
                                * Yêu cầu sẽ được gửi tới nhân viên này để phê duyệt trước khi chuyển giao thiết bị.
                            </p>
                        </div>
                    </div>

                    <!-- Products Table for Borrow -->
                    <div class="space-y-3 pt-2">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase">
                                Danh sách sản phẩm cần mượn <span class="text-red-500">*</span>
                            </label>
                            <button type="button" onclick="addBorrowRow()"
                                class="px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold rounded-lg hover:bg-emerald-100 transition-colors flex items-center gap-1 shadow-xs">
                                <i class="fas fa-plus"></i> Thêm sản phẩm mượn
                            </button>
                        </div>

                        <div class="border border-gray-200 rounded-lg overflow-hidden bg-white shadow-xs">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-semibold">
                                    <tr class="divide-x divide-gray-100 border-b border-gray-200">
                                        <th class="px-3 py-2.5 w-10 text-center">STT</th>
                                        <th class="px-4 py-2.5 min-w-[260px]">Sản phẩm (Mã / Part)</th>
                                        <th class="px-3 py-2.5 w-36 text-center">Tồn khả dụng</th>
                                        <th class="px-4 py-2.5 min-w-[220px]">Số lượng & Serial mượn</th>
                                        <th class="px-3 py-2.5 w-12 text-center">Xóa</th>
                                    </tr>
                                </thead>
                                <tbody id="borrow_tbody" class="divide-y divide-gray-200">
                                    <!-- Dynamic borrow rows -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- PRELOAD ITEMS TABLE (When preload is selected)                            -->
                <!-- ========================================================================= -->
                <div id="preload_items" class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h4 class="text-sm font-bold text-gray-700"><i class="fas fa-list text-primary mr-1.5"></i>Danh sách đặt hàng</h4>
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
                        class="px-5 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/90 transition-colors shadow-sm flex items-center gap-1.5">
                        <i class="fas fa-paper-plane text-xs"></i> Gửi yêu cầu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const currentUserId = {{ auth()->id() }};
        const currentUserName = "{{ auth()->user()->name }}";
        const canChooseBorrower = {{ !empty($canChooseBorrower) ? 'true' : 'false' }};
        let preloadRowCount = 0;
        let borrowRowCount = 0;

        // Cache holders responses by product_id
        const productHoldersCache = {};

        document.addEventListener('DOMContentLoaded', function() {
            const preselectedId = "{{ $preselectedProductId ?? '' }}";
            const preselectedCode = "{{ $preselectedProductCode ?? '' }}";

            if (preselectedId) {
                document.getElementById('type_select').value = 'borrow';
                toggleTypeFields();
                addBorrowRow(preselectedId, preselectedCode);
            } else {
                toggleTypeFields();
                addPreloadRow();
                addBorrowRow();
            }
        });

        function toggleTypeFields() {
            const type = document.getElementById('type_select').value;
            const borrowFields = document.getElementById('borrow_fields');
            const preloadItems = document.getElementById('preload_items');
            const borrowerWrapper = document.getElementById('borrower_selection_wrapper');

            if (type === 'borrow') {
                borrowFields.classList.remove('hidden');
                preloadItems.classList.add('hidden');
                borrowerWrapper.classList.remove('hidden');
                if (document.querySelectorAll('#borrow_tbody tr').length === 0) {
                    addBorrowRow();
                }
            } else {
                borrowFields.classList.add('hidden');
                preloadItems.classList.remove('hidden');
                borrowerWrapper.classList.add('hidden');
            }
        }

        function onSourceTypeChange() {
            const sourceType = document.querySelector('input[name="source_type"]:checked').value;
            const targetUserContainer = document.getElementById('target_user_container');

            if (sourceType === 'sales') {
                targetUserContainer.classList.remove('hidden');
            } else {
                targetUserContainer.classList.add('hidden');
            }

            refreshAllBorrowRowsStock();
        }

        function onTargetUserChange() {
            refreshAllBorrowRowsStock();
        }

        function refreshAllBorrowRowsStock() {
            const rows = document.querySelectorAll('#borrow_tbody tr');
            rows.forEach(row => {
                const rowId = row.dataset.rowId;
                const prodInput = row.querySelector('.borrow-product-id');
                if (prodInput && prodInput.value) {
                    fetchAndRenderRowStock(rowId, prodInput.value);
                }
            });
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
                if (onSelectCallback) onSelectCallback('', '');
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
                            option.className = 'px-4 py-2.5 text-sm text-gray-700 hover:bg-primary hover:text-white cursor-pointer transition-colors border-b border-gray-50 last:border-0';
                            option.innerHTML = `<div class="font-bold">${prod.code}</div>` + (prod.name ? `<div class="text-xs text-gray-400 group-hover:text-white/80">${prod.name}</div>` : '');
                            option.onclick = () => {
                                input.value = prod.code;
                                hiddenInput.value = prod.id;
                                resultsDiv.classList.add('hidden');
                                if (onSelectCallback) {
                                    onSelectCallback(prod.id, prod.code);
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

        // =========================================================================
        // BORROW MULTI-PRODUCT ROWS LOGIC
        // =========================================================================
        function addBorrowRow(prefillId = '', prefillCode = '') {
            borrowRowCount++;
            const rowId = borrowRowCount;
            const tbody = document.getElementById('borrow_tbody');
            const row = document.createElement('tr');
            row.id = `borrow_row_${rowId}`;
            row.dataset.rowId = rowId;
            row.className = 'divide-x divide-gray-100 hover:bg-gray-50/50 transition-colors';

            row.innerHTML = `
                <td class="px-3 py-3 text-center text-xs font-bold text-gray-500 borrow-stt">
                    ${tbody.children.length + 1}
                </td>
                <td class="px-4 py-3 relative product-search-container">
                    <input type="text" autocomplete="off" placeholder="Nhập mã part / tên để tìm..."
                        value="${prefillCode}"
                        class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary borrow-search-input"
                        onfocus="this.select()"
                        oninput="searchProducts(this, 'borrow_prod_id_${rowId}', 'borrow_results_${rowId}', (id, code) => onBorrowRowProductSelected(${rowId}, id, code))">
                    <input type="hidden" class="borrow-product-id" id="borrow_prod_id_${rowId}" value="${prefillId}">
                    <div id="borrow_results_${rowId}" class="search-results-dropdown absolute z-50 w-full left-0 bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-56 overflow-y-auto hidden">
                    </div>
                </td>
                <td class="px-3 py-3 text-center" id="borrow_stock_col_${rowId}">
                    <span class="text-xs text-gray-400 italic">Chọn sản phẩm</span>
                </td>
                <td class="px-4 py-3" id="borrow_qty_col_${rowId}">
                    <span class="text-xs text-gray-400 italic">--</span>
                </td>
                <td class="px-3 py-3 text-center">
                    <button type="button" onclick="removeBorrowRow(${rowId})" class="text-red-400 hover:text-red-600 transition-colors p-1" title="Xóa dòng">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(row);
            renumberBorrowRows();

            if (prefillId) {
                fetchAndRenderRowStock(rowId, prefillId);
            }
        }

        function removeBorrowRow(rowId) {
            const row = document.getElementById(`borrow_row_${rowId}`);
            if (row) {
                row.remove();
                renumberBorrowRows();
            }
        }

        function renumberBorrowRows() {
            const rows = document.querySelectorAll('#borrow_tbody tr');
            rows.forEach((r, idx) => {
                const sttEl = r.querySelector('.borrow-stt');
                if (sttEl) sttEl.textContent = idx + 1;
            });
        }

        function onBorrowRowProductSelected(rowId, productId, productCode) {
            if (!productId) {
                const stockCol = document.getElementById(`borrow_stock_col_${rowId}`);
                const qtyCol = document.getElementById(`borrow_qty_col_${rowId}`);
                if (stockCol) stockCol.innerHTML = '<span class="text-xs text-gray-400 italic">Chọn sản phẩm</span>';
                if (qtyCol) qtyCol.innerHTML = '<span class="text-xs text-gray-400 italic">--</span>';
                return;
            }
            fetchAndRenderRowStock(rowId, productId);
        }

        function fetchAndRenderRowStock(rowId, productId) {
            const stockCol = document.getElementById(`borrow_stock_col_${rowId}`);
            const qtyCol = document.getElementById(`borrow_qty_col_${rowId}`);

            if (stockCol) stockCol.innerHTML = '<span class="text-xs text-gray-400"><i class="fas fa-spinner fa-spin mr-1"></i>Kiểm tra...</span>';
            if (qtyCol) qtyCol.innerHTML = '<span class="text-xs text-gray-400 italic">--</span>';

            const handleData = (data) => {
                const sourceType = document.querySelector('input[name="source_type"]:checked').value;
                const targetUserId = document.getElementById('target_user_id')?.value || '';

                let availableQty = 0;
                let availableItems = [];

                if (sourceType === 'warehouse') {
                    if (data.warehouses && data.warehouses.length > 0) {
                        data.warehouses.forEach(wh => {
                            availableQty += wh.qty;
                            availableItems = availableItems.concat(wh.items || []);
                        });
                    }
                } else {
                    if (targetUserId && data.sales && data.sales.length > 0) {
                        const s = data.sales.find(item => item.user_id == targetUserId);
                        if (s) {
                            availableQty = s.qty;
                            availableItems = s.items || [];
                        }
                    }
                }

                // Render Stock column
                if (sourceType === 'sales' && !targetUserId) {
                    stockCol.innerHTML = '<span class="text-[11px] text-orange-600 italic">Chưa chọn nhân viên</span>';
                    qtyCol.innerHTML = '<span class="text-[11px] text-gray-400 italic">Vui lòng chọn nhân viên ở trên</span>';
                    return;
                }

                if (availableQty > 0) {
                    stockCol.innerHTML = `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-teal-100 text-teal-800"><i class="fas fa-check-circle mr-1 text-[10px]"></i>Còn ${availableQty}</span>`;
                } else {
                    stockCol.innerHTML = `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-700"><i class="fas fa-times-circle mr-1 text-[10px]"></i>Hết hàng (0)</span>`;
                    qtyCol.innerHTML = `<span class="text-xs text-red-500 font-medium">Không thể mượn do hết tồn khả dụng</span>`;
                    return;
                }

                // Render Qty / Serial column
                const realSerials = availableItems.filter(item => !item.is_placeholder);

                if (realSerials.length > 0) {
                    // Item has real serial numbers
                    qtyCol.innerHTML = `
                        <div>
                            <button type="button" onclick="toggleSerialBox(${rowId})" class="px-2.5 py-1 text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200 rounded-lg hover:bg-purple-100 flex items-center gap-1.5 shadow-xs">
                                <i class="fas fa-barcode"></i> Chọn Serial (<span id="serial_badge_${rowId}" class="text-purple-900 font-bold">0</span>/${realSerials.length})
                                <i class="fas fa-chevron-down text-[10px] ml-1"></i>
                            </button>
                            <div id="serial_box_${rowId}" class="mt-2 p-2.5 bg-gray-50 border border-gray-200 rounded-lg hidden space-y-2">
                                <div class="flex items-center justify-between text-[11px] font-bold text-gray-600 uppercase border-b border-gray-200 pb-1">
                                    <span>Danh sách Serial khả dụng:</span>
                                    <div class="flex items-center gap-2">
                                        <button type="button" onclick="selectAllRowSerials(${rowId}, true)" class="text-primary hover:underline text-[10px]">Tất cả</button>
                                        <span class="text-gray-300">|</span>
                                        <button type="button" onclick="selectAllRowSerials(${rowId}, false)" class="text-gray-500 hover:underline text-[10px]">Bỏ chọn</button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-36 overflow-y-auto">
                                    ${realSerials.map(s => `
                                        <label class="flex items-center gap-1.5 p-1.5 bg-white border border-gray-200 rounded text-xs font-mono hover:border-purple-300 cursor-pointer select-none">
                                            <input type="checkbox" name="row_serial_${rowId}" value="${s.id}" onchange="updateRowSerialCount(${rowId})" class="row-serial-cb-${rowId} rounded text-purple-600 focus:ring-purple-500 w-3.5 h-3.5">
                                            <span class="truncate">${s.sku}</span>
                                        </label>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    // Non-serial managed items
                    qtyCol.innerHTML = `
                        <div class="flex items-center gap-2">
                            <input type="number" min="1" max="${availableQty}" value="1" id="borrow_qty_${rowId}"
                                class="w-24 border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary borrow-qty-input font-bold text-gray-800"
                                oninput="validateRowQty(${rowId}, ${availableQty})">
                            <span class="text-xs text-gray-500">thiết bị</span>
                        </div>
                    `;
                }
            };

            if (productHoldersCache[productId]) {
                handleData(productHoldersCache[productId]);
            } else {
                fetch(`/tickets/holders?product_id=${productId}`)
                    .then(res => res.json())
                    .then(data => {
                        productHoldersCache[productId] = data;
                        handleData(data);
                    })
                    .catch(err => {
                        console.error(err);
                        if (stockCol) stockCol.innerHTML = '<span class="text-xs text-red-500">Lỗi tải dữ liệu</span>';
                    });
            }
        }

        function toggleSerialBox(rowId) {
            const box = document.getElementById(`serial_box_${rowId}`);
            if (box) box.classList.toggle('hidden');
        }

        function updateRowSerialCount(rowId) {
            const checked = document.querySelectorAll(`.row-serial-cb-${rowId}:checked`);
            const badge = document.getElementById(`serial_badge_${rowId}`);
            if (badge) badge.textContent = checked.length;
        }

        function selectAllRowSerials(rowId, selectAll) {
            const cbs = document.querySelectorAll(`.row-serial-cb-${rowId}`);
            cbs.forEach(cb => cb.checked = selectAll);
            updateRowSerialCount(rowId);
        }

        function validateRowQty(rowId, maxVal) {
            const input = document.getElementById(`borrow_qty_${rowId}`);
            if (input) {
                let v = parseInt(input.value) || 0;
                if (v > maxVal) input.value = maxVal;
                if (v < 1) input.value = 1;
            }
        }

        // =========================================================================
        // PRELOAD ROWS LOGIC
        // =========================================================================
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
                    <div id="preload_results_${preloadRowCount}" class="search-results-dropdown absolute z-50 w-full left-0 bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto hidden">
                    </div>
                </td>
                <td class="px-4 py-2">
                    <input type="number" min="1" value="1" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary qty-input font-bold text-gray-800">
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
            if (row) row.remove();
        }

        // =========================================================================
        // FORM SUBMIT BUILDER & VALIDATION
        // =========================================================================
        function submitTicketForm() {
            const type = document.getElementById('type_select').value;
            const hiddenDiv = document.getElementById('hidden_inputs');
            hiddenDiv.innerHTML = '';

            if (type === 'preload') {
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
                        alert('Vui lòng chọn sản phẩm ở tất cả các dòng đặt hàng.');
                        valid = false;
                        return;
                    }
                    if (qty <= 0) {
                        alert('Số lượng sản phẩm đặt hàng phải lớn hơn 0.');
                        valid = false;
                        return;
                    }

                    hiddenDiv.innerHTML += `
                        <input type="hidden" name="items[${index}][product_id]" value="${prodId}">
                        <input type="hidden" name="items[${index}][quantity]" value="${qty}">
                    `;
                });

                if (!valid) return;

            } else {
                // BORROW FORM VALIDATION
                const sourceType = document.querySelector('input[name="source_type"]:checked').value;
                const targetUserId = document.getElementById('target_user_id')?.value || '';
                const borrowerUserId = document.getElementById('borrower_user_id')?.value || currentUserId;

                if (sourceType === 'sales') {
                    if (!targetUserId) {
                        alert('Vui lòng chọn nhân viên muốn mượn hàng.');
                        return;
                    }
                    if (parseInt(targetUserId) === parseInt(borrowerUserId)) {
                        if (!confirm('Chú ý: Người mượn và người giữ hàng là cùng một người. Bạn có chắc chắn muốn tiếp tục?')) {
                            return;
                        }
                    }
                }

                const rows = document.querySelectorAll('#borrow_tbody tr');
                if (rows.length === 0) {
                    alert('Vui lòng thêm ít nhất một sản phẩm cần mượn.');
                    return;
                }

                const selectedProducts = new Set();
                let valid = true;

                rows.forEach((row, index) => {
                    if (!valid) return;
                    const rowId = row.dataset.rowId;
                    const prodInput = row.querySelector('.borrow-product-id');
                    const prodId = prodInput ? prodInput.value : '';

                    if (!prodId) {
                        alert(`Dòng thứ ${index + 1}: Vui lòng chọn sản phẩm cần mượn.`);
                        valid = false;
                        return;
                    }

                    if (selectedProducts.has(prodId)) {
                        alert(`Sản phẩm tại dòng thứ ${index + 1} bị trùng với một dòng trước đó. Vui lòng gộp số lượng hoặc xóa dòng trùng.`);
                        valid = false;
                        return;
                    }
                    selectedProducts.add(prodId);

                    // Check Serials vs Quantity input
                    const serialCheckboxes = document.querySelectorAll(`.row-serial-cb-${rowId}:checked`);
                    const qtyInput = document.getElementById(`borrow_qty_${rowId}`);

                    let finalQty = 0;
                    let selectedSerials = [];

                    if (document.querySelectorAll(`.row-serial-cb-${rowId}`).length > 0) {
                        // Product has serials
                        if (serialCheckboxes.length === 0) {
                            alert(`Dòng thứ ${index + 1}: Vui lòng bấm "Chọn Serial" và chọn ít nhất một số Serial.`);
                            valid = false;
                            return;
                        }
                        finalQty = serialCheckboxes.length;
                        serialCheckboxes.forEach(cb => selectedSerials.push(cb.value));
                    } else if (qtyInput) {
                        finalQty = parseInt(qtyInput.value) || 0;
                        if (finalQty <= 0) {
                            alert(`Dòng thứ ${index + 1}: Số lượng mượn phải lớn hơn 0.`);
                            valid = false;
                            return;
                        }
                    } else {
                        alert(`Dòng thứ ${index + 1}: Sản phẩm hiện không có tồn khả dụng để mượn.`);
                        valid = false;
                        return;
                    }

                    hiddenDiv.innerHTML += `
                        <input type="hidden" name="items[${index}][product_id]" value="${prodId}">
                        <input type="hidden" name="items[${index}][quantity]" value="${finalQty}">
                    `;

                    selectedSerials.forEach((sId, sIdx) => {
                        hiddenDiv.innerHTML += `
                            <input type="hidden" name="items[${index}][selected_serial_ids][${sIdx}]" value="${sId}">
                        `;
                    });
                });

                if (!valid) return;

                hiddenDiv.innerHTML += `
                    <input type="hidden" name="borrower_user_id" value="${borrowerUserId}">
                    <input type="hidden" name="source" value="${sourceType}">
                    <input type="hidden" name="target_user_id" value="${sourceType === 'sales' ? targetUserId : ''}">
                `;
            }

            document.getElementById('ticketForm').submit();
        }
    </script>
@endsection