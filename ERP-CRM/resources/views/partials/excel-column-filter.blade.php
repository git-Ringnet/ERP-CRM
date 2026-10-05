<!-- Global Excel Column Filter Popover (Singleton) -->
<div id="excelColumnFilterPopover" class="hidden fixed z-50 bg-white border border-gray-300 rounded-xl shadow-2xl w-80 text-xs font-sans select-none animate-fadeIn flex flex-col overflow-hidden max-h-[85vh]">
    <!-- Popover Header -->
    <div class="px-3 py-2 bg-gradient-to-r from-[#1a3a5c] to-[#2a5584] text-white rounded-t-xl flex items-center justify-between flex-shrink-0">
        <span class="font-bold flex items-center gap-1.5 truncate" id="popoverColTitle">
            <i class="fas fa-filter text-yellow-300 text-[11px]"></i>
            <span id="popoverColNameText">Lọc cột</span>
        </span>
        <button type="button" id="closeFilterPopoverBtn" class="text-white/80 hover:text-white p-0.5 rounded hover:bg-white/20 transition-colors">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Sort Section -->
    <div class="p-2 border-b border-gray-200 bg-gray-50/70 space-y-1 flex-shrink-0">
        <button type="button" id="btnSortAsc" class="w-full text-left px-2.5 py-1.5 rounded hover:bg-blue-50 hover:text-blue-700 flex items-center gap-2 font-medium text-gray-700 transition-colors">
            <i class="fas fa-arrow-down-a-z text-blue-600 w-4"></i>
            <span id="sortAscLabel">Sắp xếp tăng dần (A → Z)</span>
        </button>
        <button type="button" id="btnSortDesc" class="w-full text-left px-2.5 py-1.5 rounded hover:bg-blue-50 hover:text-blue-700 flex items-center gap-2 font-medium text-gray-700 transition-colors">
            <i class="fas fa-arrow-down-z-a text-blue-600 w-4"></i>
            <span id="sortDescLabel">Sắp xếp giảm dần (Z → A)</span>
        </button>
    </div>

    <!-- Filter Subtabs -->
    <div class="flex border-b border-gray-200 bg-gray-100 text-gray-600 font-semibold text-[11px] flex-shrink-0">
        <button type="button" id="tabBtnFilterValue" class="flex-1 py-1.5 text-center border-b-2 border-primary text-primary bg-white transition-colors">
            Lọc theo giá trị
        </button>
        <button type="button" id="tabBtnFilterCondition" class="flex-1 py-1.5 text-center border-b-2 border-transparent hover:text-gray-900 transition-colors">
            Lọc theo điều kiện
        </button>
    </div>

    <!-- Tab 1: Value Checkbox List -->
    <div id="filterTabValueContent" class="p-2.5 space-y-2 flex-1 min-h-0 overflow-y-auto">
        <!-- Search Inside Values -->
        <div class="relative flex-shrink-0">
            <i class="fas fa-search absolute left-2.5 top-2 text-gray-400 text-[10px]"></i>
            <input type="text" id="filterValueSearchInput" placeholder="Tìm kiếm trong danh sách..."
                class="w-full pl-7 pr-2.5 py-1 text-xs border border-gray-300 rounded-md focus:ring-1 focus:ring-primary focus:border-primary">
        </div>

        <!-- Selection Shortcuts -->
        <div class="flex items-center justify-between text-[11px] text-blue-600 font-medium px-1 flex-shrink-0">
            <button type="button" id="btnSelectAllValues" class="hover:underline">Chọn tất cả</button>
            <span class="text-gray-300">|</span>
            <button type="button" id="btnInvertSelectValues" class="hover:underline">Đảo chọn</button>
            <span class="text-gray-300">|</span>
            <button type="button" id="btnClearSelectValues" class="hover:underline text-gray-500">Bỏ chọn</button>
        </div>

        <!-- Scrollable Value Checkboxes List -->
        <div id="filterValueListContainer" class="max-h-40 overflow-y-auto border border-gray-200 rounded-md p-1.5 space-y-1 bg-gray-50/50">
            <!-- Checkboxes populated by JS -->
        </div>
    </div>

    <!-- Tab 2: Condition / Number / Text Filter -->
    <div id="filterTabConditionContent" class="p-2.5 space-y-2.5 hidden flex-1 min-h-0 overflow-y-auto">
        <div>
            <label class="block text-[11px] font-semibold text-gray-700 mb-1">Loại điều kiện:</label>
            <select id="filterConditionOperator" class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md bg-white focus:ring-1 focus:ring-primary">
                <!-- Populated dynamically based on column type -->
            </select>
        </div>

        <div id="filterConditionInput1Wrapper">
            <label class="block text-[11px] font-semibold text-gray-700 mb-1" id="filterConditionLabel1">Giá trị:</label>
            <input type="text" id="filterConditionValue1" placeholder="Nhập giá trị lọc..."
                class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:ring-1 focus:ring-primary">
        </div>

        <div id="filterConditionInput2Wrapper" class="hidden">
            <label class="block text-[11px] font-semibold text-gray-700 mb-1">Đến giá trị:</label>
            <input type="text" id="filterConditionValue2" placeholder="Nhập giá trị đến..."
                class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:ring-1 focus:ring-primary">
        </div>
    </div>

    <!-- Popover Footer Actions -->
    <div class="px-3 py-2 bg-gray-100 rounded-b-xl border-t border-gray-200 flex items-center justify-between gap-2 flex-shrink-0">
        <button type="button" id="btnClearColumnFilter" class="px-2.5 py-1 text-gray-600 hover:text-red-700 hover:bg-white rounded border border-transparent hover:border-gray-300 font-semibold transition-colors">
            <i class="fas fa-trash-alt mr-1"></i>Xóa lọc
        </button>
        <div class="flex items-center gap-1.5">
            <button type="button" id="btnCancelFilter" class="px-3 py-1 bg-white border border-gray-300 text-gray-700 rounded hover:bg-gray-50 font-semibold transition-colors">
                Hủy
            </button>
            <button type="button" id="btnApplyFilter" class="px-3.5 py-1 bg-primary text-white rounded hover:bg-primary-dark font-bold shadow-xs transition-colors">
                Áp dụng (OK)
            </button>
        </div>
    </div>
</div>

<style>
    .excel-col-filter-btn.is-filtered {
        background-color: #f59e0b !important;
        color: #0f172a !important;
        font-weight: bold !important;
        box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.4) !important;
        border-radius: 4px;
    }
    @keyframes excelFadeIn {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeIn {
        animation: excelFadeIn 0.15s ease-out forwards;
    }
</style>

<script src="{{ asset('js/excel-table-filter.js') }}"></script>
