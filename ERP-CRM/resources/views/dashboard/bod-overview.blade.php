@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<style>
    [x-cloak] { display: none !important; }
</style>
<div class="space-y-6 pb-12" x-data="bodDashboardData()">
    <!-- 1. STICKY HEADER FILTER BAR -->
    <div class="sticky top-0 z-40 bg-white/95 backdrop-blur-md shadow-md rounded-2xl p-4 border border-slate-200/80 transition-all duration-300">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <div class="p-2 bg-indigo-50 text-indigo-600 rounded-xl">
                    <i class="fas fa-sliders-h text-lg"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-800">Bộ Lọc Cố Định</h3>
                    <p class="text-xs text-slate-500">Áp dụng tức thì cho toàn bộ chỉ số</p>
                </div>
            </div>

            <!-- Filter Controls Grid -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Team / Sales Filter (Hierarchical) -->
                <div class="relative">
                    <select x-model="filters.team" @change="applyFilters()" class="px-3 py-2 text-xs font-medium text-slate-700 bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all">
                        <option value="">-- Toàn Công Ty --</option>
                        <template x-for="teamItem in filterOptions.teams" :key="teamItem">
                            <option :value="teamItem" x-text="teamItem"></option>
                        </template>
                    </select>
                </div>

                <!-- Sales PIC Filter -->
                <div class="relative">
                    <select x-model="filters.sales_id" @change="applyFilters()" class="px-3 py-2 text-xs font-medium text-slate-700 bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all">
                        <option value="">-- Tất cả Sales --</option>
                        <template x-for="sales in filterOptions.sales_users" :key="sales.id">
                            <option :value="sales.id" x-text="sales.name + ' (' + (sales.employee_code || 'Sales') + ')'"></option>
                        </template>
                    </select>
                </div>

                <!-- Customer Filter -->
                <div class="relative">
                    <select x-model="filters.customer_id" @change="applyFilters()" class="px-3 py-2 text-xs font-medium text-slate-700 bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all max-w-[180px]">
                        <option value="">-- Tất cả Khách Hàng --</option>
                        <template x-for="cust in filterOptions.customers" :key="cust.id">
                            <option :value="cust.id" x-text="cust.name + (cust.tax_code ? ' (' + cust.tax_code + ')' : '')"></option>
                        </template>
                    </select>
                </div>

                <!-- Vendor Filter -->
                <div class="relative">
                    <select x-model="filters.vendor_id" @change="applyFilters()" class="px-3 py-2 text-xs font-medium text-slate-700 bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all">
                        <option value="">-- Tất cả Vendor / Hãng --</option>
                        <template x-for="v in filterOptions.vendors" :key="v.id">
                            <option :value="v.id" x-text="v.name + (v.code ? ' (' + v.code + ')' : '')"></option>
                        </template>
                    </select>
                </div>

                <!-- Model / Part Number Search -->
                <div class="relative">
                    <input type="text" x-model.debounce.500ms="filters.model_code" @input="applyFilters()" placeholder="Tìm Model / Part Number..." class="px-3 py-2 pl-8 text-xs font-medium text-slate-700 bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all w-40">
                    <i class="fas fa-search absolute left-3 top-2.5 text-xs text-slate-400"></i>
                </div>

                <!-- Period Type Filter -->
                <div class="relative">
                    <select x-model="filters.period_type" @change="applyFilters()" class="px-3 py-2 text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all">
                        <option value="today">Hôm nay</option>
                        <option value="week">Tuần này</option>
                        <option value="month" selected>Tháng này</option>
                        <option value="quarter">Quý này</option>
                        <option value="year">Năm nay</option>
                    </select>
                </div>

                <!-- Deal Type / Class Filter -->
                <div class="relative">
                    <select x-model="filters.deal_type" @change="applyFilters()" class="px-3 py-2 text-xs font-medium text-slate-700 bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all">
                        <option value="">-- Tất cả Loại Hàng --</option>
                        <option value="runrate">Runrate (Thương mại)</option>
                        <option value="project">Dự án (Project)</option>
                        <option value="hang_r">Hàng R (Bảo hành)</option>
                        <option value="poc">POC (Hàng Demo)</option>
                    </select>
                </div>

                <!-- Reset Button -->
                <button @click="resetFilters()" class="p-2 text-xs text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-all" title="Xóa bộ lọc">
                    <i class="fas fa-undo"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. BOTTLENECK ALERT TOWER (CẢNH BÁO ĐIỂM NGHẼN) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- SLA Overdue Alert Card -->
        <div @click="openDrillDown('sla_overdue')" class="group relative overflow-hidden bg-gradient-to-br from-rose-50 to-red-100/60 p-4 rounded-2xl border border-rose-200/80 shadow-sm hover:shadow-md transition-all cursor-pointer">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Dự án Quá SLA</span>
                    <h4 class="text-2xl font-black text-slate-800 mt-1" x-text="bottlenecks.total_sla_overdue">0</h4>
                    <p class="text-xs text-slate-600 mt-1">
                        <span class="font-semibold text-rose-700" x-text="bottlenecks.pm_sla_overdue">0</span> PM trễ &bull;
                        <span class="font-semibold text-rose-700" x-text="bottlenecks.vendor_sla_overdue">0</span> Hãng trễ
                    </p>
                </div>
                <div class="p-3 bg-rose-500 text-white rounded-2xl shadow-rose-200 shadow-lg group-hover:scale-110 transition-transform">
                    <i class="fas fa-exclamation-triangle text-xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs font-semibold text-rose-700 group-hover:underline">
                <span>Xem chi tiết danh sách</span>
                <i class="fas fa-arrow-right ml-1 text-[10px]"></i>
            </div>
        </div>

        <!-- Aged Inventory Alert Card -->
        <div @click="openDrillDown('aged_inventory')" class="group relative overflow-hidden bg-gradient-to-br from-amber-50 to-yellow-100/60 p-4 rounded-2xl border border-amber-200/80 shadow-sm hover:shadow-md transition-all cursor-pointer">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-700">Hàng Tồn Kho > 90 Ngày</span>
                    <h4 class="text-2xl font-black text-slate-800 mt-1" x-text="bottlenecks.aged_inventory_count">0</h4>
                    <p class="text-xs text-amber-800 font-medium mt-1">
                        Giá trị: <span class="font-bold text-slate-900" x-text="formatCurrency(bottlenecks.aged_inventory_value)">0 ₫</span>
                    </p>
                </div>
                <div class="p-3 bg-amber-500 text-white rounded-2xl shadow-amber-200 shadow-lg group-hover:scale-110 transition-transform">
                    <i class="fas fa-boxes text-xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs font-semibold text-amber-800 group-hover:underline">
                <span>Kiểm tra danh sách tồn lâu</span>
                <i class="fas fa-arrow-right ml-1 text-[10px]"></i>
            </div>
        </div>

        <!-- Marketing Overrun Alert Card -->
        <div @click="openDrillDown('mkt_overrun')" class="group relative overflow-hidden bg-gradient-to-br from-orange-50 to-amber-100/60 p-4 rounded-2xl border border-orange-200/80 shadow-sm hover:shadow-md transition-all cursor-pointer">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-orange-700">MKT Vượt Ngân Sách</span>
                    <h4 class="text-2xl font-black text-slate-800 mt-1" x-text="bottlenecks.mkt_overrun_count">0</h4>
                    <p class="text-xs text-slate-600 mt-1">Chi phí thực tế > Dự toán ban đầu</p>
                </div>
                <div class="p-3 bg-orange-500 text-white rounded-2xl shadow-orange-200 shadow-lg group-hover:scale-110 transition-transform">
                    <i class="fas fa-chart-line-down text-xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs font-semibold text-orange-800 group-hover:underline">
                <span>Xem sự kiện vượt chi</span>
                <i class="fas fa-arrow-right ml-1 text-[10px]"></i>
            </div>
        </div>

        <!-- Project Expiry Warning Alert Card -->
        <div class="group relative overflow-hidden bg-gradient-to-br from-sky-50 to-blue-100/60 p-4 rounded-2xl border border-sky-200/80 shadow-sm hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-sky-700">Dự án Sắp Hết Hạn Update</span>
                    <h4 class="text-2xl font-black text-slate-800 mt-1" x-text="bottlenecks.nearing_expiry_count">0</h4>
                    <p class="text-xs text-slate-600 mt-1">Chưa cập nhật > 60 ngày</p>
                </div>
                <div class="p-3 bg-sky-500 text-white rounded-2xl shadow-sky-200 shadow-lg group-hover:scale-110 transition-transform">
                    <i class="fas fa-clock text-xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs font-semibold text-sky-700">
                <span>Cần nhắc nhở Sales PIC</span>
            </div>
        </div>
    </div>

    <!-- 3. MAIN TAB NAVIGATION BAR -->
    <div class="flex border-b border-slate-200 space-x-2 bg-white p-2 rounded-2xl shadow-sm">
        <button @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100'" class="px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2">
            <i class="fas fa-th-large"></i> Tổng Quan 360° & KPI Matrix
        </button>
        <button @click="activeTab = 'pipeline'" :class="activeTab === 'pipeline' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100'" class="px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2">
            <i class="fas fa-project-diagram"></i> Pipeline & ĐKDA
        </button>
        <button @click="activeTab = 'inventory'" :class="activeTab === 'inventory' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100'" class="px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2">
            <i class="fas fa-warehouse"></i> Kho & Hàng Hóa
        </button>
        <button @click="activeTab = 'marketing'" :class="activeTab === 'marketing' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100'" class="px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2">
            <i class="fas fa-bullhorn"></i> Marketing & ROI
        </button>
    </div>

    <!-- TAB 1: EXECUTIVE OVERVIEW & KPI MATRIX -->
    <div x-show="activeTab === 'overview'" x-cloak class="space-y-6">
        <!-- CONTEXTUAL 360 CARD (IF ENTITY FILTERED) -->
        <template x-if="cross_view_360">
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 rounded-3xl shadow-xl border border-indigo-800/40 relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-indigo-500/20 text-indigo-300 rounded-2xl border border-indigo-400/30">
                            <i class="fas fa-user-shield text-2xl"></i>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-indigo-300 uppercase tracking-widest">Hồ Sơ Tổng Hợp 360°</span>
                            <h3 class="text-2xl font-black text-white" x-text="cross_view_360.name || cross_view_360.company_name || cross_view_360.model_code"></h3>
                            <p class="text-xs text-slate-300 mt-0.5" x-text="'Đang xem chi tiết thông tin lọc theo ' + cross_view_360.type"></p>
                        </div>
                    </div>
                    <button @click="resetFilters()" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-xs font-bold rounded-xl backdrop-blur-sm transition-all">
                        Đóng Hồ Sơ 360°
                    </button>
                </div>
            </div>
        </template>

        <!-- KPI SUMMARY CARDS MATRIX (SECTION D) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Pipeline Group Card -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center justify-between text-slate-500 mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Kinh Doanh & Pipeline</span>
                    <i class="fas fa-chart-pie text-indigo-500"></i>
                </div>
                <h3 class="text-2xl font-extrabold text-slate-800" x-text="formatCurrency(pipeline.total_pipeline_value)">0 ₫</h3>
                <div class="mt-3 pt-3 border-t border-slate-100 text-xs space-y-1.5 text-slate-600">
                    <div class="flex justify-between">
                        <span>Tỷ lệ Closed Won:</span>
                        <span class="font-bold text-emerald-600" x-text="pipeline.win_rate + '%'">0%</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Số dự án Active:</span>
                        <span class="font-bold text-slate-800" x-text="pipeline.total_active_count">0</span>
                    </div>
                </div>
            </div>

            <!-- Inventory Group Card -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center justify-between text-slate-500 mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-teal-600">Kho & Giá Trị Tồn</span>
                    <i class="fas fa-boxes text-teal-500"></i>
                </div>
                <h3 class="text-2xl font-extrabold text-slate-800" x-text="formatCurrency(inventory.total_valuation)">0 ₫</h3>
                <div class="mt-3 pt-3 border-t border-slate-100 text-xs space-y-1.5 text-slate-600">
                    <div class="flex justify-between">
                        <span>Tổng SL tồn khả dụng:</span>
                        <span class="font-bold text-slate-800" x-text="inventory.total_stock">0</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Số đơn xuất trong kỳ:</span>
                        <span class="font-bold text-teal-600" x-text="inventory.export_count">0</span>
                    </div>
                </div>
            </div>

            <!-- Marketing Group Card -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center justify-between text-slate-500 mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-purple-600">Marketing & Chi Phí</span>
                    <i class="fas fa-bullhorn text-purple-500"></i>
                </div>
                <h3 class="text-2xl font-extrabold text-slate-800" x-text="formatCurrency(marketing.total_actual_cost)">0 ₫</h3>
                <div class="mt-3 pt-3 border-t border-slate-100 text-xs space-y-1.5 text-slate-600">
                    <div class="flex justify-between">
                        <span>Tỷ lệ Ticket đúng hạn:</span>
                        <span class="font-bold text-purple-600" x-text="marketing.ticket_sla_rate + '%'">100%</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Sự kiện triển khai:</span>
                        <span class="font-bold text-slate-800" x-text="marketing.active_events_count">0</span>
                    </div>
                </div>
            </div>

            <!-- Top Performers Summary Card -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center justify-between text-slate-500 mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-600">Top Vendor & Khách Hàng</span>
                    <i class="fas fa-award text-amber-500"></i>
                </div>
                <h3 class="text-2xl font-extrabold text-slate-800" x-text="(pipeline.top_vendors ? pipeline.top_vendors.length : 0) + ' Hãng Đối Tác'"></h3>
                <div class="mt-3 pt-3 border-t border-slate-100 text-xs space-y-1.5 text-slate-600">
                    <div class="flex justify-between">
                        <span>Top Vendor:</span>
                        <span class="font-bold text-amber-700" x-text="pipeline.top_vendors && pipeline.top_vendors[0] ? pipeline.top_vendors[0].vendor_name : 'N/A'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Top Customer:</span>
                        <span class="font-bold text-slate-800" x-text="pipeline.top_customers && pipeline.top_customers[0] ? pipeline.top_customers[0].customer_name : 'N/A'"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: PIPELINE & ĐKDA -->
    <div x-show="activeTab === 'pipeline'" x-cloak class="space-y-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Trạng Thái Đăng Ký Dự Án (ĐKDA)</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                    <span class="text-xs text-slate-500 font-semibold">Closed Won (Thành công)</span>
                    <h4 class="text-xl font-bold text-emerald-600 mt-1" x-text="pipeline.closed_won_count">0</h4>
                </div>
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                    <span class="text-xs text-slate-500 font-semibold">Closed Lost (Thất bại)</span>
                    <h4 class="text-xl font-bold text-rose-600 mt-1" x-text="pipeline.closed_lost_count">0</h4>
                </div>
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                    <span class="text-xs text-slate-500 font-semibold">Expired (Hết hạn)</span>
                    <h4 class="text-xl font-bold text-amber-600 mt-1" x-text="pipeline.expired_count">0</h4>
                </div>
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                    <span class="text-xs text-slate-500 font-semibold">Duplicate (Trùng lặp)</span>
                    <h4 class="text-xl font-bold text-slate-600 mt-1" x-text="pipeline.duplicate_count">0</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: KHO & HÀNG HÓA -->
    <div x-show="activeTab === 'inventory'" x-cloak class="space-y-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Tình Trạng Tồn Kho & Sản Phẩm</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 bg-teal-50 rounded-xl border border-teal-200">
                    <span class="text-xs text-teal-700 font-semibold">Khả Dụng Tồn Kho</span>
                    <h4 class="text-2xl font-bold text-teal-900 mt-1" x-text="inventory.available_count">0</h4>
                </div>
                <div class="p-4 bg-indigo-50 rounded-xl border border-indigo-200">
                    <span class="text-xs text-indigo-700 font-semibold">Đang Giữ Cho Dự Án</span>
                    <h4 class="text-2xl font-bold text-indigo-900 mt-1" x-text="inventory.reserved_count">0</h4>
                </div>
                <div class="p-4 bg-amber-50 rounded-xl border border-amber-200">
                    <span class="text-xs text-amber-700 font-semibold">Đang Mượn Demo / POC</span>
                    <h4 class="text-2xl font-bold text-amber-900 mt-1" x-text="inventory.borrowed_count">0</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 4: MARKETING -->
    <div x-show="activeTab === 'marketing'" x-cloak class="space-y-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Chi Phí Marketing & Tỷ Lệ Hoàn Thành Ticket</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-5 bg-purple-50 rounded-2xl border border-purple-200">
                    <span class="text-xs font-bold text-purple-700 uppercase">Ngân Sách Được Duyệt</span>
                    <h4 class="text-3xl font-black text-purple-900 mt-1" x-text="formatCurrency(marketing.total_budget)">0 ₫</h4>
                    <p class="text-xs text-purple-800 mt-2">Thực tế đã chi: <span class="font-bold" x-text="formatCurrency(marketing.total_actual_cost)">0 ₫</span></p>
                </div>
                <div class="p-5 bg-emerald-50 rounded-2xl border border-emerald-200">
                    <span class="text-xs font-bold text-emerald-700 uppercase">Tỷ Lệ Ticket Đúng Hạn</span>
                    <h4 class="text-3xl font-black text-emerald-900 mt-1" x-text="marketing.ticket_sla_rate + '%'">100%</h4>
                    <p class="text-xs text-emerald-800 mt-2">Hoàn thành đúng hạn: <span class="font-bold" x-text="marketing.on_time_tickets + '/' + marketing.total_tickets"></span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. DRILL-DOWN MODAL DRAWER -->
    <div x-show="drillDownModal.open" class="fixed inset-0 z-50 overflow-hidden" x-cloak>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="drillDownModal.open = false"></div>
        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div class="w-screen max-w-2xl bg-white shadow-2xl border-l border-slate-200 p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-800" x-text="drillDownModal.title">Chi tiết Cảnh báo</h3>
                        <button @click="drillDownModal.open = false" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>

                    <div class="mt-4 overflow-y-auto max-h-[calc(100vh-180px)] space-y-3">
                        <template x-if="drillDownModal.loading">
                            <div class="text-center py-10 text-slate-400">
                                <i class="fas fa-circle-notch fa-spin text-2xl"></i>
                                <p class="text-xs mt-2">Đang tải dữ liệu chi tiết...</p>
                            </div>
                        </template>

                        <template x-if="!drillDownModal.loading && drillDownModal.items.length === 0">
                            <div class="text-center py-10 text-slate-400">
                                <i class="fas fa-check-circle text-3xl text-emerald-500 mb-2"></i>
                                <p class="text-sm font-semibold text-slate-600">Không có bản ghi nào bị ảnh hưởng!</p>
                            </div>
                        </template>

                        <template x-for="item in drillDownModal.items" :key="item.id">
                            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs space-y-1 hover:border-indigo-300 transition-all">
                                <div class="flex justify-between font-bold text-slate-800">
                                    <span x-text="item.name || item.title || item.product_name || item.code"></span>
                                    <span class="text-indigo-600" x-text="item.code || item.product_code || ''"></span>
                                </div>
                                <div class="text-slate-500 flex justify-between">
                                    <span x-text="'Trạng thái: ' + (item.registration_status || item.status || 'N/A')"></span>
                                    <span x-text="item.budget ? formatCurrency(item.budget) : (item.stock ? 'Số lượng: ' + item.stock : '')"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200 flex justify-end">
                    <button @click="drillDownModal.open = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                        Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function bodDashboardData() {
    return {
        activeTab: 'overview',
        filters: {
            period_type: '{{ $filters["period_type"] ?? "month" }}',
            date_from: '{{ $filters["date_from"] ?? "" }}',
            date_to: '{{ $filters["date_to"] ?? "" }}',
            team: '{{ $filters["team"] ?? "" }}',
            sales_id: '{{ $filters["sales_id"] ?? "" }}',
            customer_id: '{{ $filters["customer_id"] ?? "" }}',
            vendor_id: '{{ $filters["vendor_id"] ?? "" }}',
            model_code: '{{ $filters["model_code"] ?? "" }}',
            deal_type: '{{ $filters["deal_type"] ?? "" }}',
        },
        filterOptions: @json($filter_options ?? []),
        bottlenecks: @json($bottlenecks ?? []),
        pipeline: @json($pipeline ?? []),
        inventory: @json($inventory ?? []),
        marketing: @json($marketing ?? []),
        kpi_matrix: @json($kpi_matrix ?? []),
        cross_view_360: @json($cross_view_360 ?? null),
        drillDownModal: {
            open: false,
            loading: false,
            title: '',
            items: []
        },

        applyFilters() {
            fetch('{{ route("dashboard.bod-filter") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(this.filters)
            })
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    this.bottlenecks = res.data.bottlenecks;
                    this.pipeline = res.data.pipeline;
                    this.inventory = res.data.inventory;
                    this.marketing = res.data.marketing;
                    this.kpi_matrix = res.data.kpi_matrix;
                    this.cross_view_360 = res.data.cross_view_360;
                }
            })
            .catch(err => console.error('Filter error:', err));
        },

        resetFilters() {
            this.filters = {
                period_type: 'month',
                date_from: '',
                date_to: '',
                team: '',
                sales_id: '',
                customer_id: '',
                vendor_id: '',
                model_code: '',
                deal_type: ''
            };
            this.applyFilters();
        },

        openDrillDown(type) {
            this.drillDownModal.open = true;
            this.drillDownModal.loading = true;
            this.drillDownModal.items = [];

            const params = new URLSearchParams({
                type: type,
                team: this.filters.team || '',
                sales_id: this.filters.sales_id || '',
                customer_id: this.filters.customer_id || '',
                vendor_id: this.filters.vendor_id || ''
            });

            fetch(`{{ route("dashboard.bod-drill-down") }}?${params.toString()}`)
                .then(res => res.json())
                .then(res => {
                    this.drillDownModal.loading = false;
                    if (res.success) {
                        this.drillDownModal.title = res.title;
                        this.drillDownModal.items = res.items || [];
                    }
                })
                .catch(err => {
                    this.drillDownModal.loading = false;
                    console.error(err);
                });
        },

        formatCurrency(amount) {
            if (!amount) return '0 ₫';
            return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
        }
    }
}
</script>
@endsection
