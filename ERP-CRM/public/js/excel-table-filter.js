/**
 * Universal Excel-Style Column Filter & Sorter for Mini ERP
 * Supports both Client-Side DOM filtering and Server-Side AJAX + PushState filtering.
 */
(function (window, document) {
    'use strict';

    // Global state
    let activeFilterTableInstance = null;
    let popoverElement = null;
    let currentColumn = null; // { tableInstance, colIndex, colKey, colTitle, colType, triggerBtn }
    const registeredTables = new Map();
    let popstateListenerAttached = false;

    /**
     * Parse cell content to plain text, number, or date
     */
    function parseCellValue(td, colType) {
        if (!td) return { text: '', num: NaN, date: null, raw: '' };
        
        let raw = (td.dataset.filterValue !== undefined && td.dataset.filterValue !== null)
            ? td.dataset.filterValue
            : td.innerText;

        raw = (raw || '').replace(/\s+/g, ' ').trim();
        const displayVal = raw === '' ? '(Trống / N/A)' : raw;

        let num = NaN;
        if (colType === 'number' && raw !== '') {
            let cleaned = raw.replace(/[^\d.-]/g, '');
            num = parseFloat(cleaned);
        }

        let date = null;
        if (colType === 'date' && raw !== '') {
            const dmy = raw.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/);
            const ymd = raw.match(/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})/);
            if (dmy) {
                date = new Date(parseInt(dmy[3], 10), parseInt(dmy[2], 10) - 1, parseInt(dmy[1], 10));
            } else if (ymd) {
                date = new Date(parseInt(ymd[1], 10), parseInt(ymd[2], 10) - 1, parseInt(ymd[3], 10));
            } else {
                const parsed = Date.parse(raw);
                if (!isNaN(parsed)) date = new Date(parsed);
            }
        }

        return { text: displayVal, num, date, raw };
    }

    /**
     * Determine column type (number, date, text)
     */
    function detectColumnType(th, sampleCells) {
        if (th.dataset.colType) return th.dataset.colType;

        const title = (th.innerText || '').toLowerCase();
        
        if (
            title.includes('stt') ||
            title.includes('số lượng') ||
            title.includes('sl') ||
            title.includes('tiền') ||
            title.includes('giá') ||
            title.includes('doanh số') ||
            title.includes('chi phí') ||
            title.includes('margin') ||
            title.includes('thuế') ||
            title.includes('vat') ||
            title.includes('đơn giá') ||
            title.includes('thành tiền') ||
            title.includes('tỷ lệ') ||
            title.includes('%') ||
            title.includes('nợ') ||
            title.includes('tồn kho') ||
            title.includes('point')
        ) {
            return 'number';
        }

        if (
            title.includes('ngày') ||
            title.includes('date') ||
            title.includes('thời gian') ||
            title.includes('hạn') ||
            title.includes('deadline') ||
            title.includes('lúc')
        ) {
            return 'date';
        }

        let numberCount = 0;
        let dateCount = 0;
        let totalCount = 0;

        for (let td of sampleCells) {
            const txt = (td.innerText || '').trim();
            if (!txt || txt === '-' || txt === 'N/A') continue;
            totalCount++;

            if (/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}/.test(txt) || /^\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}/.test(txt)) {
                dateCount++;
                continue;
            }

            const cleaned = txt.replace(/[^\d.-]/g, '');
            if (cleaned !== '' && !isNaN(Number(cleaned)) && (/[₫đ$%\d]/.test(txt))) {
                numberCount++;
            }
        }

        if (totalCount > 0) {
            if (dateCount / totalCount >= 0.7) return 'date';
            if (numberCount / totalCount >= 0.7) return 'number';
        }

        return 'text';
    }

    /**
     * ExcelTableFilter Class
     */
    class ExcelTableFilter {
        constructor(table, options = {}) {
            this.table = table;
            this.options = options;
            this.tbody = table.querySelector('tbody');
            if (!this.tbody) return;

            this.tableId = table.id || 'excel-table-' + Math.random().toString(36).substring(2, 9);
            table.id = this.tableId;

            this.isServer = this.checkServerMode();
            this.module = table.dataset.module || window.location.pathname.split('/').filter(Boolean)[0] || 'products';

            this.activeFilters = {}; // { [colIndex]: { colKey, type: 'value'|'condition', values: Set(), condition: {...} } }
            this.activeSort = null; // { colIndex, colKey, direction }
            this.columns = []; // Array of column metadata
            this.rows = []; // Array of row objects (client mode)

            this.init();
        }

        checkServerMode() {
            if (this.table.dataset.filterMode === 'client') return false;
            if (this.table.dataset.filterMode === 'server') return true;

            const path = window.location.pathname;
            if (path.includes('/sale-reports')) return false;

            const hasPagination = !!(
                this.table.closest('.bg-white')?.querySelector('nav[role="navigation"], .pagination, .border-t nav') ||
                document.querySelector('nav[role="navigation"]') ||
                window.location.search.includes('col_filter') ||
                window.location.search.includes('col_sort')
            );

            return hasPagination;
        }

        init() {
            this.setupBadgeBar();
            this.setupHeaders();

            if (this.isServer) {
                this.parseFiltersFromUrl();
                this.setupAjaxPagination();
                this.setupPopstateListener();
                this.renderBadges();
                this.updateHeaderHighlights();
            } else {
                this.cacheRows();
                this.setupEmptyRow();
                this.updateCounter(this.rows.length, this.rows.length);
            }

            registeredTables.set(this.tableId, this);
        }

        setupBadgeBar() {
            let container = this.table.parentElement.querySelector('.excel-active-filters-bar');
            if (!container) {
                container = document.createElement('div');
                container.className = 'excel-active-filters-bar hidden mb-2.5 p-2 bg-blue-50/70 border border-blue-200 rounded-lg flex flex-wrap items-center justify-between gap-2 text-xs animate-fadeIn';
                container.innerHTML = `
                    <div class="flex flex-wrap items-center gap-1.5 excel-badges-wrapper">
                        <span class="text-blue-900 font-semibold flex items-center gap-1 mr-1">
                            <i class="fas fa-filter text-blue-600 text-[11px]"></i>
                            <span>Đang lọc:</span>
                        </span>
                        <div class="excel-badges-list flex flex-wrap items-center gap-1.5"></div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-gray-600 font-medium excel-counter-text">Hiển thị: <b>0</b> dòng</span>
                        <button type="button" class="btn-clear-all-table-filters text-red-600 hover:text-red-800 hover:underline font-semibold flex items-center gap-1">
                            <i class="fas fa-trash-alt text-[10px]"></i>
                            <span>Xóa lọc</span>
                        </button>
                    </div>
                `;
                const refNode = this.table.closest('.overflow-x-auto') || this.table;
                refNode.parentNode.insertBefore(container, refNode);
            }

            this.badgeBar = container;
            this.badgesList = container.querySelector('.excel-badges-list');
            this.counterText = container.querySelector('.excel-counter-text');
            const clearBtn = container.querySelector('.btn-clear-all-table-filters');
            if (clearBtn) {
                clearBtn.onclick = () => this.clearAllFilters();
            }
        }

        setupHeaders() {
            const thead = this.table.querySelector('thead');
            if (!thead) return;

            const headerRows = thead.querySelectorAll('tr');
            const headerRow = headerRows[headerRows.length - 1];
            if (!headerRow) return;

            const thElements = Array.from(headerRow.children);
            this.columns = [];

            thElements.forEach((th, colIndex) => {
                if (
                    th.classList.contains('no-filter') ||
                    th.classList.contains('no-sort') ||
                    th.dataset.noFilter !== undefined ||
                    th.querySelector('input[type="checkbox"]') ||
                    th.classList.contains('w-10') ||
                    (th.classList.contains('w-12') && th.querySelector('input'))
                ) {
                    this.columns.push(null);
                    return;
                }

                const thText = (th.innerText || '').replace(/\s+/g, ' ').trim();
                const lowerText = thText.toLowerCase();

                if (lowerText === 'thao tác' || lowerText === 'hành động' || lowerText === 'action' || lowerText === 'actions') {
                    this.columns.push(null);
                    return;
                }

                const sampleRows = Array.from(this.tbody.querySelectorAll('tr:not(.excel-empty-filter-row)')).slice(0, 15);
                const sampleCells = sampleRows.map(r => r.children[colIndex]).filter(Boolean);

                const colType = detectColumnType(th, sampleCells);
                const colKey = th.dataset.col || 'col_' + colIndex;
                const colTitle = th.dataset.colTitle || thText || `Cột ${colIndex + 1}`;

                th.dataset.colIndex = colIndex;
                th.dataset.col = colKey;
                th.dataset.colTitle = colTitle;
                th.dataset.colType = colType;

                let filterBtn = th.querySelector('.excel-col-filter-btn');
                if (!filterBtn) {
                    filterBtn = document.createElement('button');
                    filterBtn.type = 'button';
                    filterBtn.className = 'excel-col-filter-btn ml-1.5 p-1 text-gray-400 hover:text-blue-600 rounded transition-colors inline-flex items-center justify-center';
                    filterBtn.title = `Lọc & Sắp xếp: ${colTitle}`;
                    filterBtn.innerHTML = '<i class="fas fa-filter text-[10px]"></i>';

                    th.style.position = 'relative';
                    th.style.verticalAlign = 'middle';

                    let wrapper = th.querySelector('.excel-th-content');
                    if (!wrapper) {
                        const originalHTML = th.innerHTML;
                        th.innerHTML = '';
                        wrapper = document.createElement('div');
                        wrapper.className = 'excel-th-content inline-flex items-center justify-between gap-1 w-full';
                        wrapper.innerHTML = `<span>${originalHTML}</span>`;
                        wrapper.appendChild(filterBtn);
                        th.appendChild(wrapper);
                    } else {
                        wrapper.appendChild(filterBtn);
                    }
                }

                filterBtn.onclick = (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    openGlobalFilterPopover(this, colIndex, colKey, colTitle, colType, filterBtn);
                };

                this.columns.push({
                    th,
                    btn: filterBtn,
                    key: colKey,
                    title: colTitle,
                    type: colType,
                    index: colIndex
                });
            });
        }

        cacheRows() {
            const trList = Array.from(this.tbody.querySelectorAll('tr:not(.excel-empty-filter-row)'));
            this.rows = trList.map(tr => {
                const cells = Array.from(tr.children).map((td, idx) => {
                    const colMeta = this.columns[idx];
                    const colType = colMeta ? colMeta.type : 'text';
                    return parseCellValue(td, colType);
                });
                return { tr, cells };
            });
        }

        setupEmptyRow() {
            let emptyTr = this.tbody.querySelector('.excel-empty-filter-row');
            if (!emptyTr) {
                const totalCols = this.columns.length || 10;
                emptyTr = document.createElement('tr');
                emptyTr.className = 'excel-empty-filter-row hidden';
                emptyTr.innerHTML = `
                    <td colspan="${totalCols}" class="px-6 py-12 text-center text-gray-500 bg-gray-50/50">
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <i class="fas fa-filter-circle-xmark text-gray-300 text-3xl"></i>
                            <div class="text-sm font-medium text-gray-600">Không tìm thấy dữ liệu khớp với bộ lọc</div>
                            <button type="button" class="btn-clear-filters-empty text-xs text-primary font-semibold hover:underline">
                                Xóa tất cả bộ lọc
                            </button>
                        </div>
                    </td>
                `;
                this.tbody.appendChild(emptyTr);

                const btn = emptyTr.querySelector('.btn-clear-filters-empty');
                if (btn) btn.onclick = () => this.clearAllFilters();
            }
            this.emptyRow = emptyTr;
        }

        parseFiltersFromUrl() {
            this.activeFilters = {};
            this.activeSort = null;

            const url = new URL(window.location.href);
            const params = url.searchParams;

            // Sort params
            const colSort = params.get('col_sort');
            const colDir = params.get('col_dir') || 'asc';
            if (colSort) {
                const foundCol = this.columns.find(c => c && c.key === colSort);
                if (foundCol) {
                    this.activeSort = {
                        colIndex: foundCol.index,
                        colKey: colSort,
                        direction: colDir
                    };
                }
            }

            // Value filters: col_filter[key][]
            for (const [key, value] of params.entries()) {
                const matchVal = key.match(/^col_filter\[([a-zA-Z0-9_.]+)\](\[\])?$/);
                if (matchVal) {
                    const colKey = matchVal[1];
                    const foundCol = this.columns.find(c => c && c.key === colKey);
                    if (foundCol) {
                        const colIdx = foundCol.index;
                        if (!this.activeFilters[colIdx]) {
                            this.activeFilters[colIdx] = {
                                colKey,
                                type: 'value',
                                title: foundCol.title,
                                colType: foundCol.type,
                                values: new Set()
                            };
                        }
                        this.activeFilters[colIdx].values.add(value);
                    }
                }

                // Condition filters: col_filter_op[key]
                const matchOp = key.match(/^col_filter_op\[([a-zA-Z0-9_.]+)\]$/);
                if (matchOp) {
                    const colKey = matchOp[1];
                    const foundCol = this.columns.find(c => c && c.key === colKey);
                    if (foundCol) {
                        const colIdx = foundCol.index;
                        const op = value;
                        const val1 = params.get(`col_filter_val[${colKey}]`) || '';
                        const val2 = params.get(`col_filter_val2[${colKey}]`) || '';
                        this.activeFilters[colIdx] = {
                            colKey,
                            type: 'condition',
                            title: foundCol.title,
                            colType: foundCol.type,
                            condition: { op, val1, val2 }
                        };
                    }
                }
            }
        }

        updateHeaderHighlights() {
            this.columns.forEach((col, idx) => {
                if (!col || !col.btn) return;
                const isFiltered = !!this.activeFilters[idx];
                col.btn.classList.toggle('is-filtered', isFiltered);
            });
        }

        setupAjaxPagination() {
            const tableContainer = this.table.closest('.bg-white') || this.table.parentElement;
            if (!tableContainer) return;

            const paginationLinks = tableContainer.querySelectorAll('nav[role="navigation"] a, .pagination a');
            paginationLinks.forEach(link => {
                link.onclick = (e) => {
                    e.preventDefault();
                    if (link.href && link.href !== '#' && !link.href.startsWith('javascript:')) {
                        this.loadServerData(link.href, true);
                    }
                };
            });
        }

        setupPopstateListener() {
            if (popstateListenerAttached) return;
            popstateListenerAttached = true;

            window.addEventListener('popstate', () => {
                for (const instance of registeredTables.values()) {
                    if (instance.isServer) {
                        instance.loadServerData(window.location.href, false);
                    }
                }
            });
        }

        loadServerData(targetUrl, pushState = true) {
            const tableWrapper = this.table.closest('.overflow-x-auto') || this.table;
            tableWrapper.classList.add('opacity-50', 'pointer-events-none', 'transition-opacity');

            if (pushState) {
                window.history.pushState(null, '', targetUrl);
            }

            fetch(targetUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html,application/xhtml+xml,application/xml'
                }
            })
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.text();
            })
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                const newTable = doc.getElementById(this.tableId) ||
                    doc.querySelector(`table[data-module="${this.module}"]`) ||
                    doc.querySelector('table');

                if (newTable && newTable.querySelector('tbody')) {
                    this.tbody.innerHTML = newTable.querySelector('tbody').innerHTML;
                }

                // Update pagination
                const currentContainer = this.table.closest('.bg-white') || this.table.parentElement;
                const newContainer = doc.getElementById(this.tableId)?.closest('.bg-white') || doc.querySelector('.bg-white');

                if (currentContainer && newContainer) {
                    const currentNav = currentContainer.querySelector('.border-t, nav[role="navigation"]')?.parentElement;
                    const newNav = newContainer.querySelector('.border-t, nav[role="navigation"]')?.parentElement;

                    if (currentNav && newNav) {
                        currentNav.innerHTML = newNav.innerHTML;
                    }
                }

                tableWrapper.classList.remove('opacity-50', 'pointer-events-none');

                // Re-sync filters from URL
                this.parseFiltersFromUrl();
                this.updateHeaderHighlights();
                this.renderBadges();
                this.setupAjaxPagination();

                this.table.dispatchEvent(new CustomEvent('excel-filter-applied', {
                    bubbles: true,
                    detail: { isServer: true, activeFilters: this.activeFilters }
                }));
            })
            .catch(err => {
                console.error('Failed to load filtered table data:', err);
                tableWrapper.classList.remove('opacity-50', 'pointer-events-none');
                // Fallback to full page reload if fetch failed
                if (pushState) {
                    window.location.href = targetUrl;
                }
            });
        }

        applySort(colIndex, direction) {
            const colMeta = this.columns[colIndex];
            if (!colMeta) return;

            if (this.isServer) {
                const url = new URL(window.location.href);
                url.searchParams.set('col_sort', colMeta.key);
                url.searchParams.set('col_dir', direction);
                url.searchParams.delete('page');
                this.loadServerData(url.toString(), true);
                return;
            }

            // Client-side sort
            this.activeSort = { colIndex, direction };
            const colType = colMeta.type;

            this.rows.sort((a, b) => {
                const valA = a.cells[colIndex];
                const valB = b.cells[colIndex];

                if (colType === 'number') {
                    const numA = isNaN(valA.num) ? -Infinity : valA.num;
                    const numB = isNaN(valB.num) ? -Infinity : valB.num;
                    return direction === 'asc' ? numA - numB : numB - numA;
                }

                if (colType === 'date') {
                    const timeA = valA.date ? valA.date.getTime() : 0;
                    const timeB = valB.date ? valB.date.getTime() : 0;
                    return direction === 'asc' ? timeA - timeB : timeB - timeA;
                }

                const strA = (valA.raw || '').toLowerCase();
                const strB = (valB.raw || '').toLowerCase();
                return direction === 'asc' ? strA.localeCompare(strB, 'vi') : strB.localeCompare(strA, 'vi');
            });

            this.rows.forEach(r => this.tbody.appendChild(r.tr));
            if (this.emptyRow) this.tbody.appendChild(this.emptyRow);

            this.applyFilters();
        }

        applyFilters() {
            if (this.isServer) return; // Server mode handled via AJAX

            let visibleCount = 0;
            const filterEntries = Object.entries(this.activeFilters);

            this.rows.forEach(row => {
                let matches = true;

                for (const [colIdxStr, filter] of filterEntries) {
                    const colIdx = parseInt(colIdxStr, 10);
                    const cellVal = row.cells[colIdx];
                    if (!cellVal) continue;

                    if (filter.type === 'value') {
                        if (!filter.values.has(cellVal.text)) {
                            matches = false;
                            break;
                        }
                    } else if (filter.type === 'condition') {
                        const { op, val1, val2 } = filter.condition;
                        const colType = filter.colType;

                        if (colType === 'number') {
                            const num = cellVal.num;
                            const n1 = parseFloat(val1);
                            const n2 = parseFloat(val2);

                            if (op === 'gt_0' && (isNaN(num) || num <= 0)) matches = false;
                            else if (op === 'eq_0' && num !== 0) matches = false;
                            else if (op === 'eq' && num !== n1) matches = false;
                            else if (op === 'neq' && num === n1) matches = false;
                            else if (op === 'gt' && (isNaN(num) || num <= n1)) matches = false;
                            else if (op === 'gte' && (isNaN(num) || num < n1)) matches = false;
                            else if (op === 'lt' && (isNaN(num) || num >= n1)) matches = false;
                            else if (op === 'lte' && (isNaN(num) || num > n1)) matches = false;
                            else if (op === 'between' && (isNaN(num) || num < n1 || num > n2)) matches = false;
                        } else if (colType === 'date') {
                            const time = cellVal.date ? cellVal.date.getTime() : null;
                            const d1 = val1 ? Date.parse(val1) : null;
                            const d2 = val2 ? Date.parse(val2) : null;

                            if (op === 'is_empty' && cellVal.date !== null) matches = false;
                            else if (op === 'is_not_empty' && cellVal.date === null) matches = false;
                            else if (op === 'before' && (!time || !d1 || time >= d1)) matches = false;
                            else if (op === 'after' && (!time || !d1 || time <= d1)) matches = false;
                            else if (op === 'between' && (!time || !d1 || !d2 || time < d1 || time > d2)) matches = false;
                        } else {
                            const str = (cellVal.raw || '').toLowerCase();
                            const s1 = (val1 || '').toLowerCase();

                            if (op === 'contains' && !str.includes(s1)) matches = false;
                            else if (op === 'not_contains' && str.includes(s1)) matches = false;
                            else if (op === 'equals' && str !== s1) matches = false;
                            else if (op === 'starts_with' && !str.startsWith(s1)) matches = false;
                            else if (op === 'ends_with' && !str.endsWith(s1)) matches = false;
                            else if (op === 'is_empty' && cellVal.raw !== '') matches = false;
                            else if (op === 'is_not_empty' && cellVal.raw === '') matches = false;
                        }

                        if (!matches) break;
                    }
                }

                if (matches) {
                    row.tr.classList.remove('hidden');
                    visibleCount++;
                } else {
                    row.tr.classList.add('hidden');
                }
            });

            if (this.emptyRow) {
                this.emptyRow.classList.toggle('hidden', visibleCount > 0 || this.rows.length === 0);
            }

            this.updateHeaderHighlights();
            this.renderBadges();
            this.updateCounter(visibleCount, this.rows.length);

            this.table.dispatchEvent(new CustomEvent('excel-filter-applied', {
                bubbles: true,
                detail: {
                    table: this.table,
                    visibleRows: this.rows.filter(r => !r.tr.classList.contains('hidden')),
                    visibleCount,
                    totalCount: this.rows.length,
                    activeFilters: this.activeFilters
                }
            }));
        }

        renderBadges() {
            if (!this.badgesList) return;
            this.badgesList.innerHTML = '';

            const filterKeys = Object.keys(this.activeFilters);
            const hasFilters = filterKeys.length > 0;

            this.badgeBar.classList.toggle('hidden', !hasFilters);

            filterKeys.forEach(colIdxStr => {
                const colIdx = parseInt(colIdxStr, 10);
                const filter = this.activeFilters[colIdx];
                const colMeta = this.columns[colIdx];
                const colTitle = colMeta ? colMeta.title : `Cột ${colIdx + 1}`;

                let labelText = '';
                if (filter.type === 'value') {
                    const vals = Array.from(filter.values);
                    if (vals.length <= 2) {
                        labelText = vals.join(', ');
                    } else {
                        labelText = `${vals[0]}, +${vals.length - 1} mục`;
                    }
                } else if (filter.type === 'condition') {
                    const { op, val1, val2 } = filter.condition;
                    if (op === 'gt_0') labelText = '> 0';
                    else if (op === 'eq_0') labelText = '= 0';
                    else if (op === 'between') labelText = `${val1} → ${val2}`;
                    else if (op === 'contains') labelText = `chứa "${val1}"`;
                    else labelText = `${op} ${val1}`;
                }

                const badge = document.createElement('span');
                badge.className = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-900 border border-blue-300 shadow-2xs';
                badge.innerHTML = `
                    <span><b>${colTitle}:</b> ${labelText}</span>
                    <button type="button" class="text-blue-700 hover:text-red-700 ml-0.5 focus:outline-none" title="Xóa lọc cột này">
                        <i class="fas fa-times-circle"></i>
                    </button>
                `;
                badge.querySelector('button').onclick = () => {
                    this.clearColumnFilter(colIdx);
                };
                this.badgesList.appendChild(badge);
            });

            if (this.isServer) {
                const visibleTrs = this.tbody.querySelectorAll('tr:not(.excel-empty-filter-row)').length;
                this.updateCounter(visibleTrs, null);
            }
        }

        updateCounter(visible, total) {
            if (this.counterText) {
                if (this.isServer && (total === null || total === undefined)) {
                    this.counterText.innerHTML = `Hiển thị: <b>${visible}</b> dòng trên trang`;
                } else {
                    this.counterText.innerHTML = `Hiển thị: <b>${visible}</b> / <b>${total}</b> dòng`;
                }
            }
        }

        clearAllFilters() {
            if (this.isServer) {
                const url = new URL(window.location.href);
                const keysToDelete = [];
                for (const key of url.searchParams.keys()) {
                    if (key.startsWith('col_filter') || key.startsWith('col_sort') || key.startsWith('col_dir')) {
                        keysToDelete.push(key);
                    }
                }
                keysToDelete.forEach(k => url.searchParams.delete(k));
                url.searchParams.delete('page');
                this.loadServerData(url.toString(), true);
                return;
            }

            this.activeFilters = {};
            this.activeSort = null;
            this.applyFilters();
        }

        clearColumnFilter(colIndex) {
            const colMeta = this.columns[colIndex];
            if (!colMeta) return;

            if (this.isServer) {
                const url = new URL(window.location.href);
                const colKey = colMeta.key;
                const keysToDelete = [];
                for (const key of url.searchParams.keys()) {
                    if (key.includes(`[${colKey}]`)) {
                        keysToDelete.push(key);
                    }
                }
                keysToDelete.forEach(k => url.searchParams.delete(k));
                url.searchParams.delete('page');
                this.loadServerData(url.toString(), true);
                return;
            }

            delete this.activeFilters[colIndex];
            this.applyFilters();
        }
    }

    function closeGlobalPopover() {
        if (popoverElement) {
            popoverElement.classList.add('hidden');
        }
        currentColumn = null;
    }

    function updatePopoverPosition() {
        if (!popoverElement || popoverElement.classList.contains('hidden') || !currentColumn) return;
        const triggerBtn = currentColumn.triggerBtn;
        if (!triggerBtn || !document.body.contains(triggerBtn)) {
            closeGlobalPopover();
            return;
        }

        const rect = triggerBtn.getBoundingClientRect();

        if (rect.bottom < 40 || rect.top > window.innerHeight - 30) {
            closeGlobalPopover();
            return;
        }

        const popoverWidth = 320;
        let left = rect.left;
        if (left + popoverWidth > window.innerWidth - 12) {
            left = Math.max(12, rect.right - popoverWidth);
        }
        if (left + popoverWidth > window.innerWidth - 12) {
            left = window.innerWidth - popoverWidth - 12;
        }
        if (left < 12) left = 12;

        const spaceBelow = window.innerHeight - rect.bottom - 16;
        const spaceAbove = rect.top - 16;

        let top = 0;
        let maxHeight = 520;

        if (spaceBelow >= 280 || spaceBelow >= spaceAbove) {
            top = rect.bottom + 4;
            maxHeight = Math.max(200, spaceBelow);
        } else {
            const popoverHeight = Math.min(480, popoverElement.offsetHeight || 380);
            top = Math.max(12, rect.top - popoverHeight - 4);
            maxHeight = Math.max(200, spaceAbove);
        }

        popoverElement.style.left = `${Math.round(left)}px`;
        popoverElement.style.top = `${Math.round(top)}px`;
        popoverElement.style.maxHeight = `${Math.round(maxHeight)}px`;

        const listContainer = document.getElementById('filterValueListContainer');
        if (listContainer) {
            const availableForList = maxHeight - 260;
            listContainer.style.maxHeight = `${Math.max(100, Math.min(200, availableForList))}px`;
        }
    }

    /**
     * Singleton Popover Management
     */
    function initGlobalPopover() {
        popoverElement = document.getElementById('excelColumnFilterPopover');
        if (!popoverElement) return;

        const closeBtn = document.getElementById('closeFilterPopoverBtn');
        const cancelBtn = document.getElementById('btnCancelFilter');
        const applyBtn = document.getElementById('btnApplyFilter');
        const clearColBtn = document.getElementById('btnClearColumnFilter');
        const btnSortAsc = document.getElementById('btnSortAsc');
        const btnSortDesc = document.getElementById('btnSortDesc');
        const tabBtnFilterValue = document.getElementById('tabBtnFilterValue');
        const tabBtnFilterCondition = document.getElementById('tabBtnFilterCondition');
        const filterValueSearchInput = document.getElementById('filterValueSearchInput');
        const btnSelectAllValues = document.getElementById('btnSelectAllValues');
        const btnInvertSelectValues = document.getElementById('btnInvertSelectValues');
        const btnClearSelectValues = document.getElementById('btnClearSelectValues');
        const filterConditionOperator = document.getElementById('filterConditionOperator');

        function close() {
            closeGlobalPopover();
        }

        if (closeBtn) closeBtn.onclick = closeGlobalPopover;
        if (cancelBtn) cancelBtn.onclick = closeGlobalPopover;

        document.addEventListener('click', function (e) {
            if (popoverElement && !popoverElement.classList.contains('hidden')) {
                if (!popoverElement.contains(e.target) && !e.target.closest('.excel-col-filter-btn')) {
                    closeGlobalPopover();
                }
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && popoverElement && !popoverElement.classList.contains('hidden')) {
                closeGlobalPopover();
            }
        });

        document.addEventListener('scroll', function (e) {
            if (popoverElement && !popoverElement.classList.contains('hidden')) {
                if (popoverElement.contains(e.target)) return;
                updatePopoverPosition();
            }
        }, { capture: true, passive: true });

        window.addEventListener('resize', updatePopoverPosition, { passive: true });

        if (tabBtnFilterValue && tabBtnFilterCondition) {
            tabBtnFilterValue.onclick = () => switchPopoverSubtab('value');
            tabBtnFilterCondition.onclick = () => switchPopoverSubtab('condition');
        }

        let searchDebounceTimer = null;
        if (filterValueSearchInput) {
            filterValueSearchInput.oninput = function () {
                const query = this.value.toLowerCase().trim();
                const container = document.getElementById('filterValueListContainer');
                if (!container) return;

                if (currentColumn && currentColumn.tableInstance.isServer) {
                    clearTimeout(searchDebounceTimer);
                    searchDebounceTimer = setTimeout(() => {
                        populateValuesServer(currentColumn.tableInstance, currentColumn.colIndex, currentColumn.colKey, query);
                    }, 300);
                } else {
                    const items = container.querySelectorAll('.value-item');
                    items.forEach(item => {
                        const text = item.dataset.value || '';
                        item.classList.toggle('hidden', !text.includes(query));
                    });
                }
            };
        }

        if (btnSelectAllValues) {
            btnSelectAllValues.onclick = () => {
                const container = document.getElementById('filterValueListContainer');
                if (!container) return;
                container.querySelectorAll('.value-item:not(.hidden) .val-checkbox').forEach(cb => cb.checked = true);
            };
        }
        if (btnClearSelectValues) {
            btnClearSelectValues.onclick = () => {
                const container = document.getElementById('filterValueListContainer');
                if (!container) return;
                container.querySelectorAll('.value-item:not(.hidden) .val-checkbox').forEach(cb => cb.checked = false);
            };
        }
        if (btnInvertSelectValues) {
            btnInvertSelectValues.onclick = () => {
                const container = document.getElementById('filterValueListContainer');
                if (!container) return;
                container.querySelectorAll('.value-item:not(.hidden) .val-checkbox').forEach(cb => cb.checked = !cb.checked);
            };
        }

        if (filterConditionOperator) {
            filterConditionOperator.onchange = updateConditionInputsVisibility;
        }

        if (btnSortAsc) {
            btnSortAsc.onclick = () => {
                if (!currentColumn) return;
                currentColumn.tableInstance.applySort(currentColumn.colIndex, 'asc');
                close();
            };
        }
        if (btnSortDesc) {
            btnSortDesc.onclick = () => {
                if (!currentColumn) return;
                currentColumn.tableInstance.applySort(currentColumn.colIndex, 'desc');
                close();
            };
        }

        if (clearColBtn) {
            clearColBtn.onclick = () => {
                if (!currentColumn) return;
                currentColumn.tableInstance.clearColumnFilter(currentColumn.colIndex);
                close();
            };
        }

        if (applyBtn) {
            applyBtn.onclick = () => {
                if (!currentColumn) return;
                const { tableInstance, colIndex, colKey, colTitle, colType } = currentColumn;
                const isConditionTab = !document.getElementById('filterTabConditionContent').classList.contains('hidden');

                if (tableInstance.isServer) {
                    const url = new URL(window.location.href);

                    if (isConditionTab) {
                        const op = filterConditionOperator.value;
                        const val1 = (document.getElementById('filterConditionValue1')?.value || '').trim();
                        const val2 = (document.getElementById('filterConditionValue2')?.value || '').trim();

                        // Clear value filters for this column
                        for (const key of Array.from(url.searchParams.keys())) {
                            if (key === `col_filter[${colKey}]` || key === `col_filter[${colKey}][]`) {
                                url.searchParams.delete(key);
                            }
                        }

                        if (op === 'all') {
                            url.searchParams.delete(`col_filter_op[${colKey}]`);
                            url.searchParams.delete(`col_filter_val[${colKey}]`);
                            url.searchParams.delete(`col_filter_val2[${colKey}]`);
                        } else {
                            url.searchParams.set(`col_filter_op[${colKey}]`, op);
                            url.searchParams.set(`col_filter_val[${colKey}]`, val1);
                            if (val2) {
                                url.searchParams.set(`col_filter_val2[${colKey}]`, val2);
                            } else {
                                url.searchParams.delete(`col_filter_val2[${colKey}]`);
                            }
                        }
                    } else {
                        const container = document.getElementById('filterValueListContainer');
                        const checkedBoxes = Array.from(container.querySelectorAll('.val-checkbox:checked'));
                        const allBoxes = Array.from(container.querySelectorAll('.val-checkbox'));

                        // Clear condition filters for this column
                        url.searchParams.delete(`col_filter_op[${colKey}]`);
                        url.searchParams.delete(`col_filter_val[${colKey}]`);
                        url.searchParams.delete(`col_filter_val2[${colKey}]`);

                        // Clear value filters for this column
                        for (const key of Array.from(url.searchParams.keys())) {
                            if (key === `col_filter[${colKey}]` || key === `col_filter[${colKey}][]`) {
                                url.searchParams.delete(key);
                            }
                        }

                        if (checkedBoxes.length > 0 && checkedBoxes.length < allBoxes.length) {
                            checkedBoxes.forEach(cb => {
                                url.searchParams.append(`col_filter[${colKey}][]`, decodeURIComponent(cb.value));
                            });
                        }
                    }

                    url.searchParams.delete('page');
                    close();
                    tableInstance.loadServerData(url.toString(), true);
                    return;
                }

                // Client-side filter
                if (isConditionTab) {
                    const op = filterConditionOperator.value;
                    const val1 = (document.getElementById('filterConditionValue1')?.value || '').trim();
                    const val2 = (document.getElementById('filterConditionValue2')?.value || '').trim();

                    if (op === 'all') {
                        delete tableInstance.activeFilters[colIndex];
                    } else {
                        tableInstance.activeFilters[colIndex] = {
                            colKey,
                            type: 'condition',
                            title: colTitle,
                            colType,
                            condition: { op, val1, val2 }
                        };
                    }
                } else {
                    const container = document.getElementById('filterValueListContainer');
                    const checkedBoxes = Array.from(container.querySelectorAll('.val-checkbox:checked'));
                    const allBoxes = Array.from(container.querySelectorAll('.val-checkbox'));

                    if (checkedBoxes.length === allBoxes.length || checkedBoxes.length === 0) {
                        delete tableInstance.activeFilters[colIndex];
                    } else {
                        const selectedValues = new Set(checkedBoxes.map(cb => decodeURIComponent(cb.value)));
                        tableInstance.activeFilters[colIndex] = {
                            colKey,
                            type: 'value',
                            title: colTitle,
                            colType,
                            values: selectedValues
                        };
                    }
                }

                close();
                tableInstance.applyFilters();
            };
        }
    }

    function switchPopoverSubtab(tab) {
        const tabBtnVal = document.getElementById('tabBtnFilterValue');
        const tabBtnCond = document.getElementById('tabBtnFilterCondition');
        const contentVal = document.getElementById('filterTabValueContent');
        const contentCond = document.getElementById('filterTabConditionContent');

        if (tab === 'value') {
            tabBtnVal?.classList.add('border-primary', 'text-primary', 'bg-white');
            tabBtnVal?.classList.remove('border-transparent', 'text-gray-600');
            tabBtnCond?.classList.remove('border-primary', 'text-primary', 'bg-white');
            tabBtnCond?.classList.add('border-transparent', 'text-gray-600');
            contentVal?.classList.remove('hidden');
            contentCond?.classList.add('hidden');
        } else {
            tabBtnCond?.classList.add('border-primary', 'text-primary', 'bg-white');
            tabBtnCond?.classList.remove('border-transparent', 'text-gray-600');
            tabBtnVal?.classList.remove('border-primary', 'text-primary', 'bg-white');
            tabBtnVal?.classList.add('border-transparent', 'text-gray-600');
            contentCond?.classList.remove('hidden');
            contentVal?.classList.add('hidden');
        }
    }

    function updateConditionInputsVisibility() {
        const opSelect = document.getElementById('filterConditionOperator');
        if (!opSelect) return;
        const op = opSelect.value;
        const hideInput1 = (op === 'all' || op === 'gt_0' || op === 'eq_0' || op === 'is_empty' || op === 'is_not_empty');
        const showInput2 = (op === 'between');

        const wrapper1 = document.getElementById('filterConditionInput1Wrapper');
        const wrapper2 = document.getElementById('filterConditionInput2Wrapper');

        if (wrapper1) wrapper1.classList.toggle('hidden', hideInput1);
        if (wrapper2) wrapper2.classList.toggle('hidden', !showInput2);
    }

    function openGlobalFilterPopover(tableInstance, colIndex, colKey, colTitle, colType, triggerBtn) {
        if (!popoverElement) initGlobalPopover();
        if (!popoverElement) return;

        if (!popoverElement.classList.contains('hidden') && currentColumn && currentColumn.colIndex === colIndex && currentColumn.tableInstance === tableInstance) {
            popoverElement.classList.add('hidden');
            currentColumn = null;
            return;
        }

        currentColumn = { tableInstance, colIndex, colKey, colTitle, colType, triggerBtn };

        const titleEl = document.getElementById('popoverColNameText');
        if (titleEl) titleEl.textContent = colTitle;

        const sortAscLabel = document.getElementById('sortAscLabel');
        const sortDescLabel = document.getElementById('sortDescLabel');
        if (colType === 'number') {
            if (sortAscLabel) sortAscLabel.textContent = 'Sắp xếp nhỏ → lớn (0 → 9)';
            if (sortDescLabel) sortDescLabel.textContent = 'Sắp xếp lớn → nhỏ (9 → 0)';
        } else if (colType === 'date') {
            if (sortAscLabel) sortAscLabel.textContent = 'Sắp xếp cũ nhất → mới nhất';
            if (sortDescLabel) sortDescLabel.textContent = 'Sắp xếp mới nhất → cũ nhất';
        } else {
            if (sortAscLabel) sortAscLabel.textContent = 'Sắp xếp tăng dần (A → Z)';
            if (sortDescLabel) sortDescLabel.textContent = 'Sắp xếp giảm dần (Z → A)';
        }

        switchPopoverSubtab('value');

        if (tableInstance.isServer) {
            populateValuesServer(tableInstance, colIndex, colKey, '');
        } else {
            populateValuesClient(tableInstance, colIndex, colType);
        }

        populateConditions(colType, tableInstance.activeFilters[colIndex]);

        popoverElement.classList.remove('hidden');
        updatePopoverPosition();
        requestAnimationFrame(updatePopoverPosition);

        const searchInput = document.getElementById('filterValueSearchInput');
        if (searchInput) {
            searchInput.value = '';
            setTimeout(() => searchInput.focus(), 50);
        }
    }

    function populateValuesServer(tableInstance, colIndex, colKey, searchTerm) {
        const container = document.getElementById('filterValueListContainer');
        if (!container) return;

        container.innerHTML = `
            <div class="p-4 text-center text-gray-500 text-xs">
                <i class="fas fa-spinner fa-spin mr-1.5 text-blue-600"></i>
                Đang tải dữ liệu từ máy chủ...
            </div>
        `;

        const module = tableInstance.module;
        const url = `/api/table-column-filter/values?module=${encodeURIComponent(module)}&column=${encodeURIComponent(colKey)}&search=${encodeURIComponent(searchTerm)}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                container.innerHTML = '';
                const items = data.values || [];

                if (items.length === 0) {
                    container.innerHTML = `<div class="p-3 text-center text-gray-400 text-xs">Không có dữ liệu</div>`;
                    return;
                }

                const currentFilter = tableInstance.activeFilters[colIndex];
                const selectedSet = (currentFilter && currentFilter.type === 'value') ? currentFilter.values : null;

                items.forEach(item => {
                    const val = item.value;
                    const count = item.count;
                    const isChecked = selectedSet === null || selectedSet.has(val);

                    const label = document.createElement('label');
                    label.className = 'flex items-center justify-between p-1 hover:bg-blue-50 rounded cursor-pointer text-gray-800 value-item';
                    label.dataset.value = val.toLowerCase();
                    label.innerHTML = `
                        <div class="flex items-center gap-2 truncate pr-2">
                            <input type="checkbox" value="${encodeURIComponent(val)}" class="val-checkbox rounded text-primary focus:ring-primary h-3.5 w-3.5" ${isChecked ? 'checked' : ''}>
                            <span class="truncate" title="${val}">${val}</span>
                        </div>
                        <span class="text-[10px] text-gray-400 font-mono flex-shrink-0">(${count})</span>
                    `;
                    container.appendChild(label);
                });
            })
            .catch(err => {
                console.error('Error fetching distinct values:', err);
                container.innerHTML = `<div class="p-3 text-center text-red-500 text-xs">Lỗi khi tải dữ liệu từ máy chủ</div>`;
            });
    }

    function populateValuesClient(tableInstance, colIndex, colType) {
        const container = document.getElementById('filterValueListContainer');
        if (!container) return;
        container.innerHTML = '';

        const counts = {};
        tableInstance.rows.forEach(r => {
            const cell = r.cells[colIndex];
            const text = cell ? cell.text : '(Trống / N/A)';
            counts[text] = (counts[text] || 0) + 1;
        });

        const sortedValues = Object.keys(counts).sort((a, b) => {
            if (colType === 'number') {
                const numA = parseFloat(a.replace(/[^\d.-]/g, '')) || 0;
                const numB = parseFloat(b.replace(/[^\d.-]/g, '')) || 0;
                return numA - numB;
            }
            return a.localeCompare(b, 'vi');
        });

        const currentFilter = tableInstance.activeFilters[colIndex];
        const selectedValuesSet = (currentFilter && currentFilter.type === 'value')
            ? currentFilter.values
            : null;

        sortedValues.forEach(val => {
            const count = counts[val];
            const isChecked = selectedValuesSet === null || selectedValuesSet.has(val);

            const label = document.createElement('label');
            label.className = 'flex items-center justify-between p-1 hover:bg-blue-50 rounded cursor-pointer text-gray-800 value-item';
            label.dataset.value = val.toLowerCase();
            label.innerHTML = `
                <div class="flex items-center gap-2 truncate pr-2">
                    <input type="checkbox" value="${encodeURIComponent(val)}" class="val-checkbox rounded text-primary focus:ring-primary h-3.5 w-3.5" ${isChecked ? 'checked' : ''}>
                    <span class="truncate" title="${val}">${val}</span>
                </div>
                <span class="text-[10px] text-gray-400 font-mono flex-shrink-0">(${count})</span>
            `;
            container.appendChild(label);
        });
    }

    function populateConditions(colType, savedFilter) {
        const opSelect = document.getElementById('filterConditionOperator');
        if (!opSelect) return;
        opSelect.innerHTML = '';

        let options = [];
        if (colType === 'number') {
            options = [
                { val: 'all', label: 'Tất cả (Không điều kiện)' },
                { val: 'gt_0', label: 'Lớn hơn 0 (> 0)' },
                { val: 'eq_0', label: 'Bằng 0 (= 0)' },
                { val: 'eq', label: 'Bằng (=)' },
                { val: 'neq', label: 'Khác (≠)' },
                { val: 'gt', label: 'Lớn hơn (>)' },
                { val: 'gte', label: 'Lớn hơn hoặc bằng (≥)' },
                { val: 'lt', label: 'Nhỏ hơn (<)' },
                { val: 'lte', label: 'Nhỏ hơn hoặc bằng (≤)' },
                { val: 'between', label: 'Trong khoảng (A → B)' }
            ];
        } else if (colType === 'date') {
            options = [
                { val: 'all', label: 'Tất cả (Không điều kiện)' },
                { val: 'before', label: 'Trước ngày' },
                { val: 'after', label: 'Sau ngày' },
                { val: 'between', label: 'Trong khoảng ngày' },
                { val: 'is_empty', label: 'Để trống (Chưa có ngày)' },
                { val: 'is_not_empty', label: 'Đã có ngày' }
            ];
        } else {
            options = [
                { val: 'all', label: 'Tất cả (Không điều kiện)' },
                { val: 'contains', label: 'Chứa ký tự...' },
                { val: 'not_contains', label: 'Không chứa...' },
                { val: 'equals', label: 'Chính xác bằng...' },
                { val: 'starts_with', label: 'Bắt đầu bằng...' },
                { val: 'ends_with', label: 'Kết thúc bằng...' },
                { val: 'is_empty', label: 'Ô trống' },
                { val: 'is_not_empty', label: 'Có dữ liệu' }
            ];
        }

        options.forEach(opt => {
            const el = document.createElement('option');
            el.value = opt.val;
            el.textContent = opt.label;
            opSelect.appendChild(el);
        });

        const input1 = document.getElementById('filterConditionValue1');
        const input2 = document.getElementById('filterConditionValue2');

        if (savedFilter && savedFilter.type === 'condition') {
            opSelect.value = savedFilter.condition.op || 'all';
            if (input1) input1.value = savedFilter.condition.val1 || '';
            if (input2) input2.value = savedFilter.condition.val2 || '';
        } else {
            opSelect.value = 'all';
            if (input1) input1.value = '';
            if (input2) input2.value = '';
        }

        updateConditionInputsVisibility();
    }

    /**
     * Auto scan and initialize tables with column filtering
     */
    function autoInitTables() {
        initGlobalPopover();

        const candidateTables = document.querySelectorAll(
            'table[data-filter-mode="server"], table[data-module], table.has-excel-filter, .overflow-x-auto > table'
        );

        candidateTables.forEach(table => {
            if (table.dataset.noExcelFilter !== undefined) return;
            if (table.closest('#excelColumnFilterPopover')) return;
            if (table.querySelector('tbody tr') === null && !table.dataset.filterMode) return;

            const existing = registeredTables.get(table.id);
            if (existing) return;

            new ExcelTableFilter(table);
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInitTables);
    } else {
        autoInitTables();
    }

    // Expose Global API
    window.ExcelTableFilter = {
        init: autoInitTables,
        getInstance: (tableId) => registeredTables.get(tableId),
        getAllInstances: () => Array.from(registeredTables.values()),
        closePopover: closeGlobalPopover
    };

})(window, document);
