@extends('layouts.app')

@section('title', 'Báo cáo tiếp cận & Tần suất gặp gỡ
')
@section('page-title', 'Báo cáo tiếp cận & Tần suất gặp gỡ
')

@push('styles')
    <style>
        .metric-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.25rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border: 1px solid #f3f4f6;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }

        @media (min-width: 768px) {
            .chart-container {
                height: 300px;
            }
        }
    </style>
@endpush

@section('content')
    <div x-data="reportApp()" x-init="init()" class="space-y-6 pb-12">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
               
                <p class="text-sm text-gray-500 mt-1">Phân tích tần suất tương tác, tiếp cận khách hàng qua CRM/Cơ hội.</p>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
            <form method="GET" action="{{ route('opportunities.report') }}" id="filterForm" class="space-y-4">
                <div class="flex flex-wrap items-center gap-3">
                    <label class="text-sm font-semibold text-gray-700">Thời gian:</label>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="selectPeriod('today')"
                            :class="periodType === 'today' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-3 py-1.5 rounded-lg transition-colors text-xs font-medium">
                            Hôm nay
                        </button>
                        <button type="button" @click="selectPeriod('week')"
                            :class="periodType === 'week' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-3 py-1.5 rounded-lg transition-colors text-xs font-medium">
                            Tuần này
                        </button>
                        <button type="button" @click="selectPeriod('month')"
                            :class="periodType === 'month' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-3 py-1.5 rounded-lg transition-colors text-xs font-medium">
                            Tháng này
                        </button>
                        <button type="button" @click="selectPeriod('quarter')"
                            :class="periodType === 'quarter' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-3 py-1.5 rounded-lg transition-colors text-xs font-medium">
                            Quý này
                        </button>
                        <button type="button" @click="selectPeriod('year')"
                            :class="periodType === 'year' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-3 py-1.5 rounded-lg transition-colors text-xs font-medium">
                            Năm nay
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                    <!-- Custom Start Date -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Từ ngày</label>
                        <input type="text" name="start_date" x-model="startDate" x-ref="startDatePicker"
                            x-init="flatpickr($refs.startDatePicker, { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', locale: 'vn', defaultDate: startDate })"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            placeholder="Chọn ngày bắt đầu">
                    </div>

                    <!-- Custom End Date -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Đến ngày</label>
                        <input type="text" name="end_date" x-model="endDate" x-ref="endDatePicker"
                            x-init="flatpickr($refs.endDatePicker, { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', locale: 'vn', defaultDate: endDate })"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            placeholder="Chọn ngày kết thúc">
                    </div>

                    <!-- Sales Rep (if Manager) -->
                    @if ($isManager)
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Nhân viên phụ trách</label>
                            <select name="assigned_to" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
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
                        <label class="block text-xs font-medium text-gray-600 mb-1">Partner phụ trách</label>
                        <select name="customer_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
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
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tìm Khách hàng/EU</label>
                        <input type="text" name="search_customer" value="{{ $searchCustomer }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            placeholder="Nhập tên đối tác...">
                    </div>

                    <!-- Activity Type -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Loại hoạt động</label>
                        <select name="activity_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">Tất cả hoạt động</option>
                            @foreach ($activityTypes as $val => $label)
                                <option value="{{ $val }}" {{ $activityType == $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Trạng thái</label>
                        <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">Tất cả trạng thái</option>
                            @foreach ($statuses as $val => $label)
                                <option value="{{ $val }}" {{ $status == $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <a href="{{ route('opportunities.report') }}"
                        class="px-4 py-2 border border-gray-350 text-gray-700 bg-white rounded-lg hover:bg-gray-100 transition-colors text-sm font-medium">
                        Làm mới bộ lọc
                    </a>
                    <button type="submit"
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm font-medium">
                        <i class="fas fa-filter mr-1 text-xs"></i> Áp dụng
                    </button>
                </div>

                <input type="hidden" name="period_type" x-model="periodType">
            </form>
        </div>

        <!-- Key Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Activities -->
            <div class="metric-card">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Số cuộc gặp / Tác vụ</h3>
                    <div class="p-2 rounded-lg bg-blue-50 text-blue-500">
                        <i class="fas fa-handshake text-lg"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total']) }}</p>
                <div class="text-xs text-gray-400 mt-2">Tổng số cơ hội tiếp cận đã tạo</div>
            </div>

            <!-- Completion Rate -->
            <div class="metric-card">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Tỷ lệ hoàn thành</h3>
                    <div class="p-2 rounded-lg bg-green-50 text-green-500">
                        <i class="fas fa-check-circle text-lg"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ $stats['completion_rate'] }}%</p>
                <div class="flex items-center mt-2 text-xs text-gray-500">
                    <span class="font-semibold text-green-600">{{ number_format($stats['completed']) }}</span>
                    <span class="ml-1">cuộc gặp đã hoàn thành</span>
                </div>
            </div>

            <!-- Total Duration -->
            <div class="metric-card">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Tổng thời lượng gặp</h3>
                    <div class="p-2 rounded-lg bg-amber-50 text-amber-500">
                        <i class="fas fa-clock text-lg"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900">
                    @if ($stats['total_duration'] >= 60)
                        {{ floor($stats['total_duration'] / 60) }}h {{ $stats['total_duration'] % 60 }}m
                    @else
                        {{ $stats['total_duration'] }}m
                    @endif
                </p>
                <div class="text-xs text-gray-400 mt-2">Tính theo tổng thời lượng cuộc họp</div>
            </div>

            <!-- Potential Rating -->
            <div class="metric-card">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Tiềm năng trung bình</h3>
                    <div class="p-2 rounded-lg bg-red-50 text-red-500">
                        <i class="fas fa-fire text-lg"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ $stats['avg_potential'] }}%</p>
                <div class="text-xs text-gray-400 mt-2">Dựa trên cơ hội có đánh giá khả thi</div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Timeline Chart -->
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200 lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-gray-800">
                        <i class="fas fa-chart-line text-blue-500 mr-1.5"></i>Xu hướng gặp gỡ theo ngày
                    </h2>
                </div>
                <div class="chart-container">
                    @if (count($charts['timeline']['counts']) > 0)
                        <canvas id="timelineChart"></canvas>
                    @else
                        <div class="flex flex-col items-center justify-center h-full text-gray-400">
                            <i class="fas fa-chart-line text-4xl mb-2"></i>
                            <p class="text-sm">Không có dữ liệu xu hướng cho khoảng thời gian này</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Status Distribution -->
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-gray-800">
                        <i class="fas fa-chart-pie text-indigo-500 mr-1.5"></i>Trạng thái cuộc gặp
                    </h2>
                </div>
                <div class="chart-container">
                    @if (count($charts['statuses']['counts']) > 0)
                        <canvas id="statusChart"></canvas>
                    @else
                        <div class="flex flex-col items-center justify-center h-full text-gray-400">
                            <i class="fas fa-chart-pie text-4xl mb-2"></i>
                            <p class="text-sm">Không có dữ liệu trạng thái</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Activity Type Distribution -->
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-gray-800">
                        <i class="fas fa-list-ul text-yellow-500 mr-1.5"></i>Phương thức tương tác
                    </h2>
                </div>
                <div class="chart-container">
                    @if (count($charts['activity_types']['counts']) > 0)
                        <canvas id="activityTypeChart"></canvas>
                    @else
                        <div class="flex flex-col items-center justify-center h-full text-gray-400">
                            <i class="fas fa-list-ul text-4xl mb-2"></i>
                            <p class="text-sm">Không có dữ liệu loại hoạt động</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Top Customers -->
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-gray-800">
                        <i class="fas fa-users text-green-500 mr-1.5"></i>Top 10 Khách hàng được chăm sóc
                    </h2>
                </div>
                <div class="chart-container">
                    @if (count($charts['top_customers']['counts']) > 0)
                        <canvas id="topCustomersChart"></canvas>
                    @else
                        <div class="flex flex-col items-center justify-center h-full text-gray-400">
                            <i class="fas fa-users text-4xl mb-2"></i>
                            <p class="text-sm">Không có dữ liệu khách hàng</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Top Sales Reps (Manager Only) -->
            @if ($isManager)
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-bold text-gray-800">
                            <i class="fas fa-user-friends text-orange-500 mr-1.5"></i>Tần suất đi gặp của Sales
                        </h2>
                    </div>
                    <div class="chart-container">
                        @if (count($charts['top_sales_reps']['counts']) > 0)
                            <canvas id="topSalesRepsChart"></canvas>
                        @else
                            <div class="flex flex-col items-center justify-center h-full text-gray-400">
                                <i class="fas fa-user-friends text-4xl mb-2"></i>
                                <p class="text-sm">Không có dữ liệu nhân viên</p>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <!-- Placeholder card to keep grid balanced for sales reps -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl shadow-sm p-6 border border-blue-100 flex flex-col justify-center items-center text-center">
                    <i class="fas fa-bullseye text-blue-500 text-4xl mb-3"></i>
                    <h3 class="text-base font-bold text-blue-900 mb-1">Mục tiêu hoạt động</h3>
                    <p class="text-xs text-blue-700 max-w-xs">Tăng cường gặp gỡ trực tiếp và trình bày demo sản phẩm để nâng cao tỷ lệ chuyển đổi cơ hội kinh doanh thành công!</p>
                </div>
            @endif
        </div>

        <!-- Detailed Feedback Table -->
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
            <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                <h2 class="text-base font-bold text-gray-800">
                    <i class="fas fa-comments text-pink-500 mr-1.5"></i>Phản hồi & Ý kiến từ khách hàng
                </h2>
                <span class="text-xs font-semibold px-2.5 py-1 bg-gray-100 text-gray-600 rounded-full">
                    Hiển thị {{ $activities->firstItem() ?? 0 }}-{{ $activities->lastItem() ?? 0 }} trên {{ $activities->total() }} cuộc gặp
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 table-fixed">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="w-24 px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Ngày</th>
                            <th scope="col" class="w-36 px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nhân viên</th>
                            <th scope="col" class="w-48 px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Khách hàng</th>
                            <th scope="col" class="w-44 px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Hoạt động</th>
                            <th scope="col" class="w-28 px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Tiềm năng</th>
                            <th scope="col" class="w-64 px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Ý kiến / Pain Points</th>
                            <th scope="col" class="w-64 px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kết quả / Next Action</th>
                            <th scope="col" class="w-20 px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Xem</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($activities as $act)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ $act->activity_date->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                                    {{ $act->assignedTo->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 font-medium truncate" title="{{ $act->customer_display_name }}">
                                    {{ $act->customer_display_name }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-gray-800">{{ $act->name }}</span>
                                        <span class="text-xs text-gray-400 mt-0.5">{{ $act->activity_type_label }} ({{ $act->duration_minutes }}m)</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($act->potential_rating)
                                        <span class="px-2 py-1 text-xs font-bold rounded-full {{ $act->potential_rating_color }}">
                                            {{ $act->potential_rating }}%
                                        </span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    <div class="space-y-1">
                                        @if ($act->customer_feedback)
                                            <div>
                                                <strong class="text-xs text-gray-500">Phản hồi:</strong>
                                                <div class="line-clamp-2 text-xs" title="{{ $act->customer_feedback }}">{{ $act->customer_feedback }}</div>
                                            </div>
                                        @endif
                                        @if ($act->pain_points)
                                            <div>
                                                <strong class="text-xs text-red-500">Pain Points:</strong>
                                                <div class="line-clamp-2 text-xs text-red-700" title="{{ $act->pain_points }}">{{ $act->pain_points }}</div>
                                            </div>
                                        @endif
                                        @if (!$act->customer_feedback && !$act->pain_points)
                                            <span class="text-gray-450 italic text-xs">Không ghi nhận</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    <div class="space-y-1">
                                        @if ($act->meeting_result)
                                            <div>
                                                <strong class="text-xs text-gray-500">Kết quả:</strong>
                                                <div class="line-clamp-2 text-xs" title="{{ $act->meeting_result }}">{{ $act->meeting_result }}</div>
                                            </div>
                                        @endif
                                        @if ($act->next_action)
                                            <div>
                                                <strong class="text-xs text-green-600">Next Action:</strong>
                                                <div class="line-clamp-2 text-xs text-green-700" title="{{ $act->next_action }}">{{ $act->next_action }}</div>
                                            </div>
                                        @endif
                                        @if (!$act->meeting_result && !$act->next_action)
                                            <span class="text-gray-450 italic text-xs">Không ghi nhận</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('opportunities.show', $act->id) }}" class="text-primary hover:text-primary-dark">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                                    <i class="fas fa-calendar-times text-3xl mb-2"></i>
                                    <p class="text-sm">Không có cuộc gặp nào khớp với bộ lọc trong khoảng thời gian này</p>
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

    @push('scripts')
        <script>
            function reportApp() {
                return {
                    periodType: '{{ $periodType }}',
                    startDate: '{{ $startDate }}',
                    endDate: '{{ $endDate }}',

                    // Chart instances
                    timelineChart: null,
                    statusChart: null,
                    activityTypeChart: null,
                    topCustomersChart: null,
                    topSalesRepsChart: null,

                    initialized: false,

                    init() {
                        if (this.initialized) return;
                        this.initialized = true;

                        try {
                            this.initTimelineChart();
                            this.initStatusChart();
                            this.initActivityTypeChart();
                            this.initTopCustomersChart();
                            @if ($isManager)
                                this.initTopSalesRepsChart();
                            @endif
                        } catch (error) {
                            console.error('Lỗi khi tải biểu đồ báo cáo:', error);
                        }
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

                    initTimelineChart() {
                        const canvas = document.getElementById('timelineChart');
                        if (!canvas) return;

                        const labels = {!! json_encode($charts['timeline']['labels']) !!};
                        const counts = {!! json_encode($charts['timeline']['counts']) !!};

                        this.timelineChart = new Chart(canvas.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Số cuộc gặp',
                                    data: counts,
                                    borderColor: 'rgb(59, 130, 246)',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    borderWidth: 2,
                                    tension: 0.3,
                                    fill: true
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
                                        ticks: { stepSize: 1 }
                                    }
                                }
                            }
                        });
                    },

                    initStatusChart() {
                        const canvas = document.getElementById('statusChart');
                        if (!canvas) return;

                        const labels = {!! json_encode($charts['statuses']['labels']) !!};
                        const counts = {!! json_encode($charts['statuses']['counts']) !!};

                        this.statusChart = new Chart(canvas.getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: labels,
                                datasets: [{
                                    data: counts,
                                    backgroundColor: [
                                        'rgb(59, 130, 246)',
                                        'rgb(16, 185, 129)',
                                        'rgb(245, 158, 11)',
                                        'rgb(239, 68, 68)',
                                        'rgb(139, 92, 246)',
                                        'rgb(156, 163, 175)'
                                    ]
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { position: 'bottom' }
                                }
                            }
                        });
                    },

                    initActivityTypeChart() {
                        const canvas = document.getElementById('activityTypeChart');
                        if (!canvas) return;

                        const labels = {!! json_encode($charts['activity_types']['labels']) !!};
                        const counts = {!! json_encode($charts['activity_types']['counts']) !!};

                        this.activityTypeChart = new Chart(canvas.getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: labels,
                                datasets: [{
                                    data: counts,
                                    backgroundColor: [
                                        'rgb(99, 102, 241)',
                                        'rgb(236, 72, 153)',
                                        'rgb(245, 158, 11)',
                                        'rgb(16, 185, 129)',
                                        'rgb(14, 165, 233)',
                                        'rgb(168, 85, 247)',
                                        'rgb(156, 163, 175)'
                                    ]
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { position: 'bottom' }
                                }
                            }
                        });
                    },

                    initTopCustomersChart() {
                        const canvas = document.getElementById('topCustomersChart');
                        if (!canvas) return;

                        const labels = {!! json_encode($charts['top_customers']['labels']) !!};
                        const counts = {!! json_encode($charts['top_customers']['counts']) !!};

                        this.topCustomersChart = new Chart(canvas.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: labels.map(l => l.length > 15 ? l.substring(0, 15) + '...' : l),
                                datasets: [{
                                    label: 'Số lần gặp',
                                    data: counts,
                                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                                    borderColor: 'rgb(16, 185, 129)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false }
                                },
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        ticks: { stepSize: 1 }
                                    }
                                }
                            }
                        });
                    },

                    @if ($isManager)
                        initTopSalesRepsChart() {
                            const canvas = document.getElementById('topSalesRepsChart');
                            if (!canvas) return;

                            const labels = {!! json_encode($charts['top_sales_reps']['labels']) !!};
                            const counts = {!! json_encode($charts['top_sales_reps']['counts']) !!};

                            this.topSalesRepsChart = new Chart(canvas.getContext('2d'), {
                                type: 'bar',
                                data: {
                                    labels: labels,
                                    datasets: [{
                                        label: 'Số cuộc gặp',
                                        data: counts,
                                        backgroundColor: 'rgba(249, 115, 22, 0.85)',
                                        borderColor: 'rgb(249, 115, 22)',
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
                                            ticks: { stepSize: 1 }
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
