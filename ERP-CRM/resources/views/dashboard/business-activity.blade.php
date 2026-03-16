@extends('layouts.app')

@section('title', 'Dashboard Hoạt Động Kinh Doanh')
@section('page-title', 'Dashboard Hoạt Động Kinh Doanh')

@push('styles')
<style>
    /* Metric card styles - match main dashboard */
    .metric-card {
        background: white;
        border-radius: 0.75rem;
        padding: 1rem;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        border: 1px solid #f3f4f6;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    .growth-positive { color: #10b981; }
    .growth-negative { color: #ef4444; }
    .growth-neutral { color: #6b7280; }
    
    /* Chart container - smaller */
    .chart-container {
        position: relative;
        height: 220px;
        width: 100%;
    }
    
    @media (min-width: 768px) {
        .chart-container {
            height: 280px;
        }
    }
    
    /* Responsive grid */
    .metrics-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    @media (min-width: 640px) {
        .metrics-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (min-width: 1024px) {
        .metrics-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }
    
    /* Top performers table */
    .top-performers-table {
        width: 100%;
        overflow-x: auto;
    }
    
    .top-performers-table table {
        min-width: 100%;
        font-size: 0.875rem;
    }
</style>
@endpush

@section('content')
<div x-data="dashboardApp()" x-init="init()" class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
           
            <p class="text-sm text-gray-600 mt-1">Tổng quan các chỉ số kinh doanh quan trọng</p>
        </div>
        
        <div class="flex flex-wrap gap-2 mt-4 md:mt-0">
            <!-- Refresh Button -->
            <form method="POST" action="{{ route('dashboard.business-activity.refresh') }}" class="inline">
                @csrf
                <button type="submit" 
                    class="inline-flex items-center px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors text-sm"
                    aria-label="Làm mới dữ liệu">
                    <i class="fas fa-sync-alt mr-1.5 text-xs"></i>
                    <span>Làm mới</span>
                </button>
            </form>
            
            <!-- Export Button (conditional on permission) -->
            @if(isset($can_export) && $can_export)
            <button @click="showExportModal = true" 
                class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm"
                aria-label="Xuất báo cáo">
                <i class="fas fa-download mr-1.5 text-xs"></i>
                <span>Xuất báo cáo</span>
            </button>
            @endif
        </div>
    </div>

    <!-- Error Messages -->
    @if(isset($error))
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6" role="alert" aria-live="polite">
        <div class="flex">
            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
            <p class="text-red-700">{{ $error }}</p>
        </div>
    </div>
    @endif
    
    @if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6" role="alert" aria-live="polite">
        <div class="flex">
            <i class="fas fa-check-circle text-green-500 mr-3"></i>
            <p class="text-green-700">{{ session('success') }}</p>
        </div>
    </div>
    @endif
    
    @if(session('error'))
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6" role="alert" aria-live="polite">
        <div class="flex">
            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
            <p class="text-red-700">{{ session('error') }}</p>
        </div>
    </div>
    @endif

    <!-- Time Period Filter -->
    <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
        <form method="GET" action="{{ route('dashboard.business-activity') }}" id="filterForm" class="flex flex-wrap items-center gap-4">
            <!-- Predefined Periods -->
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">Lọc theo:</label>
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="selectPeriod('today')" 
                        :class="periodType === 'today' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-3 py-1.5 rounded-lg transition-colors text-sm">
                        Hôm nay
                    </button>
                    <button type="button" @click="selectPeriod('week')" 
                        :class="periodType === 'week' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-3 py-1.5 rounded-lg transition-colors text-sm">
                        Tuần này
                    </button>
                    <button type="button" @click="selectPeriod('month')" 
                        :class="periodType === 'month' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-3 py-1.5 rounded-lg transition-colors text-sm">
                        Tháng này
                    </button>
                    <button type="button" @click="selectPeriod('quarter')" 
                        :class="periodType === 'quarter' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-3 py-1.5 rounded-lg transition-colors text-sm">
                        Quý này
                    </button>
                    <button type="button" @click="selectPeriod('year')" 
                        :class="periodType === 'year' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-3 py-1.5 rounded-lg transition-colors text-sm">
                        Năm nay
                    </button>
                </div>
            </div>
            
            <!-- Custom Date Range -->
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Từ ngày</label>
                <input type="text" name="start_date" x-model="startDate" x-ref="startDatePicker"
                    x-init="flatpickr($refs.startDatePicker, { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', locale: 'vn', defaultDate: startDate })"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    aria-label="Ngày bắt đầu" placeholder="Từ ngày">
            </div>
            
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">đến</label>
                <input type="text" name="end_date" x-model="endDate" x-ref="endDatePicker"
                    x-init="flatpickr($refs.endDatePicker, { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', locale: 'vn', defaultDate: endDate })"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    aria-label="Ngày kết thúc" placeholder="Đến ngày">
            </div>
            
            <!-- Apply Button -->
            <button type="submit" 
                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm">
                <i class="fas fa-filter mr-1"></i> Áp dụng
            </button>
            
            <input type="hidden" name="period_type" x-model="periodType">
        </form>
    </div>

    @if(!isset($error) && isset($metrics))
    <!-- Key Metrics Cards -->
    <div class="metrics-grid">
        <!-- Revenue Card -->
        <div class="metric-card">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-xs font-medium text-gray-600">Doanh thu</h3>
                <i class="fas fa-dollar-sign text-blue-500"></i>
            </div>
            <p class="text-xl font-bold text-gray-900" aria-label="Doanh thu hiện tại">
                {{ number_format($metrics['revenue']['current'], 0, ',', '.') }} ₫
            </p>
            @if($metrics['revenue']['growth_rate'] !== null)
            <div class="flex items-center mt-2">
                <span class="text-xs {{ $metrics['revenue']['trend'] === 'up' ? 'growth-positive' : ($metrics['revenue']['trend'] === 'down' ? 'growth-negative' : 'growth-neutral') }}">
                    <i class="fas fa-{{ $metrics['revenue']['trend'] === 'up' ? 'arrow-up' : ($metrics['revenue']['trend'] === 'down' ? 'arrow-down' : 'minus') }} mr-1"></i>
                    {{ number_format(abs($metrics['revenue']['growth_rate']), 1, ',', '.') }}%
                </span>
                <span class="text-xs text-gray-500 ml-1">so với kỳ trước</span>
            </div>
            @else
            <div class="text-xs text-gray-400 mt-2">Chưa có dữ liệu kỳ trước</div>
            @endif
        </div>
        
        <!-- Profit Card -->
        <div class="metric-card">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Lợi nhuận</h3>
                <i class="fas fa-chart-line text-green-500 text-xl"></i>
            </div>
            <p class="text-2xl font-bold text-gray-900" aria-label="Lợi nhuận hiện tại">
                {{ number_format($metrics['profit']['current'], 0, ',', '.') }} ₫
            </p>
            @if($metrics['profit']['growth_rate'] !== null)
            <div class="flex items-center mt-2">
                <span class="text-sm {{ $metrics['profit']['trend'] === 'up' ? 'growth-positive' : ($metrics['profit']['trend'] === 'down' ? 'growth-negative' : 'growth-neutral') }}">
                    <i class="fas fa-{{ $metrics['profit']['trend'] === 'up' ? 'arrow-up' : ($metrics['profit']['trend'] === 'down' ? 'arrow-down' : 'minus') }} mr-1"></i>
                    {{ number_format(abs($metrics['profit']['growth_rate']), 2, ',', '.') }}%
                </span>
                <span class="text-xs text-gray-500 ml-2">so với kỳ trước</span>
            </div>
            @else
            <div class="text-xs text-gray-400 mt-2">Chưa có dữ liệu kỳ trước</div>
            @endif
        </div>
        
        <!-- Profit Margin Card -->
        <div class="metric-card">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Tỷ suất lợi nhuận</h3>
                <i class="fas fa-percentage text-purple-500 text-xl"></i>
            </div>
            <p class="text-2xl font-bold text-gray-900" aria-label="Tỷ suất lợi nhuận">
                @if($metrics['profit_margin'] !== null)
                    {{ number_format($metrics['profit_margin'], 2, ',', '.') }}%
                @else
                    <span class="text-gray-400">--</span>
                @endif
            </p>
            <div class="text-sm text-gray-500 mt-2">Lợi nhuận / Doanh thu</div>
        </div>
        
        <!-- Purchase Cost Card -->
        <div class="metric-card">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Chi phí mua hàng</h3>
                <i class="fas fa-shopping-cart text-orange-500 text-xl"></i>
            </div>
            <p class="text-2xl font-bold text-gray-900" aria-label="Chi phí mua hàng">
                {{ number_format($metrics['purchase_cost']['current'], 0, ',', '.') }} ₫
            </p>
            @if($metrics['purchase_cost']['growth_rate'] !== null)
            <div class="flex items-center mt-2">
                <span class="text-sm {{ $metrics['purchase_cost']['trend'] === 'up' ? 'growth-positive' : ($metrics['purchase_cost']['trend'] === 'down' ? 'growth-negative' : 'growth-neutral') }}">
                    <i class="fas fa-{{ $metrics['purchase_cost']['trend'] === 'up' ? 'arrow-up' : ($metrics['purchase_cost']['trend'] === 'down' ? 'arrow-down' : 'minus') }} mr-1"></i>
                    {{ number_format(abs($metrics['purchase_cost']['growth_rate']), 2, ',', '.') }}%
                </span>
                <span class="text-xs text-gray-500 ml-2">so với kỳ trước</span>
            </div>
            @else
            <div class="text-sm text-gray-500 mt-2">Chưa có dữ liệu kỳ trước</div>
            @endif
        </div>
        
        <!-- Inventory Value Card -->
        <div class="metric-card">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Giá trị tồn kho</h3>
                <i class="fas fa-warehouse text-indigo-500 text-xl"></i>
            </div>
            <p class="text-2xl font-bold text-gray-900" aria-label="Giá trị tồn kho">
                @if($metrics['inventory_value'] !== null && $metrics['inventory_value'] > 0)
                    {{ number_format($metrics['inventory_value'], 0, ',', '.') }} ₫
                @else
                    <span class="text-gray-400">--</span>
                @endif
            </p>
            <div class="text-sm text-gray-500 mt-2">Tại thời điểm hiện tại</div>
        </div>
        
        <!-- Sales Count Card -->
        <div class="metric-card">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Số đơn bán hàng</h3>
                <i class="fas fa-receipt text-pink-500 text-xl"></i>
            </div>
            <p class="text-2xl font-bold text-gray-900" aria-label="Số đơn bán hàng">
                {{ number_format($metrics['sales_count']['current'], 0, ',', '.') }}
            </p>
            @if($metrics['sales_count']['growth_rate'] !== null)
            <div class="flex items-center mt-2">
                <span class="text-sm {{ $metrics['sales_count']['growth_rate'] > 0 ? 'growth-positive' : ($metrics['sales_count']['growth_rate'] < 0 ? 'growth-negative' : 'growth-neutral') }}">
                    <i class="fas fa-{{ $metrics['sales_count']['growth_rate'] > 0 ? 'arrow-up' : ($metrics['sales_count']['growth_rate'] < 0 ? 'arrow-down' : 'minus') }} mr-1"></i>
                    {{ number_format(abs($metrics['sales_count']['growth_rate']), 2, ',', '.') }}%
                </span>
                <span class="text-xs text-gray-500 ml-2">so với kỳ trước</span>
            </div>
            @else
            <div class="text-sm text-gray-500 mt-2">Chưa có dữ liệu kỳ trước</div>
            @endif
        </div>
        
        <!-- Purchase Orders Count Card -->
        <div class="metric-card">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Số đơn mua hàng</h3>
                <i class="fas fa-file-invoice text-cyan-500 text-xl"></i>
            </div>
            <p class="text-2xl font-bold text-gray-900" aria-label="Số đơn mua hàng">
                {{ number_format($metrics['purchase_orders_count']['current'], 0, ',', '.') }}
            </p>
            @if($metrics['purchase_orders_count']['growth_rate'] !== null)
            <div class="flex items-center mt-2">
                <span class="text-sm {{ $metrics['purchase_orders_count']['growth_rate'] > 0 ? 'growth-positive' : ($metrics['purchase_orders_count']['growth_rate'] < 0 ? 'growth-negative' : 'growth-neutral') }}">
                    <i class="fas fa-{{ $metrics['purchase_orders_count']['growth_rate'] > 0 ? 'arrow-up' : ($metrics['purchase_orders_count']['growth_rate'] < 0 ? 'arrow-down' : 'minus') }} mr-1"></i>
                    {{ number_format(abs($metrics['purchase_orders_count']['growth_rate']), 2, ',', '.') }}%
                </span>
                <span class="text-xs text-gray-500 ml-2">so với kỳ trước</span>
            </div>
            @else
            <div class="text-sm text-gray-500 mt-2">Chưa có dữ liệu kỳ trước</div>
            @endif
        </div>
    </div>

    <!-- Revenue and Profit Trend Chart -->
    @if(isset($charts['revenue_profit_trend']))
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Xu hướng Doanh thu & Lợi nhuận</h2>
            <i class="fas fa-chart-line text-blue-500"></i>
        </div>
        <div class="chart-container">
            <canvas id="revenueProfitChart" role="img" aria-label="Biểu đồ xu hướng doanh thu và lợi nhuận"></canvas>
        </div>
    </div>
    @endif

    <!-- Sales Performance Analysis -->
    @if(isset($sales_analysis))
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Phân tích Hiệu suất Bán hàng</h2>
            <i class="fas fa-chart-bar text-green-500"></i>
        </div>
        
        <!-- Sales Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 rounded-lg p-3 border border-gray-100">
                <div class="text-xs text-gray-600 mb-1">Đơn hoàn thành</div>
                <div class="text-xl font-bold text-blue-600">{{ number_format($sales_analysis['completed_count'], 0, ',', '.') }}</div>
            </div>
            <div class="bg-yellow-50 rounded-lg p-3 border border-gray-100">
                <div class="text-xs text-gray-600 mb-1">Đơn chờ xử lý</div>
                <div class="text-xl font-bold text-yellow-600">{{ number_format($sales_analysis['pending_count'], 0, ',', '.') }}</div>
            </div>
            <div class="bg-green-50 rounded-lg p-3 border border-gray-100">
                <div class="text-xs text-gray-600 mb-1">Giá trị trung bình</div>
                <div class="text-xl font-bold text-green-600">
                    @if($sales_analysis['average_value'] !== null)
                        {{ number_format($sales_analysis['average_value'], 0, ',', '.') }} ₫
                    @else
                        N/A
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Top Products, Customers and Payment Status - 3 columns -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Top Products -->
            @if(isset($top_performers['top_products']) && count($top_performers['top_products']) > 0)
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Top 10 Sản phẩm</h3>
                <div class="top-performers-table overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                                <th scope="col" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">SL</th>
                                <th scope="col" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($top_performers['top_products'] as $product)
                            <tr>
                                <td class="px-3 py-2 text-sm text-gray-900">{{ $product->product_name }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">{{ number_format($product->quantity_sold, 0, ',', '.') }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">{{ number_format($product->revenue, 0, ',', '.') }} ₫</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
            
            <!-- Top Customers -->
            @if(isset($top_performers['top_customers']) && count($top_performers['top_customers']) > 0)
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Top 10 Khách hàng</h3>
                <div class="top-performers-table overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                                <th scope="col" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Đơn hàng</th>
                                <th scope="col" class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($top_performers['top_customers'] as $customer)
                            <tr>
                                <td class="px-3 py-2 text-sm text-gray-900">{{ $customer->customer_name }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">{{ number_format($customer->order_count, 0, ',', '.') }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">{{ number_format($customer->revenue, 0, ',', '.') }} ₫</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
            
            <!-- Payment Status Distribution Chart -->
            @if(isset($sales_analysis['payment_status_distribution']) && count($sales_analysis['payment_status_distribution']) > 0)
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Phân bố Thanh toán</h3>
                <div class="chart-container" style="height: 300px;">
                    <canvas id="paymentStatusChart" role="img" aria-label="Biểu đồ phân bố trạng thái thanh toán"></canvas>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Purchase Performance Analysis -->
    @if(isset($purchase_analysis))
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Phân tích Hiệu suất Mua hàng</h2>
            <i class="fas fa-shopping-cart text-purple-500"></i>
        </div>
        
        <!-- Purchase Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-purple-50 rounded-lg p-3 border border-gray-100">
                <div class="text-xs text-gray-600 mb-1">Tổng đơn hàng</div>
                <div class="text-xl font-bold text-purple-600">{{ number_format($purchase_analysis['total_count'], 0, ',', '.') }}</div>
            </div>
            <div class="bg-yellow-50 rounded-lg p-3 border border-gray-100">
                <div class="text-xs text-gray-600 mb-1">Đơn chờ xử lý</div>
                <div class="text-xl font-bold text-yellow-600">{{ number_format($purchase_analysis['pending_count'], 0, ',', '.') }}</div>
            </div>
            <div class="bg-green-50 rounded-lg p-3 border border-gray-100">
                <div class="text-xs text-gray-600 mb-1">Đơn hoàn thành</div>
                <div class="text-xl font-bold text-green-600">{{ number_format($purchase_analysis['completed_count'], 0, ',', '.') }}</div>
            </div>
            <div class="bg-blue-50 rounded-lg p-3 border border-gray-100">
                <div class="text-xs text-gray-600 mb-1">Giá trị trung bình</div>
                <div class="text-xl font-bold text-blue-600">
                    @if($purchase_analysis['average_value'] !== null)
                        {{ number_format($purchase_analysis['average_value'], 0, ',', '.') }} ₫
                    @else
                        N/A
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Top Suppliers and Status Distribution -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Suppliers -->
            @if(isset($top_performers['top_suppliers']) && count($top_performers['top_suppliers']) > 0)
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Top 10 Nhà cung cấp</h3>
                <div class="top-performers-table overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nhà cung cấp</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Đơn hàng</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Chi phí</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($top_performers['top_suppliers'] as $supplier)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $supplier->supplier_name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($supplier->order_count, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($supplier->total_cost, 0, ',', '.') }} ₫</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
            
            <!-- Order Status Distribution Chart -->
            @if(isset($purchase_analysis['status_distribution']) && count($purchase_analysis['status_distribution']) > 0)
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Phân bố theo Trạng thái Đơn hàng</h3>
                <div class="chart-container" style="height: 250px;">
                    <canvas id="orderStatusChart" role="img" aria-label="Biểu đồ phân bố trạng thái đơn hàng"></canvas>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Inventory Status Overview -->
    @if(isset($inventory_analysis))
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Tổng quan Tình trạng Tồn kho</h2>
            <i class="fas fa-warehouse text-indigo-500"></i>
        </div>
        
        <!-- Inventory Stats -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-indigo-50 rounded-lg p-3 border border-gray-100">
                <div class="text-xs text-gray-600 mb-1">Giá trị tồn kho</div>
                <div class="text-base font-bold text-indigo-600">
                    @if(isset($inventory_analysis['total_value']))
                        {{ number_format($inventory_analysis['total_value'], 0, ',', '.') }} ₫
                    @else
                        N/A
                    @endif
                </div>
            </div>
            <div class="bg-blue-50 rounded-lg p-3 border border-gray-100">
                <div class="text-xs text-gray-600 mb-1">Số sản phẩm</div>
                <div class="text-base font-bold text-blue-600">
                    @if(isset($inventory_analysis['unique_products']))
                        {{ number_format($inventory_analysis['unique_products'], 0, ',', '.') }}
                    @else
                        N/A
                    @endif
                </div>
            </div>
            <div class="bg-green-50 rounded-lg p-3 border border-gray-100">
                <div class="text-xs text-gray-600 mb-1">Tổng số lượng</div>
                <div class="text-base font-bold text-green-600">
                    @if(isset($inventory_analysis['total_quantity']))
                        {{ number_format($inventory_analysis['total_quantity'], 0, ',', '.') }}
                    @else
                        N/A
                    @endif
                </div>
            </div>
            <div class="bg-red-50 rounded-lg p-3 border border-gray-100">
                <div class="text-xs text-gray-600 mb-1">Tồn kho thấp</div>
                <div class="text-base font-bold text-red-600">
                    @if(isset($inventory_analysis['low_stock_count']))
                        {{ number_format($inventory_analysis['low_stock_count'], 0, ',', '.') }}
                    @else
                        N/A
                    @endif
                </div>
            </div>
            <div class="bg-orange-50 rounded-lg p-3 border border-gray-100">
                <div class="text-xs text-gray-600 mb-1">Tồn kho cao</div>
                <div class="text-base font-bold text-orange-600">
                    @if(isset($inventory_analysis['overstock_count']))
                        {{ number_format($inventory_analysis['overstock_count'], 0, ',', '.') }}
                    @else
                        N/A
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Inventory Charts and Top Products -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Inventory Value by Warehouse Chart -->
            @if(isset($inventory_analysis['value_by_warehouse']) && count($inventory_analysis['value_by_warehouse']) > 0)
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Giá trị Tồn kho theo Kho</h3>
                <div class="chart-container" style="height: 250px;">
                    <canvas id="warehouseValueChart" role="img" aria-label="Biểu đồ giá trị tồn kho theo kho"></canvas>
                </div>
            </div>
            @endif
            
            <!-- Top Products by Quantity -->
            @if(isset($inventory_analysis['top_by_quantity']) && count($inventory_analysis['top_by_quantity']) > 0)
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Top 10 Sản phẩm theo Số lượng</h3>
                <div class="top-performers-table overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($inventory_analysis['top_by_quantity'] as $item)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $item->product_name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($item->total_stock, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
        
        <!-- Top Products by Value -->
        @if(isset($inventory_analysis['top_by_value']) && count($inventory_analysis['top_by_value']) > 0)
        <div class="mt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Top 10 Sản phẩm theo Giá trị</h3>
            <div class="top-performers-table overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Giá trị</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($inventory_analysis['top_by_value'] as $item)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $item->product_name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($item->total_stock, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($item->total_value, 0, ',', '.') }} ₫</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
    @endif
    @endif

    <!-- Export Modal -->
    <div x-show="showExportModal" 
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto" 
        aria-labelledby="modal-title" 
        role="dialog" 
        aria-modal="true"
        @keydown.escape.window="showExportModal = false">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div x-show="showExportModal" 
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                @click="showExportModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal panel -->
            <div x-show="showExportModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-download text-blue-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Xuất báo cáo Dashboard
                            </h3>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500 mb-4">Chọn định dạng file để xuất báo cáo:</p>
                                <form method="POST" action="{{ route('dashboard.business-activity.export') }}" id="exportForm">
                                    @csrf
                                    <div class="space-y-3">
                                        <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                            <input type="radio" name="format" value="pdf" class="mr-3" required>
                                            <div class="flex-1">
                                                <div class="font-medium text-gray-900">PDF</div>
                                                <div class="text-sm text-gray-500">Định dạng PDF với biểu đồ</div>
                                            </div>
                                            <i class="fas fa-file-pdf text-red-500 text-xl"></i>
                                        </label>
                                        <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                            <input type="radio" name="format" value="excel" class="mr-3" required>
                                            <div class="flex-1">
                                                <div class="font-medium text-gray-900">Excel</div>
                                                <div class="text-sm text-gray-500">Định dạng Excel với biểu đồ</div>
                                            </div>
                                            <i class="fas fa-file-excel text-green-500 text-xl"></i>
                                        </label>
                                        <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                            <input type="radio" name="format" value="csv" class="mr-3" required>
                                            <div class="flex-1">
                                                <div class="font-medium text-gray-900">CSV</div>
                                                <div class="text-sm text-gray-500">Định dạng CSV (chỉ dữ liệu)</div>
                                            </div>
                                            <i class="fas fa-file-csv text-blue-500 text-xl"></i>
                                        </label>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" form="exportForm"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Xuất báo cáo
                    </button>
                    <button type="button" @click="showExportModal = false"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Hủy
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script>
function dashboardApp() {
    return {
        showExportModal: false,
        periodType: '{{ request('period_type', session('dashboard_period_type', 'month')) }}',
        startDate: '{{ request('start_date', session('dashboard_start_date', '')) }}',
        endDate: '{{ request('end_date', session('dashboard_end_date', '')) }}',
        
        // Chart instances để quản lý và destroy khi cần
        revenueProfitChartInstance: null,
        paymentStatusChartInstance: null,
        orderStatusChartInstance: null,
        warehouseValueChartInstance: null,
        
        // Flag to prevent double initialization
        initialized: false,
        
        init() {
            if (this.initialized) {
                console.warn('Dashboard already initialized, skipping...');
                return;
            }
            
            this.initialized = true;
            console.log('Initializing dashboard charts...');
            
            try {
                // Initialize charts với error handling
                this.initRevenueProfitChart();
                this.initPaymentStatusChart();
                this.initOrderStatusChart();
                this.initWarehouseValueChart();
            } catch (error) {
                console.error('Lỗi khởi tạo dashboard:', error);
            }
        },
        
        selectPeriod(period) {
            this.periodType = period;
            // Clear custom dates when selecting predefined period
            this.startDate = '';
            this.endDate = '';
            if (this.$refs.startDatePicker && this.$refs.startDatePicker._flatpickr) {
                this.$refs.startDatePicker._flatpickr.clear();
            }
            if (this.$refs.endDatePicker && this.$refs.endDatePicker._flatpickr) {
                this.$refs.endDatePicker._flatpickr.clear();
            }
            // Wait for Alpine to update the DOM before submitting
            this.$nextTick(() => {
                document.getElementById('filterForm').submit();
            });
        },
        
        initRevenueProfitChart() {
            const canvas = document.getElementById('revenueProfitChart');
            
            if (!canvas) {
                return;
            }
            
            try {
                // Destroy existing chart instance nếu tồn tại
                if (this.revenueProfitChartInstance) {
                    this.revenueProfitChartInstance.destroy();
                    this.revenueProfitChartInstance = null;
                }
                
                @if(isset($charts['revenue_profit_trend']))
                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    console.error('Không thể lấy context từ canvas revenueProfitChart');
                    return;
                }
                
                const chartData = {
                    labels: {!! json_encode($charts['revenue_profit_trend']['labels']) !!},
                    revenue: {!! json_encode($charts['revenue_profit_trend']['revenue']) !!},
                    profit: {!! json_encode($charts['revenue_profit_trend']['profit']) !!}
                };
                
                this.revenueProfitChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [
                        {
                            label: 'Doanh thu',
                            data: chartData.revenue,
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Lợi nhuận',
                            data: chartData.profit,
                            borderColor: 'rgb(16, 185, 129)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += new Intl.NumberFormat('vi-VN', {
                                        style: 'currency',
                                        currency: 'VND'
                                    }).format(context.parsed.y);
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('vi-VN', {
                                        notation: 'compact',
                                        compactDisplay: 'short'
                                    }).format(value) + ' ₫';
                                }
                            }
                        }
                    }
                }
            });
            @endif
            } catch (error) {
                console.error('Lỗi khởi tạo biểu đồ Doanh thu & Lợi nhuận:', error);
            }
        },
        
        initPaymentStatusChart() {
            const canvas = document.getElementById('paymentStatusChart');
            
            if (!canvas) {
                return;
            }
            
            try {
                // Destroy existing chart instance nếu tồn tại
                if (this.paymentStatusChartInstance) {
                    this.paymentStatusChartInstance.destroy();
                    this.paymentStatusChartInstance = null;
                }
                
                @if(isset($sales_analysis['payment_status_distribution']) && count($sales_analysis['payment_status_distribution']) > 0)
                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    console.error('Không thể lấy context từ canvas paymentStatusChart');
                    return;
                }
                
                const rawData = {!! json_encode($sales_analysis['payment_status_distribution']) !!};
                
                // Kiểm tra data là Array trước khi gọi .map()
                if (!Array.isArray(rawData) || rawData.length === 0) {
                    return;
                }
                
                const data = rawData;
                
                this.paymentStatusChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.map(item => this.translatePaymentStatus(item.payment_status)),
                    datasets: [{
                        data: data.map(item => item.count),
                        backgroundColor: [
                            'rgb(16, 185, 129)',
                            'rgb(251, 191, 36)',
                            'rgb(239, 68, 68)',
                            'rgb(156, 163, 175)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            @endif
            } catch (error) {
                console.error('Lỗi khởi tạo biểu đồ Trạng thái Thanh toán:', error);
            }
        },
        
        initOrderStatusChart() {
            const canvas = document.getElementById('orderStatusChart');
            
            if (!canvas) {
                return;
            }
            
            try {
                // Destroy existing chart instance nếu tồn tại
                if (this.orderStatusChartInstance) {
                    this.orderStatusChartInstance.destroy();
                    this.orderStatusChartInstance = null;
                }
                
                @if(isset($purchase_analysis['status_distribution']) && count($purchase_analysis['status_distribution']) > 0)
                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    console.error('Không thể lấy context từ canvas orderStatusChart');
                    return;
                }
                
                const rawData = {!! json_encode($purchase_analysis['status_distribution']) !!};
                
                // Kiểm tra data là Array trước khi gọi .map()
                if (!Array.isArray(rawData) || rawData.length === 0) {
                    return;
                }
                
                const data = rawData;
                
                this.orderStatusChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.map(item => this.translateOrderStatus(item.status)),
                    datasets: [{
                        data: data.map(item => item.count),
                        backgroundColor: [
                            'rgb(59, 130, 246)',
                            'rgb(251, 191, 36)',
                            'rgb(16, 185, 129)',
                            'rgb(239, 68, 68)',
                            'rgb(156, 163, 175)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            @endif
            } catch (error) {
                console.error('Lỗi khởi tạo biểu đồ Trạng thái Đơn hàng:', error);
            }
        },
        
        initWarehouseValueChart() {
            const canvas = document.getElementById('warehouseValueChart');
            
            if (!canvas) {
                return;
            }
            
            try {
                // Destroy existing chart instance nếu tồn tại
                if (this.warehouseValueChartInstance) {
                    this.warehouseValueChartInstance.destroy();
                    this.warehouseValueChartInstance = null;
                }
                
                @if(isset($inventory_analysis['value_by_warehouse']) && count($inventory_analysis['value_by_warehouse']) > 0)
                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    console.error('Không thể lấy context từ canvas warehouseValueChart');
                    return;
                }
                
                const rawData = {!! json_encode($inventory_analysis['value_by_warehouse']) !!};
                
                // Kiểm tra data là Array trước khi gọi .map()
                if (!Array.isArray(rawData) || rawData.length === 0) {
                    return;
                }
                
                const data = rawData;
                
                this.warehouseValueChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(item => item.warehouse_name),
                    datasets: [{
                        label: 'Giá trị tồn kho',
                        data: data.map(item => item.total_value),
                        backgroundColor: 'rgba(99, 102, 241, 0.8)',
                        borderColor: 'rgb(99, 102, 241)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return new Intl.NumberFormat('vi-VN', {
                                        style: 'currency',
                                        currency: 'VND'
                                    }).format(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('vi-VN', {
                                        notation: 'compact',
                                        compactDisplay: 'short'
                                    }).format(value) + ' ₫';
                                }
                            }
                        }
                    }
                }
            });
            @endif
            } catch (error) {
                console.error('Lỗi khởi tạo biểu đồ Giá trị Kho:', error);
            }
        },
        
        translatePaymentStatus(status) {
            const translations = {
                'paid': 'Đã thanh toán',
                'partial': 'Thanh toán một phần',
                'unpaid': 'Chưa thanh toán',
                'pending': 'Chờ xử lý'
            };
            return translations[status] || status;
        },
        
        translateOrderStatus(status) {
            const translations = {
                'draft': 'Nháp',
                'pending_approval': 'Chờ duyệt',
                'approved': 'Đã duyệt',
                'sent': 'Đã gửi',
                'confirmed': 'Đã xác nhận',
                'shipping': 'Đang vận chuyển',
                'partial_received': 'Nhận một phần',
                'received': 'Đã nhận',
                'pending': 'Chờ xử lý',
                'completed': 'Hoàn thành',
                'cancelled': 'Đã hủy'
            };
            return translations[status] || status;
        }
    };
}
</script>
@endpush

@endsection
