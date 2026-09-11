@extends('layouts.app')

@section('title', 'Dashboard Điều Hành BOD & Quản Lý')
@section('page-title', 'Dashboard Điều Hành')

@section('content')
    <style>
        [x-cloak] {
            display: none !important;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>

    <div class="space-y-6 pb-16" x-data="bodDashboardData()" x-init="init()">
        <!-- ========================================================================= -->
        <!-- 1. STICKY HEADER FILTER BAR (BỘ LỌC ĐIỀU HÀNH 2 TẦNG CHUYÊN NGHIỆP) -->
        <!-- ========================================================================= -->
        <div
            class="sticky top-0 z-40 bg-white/95 backdrop-blur-md shadow-md rounded-2xl p-5 border border-slate-200 transition-all duration-200 space-y-4">
            <!-- TOP ROW: TITLE & QUICK PERIOD / ACTIONS -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                <!-- Left Info -->
                <div class="flex items-center gap-3.5">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h2 class="text-base font-extrabold text-slate-900">Bộ Lọc Điều Hành BOD & Manager</h2>
                            <span x-show="loading"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-700 animate-pulse">
                                <svg class="animate-spin -ml-0.5 mr-1.5 h-3 w-3 text-indigo-700" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Đang tải dữ liệu...
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium">Theo dõi tức thì mọi thông số Deals, Đơn hàng bán,
                            Hiệu suất Sales và Tồn kho</p>
                        <p x-show="filterError" x-cloak class="mt-1 text-xs font-semibold text-rose-600" x-text="filterError"></p>
                    </div>
                </div>

                <!-- Right Controls: Period + Actions with Generous Padding -->
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Period Type Filter -->
                    <div class="relative">
                        <select x-model="filters.period_type" @change="onPeriodChange()"
                            class="px-4 py-2.5 text-xs font-bold text-indigo-900 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all cursor-pointer">
                            <option value="today">Hôm nay</option>
                            <option value="week">Tuần này</option>
                            <option value="month">Tháng này</option>
                            <option value="quarter">Quý này</option>
                            <option value="year">Năm nay</option>
                            <option value="custom">Tùy chọn ngày...</option>
                            <option value="all">Tất cả thời gian</option>
                        </select>
                    </div>

                    <!-- Custom Date Range Inputs (Visible when period_type === 'custom') -->
                    <template x-if="filters.period_type === 'custom'">
                        <div class="flex items-center gap-1.5 bg-slate-50 p-1.5 rounded-xl border border-slate-200">
                            <input type="date" x-model="filters.date_from" @change="applyFilters()"
                                class="px-2.5 py-1 text-xs text-slate-700 bg-white border border-slate-300 rounded-lg focus:ring-1 focus:ring-indigo-500">
                            <span class="text-xs text-slate-400 font-bold">-</span>
                            <input type="date" x-model="filters.date_to" @change="applyFilters()"
                                class="px-2.5 py-1 text-xs text-slate-700 bg-white border border-slate-300 rounded-lg focus:ring-1 focus:ring-indigo-500">
                        </div>
                    </template>

                    <!-- Apply Filter Button (Spacious padding, bold text & standout visual) -->
                    <button @click="applyFilters()"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-300 hover:shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer whitespace-nowrap">
                        <svg class="w-4 h-4 text-white" :class="loading ? 'animate-spin' : ''" fill="none"
                            stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                        <span>Lọc Dữ Liệu</span>
                    </button>

                    <!-- Reset Filter Button -->
                    <button @click="resetFilters()"
                        class="px-4 py-2.5 bg-slate-100 hover:bg-rose-50 text-slate-700 hover:text-rose-600 rounded-xl border border-slate-200 hover:border-rose-200 text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer whitespace-nowrap"
                        title="Xóa toàn bộ bộ lọc">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                        <span>Đặt Lại</span>
                    </button>
                </div>
            </div>

            <!-- BOTTOM ROW: 6-COLUMN EQUAL GRID (THÔNG THOÁNG, ĐỦ PADDING, ĐỒNG BỘ) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <!-- 1. Team / Phòng Ban Filter -->
                <div class="flex flex-col space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-600">Phòng Ban / Team</label>
                    <select x-model="filters.team" @change="applyFilters()"
                        class="w-full px-3 py-2 text-xs font-medium text-slate-700 bg-slate-50 hover:bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all cursor-pointer">
                        <option value="">-- Toàn Công Ty --</option>
                        <template x-for="teamItem in filterOptions.teams" :key="teamItem">
                            <option :value="teamItem" x-text="teamItem"></option>
                        </template>
                    </select>
                </div>

                <!-- 2. Sales PIC Filter -->
                <div class="flex flex-col space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-600">Sales PIC Phụ Trách</label>
                    <select x-model="filters.sales_id" @change="applyFilters()"
                        class="w-full px-3 py-2 text-xs font-medium text-slate-700 bg-slate-50 hover:bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all cursor-pointer">
                        <option value="">-- Tất cả Sales --</option>
                        <template x-for="sales in filterOptions.sales_users" :key="sales.id">
                            <option :value="sales.id"
                                x-text="sales.name + (sales.employee_code ? ' (' + sales.employee_code + ')' : '')">
                            </option>
                        </template>
                    </select>
                </div>

                <!-- 3. Customer Filter -->
                <div class="flex flex-col space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-600">Khách Hàng / Đối
                        Tác</label>
                    <select x-model="filters.customer_id" @change="applyFilters()"
                        class="w-full px-3 py-2 text-xs font-medium text-slate-700 bg-slate-50 hover:bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all cursor-pointer truncate">
                        <option value="">-- Tất cả Khách Hàng --</option>
                        <template x-for="cust in filterOptions.customers" :key="cust.id">
                            <option :value="cust.id"
                                x-text="(cust.abv_name || cust.name) + (cust.tax_code ? ' - ' + cust.tax_code : '')">
                            </option>
                        </template>
                    </select>
                </div>

                <!-- 4. Vendor / Hãng Filter -->
                <div class="flex flex-col space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-600">Hãng / Vendor</label>
                    <select x-model="filters.vendor_id" @change="applyFilters()"
                        class="w-full px-3 py-2 text-xs font-medium text-slate-700 bg-slate-50 hover:bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all cursor-pointer truncate">
                        <option value="">-- Tất cả Vendor / Hãng --</option>
                        <template x-for="v in filterOptions.vendors" :key="v.id">
                            <option :value="v.id" x-text="v.name + (v.code ? ' (' + v.code + ')' : '')"></option>
                        </template>
                    </select>
                </div>

                <!-- 5. Deal Type Filter -->
                <div class="flex flex-col space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-600">Phân Loại Deal</label>
                    <select x-model="filters.deal_type" @change="applyFilters()"
                        class="w-full px-3 py-2 text-xs font-medium text-slate-700 bg-slate-50 hover:bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all cursor-pointer">
                        <option value="">-- Tất cả Loại Deal --</option>
                        <option value="project">Dự án (Project)</option>
                        <option value="runrate">Runrate (Thương mại)</option>
                        <option value="hang_r">Hàng R (Bảo hành)</option>
                        <option value="poc">POC (Hàng Demo)</option>
                    </select>
                </div>

                <!-- 6. Model / Keyword Search (Correctly Centered Icon & Padding) -->
                <div class="flex flex-col space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-600">Tìm Model / Từ Khóa</label>
                    <div class="relative flex items-center w-full">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="text" x-model.debounce.400ms="filters.model_code" @input="applyFilters()"
                            placeholder="Tìm model, mã..."
                            class="w-full pl-9 pr-3 py-2 text-xs font-medium text-slate-700 bg-slate-50 hover:bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all">
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- 2. BOTTLENECK ALERT TOWER (CẢNH BÁO ĐIỂM NGHẼN NỔI BẬT & RÕ RÀNG) -->
        <!-- ========================================================================= -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. SLA Overdue Alert Card -->
            <div @click="switchTabTo('pipeline', 'sla_overdue')"
                class="group relative overflow-hidden bg-gradient-to-br from-rose-50 via-rose-100/50 to-white p-5 rounded-2xl border-2 border-rose-300 shadow-sm hover:shadow-md hover:border-rose-400 transition-all cursor-pointer">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-black uppercase tracking-wider text-rose-700">Dự án Quá SLA</span>
                        <h4 class="text-3xl font-black text-rose-600 mt-1" x-text="bottlenecks.total_sla_overdue || 0">0
                        </h4>
                        <p class="text-xs text-slate-700 mt-1 font-medium">
                            <span class="font-bold text-rose-700" x-text="bottlenecks.pm_sla_overdue || 0">0</span> PM trễ
                            &bull;
                            <span class="font-bold text-rose-700" x-text="bottlenecks.vendor_sla_overdue || 0">0</span> Hãng
                            trễ
                        </p>
                    </div>
                    <div
                        class="w-12 h-12 bg-rose-600 text-white rounded-2xl shadow-rose-300 shadow-md flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2.2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-center text-xs font-bold text-rose-700 group-hover:underline">
                    <span>Xem danh sách xử lý ngay</span>
                    <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </div>

            <!-- 2. Aged Inventory Alert Card -->
            <div @click="switchTabTo('inventory', 'aged')"
                class="group relative overflow-hidden bg-gradient-to-br from-amber-50 via-amber-100/50 to-white p-5 rounded-2xl border-2 border-amber-300 shadow-sm hover:shadow-md hover:border-amber-400 transition-all cursor-pointer">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-black uppercase tracking-wider text-amber-800">Tồn Kho > 90 Ngày</span>
                        <h4 class="text-3xl font-black text-amber-700 mt-1" x-text="bottlenecks.aged_inventory_count || 0">0
                        </h4>
                        <p class="text-xs text-amber-950 font-medium mt-1">
                            Giá trị: <span class="font-extrabold text-slate-900"
                                x-text="formatCurrency(bottlenecks.aged_inventory_value)">0 ₫</span>
                        </p>
                    </div>
                    <div
                        class="w-12 h-12 bg-amber-500 text-white rounded-2xl shadow-amber-300 shadow-md flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2.2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-center text-xs font-bold text-amber-800 group-hover:underline">
                    <span>Kiểm tra danh mục tồn lâu</span>
                    <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </div>

            <!-- 3. Marketing Overrun Alert Card -->
            <div @click="switchTabTo('marketing', 'overrun')"
                class="group relative overflow-hidden bg-gradient-to-br from-orange-50 via-orange-100/50 to-white p-5 rounded-2xl border-2 border-orange-300 shadow-sm hover:shadow-md hover:border-orange-400 transition-all cursor-pointer">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-black uppercase tracking-wider text-orange-800">MKT Vượt Ngân Sách</span>
                        <h4 class="text-3xl font-black text-orange-600 mt-1" x-text="bottlenecks.mkt_overrun_count || 0">0
                        </h4>
                        <p class="text-xs text-slate-700 mt-1 font-medium">Thực tế chi > Dự toán duyệt</p>
                    </div>
                    <div
                        class="w-12 h-12 bg-orange-500 text-white rounded-2xl shadow-orange-300 shadow-md flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2.2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-center text-xs font-bold text-orange-800 group-hover:underline">
                    <span>Xem sự kiện vượt ngân sách</span>
                    <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </div>

            <!-- 4. Projects Nearing Expiry Warning Card (VIBRANT SKY-600 CLOCK ICON) -->
            <div @click="switchTabTo('pipeline', 'nearing_expiry')"
                class="group relative overflow-hidden bg-gradient-to-br from-sky-50 via-sky-100/50 to-white p-5 rounded-2xl border-2 border-sky-300 shadow-sm hover:shadow-md hover:border-sky-400 transition-all cursor-pointer">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-black uppercase tracking-wider text-sky-800">Deal Chưa Cập Nhật</span>
                        <h4 class="text-3xl font-black text-sky-600 mt-1" x-text="bottlenecks.nearing_expiry_count || 0">0
                        </h4>
                        <p class="text-xs text-slate-700 mt-1 font-medium">Chưa cập nhật > 60 ngày</p>
                    </div>
                    <div
                        class="w-12 h-12 bg-sky-600 text-white rounded-2xl shadow-sky-300 shadow-md flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2.2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-center text-xs font-bold text-sky-800 group-hover:underline">
                    <span>Xem danh sách cần đôn đốc</span>
                    <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- 3. MAIN TAB NAVIGATION BAR -->
        <!-- ========================================================================= -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 bg-white p-2.5 rounded-2xl shadow-xs">
            <!-- Tab 1: Overview -->
            <button @click="activeTab = 'overview'"
                :class="activeTab === 'overview' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100'"
                class="px-4 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                    </path>
                </svg>
                <span>Tổng Quan 360° & KPI</span>
            </button>

            <!-- Tab 2: Detailed Deals / Pipeline -->
            <button @click="activeTab = 'pipeline'"
                :class="activeTab === 'pipeline' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100'"
                class="px-4 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                    </path>
                </svg>
                <span>Chi Tiết Dự Án & Deals</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold"
                    :class="activeTab === 'pipeline' ? 'bg-white text-indigo-700' : 'bg-indigo-100 text-indigo-800'"
                    x-text="detailed_deals.items ? detailed_deals.items.length : 0">0</span>
            </button>

            <!-- Tab 3: Sales Orders -->
            <button @click="activeTab = 'sales_orders'"
                :class="activeTab === 'sales_orders' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100'"
                class="px-4 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                <span>Đơn Hàng Bán & Doanh Thu</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold"
                    :class="activeTab === 'sales_orders' ? 'bg-white text-indigo-700' : 'bg-emerald-100 text-emerald-800'"
                    x-text="sales_orders.items ? sales_orders.items.length : 0">0</span>
            </button>

            <!-- Tab 4: Sales & Team Performance Matrix -->
            <button @click="activeTab = 'sales_performance'"
                :class="activeTab === 'sales_performance' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100'"
                class="px-4 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                    </path>
                </svg>
                <span>Hiệu Suất Sales & Team</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold"
                    :class="activeTab === 'sales_performance' ? 'bg-white text-indigo-700' : 'bg-purple-100 text-purple-800'"
                    x-text="sales_performance ? sales_performance.length : 0">0</span>
            </button>

            <!-- Tab 5: Inventory -->
            <button @click="activeTab = 'inventory'"
                :class="activeTab === 'inventory' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100'"
                class="px-4 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                    </path>
                </svg>
                <span>Kho & Hàng Hóa</span>
            </button>

            <!-- Tab 6: Marketing -->
            <button @click="activeTab = 'marketing'"
                :class="activeTab === 'marketing' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100'"
                class="px-4 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z">
                    </path>
                </svg>
                <span>Marketing & ROI</span>
            </button>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: EXECUTIVE OVERVIEW & KPI MATRIX -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'overview'" x-cloak class="space-y-6">
            <!-- 360 Contextual Banner if filtered by Customer / Vendor / Model -->
            <template x-if="cross_view_360">
                <div
                    class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-5 rounded-3xl shadow-xl border border-indigo-800/40 relative overflow-hidden flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-indigo-500/20 text-indigo-300 rounded-2xl border border-indigo-400/30">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-indigo-300 uppercase tracking-widest"
                                x-text="'Hồ Sơ 360° ' + (cross_view_360.type === 'customer' ? 'Khách Hàng' : (cross_view_360.type === 'vendor' ? 'Hãng Đối Tác' : 'Model / Sản Phẩm'))"></span>
                            <h3 class="text-xl font-extrabold text-white"
                                x-text="cross_view_360.name || cross_view_360.company_name || cross_view_360.model_code">
                            </h3>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-xs">
                        <template x-if="cross_view_360.total_revenue">
                            <div class="bg-white/10 px-3 py-1.5 rounded-xl backdrop-blur-sm">
                                <span class="text-slate-300">Doanh số tích lũy:</span>
                                <span class="font-bold text-emerald-400 ml-1"
                                    x-text="formatCurrency(cross_view_360.total_revenue)"></span>
                            </div>
                        </template>
                        <template x-if="cross_view_360.pipeline_value">
                            <div class="bg-white/10 px-3 py-1.5 rounded-xl backdrop-blur-sm">
                                <span class="text-slate-300">Pipeline theo đuổi:</span>
                                <span class="font-bold text-indigo-300 ml-1"
                                    x-text="formatCurrency(cross_view_360.pipeline_value)"></span>
                            </div>
                        </template>
                        <button @click="resetFilters()"
                            class="px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white font-bold rounded-xl transition-all">
                            Đóng 360°
                        </button>
                    </div>
                </div>
            </template>

            <!-- KPI SUMMARY CARDS MATRIX -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- 1. Pipeline & Kinh Doanh -->
                <div @click="activeTab = 'pipeline'"
                    class="bg-white p-5 rounded-2xl border-2 border-indigo-100 shadow-sm hover:shadow-md hover:border-indigo-300 transition-all cursor-pointer">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-700">Kinh Doanh &
                            Pipeline</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900" x-text="formatCurrency(pipeline.total_pipeline_value)">0
                        ₫</h3>
                    <div class="mt-3 pt-3 border-t border-slate-100 text-xs space-y-1.5 text-slate-600">
                        <div class="flex justify-between">
                            <span>Tỷ lệ Closed Won:</span>
                            <span class="font-bold text-emerald-600" x-text="(pipeline.win_rate || 0) + '%'">0%</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Số deal Active đang chạy:</span>
                            <span class="font-bold text-slate-800" x-text="pipeline.total_active_count || 0">0</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Đơn Hàng & Doanh Thu Thực Tế -->
                <div @click="activeTab = 'sales_orders'"
                    class="bg-white p-5 rounded-2xl border-2 border-emerald-100 shadow-sm hover:shadow-md hover:border-emerald-300 transition-all cursor-pointer">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Đơn Hàng & Doanh
                            Thu</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900" x-text="formatCurrency(sales_orders.total_revenue)">0 ₫
                    </h3>
                    <div class="mt-3 pt-3 border-t border-slate-100 text-xs space-y-1.5 text-slate-600">
                        <div class="flex justify-between">
                            <span>Tổng lợi nhuận (Margin):</span>
                            <span class="font-bold text-emerald-600"
                                x-text="formatCurrency(sales_orders.total_margin) + ' (' + (sales_orders.avg_margin_percent || 0) + '%)'">0
                                ₫</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Số đơn đã ký:</span>
                            <span class="font-bold text-slate-800" x-text="sales_orders.total_count || 0">0</span>
                        </div>
                    </div>
                </div>

                <!-- 3. Kho & Giá Trị Tồn -->
                <div @click="activeTab = 'inventory'"
                    class="bg-white p-5 rounded-2xl border-2 border-teal-100 shadow-sm hover:shadow-md hover:border-teal-300 transition-all cursor-pointer">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-teal-700">Kho & Giá Trị Tồn</span>
                        <div class="w-8 h-8 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900" x-text="formatCurrency(inventory.total_valuation)">0 ₫
                    </h3>
                    <div class="mt-3 pt-3 border-t border-slate-100 text-xs space-y-1.5 text-slate-600">
                        <div class="flex justify-between">
                            <span>Tổng SL tồn khả dụng:</span>
                            <span class="font-bold text-slate-800" x-text="inventory.total_stock || 0">0</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Đang giữ / POC:</span>
                            <span class="font-bold text-amber-600"
                                x-text="(inventory.reserved_count || 0) + ' / ' + (inventory.borrowed_count || 0)">0</span>
                        </div>
                    </div>
                </div>

                <!-- 4. Marketing & Chi Phí -->
                <div @click="activeTab = 'marketing'"
                    class="bg-white p-5 rounded-2xl border-2 border-purple-100 shadow-sm hover:shadow-md hover:border-purple-300 transition-all cursor-pointer">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-purple-700">Marketing & Chi
                            Phí</span>
                        <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900" x-text="formatCurrency(marketing.total_actual_cost)">0 ₫
                    </h3>
                    <div class="mt-3 pt-3 border-t border-slate-100 text-xs space-y-1.5 text-slate-600">
                        <div class="flex justify-between">
                            <span>Tỷ lệ Ticket đúng hạn:</span>
                            <span class="font-bold text-purple-600"
                                x-text="(marketing.ticket_sla_rate || 100) + '%'">100%</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Sự kiện triển khai:</span>
                            <span class="font-bold text-slate-800" x-text="marketing.active_events_count || 0">0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TOP CRITICAL DEALS PREVIEW TABLE -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full bg-indigo-600"></div>
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Top Dự Án / Deals Trọng Điểm
                            Đang Triển Khai</h3>
                    </div>
                    <button @click="activeTab = 'pipeline'"
                        class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 cursor-pointer">
                        <span>Xem toàn bộ (<span x-text="detailed_deals.items ? detailed_deals.items.length : 0"></span>
                            deals)</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200">
                                <th class="p-3 pl-4">Mã & Tên Dự Án</th>
                                <th class="p-3">Khách Hàng / End-User</th>
                                <th class="p-3">Hãng / Vendor</th>
                                <th class="p-3">Phụ Trách (Sales PIC)</th>
                                <th class="p-3 text-right">Giá Trị Pipeline</th>
                                <th class="p-3 text-center">Trạng Thái ĐKDA</th>
                                <th class="p-3 text-center">Tình Trạng SLA</th>
                                <th class="p-3 text-center pr-4">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <template x-for="deal in (detailed_deals.items || []).slice(0, 7)" :key="deal.id">
                                <tr class="hover:bg-indigo-50/40 transition-colors">
                                    <td class="p-3 pl-4">
                                        <div class="font-bold text-slate-900" x-text="deal.name"></div>
                                        <span class="text-[10px] font-mono text-indigo-600 font-semibold"
                                            x-text="deal.code"></span>
                                    </td>
                                    <td class="p-3">
                                        <div class="font-semibold text-slate-800" x-text="deal.customer_name"></div>
                                        <div class="text-[10px] text-slate-500" x-show="deal.eu_name"
                                            x-text="'EU: ' + deal.eu_name"></div>
                                    </td>
                                    <td class="p-3">
                                        <span
                                            class="px-2 py-0.5 bg-slate-100 text-slate-800 font-semibold rounded-md text-[11px]"
                                            x-text="deal.vendor_name"></span>
                                    </td>
                                    <td class="p-3">
                                        <div class="font-semibold text-slate-800" x-text="deal.manager_name"></div>
                                        <span class="text-[10px] text-slate-400" x-text="deal.manager_team"></span>
                                    </td>
                                    <td class="p-3 text-right font-extrabold text-slate-900"
                                        x-text="formatCurrency(deal.deal_value)"></td>
                                    <td class="p-3 text-center">
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold"
                                            :class="getStatusBadgeClass(deal.registration_status)"
                                            x-text="getStatusLabel(deal.registration_status)"></span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <template x-if="deal.is_sla_overdue">
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">
                                                Quá hạn SLA
                                            </span>
                                        </template>
                                        <template x-if="!deal.is_sla_overdue && deal.is_nearing_expiry">
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">
                                                >60 ngày chưa update
                                            </span>
                                        </template>
                                        <template x-if="!deal.is_sla_overdue && !deal.is_nearing_expiry">
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700">
                                                Đúng hạn
                                            </span>
                                        </template>
                                    </td>
                                    <td class="p-3 text-center pr-4">
                                        <button @click="openQuickView('deal', deal.id)"
                                            class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-600 hover:text-white text-indigo-700 font-bold rounded-lg text-[11px] transition-all cursor-pointer">
                                            Chi Tiết
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            <template x-if="!detailed_deals.items || detailed_deals.items.length === 0">
                                <tr>
                                    <td colspan="8" class="p-8 text-center text-slate-400">
                                        <p class="text-xs">Không có dự án nào trong khoảng bộ lọc này.</p>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TOP VENDORS & CUSTOMERS -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Vendors -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Top 5 Hãng / Vendor Theo Giá
                            Trị Dự Án</h4>
                    </div>
                    <div class="space-y-3">
                        <template x-for="(v, index) in (pipeline.top_vendors || [])" :key="index">
                            <div
                                class="flex items-center justify-between p-3 bg-slate-50 rounded-xl hover:bg-indigo-50/50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-xs"
                                        x-text="index + 1"></span>
                                    <div>
                                        <div class="font-bold text-slate-800 text-xs" x-text="v.vendor_name"></div>
                                        <div class="text-[10px] text-slate-400" x-text="v.project_count + ' dự án'"></div>
                                    </div>
                                </div>
                                <span class="font-black text-slate-900 text-xs"
                                    x-text="formatCurrency(v.total_value)"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Top Customers -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Top 5 Khách Hàng / Đối Tác Lớn
                        </h4>
                    </div>
                    <div class="space-y-3">
                        <template x-for="(c, index) in (pipeline.top_customers || [])" :key="index">
                            <div
                                class="flex items-center justify-between p-3 bg-slate-50 rounded-xl hover:bg-indigo-50/50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-xs"
                                        x-text="index + 1"></span>
                                    <div>
                                        <div class="font-bold text-slate-800 text-xs" x-text="c.customer_name"></div>
                                        <div class="text-[10px] text-slate-400" x-text="c.project_count + ' dự án'"></div>
                                    </div>
                                </div>
                                <span class="font-black text-slate-900 text-xs"
                                    x-text="formatCurrency(c.total_value)"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: DETAILED DEALS / PIPELINE MANAGEMENT (CHI TIẾT TOÀN BỘ DỰ ÁN) -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'pipeline'" x-cloak class="space-y-5">
            <!-- Filter Pills & Search for Deals -->
            <div
                class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
                <!-- Filter Pills -->
                <div class="flex flex-wrap items-center gap-1.5 text-xs">
                    <button @click="dealFilterStatus = 'all'"
                        :class="dealFilterStatus === 'all' ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-3 py-1.5 rounded-xl transition-all cursor-pointer">
                        Tất cả (<span x-text="detailed_deals.items ? detailed_deals.items.length : 0"></span>)
                    </button>
                    <button @click="dealFilterStatus = 'sla_overdue'"
                        :class="dealFilterStatus === 'sla_overdue' ? 'bg-rose-600 text-white font-bold shadow-sm' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 font-semibold'"
                        class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1 cursor-pointer">
                        Quá hạn SLA (<span x-text="countDealsByFilter('sla_overdue')"></span>)
                    </button>
                    <button @click="dealFilterStatus = 'active'"
                        :class="dealFilterStatus === 'active' ? 'bg-sky-600 text-white font-bold' : 'bg-sky-50 text-sky-700 hover:bg-sky-100 font-semibold'"
                        class="px-3 py-1.5 rounded-xl transition-all cursor-pointer">
                        Đang theo đuổi (<span x-text="countDealsByFilter('active')"></span>)
                    </button>
                    <button @click="dealFilterStatus = 'closed_won'"
                        :class="dealFilterStatus === 'closed_won' ? 'bg-emerald-600 text-white font-bold' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-semibold'"
                        class="px-3 py-1.5 rounded-xl transition-all cursor-pointer">
                        Closed Won (<span x-text="countDealsByFilter('closed_won')"></span>)
                    </button>
                    <button @click="dealFilterStatus = 'closed_lost'"
                        :class="dealFilterStatus === 'closed_lost' ? 'bg-slate-700 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-semibold'"
                        class="px-3 py-1.5 rounded-xl transition-all cursor-pointer">
                        Closed Lost (<span x-text="countDealsByFilter('closed_lost')"></span>)
                    </button>
                    <button @click="dealFilterStatus = 'nearing_expiry'"
                        :class="dealFilterStatus === 'nearing_expiry' ? 'bg-amber-600 text-white font-bold' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 font-semibold'"
                        class="px-3 py-1.5 rounded-xl transition-all cursor-pointer">
                        Chậm Update >60 ngày (<span x-text="countDealsByFilter('nearing_expiry')"></span>)
                    </button>
                </div>

                <!-- Search box in table -->
                <div class="relative w-64">
                    <input type="text" x-model="dealSearchText" placeholder="Tìm tên deal, KH, sales..."
                        class="w-full px-3 py-1.5 pl-8 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all">
                    <svg class="w-3.5 h-3.5 absolute left-2.5 top-2.5 text-slate-400 pointer-events-none" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            <!-- Full Detailed Deals Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto custom-scrollbar max-h-[650px]">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead
                            class="sticky top-0 z-10 bg-slate-100 text-slate-600 font-bold border-b border-slate-200 shadow-xs">
                            <tr>
                                <th class="p-3 pl-4">Mã & Tên Dự Án (Deal)</th>
                                <th class="p-3">Khách Hàng / End-User</th>
                                <th class="p-3">Hãng / Vendor</th>
                                <th class="p-3">Sales PIC & Đội Ngũ</th>
                                <th class="p-3">Loại Deal & Giai Đoạn</th>
                                <th class="p-3 text-right">Giá Trị Dự Án</th>
                                <th class="p-3 text-center">Trạng Thái ĐKDA</th>
                                <th class="p-3 text-center">Hạn SLA / Cập Nhật</th>
                                <th class="p-3 text-center pr-4">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <template x-for="deal in filteredDealsList" :key="deal.id">
                                <tr class="hover:bg-indigo-50/40 transition-colors"
                                    :class="deal.is_sla_overdue ? 'bg-rose-50/20' : ''">
                                    <td class="p-3 pl-4">
                                        <div class="font-bold text-slate-900" x-text="deal.name"></div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[10px] font-mono text-indigo-600 font-bold"
                                                x-text="deal.code"></span>
                                            <span class="text-[10px] text-slate-400" x-show="deal.po_code"
                                                x-text="'PO: ' + deal.po_code"></span>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <div class="font-semibold text-slate-800" x-text="deal.customer_name"></div>
                                        <div class="text-[10px] text-slate-500" x-show="deal.eu_name"
                                            x-text="'EU: ' + deal.eu_name"></div>
                                    </td>
                                    <td class="p-3">
                                        <span
                                            class="px-2 py-0.5 bg-slate-100 text-slate-800 font-semibold rounded-md text-[11px]"
                                            x-text="deal.vendor_name"></span>
                                    </td>
                                    <td class="p-3">
                                        <div class="font-semibold text-slate-800" x-text="deal.manager_name"></div>
                                        <span class="text-[10px] text-slate-400" x-text="deal.manager_team"></span>
                                    </td>
                                    <td class="p-3">
                                        <span
                                            class="px-2 py-0.5 bg-slate-100 text-slate-700 font-medium rounded text-[10px] uppercase"
                                            x-text="deal.deal_type"></span>
                                        <div class="text-[10px] text-slate-500 mt-0.5" x-text="deal.stage"></div>
                                    </td>
                                    <td class="p-3 text-right font-extrabold text-slate-900"
                                        x-text="formatCurrency(deal.deal_value)"></td>
                                    <td class="p-3 text-center">
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold"
                                            :class="getStatusBadgeClass(deal.registration_status)"
                                            x-text="getStatusLabel(deal.registration_status)"></span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <template x-if="deal.is_sla_overdue">
                                            <div
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">
                                                Quá hạn SLA
                                            </div>
                                        </template>
                                        <template x-if="!deal.is_sla_overdue">
                                            <div class="text-[10px] text-slate-500"
                                                x-text="deal.last_updated ? ('Update ' + deal.last_updated) : deal.updated_at_raw">
                                            </div>
                                        </template>
                                    </td>
                                    <td class="p-3 text-center pr-4">
                                        <button @click="openQuickView('deal', deal.id)"
                                            class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-[11px] shadow-xs transition-all cursor-pointer">
                                            Xem Chi Tiết
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            <template x-if="filteredDealsList.length === 0">
                                <tr>
                                    <td colspan="9" class="p-12 text-center text-slate-400">
                                        <p class="text-sm font-semibold text-slate-600">Không tìm thấy dự án nào khớp với
                                            điều kiện lọc.</p>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div
                    class="p-3 bg-slate-50 border-t border-slate-200 text-xs text-slate-500 flex justify-between items-center">
                    <span>Hiển thị <strong x-text="filteredDealsList.length"></strong> dự án</span>
                    <span class="font-bold text-slate-700">Tổng giá trị: <span class="text-indigo-600"
                            x-text="formatCurrency(calculateFilteredDealsTotal())"></span></span>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 3: DETAILED SALES ORDERS & P&L TRACKING (ĐƠN HÀNG BÁN & DOANH THU) -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'sales_orders'" x-cloak class="space-y-5">
            <!-- Sales Orders Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-2xl border-2 border-slate-100 shadow-xs">
                    <span class="text-[11px] font-bold text-slate-500 uppercase">Doanh Số Bán (chưa VAT)</span>
                    <h4 class="text-2xl font-black text-slate-900 mt-1" x-text="formatCurrency(sales_orders.total_revenue)">
                        0 ₫</h4>
                </div>
                <div class="bg-white p-4 rounded-2xl border-2 border-slate-100 shadow-xs">
                    <span class="text-[11px] font-bold text-slate-500 uppercase">Tổng Giá Vốn (Cost)</span>
                    <h4 class="text-2xl font-black text-slate-700 mt-1" x-text="formatCurrency(sales_orders.total_cost)">0 ₫
                    </h4>
                </div>
                <div class="bg-white p-4 rounded-2xl border-2 border-emerald-100 shadow-xs">
                    <span class="text-[11px] font-bold text-emerald-600 uppercase">Lợi Nhuận Gộp (Margin)</span>
                    <h4 class="text-2xl font-black text-emerald-600 mt-1"
                        x-text="formatCurrency(sales_orders.total_margin)">0 ₫</h4>
                    <p class="text-[11px] text-slate-500 mt-0.5">Tỷ lệ Margin TB: <strong class="text-emerald-700"
                            x-text="(sales_orders.avg_margin_percent || 0) + '%'">0%</strong></p>
                </div>
                <div class="bg-white p-4 rounded-2xl border-2 border-amber-100 shadow-xs">
                    <span class="text-[11px] font-bold text-amber-600 uppercase">Công Nợ Phải Thu</span>
                    <h4 class="text-2xl font-black text-amber-600 mt-1" x-text="formatCurrency(sales_orders.total_debt)">0 ₫
                    </h4>
                    <p class="text-[11px] text-slate-500 mt-0.5">Đã thu: <strong class="text-slate-800"
                            x-text="formatCurrency(sales_orders.total_paid)">0 ₫</strong></p>
                </div>
            </div>

            <!-- Sales Orders Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-600"></div>
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Danh Sách Chi Tiết Đơn Hàng
                            Bán</h3>
                    </div>
                    <div class="relative w-64">
                        <input type="text" x-model="orderSearchText" placeholder="Tìm mã đơn, khách hàng, sales..."
                            class="w-full px-3 py-1.5 pl-8 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all">
                        <svg class="w-3.5 h-3.5 absolute left-2.5 top-2.5 text-slate-400 pointer-events-none" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                <div class="overflow-x-auto custom-scrollbar max-h-[600px]">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="sticky top-0 z-10 bg-slate-100 text-slate-600 font-bold border-b border-slate-200">
                            <tr>
                                <th class="p-3 pl-4">Mã Đơn & Ngày</th>
                                <th class="p-3">Khách Hàng</th>
                                <th class="p-3">Sales PIC & Team</th>
                                <th class="p-3">Dự Án Liên Kết</th>
                                <th class="p-3 text-right">Doanh Số (Revenue)</th>
                                <th class="p-3 text-right">Lợi Nhuận (Margin)</th>
                                <th class="p-3 text-center">Duyệt P&L</th>
                                <th class="p-3 text-center">Thanh Toán</th>
                                <th class="p-3 text-center pr-4">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <template x-for="order in filteredOrdersList" :key="order.id">
                                <tr class="hover:bg-emerald-50/30 transition-colors">
                                    <td class="p-3 pl-4">
                                        <div class="font-bold text-slate-900 font-mono" x-text="order.code"></div>
                                        <div class="text-[10px] text-slate-400" x-text="order.date"></div>
                                    </td>
                                    <td class="p-3">
                                        <div class="font-semibold text-slate-800" x-text="order.customer_name"></div>
                                        <span class="text-[10px] text-slate-400 font-mono" x-show="order.customer_tax_code"
                                            x-text="'MST: ' + order.customer_tax_code"></span>
                                    </td>
                                    <td class="p-3">
                                        <div class="font-semibold text-slate-800" x-text="order.sales_name"></div>
                                        <span class="text-[10px] text-slate-400" x-text="order.sales_team"></span>
                                    </td>
                                    <td class="p-3">
                                        <template x-if="order.project_code">
                                            <div>
                                                <span class="font-mono text-[10px] font-bold text-indigo-600"
                                                    x-text="order.project_code"></span>
                                                <div class="text-[10px] text-slate-500 truncate max-w-[150px]"
                                                    x-text="order.project_name"></div>
                                            </div>
                                        </template>
                                        <template x-if="!order.project_code">
                                            <span class="text-[10px] text-slate-400 italic">Bán lẻ / Runrate</span>
                                        </template>
                                    </td>
                                    <td class="p-3 text-right font-black text-slate-900"
                                        x-text="formatCurrency(order.total_revenue)"></td>
                                    <td class="p-3 text-right">
                                        <div class="font-bold text-emerald-600" x-text="formatCurrency(order.margin)"></div>
                                        <span class="text-[10px] font-bold text-slate-500"
                                            x-text="order.margin_percent + '%'"></span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                            :class="order.pl_status === 'approved' ? 'bg-emerald-100 text-emerald-800' : (order.pl_status === 'rejected' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800')"
                                            x-text="order.pl_status === 'approved' ? 'Đã duyệt' : (order.pl_status === 'rejected' ? 'Từ chối' : 'Chờ duyệt')"></span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                            :class="order.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : (order.payment_status === 'partial' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700')"
                                            x-text="order.payment_status === 'paid' ? 'Đã xong' : (order.payment_status === 'partial' ? 'Thu một phần' : 'Chưa thu')"></span>
                                    </td>
                                    <td class="p-3 text-center pr-4">
                                        <button @click="openQuickView('sale', order.id)"
                                            class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-600 hover:text-white text-emerald-700 font-bold rounded-lg text-[11px] transition-all cursor-pointer">
                                            Xem Đơn
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            <template x-if="filteredOrdersList.length === 0">
                                <tr>
                                    <td colspan="9" class="p-10 text-center text-slate-400">
                                        <p class="text-xs">Không có đơn hàng bán nào trong khoảng bộ lọc này.</p>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 4: SALES & TEAM PERFORMANCE MATRIX (HIỆU SUẤT ĐỘI NGŨ) -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'sales_performance'" x-cloak class="space-y-5">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Bảng Đánh Giá Hiệu Suất Từng
                            Sales & Bộ Phận</h3>
                        <p class="text-xs text-slate-500">Giúp BOD & Quản lý nắm bắt tức thì khối lượng deal, doanh số và tỷ
                            lệ chốt của từng nhân sự</p>
                    </div>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-100 text-slate-600 font-bold border-b border-slate-200">
                            <tr>
                                <th class="p-3 pl-4">Sales PIC / Nhân Viên</th>
                                <th class="p-3">Phòng Ban / Team</th>
                                <th class="p-3 text-center">Tổng Deals Phụ Trách</th>
                                <th class="p-3 text-center">Deal Active</th>
                                <th class="p-3 text-right">Giá Trị Pipeline</th>
                                <th class="p-3 text-center">Won / Lost</th>
                                <th class="p-3 text-center">Win Rate %</th>
                                <th class="p-3 text-right">Doanh Thu Bán</th>
                                <th class="p-3 text-right">Lợi Nhuận (Margin)</th>
                                <th class="p-3 text-center pr-4">Cảnh Báo SLA</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <template x-for="rep in (sales_performance || [])" :key="rep.sales_id">
                                <tr class="hover:bg-purple-50/30 transition-colors">
                                    <td class="p-3 pl-4">
                                        <div class="font-bold text-slate-900" x-text="rep.sales_name"></div>
                                        <span class="text-[10px] font-mono text-slate-400"
                                            x-text="rep.employee_code"></span>
                                    </td>
                                    <td class="p-3 font-semibold text-slate-700" x-text="rep.team"></td>
                                    <td class="p-3 text-center font-bold text-slate-800" x-text="rep.total_deals"></td>
                                    <td class="p-3 text-center font-bold text-sky-600" x-text="rep.active_deals"></td>
                                    <td class="p-3 text-right font-black text-indigo-700"
                                        x-text="formatCurrency(rep.pipeline_value)"></td>
                                    <td class="p-3 text-center">
                                        <span class="font-bold text-emerald-600" x-text="rep.won_count"></span> /
                                        <span class="font-bold text-rose-600" x-text="rep.lost_count"></span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="font-extrabold px-2 py-0.5 rounded text-[11px]"
                                            :class="rep.win_rate >= 50 ? 'bg-emerald-100 text-emerald-800' : (rep.win_rate >= 25 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700')"
                                            x-text="rep.win_rate + '%'"></span>
                                    </td>
                                    <td class="p-3 text-right font-black text-slate-900"
                                        x-text="formatCurrency(rep.sales_revenue)"></td>
                                    <td class="p-3 text-right font-bold text-emerald-600"
                                        x-text="formatCurrency(rep.sales_margin)"></td>
                                    <td class="p-3 text-center pr-4">
                                        <template x-if="rep.overdue_deals > 0">
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">
                                                <span x-text="rep.overdue_deals"></span> trễ SLA
                                            </span>
                                        </template>
                                        <template x-if="rep.overdue_deals === 0">
                                            <span class="text-[10px] text-emerald-600 font-semibold">Chuẩn SLA</span>
                                        </template>
                                    </td>
                                </tr>
                            </template>

                            <template x-if="!sales_performance || sales_performance.length === 0">
                                <tr>
                                    <td colspan="10" class="p-8 text-center text-slate-400">
                                        Chưa có dữ liệu hiệu suất của nhân viên sales.
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 5: INVENTORY & STOCK MANAGEMENT (KHO & TỒN KHO LÂU NGÀY) -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'inventory'" x-cloak class="space-y-5">
            <!-- Metric Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-2xl border-2 border-teal-100 shadow-xs">
                    <span class="text-[11px] font-bold text-teal-600 uppercase">Tổng Tồn Khả Dụng</span>
                    <h4 class="text-2xl font-black text-slate-900 mt-1" x-text="inventory.available_count || 0">0</h4>
                </div>
                <div class="bg-white p-4 rounded-2xl border-2 border-indigo-100 shadow-xs">
                    <span class="text-[11px] font-bold text-indigo-600 uppercase">Đang Giữ Cho Dự Án</span>
                    <h4 class="text-2xl font-black text-slate-900 mt-1" x-text="inventory.reserved_count || 0">0</h4>
                </div>
                <div class="bg-white p-4 rounded-2xl border-2 border-amber-100 shadow-xs">
                    <span class="text-[11px] font-bold text-amber-600 uppercase">Đang Mượn Demo / POC</span>
                    <h4 class="text-2xl font-black text-slate-900 mt-1" x-text="inventory.borrowed_count || 0">0</h4>
                </div>
                <div class="bg-white p-4 rounded-2xl border-2 border-rose-200 shadow-xs bg-rose-50/20">
                    <span class="text-[11px] font-bold text-rose-600 uppercase">Tồn Kho Lâu Ngày (>90 Ngày)</span>
                    <h4 class="text-2xl font-black text-rose-600 mt-1" x-text="detailed_inventory.aged_count || 0">0</h4>
                    <p class="text-[11px] text-slate-500 mt-0.5">Giá trị: <strong class="text-slate-800"
                            x-text="formatCurrency(detailed_inventory.aged_value)">0 ₫</strong></p>
                </div>
            </div>

            <!-- Inventory List Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Danh Sách Hàng Tồn Kho Chi Tiết
                    </h3>
                </div>
                <div class="overflow-x-auto custom-scrollbar max-h-[600px]">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="sticky top-0 z-10 bg-slate-100 text-slate-600 font-bold border-b border-slate-200">
                            <tr>
                                <th class="p-3 pl-4">Mã Sản Phẩm</th>
                                <th class="p-3">Tên Sản Phẩm</th>
                                <th class="p-3">Kho Hàng</th>
                                <th class="p-3 text-center">Số Lượng Tồn</th>
                                <th class="p-3 text-right">Giá Vốn TB (Cost)</th>
                                <th class="p-3 text-right">Tổng Giá Trị Tồn</th>
                                <th class="p-3 text-center pr-4">Số Ngày Lưu Kho</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <template x-for="item in (detailed_inventory.items || [])" :key="item.id">
                                <tr class="hover:bg-teal-50/30 transition-colors"
                                    :class="item.is_aged ? 'bg-amber-50/20' : ''">
                                    <td class="p-3 pl-4 font-bold text-slate-900 font-mono" x-text="item.product_code"></td>
                                    <td class="p-3 font-semibold text-slate-800" x-text="item.product_name"></td>
                                    <td class="p-3 text-slate-600" x-text="item.warehouse_name"></td>
                                    <td class="p-3 text-center font-bold text-slate-900" x-text="item.stock"></td>
                                    <td class="p-3 text-right text-slate-700" x-text="formatCurrency(item.avg_cost)"></td>
                                    <td class="p-3 text-right font-black text-teal-700"
                                        x-text="formatCurrency(item.total_value)"></td>
                                    <td class="p-3 text-center pr-4">
                                        <template x-if="item.is_aged">
                                            <span
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                                <span x-text="item.days_in_stock + ' ngày'"></span>
                                            </span>
                                        </template>
                                        <template x-if="!item.is_aged">
                                            <span class="text-[10px] text-slate-500"
                                                x-text="item.days_in_stock + ' ngày'"></span>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 6: MARKETING & ROI MANAGEMENT -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'marketing'" x-cloak class="space-y-5">
            <!-- Marketing Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-4 rounded-2xl border-2 border-purple-100 shadow-xs">
                    <span class="text-[11px] font-bold text-purple-600 uppercase">Ngân Sách Được Duyệt</span>
                    <h4 class="text-2xl font-black text-slate-900 mt-1" x-text="formatCurrency(marketing.total_budget)">0 ₫
                    </h4>
                </div>
                <div class="bg-white p-4 rounded-2xl border-2 border-purple-100 shadow-xs">
                    <span class="text-[11px] font-bold text-purple-600 uppercase">Thực Tế Đã Chi</span>
                    <h4 class="text-2xl font-black text-slate-900 mt-1"
                        x-text="formatCurrency(marketing.total_actual_cost)">0 ₫</h4>
                </div>
                <div class="bg-white p-4 rounded-2xl border-2 border-emerald-100 shadow-xs">
                    <span class="text-[11px] font-bold text-emerald-600 uppercase">Tỷ Lệ Ticket Đúng Hạn</span>
                    <h4 class="text-2xl font-black text-emerald-600 mt-1" x-text="(marketing.ticket_sla_rate || 100) + '%'">
                        100%</h4>
                </div>
            </div>

            <!-- Marketing Events Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Danh Sách Sự Kiện & Chiến Dịch
                        Marketing</h3>
                </div>
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-100 text-slate-600 font-bold border-b border-slate-200">
                            <tr>
                                <th class="p-3 pl-4">Mã & Tên Sự Kiện</th>
                                <th class="p-3">Hãng Tài Trợ / Vendor</th>
                                <th class="p-3">Ngày Diễn Ra</th>
                                <th class="p-3 text-right">Ngân Sách Dự Toán</th>
                                <th class="p-3 text-right">Thực Tế Đã Chi</th>
                                <th class="p-3 text-center">Chênh Lệch</th>
                                <th class="p-3 text-center pr-4">Trạng Thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <template x-for="ev in (detailed_marketing.items || [])" :key="ev.id">
                                <tr class="hover:bg-purple-50/30 transition-colors"
                                    :class="ev.is_overrun ? 'bg-orange-50/20' : ''">
                                    <td class="p-3 pl-4">
                                        <div class="font-bold text-slate-900" x-text="ev.title"></div>
                                        <span class="text-[10px] font-mono text-purple-600 font-bold"
                                            x-text="ev.code"></span>
                                    </td>
                                    <td class="p-3 font-semibold text-slate-800" x-text="ev.vendor_name"></td>
                                    <td class="p-3 text-slate-500" x-text="ev.event_date"></td>
                                    <td class="p-3 text-right font-bold text-slate-700" x-text="formatCurrency(ev.budget)">
                                    </td>
                                    <td class="p-3 text-right font-black"
                                        :class="ev.is_overrun ? 'text-rose-600' : 'text-slate-900'"
                                        x-text="formatCurrency(ev.actual_cost)"></td>
                                    <td class="p-3 text-center">
                                        <template x-if="ev.is_overrun">
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">
                                                Vượt +<span x-text="formatCurrency(ev.variance)"></span>
                                            </span>
                                        </template>
                                        <template x-if="!ev.is_overrun">
                                            <span class="text-[10px] text-emerald-600 font-semibold">Trong định mức</span>
                                        </template>
                                    </td>
                                    <td class="p-3 text-center pr-4">
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 uppercase"
                                            x-text="ev.status"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- 4. QUICK-VIEW DETAIL DRAWER (XEM SÂU MỌI THÔNG TIN KHÔNG CẦN RỜI TRANG) -->
        <!-- ========================================================================= -->
        <div x-show="quickView.open" class="fixed inset-0 z-50 overflow-hidden" x-cloak>
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                @click="quickView.open = false"></div>

            <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
                <div class="w-screen max-w-3xl bg-white shadow-2xl border-l border-slate-200 flex flex-col justify-between">
                    <!-- Drawer Header -->
                    <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white"
                                :class="quickView.type === 'deal' ? 'bg-indigo-600' : 'bg-emerald-600'">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400"
                                    x-text="quickView.type === 'deal' ? 'Hồ Sơ Chi Tiết Dự Án (Deal)' : 'Hồ Sơ Chi Tiết Đơn Hàng Bán'"></span>
                                <h3 class="text-base font-black text-slate-800"
                                    x-text="quickView.data ? (quickView.data.code + ' - ' + (quickView.data.name || '')) : 'Đang tải...'">
                                </h3>
                            </div>
                        </div>
                        <button @click="quickView.open = false"
                            class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-200 transition-all cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Drawer Content -->
                    <div class="p-6 overflow-y-auto flex-1 space-y-6 custom-scrollbar">
                        <template x-if="quickView.loading">
                            <div class="text-center py-16 text-slate-400">
                                <svg class="animate-spin h-8 w-8 text-indigo-600 mx-auto mb-2" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <p class="text-xs font-semibold">Đang tải toàn bộ dữ liệu chi tiết...</p>
                            </div>
                        </template>

                        <!-- DEAL QUICK VIEW DETAILS -->
                        <template x-if="!quickView.loading && quickView.data && quickView.type === 'deal'">
                            <div class="space-y-6 text-xs">
                                <!-- 1. Key Value Cards -->
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="p-3 bg-indigo-50/60 rounded-xl border border-indigo-100">
                                        <span class="text-[10px] text-indigo-600 font-bold uppercase">Giá Trị Dự Án</span>
                                        <div class="text-base font-black text-slate-900 mt-0.5"
                                            x-text="formatCurrency(quickView.data.order_value || quickView.data.budget)">
                                        </div>
                                    </div>
                                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                                        <span class="text-[10px] text-slate-500 font-bold uppercase">Giai Đoạn
                                            (Stage)</span>
                                        <div class="text-sm font-bold text-slate-800 mt-0.5" x-text="quickView.data.stage">
                                        </div>
                                    </div>
                                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                                        <span class="text-[10px] text-slate-500 font-bold uppercase">Trạng Thái ĐKDA</span>
                                        <div class="text-xs font-bold mt-0.5"
                                            :class="getStatusBadgeClass(quickView.data.registration_status)"
                                            x-text="getStatusLabel(quickView.data.registration_status)"></div>
                                    </div>
                                </div>

                                <!-- 2. Customer & Vendor Info -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-1.5">
                                        <h4 class="font-bold text-slate-800 text-xs mb-2 text-indigo-700">Khách Hàng &
                                            End-User</h4>
                                        <div><strong>Khách hàng:</strong> <span
                                                x-text="quickView.data.customer?.name || 'N/A'"></span></div>
                                        <div><strong>Mã số thuế:</strong> <span
                                                x-text="quickView.data.customer?.tax_code || 'Chưa có'"></span></div>
                                        <div><strong>Điện thoại:</strong> <span
                                                x-text="quickView.data.customer?.phone || 'Chưa có'"></span></div>
                                        <div class="pt-1.5 border-t border-slate-200">
                                            <strong>End-User:</strong> <span
                                                x-text="quickView.data.end_user?.name || 'N/A'"></span>
                                            <span x-show="quickView.data.end_user?.industry"
                                                x-text="' (' + quickView.data.end_user.industry + ')'"></span>
                                        </div>
                                    </div>

                                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-1.5">
                                        <h4 class="font-bold text-slate-800 text-xs mb-2 text-indigo-700">Hãng & Sales Phụ
                                            Trách</h4>
                                        <div><strong>Hãng / Vendor:</strong> <span class="font-semibold text-slate-800"
                                                x-text="quickView.data.vendor?.name || 'N/A'"></span></div>
                                        <div><strong>Vendor Deal ID:</strong> <span class="font-mono text-indigo-600"
                                                x-text="quickView.data.vendor_deal_id || 'Chưa cấp'"></span></div>
                                        <div><strong>Sales PIC:</strong> <span class="font-bold text-slate-900"
                                                x-text="quickView.data.manager?.name || 'Chưa gán'"></span></div>
                                        <div><strong>Đội ngũ (Team):</strong> <span
                                                x-text="quickView.data.assigned_team || quickView.data.manager?.department || 'N/A'"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. SLA & Timelines -->
                                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                                    <h4 class="font-bold text-slate-800 text-xs text-indigo-700">Tiến Độ SLA & Hạn Xử Lý
                                    </h4>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div><strong>Hạn PM tiếp nhận SLA:</strong> <span
                                                x-text="quickView.data.initial_sla_due_at || 'Không có'"></span></div>
                                        <div><strong>Hạn Vendor phản hồi:</strong> <span
                                                x-text="quickView.data.vendor_due_at || 'Không có'"></span></div>
                                        <div><strong>Ngày tạo:</strong> <span x-text="quickView.data.created_at"></span>
                                        </div>
                                        <div><strong>Cập nhật gần nhất:</strong> <span
                                                x-text="quickView.data.updated_at"></span></div>
                                    </div>
                                </div>

                                <!-- 4. Linked Sales Orders -->
                                <div class="space-y-2">
                                    <h4 class="font-bold text-slate-800 text-xs text-emerald-700">Đơn Hàng Bán Đã Chốt Thuộc
                                        Dự Án Này</h4>
                                    <template x-if="quickView.data.sales_list && quickView.data.sales_list.length > 0">
                                        <div class="space-y-2">
                                            <template x-for="sale in quickView.data.sales_list" :key="sale.id">
                                                <div
                                                    class="p-3 bg-emerald-50/50 rounded-xl border border-emerald-200 flex justify-between items-center">
                                                    <div>
                                                        <span class="font-mono font-bold text-slate-900"
                                                            x-text="sale.code"></span>
                                                        <span class="text-slate-400 ml-2" x-text="sale.date"></span>
                                                    </div>
                                                    <span class="font-black text-emerald-700"
                                                        x-text="formatCurrency(sale.total)"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!quickView.data.sales_list || quickView.data.sales_list.length === 0">
                                        <p class="text-slate-400 italic">Chưa có đơn hàng bán nào được tạo cho dự án này.
                                        </p>
                                    </template>
                                </div>

                                <!-- 5. Status Updates & Notes -->
                                <div class="space-y-2">
                                    <h4 class="font-bold text-slate-800 text-xs text-slate-700">Lịch Sử Cập Nhật Của Sales
                                    </h4>
                                    <template
                                        x-if="quickView.data.status_updates && quickView.data.status_updates.length > 0">
                                        <div class="space-y-2 max-h-40 overflow-y-auto custom-scrollbar">
                                            <template x-for="(up, idx) in quickView.data.status_updates" :key="idx">
                                                <div
                                                    class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-[11px]">
                                                    <div class="flex justify-between font-semibold text-slate-700 mb-0.5">
                                                        <span x-text="up.user_name + ' (' + up.stage + ')'"></span>
                                                        <span class="text-slate-400" x-text="up.created_at"></span>
                                                    </div>
                                                    <p class="text-slate-600" x-text="up.note || 'Không có ghi chú'"></p>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template
                                        x-if="!quickView.data.status_updates || quickView.data.status_updates.length === 0">
                                        <p class="text-slate-400 italic">Chưa có nhật ký cập nhật.</p>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- SALE ORDER QUICK VIEW DETAILS -->
                        <template x-if="!quickView.loading && quickView.data && quickView.type === 'sale'">
                            <div class="space-y-6 text-xs">
                                <!-- 1. Financial Metric Summary -->
                                <div class="grid grid-cols-4 gap-3">
                                    <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200">
                                        <span class="text-[10px] text-emerald-700 font-bold uppercase">Doanh thu (chưa VAT)</span>
                                        <div class="text-base font-black text-slate-900 mt-0.5"
                                            x-text="formatCurrency(quickView.data.revenue_excluding_vat)"></div>
                                    </div>
                                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                                        <span class="text-[10px] text-slate-500 font-bold uppercase">Giá Vốn (Cost)</span>
                                        <div class="text-sm font-bold text-slate-800 mt-0.5"
                                            x-text="formatCurrency(quickView.data.cost)"></div>
                                    </div>
                                    <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200">
                                        <span class="text-[10px] text-emerald-700 font-bold uppercase">Lợi Nhuận Gộp</span>
                                        <div class="text-sm font-black text-emerald-700 mt-0.5"
                                            x-text="formatCurrency(quickView.data.margin) + ' (' + quickView.data.margin_percent + '%)'">
                                        </div>
                                    </div>
                                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200">
                                        <span class="text-[10px] text-amber-700 font-bold uppercase">Công Nợ</span>
                                        <div class="text-sm font-black text-amber-700 mt-0.5"
                                            x-text="formatCurrency(quickView.data.debt_amount)"></div>
                                    </div>
                                </div>

                                <!-- 2. Customer & Sales Info -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                                        <h4 class="font-bold text-slate-800 text-xs mb-2 text-emerald-700">Khách Hàng</h4>
                                        <div><strong>Tên:</strong> <span
                                                x-text="quickView.data.customer?.name || 'N/A'"></span></div>
                                        <div><strong>Mã số thuế:</strong> <span
                                                x-text="quickView.data.customer?.tax_code || 'N/A'"></span></div>
                                        <div><strong>Địa chỉ:</strong> <span
                                                x-text="quickView.data.customer?.address || 'N/A'"></span></div>
                                    </div>
                                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                                        <h4 class="font-bold text-slate-800 text-xs mb-2 text-emerald-700">Phụ Trách & Dự Án
                                        </h4>
                                        <div><strong>Sales:</strong> <span class="font-bold text-slate-900"
                                                x-text="quickView.data.sales_rep?.name || 'N/A'"></span></div>
                                        <div><strong>Dự án liên kết:</strong> <span
                                                class="font-mono text-indigo-600 font-bold"
                                                x-text="quickView.data.project?.code || 'Bán lẻ'"></span></div>
                                        <div><strong>Duyệt P&L:</strong> <span class="font-bold uppercase"
                                                :class="quickView.data.pl_status === 'approved' ? 'text-emerald-600' : 'text-amber-600'"
                                                x-text="quickView.data.pl_status || 'Chờ duyệt'"></span></div>
                                    </div>
                                </div>

                                <!-- 3. Line Items (Bảng Sản Phẩm) -->
                                <div class="space-y-2">
                                    <h4 class="font-bold text-slate-800 text-xs text-slate-700">Danh Sách Sản Phẩm Trong Đơn
                                    </h4>
                                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                                        <table class="w-full text-left text-xs">
                                            <thead class="bg-slate-100 font-bold text-slate-600">
                                                <tr>
                                                    <th class="p-2.5">Sản Phẩm</th>
                                                    <th class="p-2.5 text-center">SL</th>
                                                    <th class="p-2.5 text-right">Đơn Giá</th>
                                                    <th class="p-2.5 text-right">Thành Tiền</th>
                                                    <th class="p-2.5 text-right">Margin</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                <template x-for="(item, idx) in (quickView.data.items || [])" :key="idx">
                                                    <tr class="hover:bg-slate-50">
                                                        <td class="p-2.5 font-medium text-slate-800">
                                                            <div x-text="item.product_name"></div>
                                                            <span class="text-[10px] font-mono text-slate-400"
                                                                x-text="item.product_code"></span>
                                                        </td>
                                                        <td class="p-2.5 text-center font-bold" x-text="item.quantity"></td>
                                                        <td class="p-2.5 text-right"
                                                            x-text="formatCurrency(item.unit_price)"></td>
                                                        <td class="p-2.5 text-right font-bold text-slate-900"
                                                            x-text="formatCurrency(item.total)"></td>
                                                        <td class="p-2.5 text-right font-bold text-emerald-600"
                                                            x-text="formatCurrency(item.margin)"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- 4. Payment Milestones Schedule -->
                                <div class="space-y-2">
                                    <h4 class="font-bold text-slate-800 text-xs text-slate-700">Lịch Trình Các Đợt Thanh
                                        Toán</h4>
                                    <div class="space-y-2">
                                        <template x-for="(ms, idx) in (quickView.data.payment_schedules || [])" :key="idx">
                                            <div
                                                class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex justify-between items-center">
                                                <div>
                                                    <span class="font-bold text-slate-800"
                                                        x-text="ms.milestone_name"></span>
                                                    <span class="text-slate-400 ml-2"
                                                        x-text="'Hạn: ' + (ms.due_date || 'Chưa xác định')"></span>
                                                </div>
                                                <div class="text-right">
                                                    <span class="font-black text-slate-900"
                                                        x-text="formatCurrency(ms.amount)"></span>
                                                    <span class="text-[10px] text-slate-500 ml-1"
                                                        x-text="'(' + ms.percentage + '%)'"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Drawer Footer -->
                    <div class="p-4 border-t border-slate-200 bg-slate-50 flex justify-end">
                        <button @click="quickView.open = false"
                            class="px-5 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl transition-all cursor-pointer">
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
                loading: false,
                filterError: '',
                dealFilterStatus: 'all',
                dealSearchText: '',
                orderSearchText: '',

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
                detailed_deals: @json($detailed_deals ?? ['items' => []]),
                sales_orders: @json($sales_orders ?? ['items' => []]),
                sales_performance: @json($sales_performance ?? []),
                detailed_inventory: @json($detailed_inventory ?? ['items' => []]),
                detailed_marketing: @json($detailed_marketing ?? ['items' => []]),
                cross_view_360: @json($cross_view_360 ?? null),

                quickView: {
                    open: false,
                    loading: false,
                    type: 'deal',
                    data: null,
                },

                init() {
                    // Initial setup
                },

                onPeriodChange() {
                    if (this.filters.period_type !== 'custom') {
                        // Prevent the previous period's generated dates from
                        // being submitted as a custom range.
                        this.filters.date_from = '';
                        this.filters.date_to = '';
                        this.applyFilters();
                    }
                },

                applyFilters() {
                    this.loading = true;
                    this.filterError = '';
                    fetch('{{ route("dashboard.bod-filter") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(this.filters)
                    })
                        .then(async res => {
                            const payload = await res.json();
                            if (!res.ok || !payload.success) {
                                throw new Error(payload.message || 'Không thể tải dữ liệu theo bộ lọc.');
                            }
                            return payload;
                        })
                        .then(res => {
                            this.loading = false;
                            if (res.success && res.data) {
                                this.bottlenecks = res.data.bottlenecks || {};
                                this.pipeline = res.data.pipeline || {};
                                this.inventory = res.data.inventory || {};
                                this.marketing = res.data.marketing || {};
                                this.kpi_matrix = res.data.kpi_matrix || {};
                                this.detailed_deals = res.data.detailed_deals || { items: [] };
                                this.sales_orders = res.data.sales_orders || { items: [] };
                                this.sales_performance = res.data.sales_performance || [];
                                this.detailed_inventory = res.data.detailed_inventory || { items: [] };
                                this.detailed_marketing = res.data.detailed_marketing || { items: [] };
                                this.cross_view_360 = res.data.cross_view_360;
                            }
                        })
                        .catch(err => {
                            this.loading = false;
                            this.filterError = err.message || 'Không thể tải dữ liệu theo bộ lọc. Vui lòng thử lại.';
                            console.error('Filter error:', err);
                        });
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

                switchTabTo(tabName, filterKey = '') {
                    this.activeTab = tabName;
                    if (tabName === 'pipeline' && filterKey) {
                        this.dealFilterStatus = filterKey;
                    }
                },

                openQuickView(type, id) {
                    this.quickView.open = true;
                    this.quickView.loading = true;
                    this.quickView.type = type;
                    this.quickView.data = null;

                    fetch(`{{ route("dashboard.bod-entity-detail") }}?type=${type}&id=${id}`)
                        .then(res => res.json())
                        .then(res => {
                            this.quickView.loading = false;
                            if (res.success && res.data) {
                                this.quickView.data = res.data;
                            }
                        })
                        .catch(err => {
                            this.quickView.loading = false;
                            console.error('Entity detail error:', err);
                        });
                },

                // Deals List Filters
                get filteredDealsList() {
                    let list = this.detailed_deals.items || [];

                    // Filter by status tab
                    if (this.dealFilterStatus === 'sla_overdue') {
                        list = list.filter(d => d.is_sla_overdue);
                    } else if (this.dealFilterStatus === 'active') {
                        list = list.filter(d => !['closed_won', 'closed_lost', 'cancelled', 'expired'].includes(d.registration_status));
                    } else if (this.dealFilterStatus === 'closed_won') {
                        list = list.filter(d => d.registration_status === 'closed_won');
                    } else if (this.dealFilterStatus === 'closed_lost') {
                        list = list.filter(d => d.registration_status === 'closed_lost');
                    } else if (this.dealFilterStatus === 'nearing_expiry') {
                        list = list.filter(d => d.is_nearing_expiry);
                    }

                    // Search by text
                    if (this.dealSearchText.trim()) {
                        const q = this.dealSearchText.toLowerCase();
                        list = list.filter(d =>
                            (d.name && d.name.toLowerCase().includes(q)) ||
                            (d.code && d.code.toLowerCase().includes(q)) ||
                            (d.customer_name && d.customer_name.toLowerCase().includes(q)) ||
                            (d.vendor_name && d.vendor_name.toLowerCase().includes(q)) ||
                            (d.manager_name && d.manager_name.toLowerCase().includes(q))
                        );
                    }

                    return list;
                },

                countDealsByFilter(status) {
                    const list = this.detailed_deals.items || [];
                    if (status === 'sla_overdue') return list.filter(d => d.is_sla_overdue).length;
                    if (status === 'active') return list.filter(d => !['closed_won', 'closed_lost', 'cancelled', 'expired'].includes(d.registration_status)).length;
                    if (status === 'closed_won') return list.filter(d => d.registration_status === 'closed_won').length;
                    if (status === 'closed_lost') return list.filter(d => d.registration_status === 'closed_lost').length;
                    if (status === 'nearing_expiry') return list.filter(d => d.is_nearing_expiry).length;
                    return list.length;
                },

                calculateFilteredDealsTotal() {
                    return this.filteredDealsList.reduce((acc, cur) => acc + (cur.deal_value || 0), 0);
                },

                // Sales Orders Filters
                get filteredOrdersList() {
                    let list = this.sales_orders.items || [];
                    if (this.orderSearchText.trim()) {
                        const q = this.orderSearchText.toLowerCase();
                        list = list.filter(o =>
                            (o.code && o.code.toLowerCase().includes(q)) ||
                            (o.customer_name && o.customer_name.toLowerCase().includes(q)) ||
                            (o.sales_name && o.sales_name.toLowerCase().includes(q)) ||
                            (o.project_code && o.project_code.toLowerCase().includes(q))
                        );
                    }
                    return list;
                },

                getStatusLabel(status) {
                    const map = {
                        'pending_intake': 'Chờ PM tiếp nhận',
                        'intake_approved': 'PM đã duyệt',
                        'vendor_processing': 'Hãng đang xử lý',
                        'vendor_quoted': 'Hãng đã báo giá',
                        'vendor_rejected': 'Hãng từ chối',
                        'closed_won': 'Thành công (Won)',
                        'closed_lost': 'Thất bại (Lost)',
                        'expired': 'Hết hạn',
                        'duplicate': 'Trùng lặp',
                        'processing': 'Đang xử lý',
                    };
                    return map[status] || status || 'Chưa rõ';
                },

                getStatusBadgeClass(status) {
                    const map = {
                        'closed_won': 'bg-emerald-100 text-emerald-800 border border-emerald-300',
                        'closed_lost': 'bg-rose-100 text-rose-800 border border-rose-300',
                        'pending_intake': 'bg-indigo-100 text-indigo-800 border border-indigo-200',
                        'vendor_processing': 'bg-sky-100 text-sky-800 border border-sky-200',
                        'vendor_quoted': 'bg-purple-100 text-purple-800 border border-purple-200',
                        'expired': 'bg-amber-100 text-amber-800 border border-amber-200',
                        'duplicate': 'bg-slate-100 text-slate-600 border border-slate-300',
                    };
                    return map[status] || 'bg-slate-100 text-slate-700';
                },

                formatCurrency(amount) {
                    if (!amount || isNaN(amount)) return '0 ₫';
                    return new Intl.NumberFormat('en-US').format(Math.round(amount)) + ' ₫';
                }
            }
        }
    </script>
@endsection
