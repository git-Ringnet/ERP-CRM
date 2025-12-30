@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Filter Section -->
    <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
        <form action="{{ route('dashboard') }}" method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">Lọc theo:</label>
                <select name="filter" id="filterType" onchange="handleFilterChange()" 
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="today" {{ $filterType == 'today' ? 'selected' : '' }}>Hôm nay</option>
                    <option value="week" {{ $filterType == 'week' ? 'selected' : '' }}>Tuần này</option>
                    <option value="month" {{ $filterType == 'month' ? 'selected' : '' }}>Tháng này</option>
                    <option value="quarter" {{ $filterType == 'quarter' ? 'selected' : '' }}>Quý này</option>
                    <option value="year" {{ $filterType == 'year' ? 'selected' : '' }}>Năm nay</option>
                    <option value="custom" {{ $filterType == 'custom' ? 'selected' : '' }}>Tùy chọn</option>
                </select>
            </div>
            
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Từ ngày</label>
                <input type="date" name="date_from" id="dateFrom" value="{{ request('date_from', $startDate->format('Y-m-d')) }}" 
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Đến ngày</label>
                <input type="date" name="date_to" id="dateTo" value="{{ request('date_to', $endDate->format('Y-m-d')) }}" 
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm">
                <i class="fas fa-filter mr-1"></i> Lọc
            </button>
            
            <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors text-sm">
                <i class="fas fa-redo mr-1"></i> Đặt lại
            </a>
        </form>
    </div>

    <!-- Summary Cards - 4 cards only -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Products Card -->
        <a href="/products" class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all transform hover:-translate-y-1">
            <div class="flex items-center justify-between text-white">
                <div>
                    <p class="text-blue-100 text-sm font-medium mb-1">Sản phẩm</p>
                    <p class="text-3xl font-bold">{{ $totalProducts }}</p>
                    <p class="text-blue-100 text-xs mt-2">{{ $totalProductItems }} items trong kho</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-box text-2xl"></i>
                </div>
            </div>
        </a>

        <!-- Warehouses Card -->
        <a href="/warehouses" class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all transform hover:-translate-y-1">
            <div class="flex items-center justify-between text-white">
                <div>
                    <p class="text-green-100 text-sm font-medium mb-1">Kho hàng</p>
                    <p class="text-3xl font-bold">{{ $totalWarehouses }}</p>
                    <p class="text-green-100 text-xs mt-2">Điểm lưu trữ</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-warehouse text-2xl"></i>
                </div>
            </div>
        </a>

        <!-- Transactions Card -->
        <a href="{{ route('reports.transaction-report') }}" class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all transform hover:-translate-y-1">
            <div class="flex items-center justify-between text-white">
                <div>
                    <p class="text-purple-100 text-sm font-medium mb-1">Giao dịch</p>
                    <p class="text-3xl font-bold">{{ $totalTransactions }}</p>
                    <p class="text-purple-100 text-xs mt-2">{{ $pendingTransactions }} chờ duyệt</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-exchange-alt text-2xl"></i>
                </div>
            </div>
        </a>

        <!-- Inventory Value Card -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between text-white">
                <div>
                    <p class="text-orange-100 text-sm font-medium mb-1">Giá trị tồn kho</p>
                    <p class="text-2xl font-bold">VND {{ number_format($totalInventoryValue, 0) }}</p>
                    <p class="text-orange-100 text-xs mt-2">Tổng giá trị</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-dollar-sign text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Period Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-3">
                <i class="fas fa-arrow-down text-green-600"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500">Nhập kho</p>
                <p class="text-xl font-bold text-green-600">{{ $periodImports }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 flex items-center">
            <div class="bg-red-100 rounded-full p-3 mr-3">
                <i class="fas fa-arrow-up text-red-600"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500">Xuất kho</p>
                <p class="text-xl font-bold text-red-600">{{ $periodExports }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 flex items-center">
            <div class="bg-purple-100 rounded-full p-3 mr-3">
                <i class="fas fa-exchange-alt text-purple-600"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500">Chuyển kho</p>
                <p class="text-xl font-bold text-purple-600">{{ $periodTransfers }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 flex items-center">
            <div class="bg-yellow-100 rounded-full p-3 mr-3">
                <i class="fas fa-clock text-yellow-600"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500">Chờ duyệt</p>
                <p class="text-xl font-bold text-yellow-600">{{ $pendingTransactions }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-3">
                <i class="fas fa-users text-blue-600"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500">Khách hàng</p>
                <p class="text-xl font-bold text-blue-600">{{ $totalCustomers }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 flex items-center">
            <div class="bg-teal-100 rounded-full p-3 mr-3">
                <i class="fas fa-user-tie text-teal-600"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500">Nhân viên</p>
                <p class="text-xl font-bold text-teal-600">{{ $totalEmployees }}</p>
            </div>
        </div>
    </div>

    <!-- Charts Section Row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Transactions Line Chart -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Giao dịch theo thời gian</h2>
                <i class="fas fa-chart-line text-blue-500"></i>
            </div>
            <div class="relative" style="height: 280px;">
                <canvas id="transactionsLineChart"></canvas>
            </div>
        </div>

        <!-- Transaction Types - Doughnut Chart -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Phân loại giao dịch</h2>
                <i class="fas fa-chart-pie text-purple-500"></i>
            </div>
            <div class="relative" style="height: 220px;">
                <canvas id="transactionTypeChart"></canvas>
            </div>
            <div class="mt-4 grid grid-cols-3 gap-3">
                <div class="bg-green-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-green-600 font-medium">Nhập kho</p>
                    <p class="text-xl font-bold text-green-700">{{ $transactionsByType['import'] ?? 0 }}</p>
                </div>
                <div class="bg-red-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-red-600 font-medium">Xuất kho</p>
                    <p class="text-xl font-bold text-red-700">{{ $transactionsByType['export'] ?? 0 }}</p>
                </div>
                <div class="bg-purple-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-purple-600 font-medium">Chuyển kho</p>
                    <p class="text-xl font-bold text-purple-700">{{ $transactionsByType['transfer'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section Row 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Customer Types Chart -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Phân loại khách hàng</h2>
                <i class="fas fa-chart-pie text-blue-500"></i>
            </div>
            <div class="relative" style="height: 200px;">
                <canvas id="customerTypeChart"></canvas>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="bg-blue-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-blue-600 font-medium">Thường</p>
                    <p class="text-xl font-bold text-blue-700">{{ $normalCustomers }}</p>
                </div>
                <div class="bg-yellow-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-yellow-600 font-medium">VIP</p>
                    <p class="text-xl font-bold text-yellow-700">{{ $vipCustomers }}</p>
                </div>
            </div>
        </div>

        <!-- Employee Departments Chart -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Nhân viên theo phòng ban</h2>
                <i class="fas fa-chart-bar text-purple-500"></i>
            </div>
            <div class="relative" style="height: 200px;">
                <canvas id="departmentChart"></canvas>
            </div>
            <div class="mt-4 text-center">
                <p class="text-sm text-gray-600">Tổng: <span class="font-bold text-purple-600">{{ $totalEmployees }}</span> nhân viên</p>
            </div>
        </div>

        <!-- Stock by Warehouse - Horizontal Bar Chart -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Tồn kho theo kho</h2>
                <i class="fas fa-chart-bar text-teal-500"></i>
            </div>
            <div class="relative" style="height: 200px;">
                <canvas id="stockByWarehouseChart"></canvas>
            </div>
            <div class="mt-4 text-center">
                <p class="text-sm text-gray-600">Top 5 kho có tồn kho cao nhất</p>
            </div>
        </div>
    </div>


    <!-- Recent Activities -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-history mr-2 text-gray-400"></i>
                    Hoạt động gần đây
                </h2>
                <span class="text-sm text-gray-500">Trang {{ $page }}/{{ $totalActivityPages }} ({{ $totalActivities }} hoạt động)</span>
            </div>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Loại</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tên</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Thời gian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentActivities as $activity)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($activity['type'] === 'customer')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                        <i class="fas fa-users mr-1.5"></i>Khách hàng
                                    </span>
                                @elseif($activity['type'] === 'supplier')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                        <i class="fas fa-truck mr-1.5"></i>Nhà cung cấp
                                    </span>
                                @elseif($activity['type'] === 'employee')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                        <i class="fas fa-user-tie mr-1.5"></i>Nhân viên
                                    </span>
                                @elseif($activity['type'] === 'product')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-700">
                                        <i class="fas fa-box mr-1.5"></i>Sản phẩm
                                    </span>
                                @elseif($activity['type'] === 'transaction_import')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                        <i class="fas fa-arrow-down mr-1.5"></i>Nhập kho
                                    </span>
                                @elseif($activity['type'] === 'transaction_export')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                        <i class="fas fa-arrow-up mr-1.5"></i>Xuất kho
                                    </span>
                                @elseif($activity['type'] === 'transaction_transfer')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                        <i class="fas fa-exchange-alt mr-1.5"></i>Chuyển kho
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <p class="text-sm font-medium text-gray-900">{{ $activity['name'] }}</p>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-clock mr-1 text-gray-400"></i>
                                    {{ \Carbon\Carbon::parse($activity['created_at'])->diffForHumans() }}
                                </p>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center">
                                <div class="text-gray-400">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p class="text-sm">Chưa có hoạt động nào</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($totalActivityPages > 1)
            <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4">
                <div class="text-sm text-gray-500">
                    Hiển thị {{ ($page - 1) * 5 + 1 }} - {{ min($page * 5, $totalActivities) }} / {{ $totalActivities }}
                </div>
                <div class="flex gap-2">
                    @if($page > 1)
                        <a href="?activity_page={{ $page - 1 }}" class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                            <i class="fas fa-chevron-left mr-1"></i> Trước
                        </a>
                    @endif
                    
                    @for($i = 1; $i <= min($totalActivityPages, 5); $i++)
                        <a href="?activity_page={{ $i }}" 
                           class="px-3 py-1 text-sm rounded {{ $i == $page ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ $i }}
                        </a>
                    @endfor
                    
                    @if($page < $totalActivityPages)
                        <a href="?activity_page={{ $page + 1 }}" class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                            Sau <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
// Handle filter type change - update date inputs
function handleFilterChange() {
    const filterType = document.getElementById('filterType').value;
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    const today = new Date();
    
    let startDate, endDate;
    
    switch(filterType) {
        case 'today':
            startDate = endDate = today;
            break;
        case 'week':
            const dayOfWeek = today.getDay();
            const diffToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
            startDate = new Date(today);
            startDate.setDate(today.getDate() + diffToMonday);
            endDate = new Date(startDate);
            endDate.setDate(startDate.getDate() + 6);
            break;
        case 'month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            break;
        case 'quarter':
            const quarter = Math.floor(today.getMonth() / 3);
            startDate = new Date(today.getFullYear(), quarter * 3, 1);
            endDate = new Date(today.getFullYear(), quarter * 3 + 3, 0);
            break;
        case 'year':
            startDate = new Date(today.getFullYear(), 0, 1);
            endDate = new Date(today.getFullYear(), 11, 31);
            break;
        case 'custom':
            // Keep current values for custom
            return;
    }
    
    dateFrom.value = formatDate(startDate);
    dateTo.value = formatDate(endDate);
}

function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// When date inputs change, switch to custom filter
document.getElementById('dateFrom')?.addEventListener('change', function() {
    document.getElementById('filterType').value = 'custom';
});
document.getElementById('dateTo')?.addEventListener('change', function() {
    document.getElementById('filterType').value = 'custom';
});

document.addEventListener('DOMContentLoaded', function() {
    // Transactions Line Chart
    const transactionsLineCtx = document.getElementById('transactionsLineChart');
    if (transactionsLineCtx) {
        const transactionsData = @json($transactionsChart);
        const labels = Object.keys(transactionsData).map(label => {
            // Check if it's a date format (YYYY-MM-DD)
            if (label.match(/^\d{4}-\d{2}-\d{2}$/)) {
                const d = new Date(label);
                return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' });
            }
            return label;
        });
        const data = Object.values(transactionsData);
        
        new Chart(transactionsLineCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Số giao dịch',
                    data: data,
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3B82F6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5
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
                        ticks: { stepSize: 1 },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // Transaction Types Doughnut Chart
    const transactionTypeCtx = document.getElementById('transactionTypeChart');
    if (transactionTypeCtx) {
        const typeData = @json($transactionsByType);
        new Chart(transactionTypeCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Nhập kho', 'Xuất kho', 'Chuyển kho'],
                datasets: [{
                    data: [typeData.import || 0, typeData.export || 0, typeData.transfer || 0],
                    backgroundColor: ['#10B981', '#EF4444', '#8B5CF6'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, font: { size: 11 } } }
                }
            }
        });
    }

    // Customer Types Chart
    const customerTypeCtx = document.getElementById('customerTypeChart');
    if (customerTypeCtx) {
        const customerTypeData = @json($customersByType);
        new Chart(customerTypeCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Thường', 'VIP'],
                datasets: [{
                    data: [customerTypeData.normal || 0, customerTypeData.vip || 0],
                    backgroundColor: ['#3B82F6', '#F59E0B'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { padding: 10, font: { size: 11 } } } }
            }
        });
    }

    // Employee Departments Chart
    const departmentCtx = document.getElementById('departmentChart');
    if (departmentCtx) {
        const departmentData = @json($employeesByDepartment);
        new Chart(departmentCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: Object.keys(departmentData),
                datasets: [{
                    label: 'Số nhân viên',
                    data: Object.values(departmentData),
                    backgroundColor: '#8B5CF6',
                    borderRadius: 6,
                    barThickness: 30
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0, 0, 0, 0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Stock by Warehouse - Horizontal Bar Chart
    const stockByWarehouseCtx = document.getElementById('stockByWarehouseChart');
    if (stockByWarehouseCtx) {
        const stockData = @json($stockByWarehouse);
        new Chart(stockByWarehouseCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: Object.keys(stockData),
                datasets: [{
                    label: 'Số lượng tồn',
                    data: Object.values(stockData),
                    backgroundColor: ['#14B8A6', '#06B6D4', '#0EA5E9', '#3B82F6', '#6366F1'],
                    borderRadius: 6,
                    barThickness: 25
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, grid: { color: 'rgba(0, 0, 0, 0.05)' } },
                    y: { grid: { display: false } }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
