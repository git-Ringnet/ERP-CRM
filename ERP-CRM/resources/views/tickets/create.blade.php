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

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-visible">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50 rounded-t-lg">
                <h3 class="text-md font-bold text-gray-800">Thông tin phiếu yêu cầu</h3>
            </div>
            <form method="POST" action="{{ route('tickets.store') }}" id="ticketForm" class="p-6 space-y-6"
                onsubmit="event.preventDefault(); submitTicketForm();">
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
                                <span
                                    class="ml-1 text-[11px] font-normal text-purple-700 bg-purple-100 px-2 py-0.5 rounded-full">
                                    <i class="fas fa-shield-alt mr-1"></i>Admin / BOD / Quản lý kho
                                </span>
                            </label>
                            <select name="borrower_user_id" id="borrower_user_id"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ $u->id === auth()->id() ? 'selected' : '' }}>
                                        {{ $u->name }} ({{ $u->department ?: 'N/A' }})
                                        {{ $u->id === auth()->id() ? '★ [Chính bạn]' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">
                                * Có thể chọn nhân viên khác để tạo phiếu mượn hộ.
                            </p>
                        @else
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Người mượn hàng</label>
                            <input type="text" readonly
                                value="{{ auth()->user()->name }} ({{ auth()->user()->department ?: 'N/A' }})"
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
                        <span class="text-xs text-gray-500 italic">Hỗ trợ mượn một hoặc nhiều sản phẩm trong một
                            phiếu</span>
                    </div>

                    <!-- Smart Source Recognition Banner -->
                    <div
                        class="p-3.5 bg-blue-50/80 border border-blue-200/80 rounded-lg flex items-start gap-3 text-xs text-blue-900 shadow-xs">
                        <div
                            class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center shrink-0 text-blue-600 mt-0.5">
                            <i class="fas fa-magic text-sm"></i>
                        </div>
                        <div class="space-y-0.5">
                            <div class="font-bold text-blue-950">Tự động nhận diện tồn kho & người giữ theo từng sản phẩm
                            </div>
                            <div class="text-blue-800/90 leading-relaxed">
                                Khi bạn chọn hoặc nhập mã sản phẩm, hệ thống sẽ tự động quét số lượng có sẵn trong Kho
                                (Runrate) và danh sách nhân viên đang tạm giữ. Bạn có thể chọn mượn từ Kho hoặc từ nhân viên
                                tương ứng trực tiếp trên từng dòng sản phẩm.
                            </div>
                        </div>
                    </div>

                    <!-- Products Table for Borrow -->
                    <div class="space-y-3 pt-1">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase">
                                Danh sách sản phẩm cần mượn <span class="text-red-500">*</span>
                            </label>
                            <button type="button" onclick="addBorrowRow()"
                                class="px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold rounded-lg hover:bg-emerald-100 transition-colors flex items-center gap-1 shadow-xs">
                                <i class="fas fa-plus"></i> Thêm sản phẩm mượn
                            </button>
                        </div>

                        <div class="border border-gray-200 rounded-lg bg-white shadow-xs overflow-visible relative">
                            <table class="w-full text-sm text-left overflow-visible">
                                <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-semibold">
                                    <tr class="divide-x divide-gray-100 border-b border-gray-200">
                                        <th class="px-3 py-2.5 w-10 text-center rounded-tl-lg">STT</th>
                                        <th class="px-4 py-2.5 min-w-[220px]">Sản phẩm (Mã / Part)</th>
                                        <th class="px-4 py-2.5 min-w-[240px]">Tồn kho & Người đang giữ</th>
                                        <th class="px-4 py-2.5 min-w-[200px]">Chọn nguồn mượn</th>
                                        <th class="px-4 py-2.5 min-w-[140px] text-center">Số lượng mượn</th>
                                        <th class="px-3 py-2.5 w-12 text-center rounded-tr-lg">Xóa</th>
                                    </tr>
                                </thead>
                                <tbody id="borrow_tbody" class="divide-y divide-gray-200 overflow-visible">
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

        document.addEventListener('DOMContentLoaded', function () {
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
        const searchDebounceTimers = {};
        const lastSearchResults = {};

        function searchProducts(input, hiddenId, resultsId, onSelectCallback = null) {
            const query = input.value.trim();
            const resultsDiv = document.getElementById(resultsId);
            const hiddenInput = document.getElementById(hiddenId);

            if (searchDebounceTimers[resultsId]) {
                clearTimeout(searchDebounceTimers[resultsId]);
            }

            // Bring parent row/cell to front while dropdown is open
            const parentCell = input.closest('.product-search-container');
            if (parentCell) {
                parentCell.classList.add('z-50');
            }

            if (query.length < 1) {
                hiddenInput.value = '';
                resultsDiv.classList.add('hidden');
                resultsDiv.innerHTML = '';
                lastSearchResults[resultsId] = [];
                if (onSelectCallback) onSelectCallback('', '');
                return;
            }

            resultsDiv.innerHTML = '<div class="p-3 text-xs text-gray-500 italic flex items-center gap-2"><i class="fas fa-spinner fa-spin text-primary"></i> Đang tìm sản phẩm...</div>';
            resultsDiv.classList.remove('hidden');

            searchDebounceTimers[resultsId] = setTimeout(() => {
                fetch(`/tickets/search-products?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(products => {
                        lastSearchResults[resultsId] = products;
                        resultsDiv.innerHTML = '';
                        if (!products || products.length === 0) {
                            resultsDiv.innerHTML = '<div class="p-3 text-xs text-gray-500 italic text-center">Không tìm thấy sản phẩm phù hợp</div>';
                        } else {
                            products.forEach((prod, idx) => {
                                const option = document.createElement('div');
                                option.className = 'search-option px-4 py-2.5 text-sm text-gray-700 hover:bg-primary hover:text-white cursor-pointer transition-colors border-b border-gray-100 last:border-0 group select-none flex flex-col';
                                option.dataset.index = idx;
                                option.innerHTML = `
                                        <div class="font-bold flex items-center justify-between">
                                            <span>${escapeHtml(prod.code)}</span>
                                            <span class="text-[10px] font-normal opacity-70 group-hover:opacity-100"><i class="fas fa-check mr-1 text-[9px]"></i>Chọn</span>
                                        </div>
                                        ${prod.name ? `<div class="text-xs text-gray-500 group-hover:text-white/90 line-clamp-1 mt-0.5">${escapeHtml(prod.name)}</div>` : ''}
                                    `;
                                option.onmousedown = (e) => {
                                    e.preventDefault();
                                    selectProduct(input, hiddenInput, resultsDiv, prod, onSelectCallback);
                                };
                                resultsDiv.appendChild(option);
                            });
                        }
                        resultsDiv.classList.remove('hidden');
                    })
                    .catch(err => {
                        console.error(err);
                        resultsDiv.innerHTML = '<div class="p-3 text-xs text-red-500 italic text-center">Lỗi khi tìm kiếm</div>';
                    });
            }, 200);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        function selectProduct(input, hiddenInput, resultsDiv, prod, onSelectCallback) {
            input.value = prod.code;
            hiddenInput.value = prod.id;
            resultsDiv.classList.add('hidden');
            const parentCell = input.closest('.product-search-container');
            if (parentCell) {
                parentCell.classList.remove('z-50');
            }
            if (onSelectCallback) {
                onSelectCallback(prod.id, prod.code);
            }
        }

        function handleSearchKeydown(e, input, hiddenId, resultsId, onSelectCallback = null) {
            const resultsDiv = document.getElementById(resultsId);
            const hiddenInput = document.getElementById(hiddenId);
            if (!resultsDiv || resultsDiv.classList.contains('hidden')) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                }
                return;
            }

            const options = resultsDiv.querySelectorAll('.search-option');
            let activeOption = resultsDiv.querySelector('.search-option.active-option');
            let activeIndex = activeOption ? parseInt(activeOption.dataset.index) : -1;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (activeIndex < options.length - 1) {
                    if (activeOption) activeOption.classList.remove('active-option', 'bg-primary', 'text-white');
                    activeIndex++;
                    options[activeIndex].classList.add('active-option', 'bg-primary', 'text-white');
                    options[activeIndex].scrollIntoView({ block: 'nearest' });
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (activeIndex > 0) {
                    if (activeOption) activeOption.classList.remove('active-option', 'bg-primary', 'text-white');
                    activeIndex--;
                    options[activeIndex].classList.add('active-option', 'bg-primary', 'text-white');
                    options[activeIndex].scrollIntoView({ block: 'nearest' });
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const products = lastSearchResults[resultsId] || [];
                if (activeIndex >= 0 && products[activeIndex]) {
                    selectProduct(input, hiddenInput, resultsDiv, products[activeIndex], onSelectCallback);
                } else if (products.length > 0) {
                    selectProduct(input, hiddenInput, resultsDiv, products[0], onSelectCallback);
                }
            } else if (e.key === 'Escape') {
                resultsDiv.classList.add('hidden');
            }
        }

        function handleSearchBlur(input, hiddenId, resultsId, onSelectCallback = null) {
            const hiddenInput = document.getElementById(hiddenId);
            const resultsDiv = document.getElementById(resultsId);
            const query = input.value.trim().toLowerCase();

            // Auto-select if user typed exact product code
            if (!hiddenInput.value && query) {
                const products = lastSearchResults[resultsId] || [];
                const exactMatch = products.find(p => p.code.toLowerCase() === query);
                if (exactMatch) {
                    selectProduct(input, hiddenInput, resultsDiv, exactMatch, onSelectCallback);
                }
            }

            setTimeout(() => {
                if (resultsDiv) resultsDiv.classList.add('hidden');
                const parentCell = input.closest('.product-search-container');
                if (parentCell) parentCell.classList.remove('z-50');
            }, 200);
        }

        // Close search results dropdowns when clicking outside
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.product-search-container')) {
                document.querySelectorAll('.search-results-dropdown').forEach(div => {
                    div.classList.add('hidden');
                });
                document.querySelectorAll('.product-search-container').forEach(cell => {
                    cell.classList.remove('z-50');
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
            row.className = 'divide-x divide-gray-100 hover:bg-gray-50/50 transition-colors relative';

            row.innerHTML = `
                    <td class="px-3 py-3 text-center text-xs font-bold text-gray-500 borrow-stt">
                        ${tbody.children.length + 1}
                    </td>
                    <td class="px-4 py-3 relative product-search-container">
                        <div class="relative">
                            <input type="text" autocomplete="off" placeholder="Nhập mã part / tên để tìm..."
                                value="${prefillCode}"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary borrow-search-input"
                                onfocus="this.select()"
                                oninput="searchProducts(this, 'borrow_prod_id_${rowId}', 'borrow_results_${rowId}', (id, code) => onBorrowRowProductSelected(${rowId}, id, code))"
                                onkeydown="handleSearchKeydown(event, this, 'borrow_prod_id_${rowId}', 'borrow_results_${rowId}', (id, code) => onBorrowRowProductSelected(${rowId}, id, code))"
                                onblur="handleSearchBlur(this, 'borrow_prod_id_${rowId}', 'borrow_results_${rowId}', (id, code) => onBorrowRowProductSelected(${rowId}, id, code))">
                            <input type="hidden" class="borrow-product-id" id="borrow_prod_id_${rowId}" value="${prefillId}">
                            <div id="borrow_results_${rowId}" class="search-results-dropdown absolute z-50 left-0 min-w-[340px] w-full bg-white border border-gray-200 rounded-lg shadow-xl mt-1 max-h-60 overflow-y-auto hidden">
                            </div>
                        </div>
                    </td>
                    <td class="px-3 py-3" id="borrow_stock_summary_${rowId}">
                        <span class="text-xs text-gray-400 italic">Chọn sản phẩm</span>
                    </td>
                    <td class="px-3 py-3" id="borrow_source_col_${rowId}">
                        <span class="text-xs text-gray-400 italic">--</span>
                    </td>
                    <td class="px-3 py-3 text-center" id="borrow_qty_col_${rowId}">
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
                const summaryCol = document.getElementById(`borrow_stock_summary_${rowId}`);
                const sourceCol = document.getElementById(`borrow_source_col_${rowId}`);
                const qtyCol = document.getElementById(`borrow_qty_col_${rowId}`);
                if (summaryCol) summaryCol.innerHTML = '<span class="text-xs text-gray-400 italic">Chọn sản phẩm</span>';
                if (sourceCol) sourceCol.innerHTML = '<span class="text-xs text-gray-400 italic">--</span>';
                if (qtyCol) qtyCol.innerHTML = '<span class="text-xs text-gray-400 italic">--</span>';
                return;
            }
            fetchAndRenderRowStock(rowId, productId);
        }

        function fetchAndRenderRowStock(rowId, productId) {
            const summaryCol = document.getElementById(`borrow_stock_summary_${rowId}`);
            const sourceCol = document.getElementById(`borrow_source_col_${rowId}`);
            const qtyCol = document.getElementById(`borrow_qty_col_${rowId}`);

            if (summaryCol) summaryCol.innerHTML = '<span class="text-xs text-gray-400 flex items-center gap-1.5"><i class="fas fa-spinner fa-spin text-primary"></i>Đang kiểm tra...</span>';
            if (sourceCol) sourceCol.innerHTML = '<span class="text-xs text-gray-400 italic">--</span>';
            if (qtyCol) qtyCol.innerHTML = '<span class="text-xs text-gray-400 italic">--</span>';

            const handleData = (data) => {
                let totalWhQty = 0;
                (data.warehouses || []).forEach(w => {
                    totalWhQty += w.qty;
                });

                let totalSalesQty = 0;
                (data.sales || []).forEach(s => {
                    totalSalesQty += s.qty;
                });

                // 1. Render Summary Column (Tồn kho & Người đang giữ)
                if (totalWhQty === 0 && totalSalesQty === 0) {
                    summaryCol.innerHTML = `
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-bold bg-red-100 text-red-700">
                                <i class="fas fa-times-circle"></i> Hết hàng trên hệ thống (0)
                            </div>
                        `;
                    sourceCol.innerHTML = `<span class="text-xs text-red-400 italic">Không có nguồn khả dụng</span>`;
                    qtyCol.innerHTML = `<span class="text-xs text-red-500 font-medium">Không thể mượn do hết tồn</span>`;
                    return;
                }

                let sumHtml = '<div class="space-y-1 text-xs">';
                // Warehouse badges per PO
                if (data.warehouses && data.warehouses.length > 0) {
                    data.warehouses.forEach(w => {
                        sumHtml += `
                                <div class="flex items-center justify-between bg-teal-50 text-teal-800 border border-teal-200/80 rounded px-2 py-1 gap-1">
                                    <span class="font-bold flex items-center gap-1.5 truncate max-w-[170px]" title="${w.name} - ${w.po_label}">
                                        <i class="fas fa-warehouse text-teal-600"></i> ${escapeHtml(w.name)}
                                        <span class="font-mono text-teal-900 bg-teal-100 border border-teal-200 px-1 rounded text-[10px] font-bold">${escapeHtml(w.po_label)}</span>:
                                    </span>
                                    <span class="font-extrabold text-teal-900 shrink-0">${w.qty} cái</span>
                                </div>
                            `;
                    });
                } else {
                    sumHtml += `
                            <div class="flex items-center justify-between bg-gray-50 text-gray-400 border border-gray-200 rounded px-2 py-0.5">
                                <span class="flex items-center gap-1"><i class="fas fa-warehouse text-gray-400"></i> Kho Runrate:</span>
                                <span class="italic text-[11px]">Hết (0)</span>
                            </div>
                        `;
                }

                // Sales holders per PO
                if (data.sales && data.sales.length > 0) {
                    sumHtml += '<div class="pt-0.5 space-y-1">';
                    data.sales.forEach(s => {
                        const isSelf = (currentUserId && s.user_id && currentUserId === s.user_id);
                        sumHtml += `
                                <div class="flex items-center justify-between bg-orange-50 text-orange-900 border border-orange-200/80 rounded px-2 py-1 gap-1">
                                    <span class="font-medium flex items-center gap-1.5 truncate max-w-[170px]" title="${s.name} - ${s.po_label}">
                                        <i class="fas fa-user text-orange-600"></i> ${escapeHtml(s.name)}${isSelf ? ' (Bạn)' : ''}
                                        <span class="font-mono text-orange-950 bg-orange-100 border border-orange-200 px-1 rounded text-[10px] font-bold">${escapeHtml(s.po_label)}</span>:
                                    </span>
                                    <span class="font-extrabold text-orange-950 shrink-0">${s.qty} cái</span>
                                </div>
                            `;
                    });
                    sumHtml += '</div>';
                }
                sumHtml += '</div>';
                summaryCol.innerHTML = sumHtml;

                // 2. Render Source Selection Dropdown (Differentiated by PO)
                let sourceSelect = `
                        <select id="borrow_source_select_${rowId}" onchange="renderBorrowRowQtyCol(${rowId})"
                            class="w-full border-gray-200 rounded-lg text-xs font-semibold focus:border-primary focus:ring-primary bg-white shadow-xs py-1.5">
                    `;

                if (data.warehouses && data.warehouses.length > 0) {
                    data.warehouses.forEach((w, wIdx) => {
                        sourceSelect += `<option value="warehouse:0:${wIdx}">Kho (${w.po_label}) - Còn ${w.qty} cái</option>`;
                    });
                }

                if (data.sales && data.sales.length > 0) {
                    data.sales.forEach((s, sIdx) => {
                        const isSelf = (currentUserId && s.user_id && currentUserId === s.user_id);
                        sourceSelect += `<option value="sales:${s.user_id || 0}:${sIdx}">${escapeHtml(s.name)}${isSelf ? ' (Chính bạn)' : ''} (${s.po_label}) - Còn ${s.qty} cái</option>`;
                    });
                }
                sourceSelect += `</select>`;
                sourceCol.innerHTML = sourceSelect;

                // 3. Render Qty & Serials based on selected source
                renderBorrowRowQtyCol(rowId);
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
                        if (summaryCol) summaryCol.innerHTML = '<span class="text-xs text-red-500">Lỗi tải dữ liệu</span>';
                    });
            }
        }

        function renderBorrowRowQtyCol(rowId) {
            const sourceSelect = document.getElementById(`borrow_source_select_${rowId}`);
            const qtyCol = document.getElementById(`borrow_qty_col_${rowId}`);
            if (!sourceSelect || !qtyCol) return;

            const parts = sourceSelect.value.split(':');
            const sourceType = parts[0];
            const targetUserId = parts[1];
            const sourceIndex = parseInt(parts[2]) || 0;

            const prodInput = document.getElementById(`borrow_prod_id_${rowId}`);
            const productId = prodInput ? prodInput.value : null;
            const data = productHoldersCache[productId];
            if (!data) return;

            let availableQty = 0;
            if (sourceType === 'warehouse') {
                const w = (data.warehouses || [])[sourceIndex];
                if (w) availableQty = w.qty;
            } else {
                const s = (data.sales || [])[sourceIndex];
                if (s) availableQty = s.qty;
            }

            if (availableQty <= 0) {
                qtyCol.innerHTML = '<span class="text-xs text-red-500 font-medium">Hết hàng</span>';
                return;
            }

            qtyCol.innerHTML = `
                    <div class="flex items-center justify-center gap-1.5">
                        <input type="number" min="1" max="${availableQty}" value="1" id="borrow_qty_${rowId}"
                            class="w-20 border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary borrow-qty-input font-bold text-gray-800 text-center"
                            oninput="validateRowQty(${rowId}, ${availableQty})">
                        <span class="text-xs text-gray-500 font-medium whitespace-nowrap">/ ${availableQty}</span>
                    </div>
                `;
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
                        <div class="relative">
                            <input type="text" autocomplete="off" placeholder="Nhập part (mã) để tìm..." 
                                   class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary product-search-input"
                                   onfocus="this.select()"
                                   oninput="searchProducts(this, 'preload_product_id_${preloadRowCount}', 'preload_results_${preloadRowCount}')"
                                   onkeydown="handleSearchKeydown(event, this, 'preload_product_id_${preloadRowCount}', 'preload_results_${preloadRowCount}')"
                                   onblur="handleSearchBlur(this, 'preload_product_id_${preloadRowCount}', 'preload_results_${preloadRowCount}')">
                            <input type="hidden" class="product-input" id="preload_product_id_${preloadRowCount}">
                            <div id="preload_results_${preloadRowCount}" class="search-results-dropdown absolute z-50 left-0 min-w-[340px] w-full bg-white border border-gray-200 rounded-lg shadow-xl mt-1 max-h-60 overflow-y-auto hidden">
                            </div>
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
                const borrowerUserId = document.getElementById('borrower_user_id')?.value || currentUserId;
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

                    const sourceSelect = document.getElementById(`borrow_source_select_${rowId}`);
                    if (!sourceSelect || !sourceSelect.value) {
                        alert(`Dòng thứ ${index + 1}: Sản phẩm hiện không có nguồn tồn khả dụng nào để mượn.`);
                        valid = false;
                        return;
                    }

                    const parts = sourceSelect.value.split(':');
                    const rowSource = parts[0];
                    const rowTargetUserId = parts[1];
                    const sourceIndex = parseInt(parts[2]) || 0;
                    const dedupeKey = `${prodId}_${sourceSelect.value}`;

                    if (selectedProducts.has(dedupeKey)) {
                        alert(`Dòng thứ ${index + 1}: Trùng lặp sản phẩm và nguồn mượn (PO) với dòng trước. Vui lòng gộp số lượng hoặc chọn nguồn khác.`);
                        valid = false;
                        return;
                    }
                    selectedProducts.add(dedupeKey);

                    if (rowSource === 'sales' && parseInt(rowTargetUserId) === parseInt(borrowerUserId)) {
                        if (!confirm(`Dòng thứ ${index + 1}: Bạn đang chọn mượn thiết bị từ chính bạn đang giữ. Bạn có chắc chắn muốn tiếp tục?`)) {
                            valid = false;
                            return;
                        }
                    }

                    const qtyInput = document.getElementById(`borrow_qty_${rowId}`);
                    const finalQty = qtyInput ? (parseInt(qtyInput.value) || 0) : 0;

                    if (finalQty <= 0) {
                        alert(`Dòng thứ ${index + 1}: Số lượng mượn phải lớn hơn 0.`);
                        valid = false;
                        return;
                    }

                    // Grab items IDs for this specific PO
                    const cacheData = productHoldersCache[prodId];
                    let allocatedItemIds = [];
                    if (cacheData) {
                        const group = (rowSource === 'warehouse') 
                            ? (cacheData.warehouses || [])[sourceIndex]
                            : (cacheData.sales || [])[sourceIndex];
                        if (group && group.item_ids) {
                            allocatedItemIds = group.item_ids.slice(0, finalQty);
                        }
                    }

                    hiddenDiv.innerHTML += `
                            <input type="hidden" name="items[${index}][product_id]" value="${prodId}">
                            <input type="hidden" name="items[${index}][source]" value="${rowSource}">
                            <input type="hidden" name="items[${index}][target_user_id]" value="${rowSource === 'sales' ? rowTargetUserId : ''}">
                            <input type="hidden" name="items[${index}][quantity]" value="${finalQty}">
                        `;
                    allocatedItemIds.forEach(itemId => {
                        hiddenDiv.innerHTML += `
                            <input type="hidden" name="items[${index}][selected_serial_ids][]" value="${itemId}">
                        `;
                    });
                });

                if (!valid) return;

                hiddenDiv.innerHTML += `
                        <input type="hidden" name="borrower_user_id" value="${borrowerUserId}">
                    `;
            }

            document.getElementById('ticketForm').submit();
        }
    </script>
@endsection