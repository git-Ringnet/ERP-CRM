@extends('layouts.app')

@section('title', 'Tổng quan kinh doanh')
@section('page-title', 'Báo Cáo Tổng Quan Kinh Doanh')

@section('content')
<div class="space-y-6">
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
        <form method="GET" action="{{ route('reports.business-overview') }}" class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">Từ ngày</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" 
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">Đến ngày</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" 
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm">
                <i class="fas fa-filter mr-1"></i> Lọc dữ liệu
            </button>
            <a href="{{ route('reports.business-overview') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors text-sm">
                <i class="fas fa-redo mr-1"></i> Đặt lại
            </a>
        </form>
    </div>

    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Doanh thu bán hàng</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_revenue'], 0) }}</p>
                    <p class="text-xs text-blue-600 mt-1">Tổng cộng VND</p>
                </div>
                <div class="bg-blue-50 p-3 rounded-full text-blue-500">
                    <i class="fas fa-hand-holding-usd text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-orange-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Chi phí mua hàng</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_purchase'], 0) }}</p>
                    <p class="text-xs text-orange-600 mt-1">Tổng PO đã duyệt</p>
                </div>
                <div class="bg-orange-50 p-3 rounded-full text-orange-500">
                    <i class="fas fa-shopping-cart text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Lợi nhuận gộp</p>
                    <p class="text-2xl font-bold {{ $stats['total_profit'] < 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ number_format($stats['total_profit'], 0) }}
                    </p>
                    <p class="text-xs text-green-600 mt-1">Sales Profit</p>
                </div>
                <div class="bg-green-50 p-3 rounded-full text-green-500">
                    <i class="fas fa-chart-line text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Tỷ suất LN/Doanh thu</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['margin_percent'] }}%</p>
                    <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2">
                        <div class="bg-purple-500 h-1.5 rounded-full" style="width: {{ min(100, max(0, $stats['margin_percent'])) }}%"></div>
                    </div>
                </div>
                <div class="bg-purple-50 p-3 rounded-full text-purple-500">
                    <i class="fas fa-percentage text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Comparison -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-gray-800">Cân đối Bán hàng vs Mua hàng</h2>
                <div class="flex items-center gap-4 text-xs font-medium">
                    <div class="flex items-center"><span class="w-3 h-3 bg-blue-500 rounded-full mr-2"></span> Bán</div>
                    <div class="flex items-center"><span class="w-3 h-3 bg-orange-400 rounded-full mr-2"></span> Mua</div>
                    <div class="flex items-center"><span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span> Lợi nhuận</div>
                </div>
            </div>
            <div class="relative" style="height: 300px;">
                <canvas id="businessChart"></canvas>
            </div>
        </div>

        <!-- Monthly Summary Mini Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h2 class="text-sm font-bold text-gray-700 uppercase">Chi tiết theo tháng</h2>
            </div>
            <div class="overflow-y-auto" style="max-height: 300px;">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 text-gray-500 sticky top-0">
                        <tr>
                            <th class="px-4 py-2">Tháng</th>
                            <th class="px-4 py-2 text-right">Lợi nhuận</th>
                            <th class="px-4 py-2 text-center">%LN</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-600">
                        @foreach($monthlyData as $data)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-medium">{{ $data['month'] }}</td>
                            <td class="px-4 py-3 text-right font-medium {{ $data['profit'] < 0 ? 'text-red-500' : 'text-green-600' }}">
                                {{ number_format($data['profit'], 0) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php $rate = $data['revenue'] > 0 ? round(($data['profit'] / $data['revenue']) * 100, 1) : 0; @endphp
                                <span class="font-bold {{ $rate >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $rate }}%</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Real-time Tracking Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pb-6">
        <!-- Sales Tracking -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-base font-bold text-blue-800">
                    <i class="fas fa-shipping-fast mr-2"></i>Theo dõi Đơn hàng bán gần đây
                </h2>
                <a href="{{ route('sales.index') }}" class="text-xs text-blue-600 hover:underline font-medium">Xem tất cả</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-blue-50 text-blue-600 font-semibold text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3">Mã đơn</th>
                            <th class="px-4 py-3">Khách hàng</th>
                            <th class="px-4 py-3 text-center">Trạng thái</th>
                            <th class="px-4 py-3 text-right">Giá trị</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentSales as $sale)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <a href="{{ route('sales.show', $sale) }}" class="font-bold text-blue-600 hover:underline">{{ $sale->code }}</a>
                            </td>
                            <td class="px-4 py-3 text-gray-700 truncate max-w-[150px]">{{ $sale->customer_name }}</td>
                            <td class="px-4 py-3 text-center text-[11px]">
                                <span class="px-2 py-1 rounded-full font-bold {{ $sale->status_color }}">
                                    {{ $sale->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">{{ number_format($sale->total, 0) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-gray-400">Chưa có đơn hàng nào</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Purchase Tracking -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-base font-bold text-orange-800">
                    <i class="fas fa-truck-loading mr-2"></i>Theo dõi Đơn hàng nhập/PO
                </h2>
                <a href="{{ route('purchase-orders.index') }}" class="text-xs text-orange-600 hover:underline font-medium">Xem tất cả</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-orange-50 text-orange-700 font-semibold text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3">Mã PO</th>
                            <th class="px-4 py-3">Nhà cung cấp</th>
                            <th class="px-4 py-3 text-center">Trạng thái</th>
                            <th class="px-4 py-3 text-right">Ngày giao</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentPurchases as $po)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <a href="{{ route('purchase-orders.show', $po) }}" class="font-bold text-orange-600 hover:underline">{{ $po->code }}</a>
                            </td>
                            <td class="px-4 py-3 text-gray-700 truncate max-w-[150px]">{{ $po->supplier->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $poColors = match($po->status) {
                                        'received' => 'bg-green-100 text-green-700',
                                        'shipping' => 'bg-blue-100 text-blue-700',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                        default => 'bg-gray-100 text-gray-700'
                                    };
                                @endphp
                                <span class="px-2 py-1 rounded-full text-[11px] font-bold {{ $poColors }}">
                                    {{ $po->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-xs">
                                {{ $po->expected_delivery ? $po->expected_delivery->format('d/m/Y') : 'N/A' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-gray-400">Chưa có đơn nhập nào</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('businessChart').getContext('2d');
    const monthlyData = @json($monthlyData);
    
    const labels = monthlyData.map(d => d.month);
    const revenueData = monthlyData.map(d => d.revenue);
    const purchaseData = monthlyData.map(d => d.purchase);
    const profitData = monthlyData.map(d => d.profit);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Doanh thu',
                    data: revenueData,
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: '#3B82F6',
                    borderWidth: 1,
                    borderRadius: 4,
                    order: 2
                },
                {
                    label: 'Mua hàng',
                    data: purchaseData,
                    backgroundColor: 'rgba(251, 146, 60, 0.7)',
                    borderColor: '#FB923C',
                    borderWidth: 1,
                    borderRadius: 4,
                    order: 2
                },
                {
                    label: 'Lợi nhuận',
                    data: profitData,
                    type: 'line',
                    borderColor: '#10B981',
                    backgroundColor: '#10B981',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' VND';
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) return (value/1000000) + 'M';
                            if (value >= 1000) return (value/1000) + 'K';
                            return value;
                        }
                    }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
});
</script>
@endpush
@endsection
