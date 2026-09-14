@extends('layouts.app')

@section('title', 'Báo cáo Hiệu quả Tiếp cận & Chăm sóc Khách hàng')
@section('page-title', 'Báo cáo Tiếp cận & Khách hàng cần chăm sóc')

@push('styles')
    <style>
        .kpi-card {
            background: white;
            border-radius: 1rem;
            padding: 1.25rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.2s ease-in-out;
            position: relative;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
        }
        .chart-box {
            position: relative;
            height: 280px;
            width: 100%;
        }
    </style>
@endpush

@section('content')
    <div x-data="reportApp()" x-init="init()" class="space-y-6 pb-12">
        <!-- Header Info Alert -->
        <div class="bg-gradient-to-r from-blue-900 to-indigo-800 rounded-2xl p-6 text-white shadow-md flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2">
                    <i class="fas fa-chart-line text-blue-300"></i>
                    Báo cáo Hiệu quả Tiếp cận & Phễu Chăm sóc Khách hàng
                </h1>
                <p class="text-blue-100 text-sm mt-1 max-w-3xl leading-relaxed">
                    Theo dõi năng suất tiếp cận thị trường của Sales, mức độ tương tác với Đối tác (SI) / End User (EU) và cảnh báo các khách hàng đang bị bỏ quên cần chăm sóc lại.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <button type="button" @click="openGuideModal('all')"
                    class="px-3.5 py-2 bg-blue-700/80 hover:bg-blue-600 text-white font-semibold rounded-xl text-xs transition-all border border-blue-400/40 flex items-center gap-1.5 shadow-sm">
                    <i class="fas fa-circle-question text-blue-200 text-sm"></i> Hướng dẫn & Cách tính số liệu
                </button>
                <a href="{{ route('opportunities.create') }}" class="px-4 py-2 bg-white text-blue-900 hover:bg-blue-50 font-semibold rounded-xl text-xs transition-all shadow-sm flex items-center gap-1.5 whitespace-nowrap">
                    <i class="fas fa-plus-circle text-blue-600"></i> Lên lịch gặp mới
                </a>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="bg-white rounded-2xl shadow-sm p-5 border border-gray-200">
            <form method="GET" action="{{ route('opportunities.report') }}" id="filterForm" class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-3">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-calendar-alt text-primary"></i>
                        <span class="text-sm font-bold text-gray-700">Thời gian lọc:</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" @click="selectPeriod('today')"
                            :class="periodType === 'today' ? 'bg-primary text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-3 py-1.5 rounded-lg transition-colors text-xs font-semibold">
                            Hôm nay
                        </button>
                        <button type="button" @click="selectPeriod('week')"
                            :class="periodType === 'week' ? 'bg-primary text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-3 py-1.5 rounded-lg transition-colors text-xs font-semibold">
                            Tuần này
                        </button>
                        <button type="button" @click="selectPeriod('month')"
                            :class="periodType === 'month' ? 'bg-primary text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-3 py-1.5 rounded-lg transition-colors text-xs font-semibold">
                            Tháng này
                        </button>
                        <button type="button" @click="selectPeriod('quarter')"
                            :class="periodType === 'quarter' ? 'bg-primary text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-3 py-1.5 rounded-lg transition-colors text-xs font-semibold">
                            Quý này
                        </button>
                        <button type="button" @click="selectPeriod('year')"
                            :class="periodType === 'year' ? 'bg-primary text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-3 py-1.5 rounded-lg transition-colors text-xs font-semibold">
                            Năm nay
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                    <!-- Start Date -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Từ ngày</label>
                        <input type="text" name="start_date" x-model="startDate" x-ref="startDatePicker"
                            x-init="flatpickr($refs.startDatePicker, { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', locale: 'vn', defaultDate: startDate })"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-gray-50/50"
                            placeholder="Chọn ngày bắt đầu">
                    </div>

                    <!-- End Date -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Đến ngày</label>
                        <input type="text" name="end_date" x-model="endDate" x-ref="endDatePicker"
                            x-init="flatpickr($refs.endDatePicker, { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', locale: 'vn', defaultDate: endDate })"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-gray-50/50"
                            placeholder="Chọn ngày kết thúc">
                    </div>

                    <!-- Sales Rep (if Manager) -->
                    @if ($isManager)
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Nhân viên phụ trách</label>
                            <select name="assigned_to" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white">
                                <option value="">Tất cả nhân viên</option>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}" {{ $assignedTo == $u->id ? 'selected' : '' }}>
                                        {{ $u->name }} ({{ $u->employee_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <!-- Customer Partner (SI) -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Partner phụ trách</label>
                        <select name="customer_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white">
                            <option value="">Tất cả Partner</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}" {{ $customerId == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Search name / End User -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tìm Tên Khách/EU</label>
                        <input type="text" name="search_customer" value="{{ $searchCustomer }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white"
                            placeholder="Nhập tên đối tác...">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <div class="text-xs text-gray-500 italic">
                        <i class="fas fa-info-circle mr-1 text-blue-500"></i> Lọc dữ liệu theo kỳ để xem bảng cảnh báo và năng suất bán hàng chính xác.
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('opportunities.report') }}"
                            class="px-3.5 py-2 border border-gray-300 text-gray-700 bg-white rounded-lg hover:bg-gray-100 transition-colors text-xs font-semibold">
                            Làm mới
                        </a>
                        <button type="submit"
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-xs font-semibold shadow-sm flex items-center gap-1.5">
                            <i class="fas fa-filter text-xs"></i> Áp dụng bộ lọc
                        </button>
                    </div>
                </div>

                <input type="hidden" name="period_type" x-model="periodType">
            </form>
        </div>

        <!-- 4 Key Actionable Metrics Cards (With Clickable Info Icons) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Activities -->
            <div class="kpi-card border-l-4 border-l-blue-500">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Tổng lượt tiếp cận</span>
                        <button type="button" @click="openGuideModal('total')" class="text-gray-400 hover:text-blue-600 transition-colors" title="Bấm xem cách tính và lấy từ đâu">
                            <i class="fas fa-circle-info text-xs"></i>
                        </button>
                    </div>
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="fas fa-handshake text-base"></i>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-gray-900">{{ number_format($stats['total']) }}</span>
                    <span class="text-xs font-medium text-gray-500">lượt</span>
                </div>
                <div class="flex items-center mt-2 text-xs text-gray-600 font-medium">
                    <span class="text-green-600 font-bold mr-1">{{ number_format($stats['completed']) }}</span> đã hoàn thành ({{ $stats['completion_rate'] }}%)
                </div>
            </div>

            <!-- Unique Customers Cared -->
            <div class="kpi-card border-l-4 border-l-emerald-500">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Khách hàng được chăm sóc</span>
                        <button type="button" @click="openGuideModal('customers')" class="text-gray-400 hover:text-emerald-600 transition-colors" title="Bấm xem cách tính và lấy từ đâu">
                            <i class="fas fa-circle-info text-xs"></i>
                        </button>
                    </div>
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i class="fas fa-building text-base"></i>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-gray-900">{{ number_format($stats['unique_customers']) }}</span>
                    <span class="text-xs font-medium text-gray-500">đối tác/EU</span>
                </div>
                <div class="text-xs text-gray-500 mt-2">
                    Đã có ít nhất 1 tương tác trong kỳ
                </div>
            </div>

            <!-- Neglected Customers Alert -->
            <div class="kpi-card border-l-4 border-l-rose-500 bg-rose-50/20">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-700 flex items-center gap-1">
                            <i class="fas fa-triangle-exclamation text-rose-500"></i> Cảnh báo bỏ quên
                        </span>
                        <button type="button" @click="openGuideModal('neglected')" class="text-rose-400 hover:text-rose-600 transition-colors" title="Bấm xem cách tính và lấy từ đâu">
                            <i class="fas fa-circle-info text-xs"></i>
                        </button>
                    </div>
                    <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center">
                        <i class="fas fa-clock-rotate-left text-base"></i>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-rose-700">{{ number_format($stats['neglected_count']) }}</span>
                    <span class="text-xs font-bold text-rose-600">khách hàng</span>
                </div>
                <div class="text-xs text-rose-600 font-medium mt-2">
                    Quá 30 ngày chưa có ai liên hệ
                </div>
            </div>

            <!-- Converted to Projects -->
            <div class="kpi-card border-l-4 border-l-purple-500">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Chuyển đổi thành Dự án</span>
                        <button type="button" @click="openGuideModal('convert')" class="text-gray-400 hover:text-purple-600 transition-colors" title="Bấm xem cách tính và lấy từ đâu">
                            <i class="fas fa-circle-info text-xs"></i>
                        </button>
                    </div>
                    <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                        <i class="fas fa-project-diagram text-base"></i>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-purple-700">{{ number_format($stats['converted_projects']) }}</span>
                    <span class="text-xs font-medium text-gray-500">dự án</span>
                </div>
                <div class="flex items-center mt-2 text-xs text-gray-600 font-medium">
                    Tỷ lệ chuyển đổi: <strong class="text-purple-600 ml-1">{{ $stats['conversion_rate'] }}%</strong>
                </div>
            </div>
        </div>

        <!-- 2 Comparison Charts (Clean & High Value) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Customers Interaction Chart -->
            <div class="bg-white rounded-2xl shadow-sm p-5 border border-gray-200">
                <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                    <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-trophy text-emerald-500"></i>
                        Top Khách hàng / Partner được tương tác nhiều nhất
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400">Số lượt tiếp cận</span>
                        <button type="button" @click="openGuideModal('top_cust')" class="text-gray-400 hover:text-emerald-600" title="Giải thích biểu đồ">
                            <i class="fas fa-circle-info text-xs"></i>
                        </button>
                    </div>
                </div>
                <div class="chart-box">
                    @if (count($charts['top_customers']['counts']) > 0)
                        <canvas id="topCustomersChart"></canvas>
                    @else
                        <div class="flex flex-col items-center justify-center h-full text-gray-400">
                            <i class="fas fa-chart-bar text-3xl mb-2 text-gray-300"></i>
                            <p class="text-xs">Không có dữ liệu tương tác khách hàng trong kỳ này</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sales Productivity Chart (Manager or Goal) -->
            @if ($isManager)
                <div class="bg-white rounded-2xl shadow-sm p-5 border border-gray-200">
                    <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                        <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-users-gear text-blue-500"></i>
                            Năng suất Tiếp cận của Đội ngũ Sales
                        </h2>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-400">Tổng số cuộc gặp</span>
                            <button type="button" @click="openGuideModal('sales_prod')" class="text-gray-400 hover:text-blue-600" title="Giải thích biểu đồ">
                                <i class="fas fa-circle-info text-xs"></i>
                            </button>
                        </div>
                    </div>
                    <div class="chart-box">
                        @if (isset($charts['top_sales_reps']) && count($charts['top_sales_reps']['counts']) > 0)
                            <canvas id="topSalesRepsChart"></canvas>
                        @else
                            <div class="flex flex-col items-center justify-center h-full text-gray-400">
                                <i class="fas fa-user-group text-3xl mb-2 text-gray-300"></i>
                                <p class="text-xs">Chưa có dữ liệu hoạt động của nhân viên trong kỳ này</p>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <!-- Sales Rep Goal Card -->
                <div class="bg-gradient-to-br from-indigo-50 to-blue-50 rounded-2xl p-6 border border-blue-100 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-indigo-900 font-bold text-base mb-2">
                            <i class="fas fa-bullseye text-indigo-600 text-xl"></i>
                            Mục tiêu Chăm sóc Khách hàng
                        </div>
                        <p class="text-sm text-indigo-800 leading-relaxed">
                            Chủ động liên hệ lại các đối tác trong danh sách <strong>Cảnh báo bỏ quên</strong> bên dưới. Việc duy trì tần suất gặp gỡ định kỳ giúp tăng 60% tỷ lệ chuyển đổi cơ hội thành dự án thực tế.
                        </p>
                    </div>
                    <div class="mt-4 pt-4 border-t border-indigo-100 flex items-center justify-between">
                        <span class="text-xs font-semibold text-indigo-700">Khách hàng cần bạn chăm sóc:</span>
                        <span class="px-3 py-1 bg-rose-500 text-white font-bold rounded-full text-xs shadow-sm">
                            {{ count($neglectedCustomers) }} đối tác
                        </span>
                    </div>
                </div>
            @endif
        </div>

        <!-- 2-Tab Interactive Table Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden" x-data="{ activeTab: 'neglected', neglectedFilter: 'all' }">
            <!-- Tabs Navigation -->
            <div class="flex flex-wrap items-center justify-between border-b border-gray-200 px-6 pt-4 bg-gray-50/50">
                <div class="flex space-x-2">
                    <button type="button" @click="activeTab = 'neglected'"
                        :class="activeTab === 'neglected' ? 'border-primary text-primary bg-white shadow-sm' : 'border-transparent text-gray-500 hover:text-gray-700 bg-transparent'"
                        class="px-4 py-3 border-b-2 font-bold text-sm rounded-t-xl transition-all flex items-center gap-2">
                        <i class="fas fa-triangle-exclamation text-rose-500"></i>
                        <span>Khách hàng cần chăm sóc lại</span>
                        <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-rose-100 text-rose-700">
                            {{ count($neglectedCustomers) }}
                        </span>
                    </button>

                    <button type="button" @click="activeTab = 'activities'"
                        :class="activeTab === 'activities' ? 'border-primary text-primary bg-white shadow-sm' : 'border-transparent text-gray-500 hover:text-gray-700 bg-transparent'"
                        class="px-4 py-3 border-b-2 font-bold text-sm rounded-t-xl transition-all flex items-center gap-2">
                        <i class="fas fa-list-check text-blue-500"></i>
                        <span>Nhật ký Tiếp cận & Next Action</span>
                        <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-blue-100 text-blue-700">
                            {{ $activities->total() }}
                        </span>
                    </button>
                </div>

                <!-- Filter threshold in Neglected Tab -->
                <div x-show="activeTab === 'neglected'" class="flex items-center gap-2 pb-3 sm:pb-0">
                    <span class="text-xs font-semibold text-gray-500">Lọc mức độ:</span>
                    <select x-model="neglectedFilter" class="text-xs border border-gray-300 rounded-lg px-2.5 py-1.5 bg-white focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="all">Tất cả (> 30 ngày)</option>
                        <option value="60">> 60 ngày</option>
                        <option value="90">> 90 ngày</option>
                        <option value="never">Chưa từng gặp</option>
                    </select>
                </div>
            </div>

            <!-- Tab 1: Neglected Customers Table -->
            <div x-show="activeTab === 'neglected'" class="p-5">
                <div class="mb-4 bg-amber-50 border border-amber-200 rounded-xl p-3.5 text-xs text-amber-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-lightbulb text-amber-600 text-sm"></i>
                        <span>Danh sách các khách hàng đã lâu chưa có nhân viên nào liên hệ hoặc chưa từng gặp gỡ. Hãy bấm <strong>"Lên lịch gặp"</strong> để phân công chăm sóc lại.</span>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-left">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase">Khách hàng / Partner</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase">Phân loại</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase">Người phụ trách (AM)</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase">Lần gặp gần nhất</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase text-center">Thời gian chưa gặp</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase text-center">Tổng lần gặp</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase text-right">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($neglectedCustomers as $cust)
                                <tr class="hover:bg-gray-50/80 transition-colors"
                                    x-show="neglectedFilter === 'all' 
                                        || (neglectedFilter === '60' && {{ $cust->days_since }} >= 60 && {{ $cust->days_since }} < 999)
                                        || (neglectedFilter === '90' && {{ $cust->days_since }} >= 90)
                                        || (neglectedFilter === 'never' && {{ $cust->days_since }} === 999)">
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900">
                                        <a href="{{ route('customers.show', $cust->id) }}" class="hover:text-primary transition-colors">
                                            {{ $cust->name }}
                                        </a>
                                        @if ($cust->phone)
                                            <div class="text-xs text-gray-400 font-normal mt-0.5">
                                                <i class="fas fa-phone-alt text-[10px] mr-1"></i>{{ $cust->phone }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        <span class="px-2 py-0.5 rounded-md font-semibold {{ $cust->type === 'si' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                            {{ strtoupper($cust->type ?? 'SI') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 font-medium">
                                        {{ $cust->am ?: 'Chưa phân công' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $cust->last_meeting_formatted }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($cust->alert_level === 'high')
                                            <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-red-100 text-red-700 border border-red-200">
                                                {{ $cust->alert_text }}
                                            </span>
                                        @elseif ($cust->alert_level === 'medium')
                                            <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                                                {{ $cust->alert_text }}
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200">
                                                {{ $cust->alert_text }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm font-semibold text-gray-700">
                                        {{ $cust->total_meetings }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('opportunities.create', ['customer_id' => $cust->id]) }}"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary/10 hover:bg-primary text-primary hover:text-white rounded-lg text-xs font-bold transition-all">
                                            <i class="fas fa-calendar-plus text-xs"></i> Lên lịch gặp
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                        <i class="fas fa-shield-heart text-3xl mb-2 text-emerald-400"></i>
                                        <p class="text-sm font-semibold text-gray-600">Tuyệt vời! Tất cả khách hàng đều được chăm sóc định kỳ trong vòng 30 ngày qua.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 2: Detailed Activities & Next Action Table -->
            <div x-show="activeTab === 'activities'" class="p-5">
                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-left table-fixed">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="w-24 px-4 py-3 text-xs font-bold text-gray-500 uppercase">Ngày</th>
                                <th class="w-32 px-4 py-3 text-xs font-bold text-gray-500 uppercase">Nhân viên</th>
                                <th class="w-48 px-4 py-3 text-xs font-bold text-gray-500 uppercase">Khách hàng</th>
                                <th class="w-40 px-4 py-3 text-xs font-bold text-gray-500 uppercase">Loại hình / Trạng thái</th>
                                <th class="w-56 px-4 py-3 text-xs font-bold text-gray-500 uppercase">Vướng mắc / Pain points</th>
                                <th class="w-64 px-4 py-3 text-xs font-bold text-gray-500 uppercase">Kế hoạch tiếp theo (Next Action)</th>
                                <th class="w-28 px-4 py-3 text-xs font-bold text-gray-500 uppercase text-center">Dự án liên kết</th>
                                <th class="w-16 px-4 py-3 text-xs font-bold text-gray-500 uppercase text-center">Xem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($activities as $act)
                                <tr class="hover:bg-gray-50/80 transition-colors">
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $act->activity_date?->format('d/m/Y') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 font-semibold truncate" title="{{ $act->assignedTo->name ?? '—' }}">
                                        {{ $act->assignedTo->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 font-semibold truncate" title="{{ $act->customer_display_name }}">
                                        {{ $act->customer_display_name }}
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <div class="font-medium text-gray-800">{{ $act->activity_type_label }}</div>
                                        <span class="inline-block mt-1 px-2 py-0.5 rounded text-[11px] font-semibold {{ $act->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $act->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        @if ($act->pain_points)
                                            <div class="line-clamp-2 text-rose-700 font-medium" title="{{ $act->pain_points }}">
                                                <i class="fas fa-exclamation-circle mr-1 text-rose-500"></i>{{ $act->pain_points }}
                                            </div>
                                        @else
                                            <span class="text-gray-400 italic">Không có vướng mắc</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700">
                                        @if ($act->next_action)
                                            <div class="line-clamp-2 text-emerald-800 font-medium" title="{{ $act->next_action }}">
                                                <i class="fas fa-arrow-right mr-1 text-emerald-600"></i>{{ $act->next_action }}
                                            </div>
                                        @else
                                            <span class="text-gray-400 italic">Chưa ghi nhận</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-xs">
                                        @if ($act->project_id && $act->project)
                                            <a href="{{ route('projects.show', $act->project_id) }}" class="inline-flex items-center gap-1 px-2 py-1 bg-purple-100 text-purple-700 hover:bg-purple-200 rounded font-semibold transition-colors" title="{{ $act->project->name }}">
                                                <i class="fas fa-check-circle text-purple-600 text-[10px]"></i> Dự án
                                            </a>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('opportunities.show', $act->id) }}" class="text-primary hover:text-primary-dark font-semibold">
                                            <i class="fas fa-arrow-up-right-from-square"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                                        <i class="fas fa-calendar-times text-3xl mb-2 text-gray-300"></i>
                                        <p class="text-sm">Không có hoạt động nào trong khoảng thời gian đã chọn</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $activities->links() }}
                </div>
            </div>
        </div>

        <!-- Interactive Guide & Info Modal -->
        <div x-show="showGuideModal" 
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm transition-opacity"
             @keydown.escape.window="showGuideModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border border-gray-200 animate-in fade-in zoom-in duration-200"
                 @click.away="showGuideModal = false">
                
                <!-- Modal Header -->
                <div class="p-5 border-b border-gray-200 bg-gradient-to-r from-blue-900 to-indigo-800 text-white rounded-t-2xl flex items-center justify-between sticky top-0 z-10">
                    <div class="flex items-center gap-2.5">
                        <i class="fas fa-circle-question text-blue-300 text-lg"></i>
                        <h3 class="font-bold text-base">Hướng dẫn Ý nghĩa Số liệu & Quy trình Bán hàng</h3>
                    </div>
                    <button type="button" @click="showGuideModal = false" class="text-blue-200 hover:text-white text-lg p-1">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-5 text-sm text-gray-700">
                    <!-- Section 1: Nguồn gốc các con số -->
                    <div class="space-y-3">
                        <h4 class="font-bold text-gray-900 text-sm flex items-center gap-2 border-b border-gray-100 pb-1.5">
                            <i class="fas fa-calculator text-blue-600"></i> 1. Các con số trên báo cáo được lấy từ đâu?
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                            <div class="p-3 bg-blue-50/70 border border-blue-200 rounded-xl">
                                <strong class="text-blue-900 block mb-1">
                                    <i class="fas fa-handshake mr-1"></i> Tổng lượt tiếp cận:
                                </strong>
                                Đếm tất cả các bản ghi cuộc gặp / tiếp xúc được tạo trong menu <strong>Cơ hội</strong> trong khoảng thời gian đang lọc.
                            </div>

                            <div class="p-3 bg-green-50/70 border border-green-200 rounded-xl">
                                <strong class="text-green-900 block mb-1">
                                    <i class="fas fa-check-circle mr-1"></i> Đã hoàn thành (%):
                                </strong>
                                Là số cuộc gặp đã được Sales chuyển trạng thái sang <strong>"Đã hoàn thành"</strong> sau khi đi gặp khách về.
                            </div>

                            <div class="p-3 bg-emerald-50/70 border border-emerald-200 rounded-xl">
                                <strong class="text-emerald-900 block mb-1">
                                    <i class="fas fa-building mr-1"></i> Khách hàng được chăm sóc:
                                </strong>
                                Đếm số đối tác (Partner/SI) hoặc End User (EU) duy nhất có ít nhất 1 tương tác trong kỳ lọc.
                            </div>

                            <div class="p-3 bg-rose-50/70 border border-rose-200 rounded-xl">
                                <strong class="text-rose-900 block mb-1">
                                    <i class="fas fa-triangle-exclamation mr-1"></i> Cảnh báo bỏ quên:
                                </strong>
                                Đếm số khách hàng trong danh bạ mà đã <strong>quá 30 ngày (hoặc chưa từng có ai gặp)</strong> kể từ lần gặp cuối cùng.
                            </div>
                        </div>

                        <div class="p-3 bg-purple-50/70 border border-purple-200 rounded-xl text-xs">
                            <strong class="text-purple-900 block mb-1">
                                <i class="fas fa-project-diagram mr-1"></i> Chuyển đổi thành Dự án:
                            </strong>
                            Đếm số cơ hội sau khi tiếp cận đã được Sales bấm nút <strong>"Chuyển đổi thành Dự án"</strong> để triển khai hợp đồng/báo giá chính thức.
                        </div>
                    </div>

                    <!-- Section 2: Hướng dẫn thao tác cho Sales -->
                    <div class="space-y-3 pt-2">
                        <h4 class="font-bold text-gray-900 text-sm flex items-center gap-2 border-b border-gray-100 pb-1.5">
                            <i class="fas fa-user-check text-emerald-600"></i> 2. Sales thao tác như thế nào để hệ thống tính là "Đã liên hệ"?
                        </h4>
                        
                        <div class="space-y-2 text-xs leading-relaxed bg-gray-50 p-4 rounded-xl border border-gray-200">
                            <div class="flex items-start gap-2">
                                <span class="w-5 h-5 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center flex-shrink-0 text-[11px]">1</span>
                                <div><strong>Lên lịch tiếp cận:</strong> Vào menu <em>Cơ hội ➔ Thêm mới</em> (hoặc bấm <em>"Lên lịch gặp"</em> ngay ở bảng Khách hàng cần chăm sóc). Chọn tên Khách hàng, Ngày gặp và Người phụ trách.</div>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="w-5 h-5 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center flex-shrink-0 text-[11px]">2</span>
                                <div><strong>Hệ thống làm mới thời gian:</strong> Khách hàng đó ngay lập tức được ghi nhận ngày gặp mới nhất và biến mất khỏi danh sách cảnh báo bỏ quên.</div>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="w-5 h-5 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center flex-shrink-0 text-[11px]">3</span>
                                <div><strong>Sau khi gặp về:</strong> Vào sửa Cơ hội đó, chuyển sang <strong>"Đã hoàn thành"</strong>, điền ghi chú phản hồi & hành động tiếp theo (*Next Action*).</div>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="w-5 h-5 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center flex-shrink-0 text-[11px]">4</span>
                                <div><strong>Chốt dự án:</strong> Nếu khách đồng ý triển khai, bấm nút <strong>"Chuyển đổi thành Dự án"</strong> để hệ thống ghi nhận tỷ lệ chuyển đổi thành công.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex justify-end">
                    <button type="button" @click="showGuideModal = false"
                        class="px-4 py-2 bg-primary text-white font-semibold rounded-xl text-xs hover:bg-primary-dark transition-colors shadow-sm">
                        Đã hiểu
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function reportApp() {
                return {
                    periodType: '{{ $periodType }}',
                    startDate: '{{ $startDate }}',
                    endDate: '{{ $endDate }}',
                    showGuideModal: false,

                    topCustomersChart: null,
                    topSalesRepsChart: null,
                    initialized: false,

                    init() {
                        if (this.initialized) return;
                        this.initialized = true;

                        try {
                            this.initTopCustomersChart();
                            @if ($isManager)
                                this.initTopSalesRepsChart();
                            @endif
                        } catch (error) {
                            console.error('Lỗi khi khởi tạo biểu đồ:', error);
                        }
                    },

                    openGuideModal(topic = 'all') {
                        this.showGuideModal = true;
                    },

                    selectPeriod(period) {
                        this.periodType = period;
                        this.startDate = '';
                        this.endDate = '';
                        if (this.$refs.startDatePicker && this.$refs.startDatePicker._flatpickr) {
                            this.$refs.startDatePicker._flatpickr.clear();
                        }
                        if (this.$refs.endDatePicker && this.$refs.endDatePicker._flatpickr) {
                            this.$refs.endDatePicker._flatpickr.clear();
                        }
                        this.$nextTick(() => {
                            document.getElementById('filterForm').submit();
                        });
                    },

                    initTopCustomersChart() {
                        const canvas = document.getElementById('topCustomersChart');
                        if (!canvas) return;

                        const labels = {!! json_encode($charts['top_customers']['labels'] ?? []) !!};
                        const counts = {!! json_encode($charts['top_customers']['counts'] ?? []) !!};

                        if (labels.length === 0) return;

                        this.topCustomersChart = new Chart(canvas.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: labels.map(l => l.length > 20 ? l.substring(0, 20) + '...' : l),
                                datasets: [{
                                    label: 'Số lần tiếp cận',
                                    data: counts,
                                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                                    borderColor: 'rgb(16, 185, 129)',
                                    borderRadius: 6,
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            title: (items) => labels[items[0].dataIndex]
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        ticks: { stepSize: 1, precision: 0 }
                                    },
                                    y: {
                                        grid: { display: false }
                                    }
                                }
                            }
                        });
                    },

                    @if ($isManager)
                        initTopSalesRepsChart() {
                            const canvas = document.getElementById('topSalesRepsChart');
                            if (!canvas) return;

                            const labels = {!! json_encode($charts['top_sales_reps']['labels'] ?? []) !!};
                            const counts = {!! json_encode($charts['top_sales_reps']['counts'] ?? []) !!};

                            if (labels.length === 0) return;

                            this.topSalesRepsChart = new Chart(canvas.getContext('2d'), {
                                type: 'bar',
                                data: {
                                    labels: labels,
                                    datasets: [{
                                        label: 'Số cuộc gặp',
                                        data: counts,
                                        backgroundColor: 'rgba(59, 130, 246, 0.85)',
                                        borderColor: 'rgb(59, 130, 246)',
                                        borderRadius: 6,
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: { stepSize: 1, precision: 0 }
                                        },
                                        x: {
                                            grid: { display: false }
                                        }
                                    }
                                }
                            });
                        }
                    @endif
                };
            }
        </script>
    @endpush
@endsection


