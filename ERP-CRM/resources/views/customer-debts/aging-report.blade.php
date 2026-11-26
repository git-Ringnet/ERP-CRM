@extends('layouts.app')

@section('title', 'Báo cáo tuổi nợ (Aging Report)')
@section('page-title', 'Báo cáo tuổi nợ (Aging Report)')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-chart-bar text-indigo-600 mr-2"></i>Báo cáo tuổi nợ
                </h2>
                <p class="text-sm text-gray-500 mt-1">Phân tích công nợ theo thời gian quá hạn và chi tiết từng đơn hàng</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('customer-debts.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200">
                    <i class="fas fa-arrow-left mr-2"></i> Quay lại
                </a>
                <a href="{{ route('customer-debts.aging-report.export', request()->query()) }}"
                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">
                    <i class="fas fa-file-csv mr-2"></i> Xuất CSV
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-money-bill-wave text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Tổng công nợ</p>
                    <p class="text-xl font-bold text-gray-800">{{ number_format($stats['total_debt'] ?? 0) }}đ</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Hiện tại (0-30 ngày)</p>
                    <p class="text-xl font-bold text-green-600">{{ number_format($stats['current'] ?? 0) }}đ</p>
                    <p class="text-xs text-gray-400">{{ $stats['current_percent'] ?? 0 }}%</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <i class="fas fa-exclamation-triangle text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Trên 90 ngày</p>
                    <p class="text-xl font-bold text-red-600">{{ number_format($stats['over_90'] ?? 0) }}đ</p>
                    <p class="text-xs text-gray-400">{{ $stats['over_90_percent'] ?? 0 }}%</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <i class="fas fa-users text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">KH quá hạn</p>
                    <p class="text-xl font-bold text-yellow-600">{{ $stats['overdue_customers'] ?? 0 }}</p>
                    <p class="text-xs text-gray-400">khách hàng</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Aging Distribution Bar -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-base font-semibold text-gray-800 mb-3">Phân bố theo tuổi nợ</h3>
        <div class="w-full h-7 bg-gray-100 rounded-lg overflow-hidden flex">
            @php
                $total = $report['summary']['total'] ?? 0;
                $currentPct = $total > 0 ? round(($report['summary']['current'] / $total) * 100, 1) : 0;
                $d31Pct = $total > 0 ? round(($report['summary']['days_31_60'] / $total) * 100, 1) : 0;
                $d61Pct = $total > 0 ? round(($report['summary']['days_61_90'] / $total) * 100, 1) : 0;
                $over90Pct = $total > 0 ? round(($report['summary']['over_90'] / $total) * 100, 1) : 0;
            @endphp
            @if($currentPct > 0)
                <div class="bg-green-500 text-white text-xs font-medium flex items-center justify-center" style="width: {{ $currentPct }}%" title="0-30 ngày: {{ $currentPct }}%">
                    @if($currentPct >= 8){{ $currentPct }}%@endif
                </div>
            @endif
            @if($d31Pct > 0)
                <div class="bg-blue-500 text-white text-xs font-medium flex items-center justify-center" style="width: {{ $d31Pct }}%" title="31-60 ngày: {{ $d31Pct }}%">
                    @if($d31Pct >= 8){{ $d31Pct }}%@endif
                </div>
            @endif
            @if($d61Pct > 0)
                <div class="bg-yellow-500 text-white text-xs font-medium flex items-center justify-center" style="width: {{ $d61Pct }}%" title="61-90 ngày: {{ $d61Pct }}%">
                    @if($d61Pct >= 8){{ $d61Pct }}%@endif
                </div>
            @endif
            @if($over90Pct > 0)
                <div class="bg-red-500 text-white text-xs font-medium flex items-center justify-center" style="width: {{ $over90Pct }}%" title=">90 ngày: {{ $over90Pct }}%">
                    @if($over90Pct >= 8){{ $over90Pct }}%@endif
                </div>
            @endif
        </div>
        <div class="flex flex-wrap gap-4 mt-3 text-xs text-gray-600">
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-green-500 inline-block"></span> 0-30 ngày ({{ $currentPct }}%)</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-blue-500 inline-block"></span> 31-60 ngày ({{ $d31Pct }}%)</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-yellow-500 inline-block"></span> 61-90 ngày ({{ $d61Pct }}%)</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-500 inline-block"></span> &gt;90 ngày ({{ $over90Pct }}%)</span>
        </div>
    </div>

    <!-- Search / Filter -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            @if(!empty($filters['customer_id']))
                <input type="hidden" name="customer_id" value="{{ $filters['customer_id'] }}">
            @endif
            <div class="flex-1 min-w-[240px]">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       placeholder="Tìm kiếm theo tên KH, mã số thuế, mã đơn..."
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                <i class="fas fa-search mr-2"></i> Tìm kiếm
            </button>
            @if(!empty($filters['search']) || !empty($filters['customer_id']))
                <a href="{{ route('customer-debts.aging-report') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200">
                    <i class="fas fa-times mr-2"></i> Xóa lọc
                </a>
            @endif
        </form>
    </div>

    <!-- Customer Summary Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-800">
                <i class="fas fa-table text-indigo-600 mr-2"></i>Chi tiết công nợ theo khách hàng
                <span class="ml-2 px-2 py-0.5 text-xs bg-indigo-100 text-indigo-800 rounded-full">{{ count($report['customers'] ?? []) }}</span>
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-green-600 uppercase">0-30 ngày</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-blue-600 uppercase">31-60 ngày</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-yellow-600 uppercase">61-90 ngày</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-red-600 uppercase">&gt;90 ngày</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase">Tổng nợ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Rủi ro</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($report['customers'] ?? [] as $customer)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">
                                    <a href="{{ route('customer-debts.show', $customer['customer_id']) }}" class="hover:text-indigo-600">
                                        {{ $customer['customer_name'] }}
                                    </a>
                                </div>
                                <div class="text-xs text-gray-500">{{ $customer['customer_code'] }} | {{ $customer['phone'] }}</div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($customer['current'] > 0)
                                    <span class="text-green-600 font-medium">{{ number_format($customer['current']) }}đ</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($customer['days_31_60'] > 0)
                                    <span class="text-blue-600 font-medium">{{ number_format($customer['days_31_60']) }}đ</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($customer['days_61_90'] > 0)
                                    <span class="text-yellow-600 font-medium">{{ number_format($customer['days_61_90']) }}đ</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($customer['over_90'] > 0)
                                    <span class="text-red-600 font-bold">{{ number_format($customer['over_90']) }}đ</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">
                                {{ number_format($customer['total']) }}đ
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $riskClasses = [
                                        'high' => 'bg-red-100 text-red-800',
                                        'medium' => 'bg-yellow-100 text-yellow-800',
                                        'low' => 'bg-blue-100 text-blue-800',
                                        'normal' => 'bg-green-100 text-green-800',
                                    ];
                                    $riskLabels = [
                                        'high' => 'Cao',
                                        'medium' => 'TB',
                                        'low' => 'Thấp',
                                        'normal' => 'BT',
                                    ];
                                @endphp
                                <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full {{ $riskClasses[$customer['risk_level']] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $riskLabels[$customer['risk_level']] ?? $customer['risk_level'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('customer-debts.show', $customer['customer_id']) }}"
                                   class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-indigo-600 bg-indigo-50 rounded hover:bg-indigo-100" title="Xem chi tiết">
                                    <i class="fas fa-eye mr-1"></i> Chi tiết
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">Không có khách hàng nào có công nợ</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($report['customers'] ?? []) > 0)
                    <tfoot class="bg-gray-50 font-bold border-t-2 border-gray-200">
                        <tr>
                            <td class="px-4 py-3 text-gray-800">TỔNG CỘNG</td>
                            <td class="px-4 py-3 text-right text-green-600">{{ number_format($report['summary']['current'] ?? 0) }}đ</td>
                            <td class="px-4 py-3 text-right text-blue-600">{{ number_format($report['summary']['days_31_60'] ?? 0) }}đ</td>
                            <td class="px-4 py-3 text-right text-yellow-600">{{ number_format($report['summary']['days_61_90'] ?? 0) }}đ</td>
                            <td class="px-4 py-3 text-right text-red-600">{{ number_format($report['summary']['over_90'] ?? 0) }}đ</td>
                            <td class="px-4 py-3 text-right text-gray-900">{{ number_format($report['summary']['total'] ?? 0) }}đ</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- Per-order aging details (ACC-DEBT-002, paginated) -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-wrap gap-2">
            <h3 class="text-base font-semibold text-gray-800">
                <i class="fas fa-receipt text-indigo-600 mr-2"></i>Chi tiết tuổi nợ theo từng đơn hàng
                <span class="ml-2 px-2 py-0.5 text-xs bg-gray-100 text-gray-700 rounded-full">{{ $orderDetails->total() }}</span>
            </h3>
            <span class="text-xs text-gray-500">Hiển thị 20 đơn/trang để tối ưu tốc độ tải</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã đơn hàng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ngày hóa đơn</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Hạn thanh toán</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Công nợ còn lại</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tuổi nợ (ngày)</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Quá hạn</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($orderDetails as $ord)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">
                                <a href="{{ route('sales.show', $ord['sale_id']) }}" class="text-indigo-600 hover:underline">
                                    {{ $ord['sale_code'] }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('customer-debts.show', $ord['customer_id']) }}" class="text-gray-800 hover:text-indigo-600">
                                    {{ $ord['customer_name'] }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ $ord['invoice_date'] }}</td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ $ord['due_date'] }}</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">{{ number_format($ord['debt_amount']) }}đ</td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ $ord['aging_days'] }} ngày</td>
                            <td class="px-4 py-3 text-center">
                                @if($ord['is_overdue'])
                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                        Quá hạn {{ $ord['overdue_days'] }} ngày
                                    </span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        Trong hạn
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Không có đơn hàng nào còn công nợ</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orderDetails->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $orderDetails->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
