@extends('layouts.app')

@section('title', 'Báo cáo dự án')
@section('page-title', 'Báo cáo tổng hợp theo dự án')

@section('content')
<div class="space-y-4">
    <!-- Filter -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Từ ngày</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}"
                       class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Đến ngày</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}"
                       class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Trạng thái</label>
                <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả</option>
                    <option value="planning" {{ request('status') == 'planning' ? 'selected' : '' }}>Lên kế hoạch</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>Đang thực hiện</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-filter mr-1"></i> Lọc
            </button>
            <a href="{{ route('projects.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow-sm p-4">
            <p class="text-sm text-gray-500">Tổng dự toán</p>
            <p class="text-xl font-bold text-gray-900">{{ number_format($totals['budget']) }} đ</p>
        </div>
        <div class="bg-blue-50 rounded-lg p-4">
            <p class="text-sm text-blue-600">Tổng doanh thu</p>
            <p class="text-xl font-bold text-blue-700">{{ number_format($totals['revenue']) }} đ</p>
        </div>
        <div class="bg-orange-50 rounded-lg p-4">
            <p class="text-sm text-orange-600">Tổng giá vốn</p>
            <p class="text-xl font-bold text-orange-700">{{ number_format($totals['cost']) }} đ</p>
        </div>
        <div class="{{ $totals['profit'] >= 0 ? 'bg-green-50' : 'bg-red-50' }} rounded-lg p-4">
            <p class="text-sm {{ $totals['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">Tổng lợi nhuận</p>
            <p class="text-xl font-bold {{ $totals['profit'] >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ number_format($totals['profit']) }} đ</p>
        </div>
        <div class="bg-red-50 rounded-lg p-4">
            <p class="text-sm text-red-600">Tổng công nợ</p>
            <p class="text-xl font-bold text-red-700">{{ number_format($totals['debt']) }} đ</p>
        </div>
    </div>

    <!-- Report Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900">Chi tiết theo dự án</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã dự án</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên dự án</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Dự toán</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Doanh thu</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Giá vốn</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Lợi nhuận</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Công nợ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($projects as $project)
                    @php
                        $revenue = $project->total_revenue;
                        $cost = $project->total_cost;
                        $profit = $project->profit;
                        $profitPercent = $project->profit_percent;
                        $debt = $project->total_debt;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('projects.show', $project->id) }}" class="font-medium text-primary hover:underline">
                                {{ $project->code }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $project->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $project->customer_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($project->budget) }} đ</td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-blue-600">{{ number_format($revenue) }} đ</td>
                        <td class="px-4 py-3 text-sm text-right text-orange-600">{{ number_format($cost) }} đ</td>
                        <td class="px-4 py-3 text-sm text-right">
                            <span class="font-medium {{ $profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($profit) }} đ
                            </span>
                            <span class="text-xs text-gray-500">({{ number_format($profitPercent, 1) }}%)</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right {{ $debt > 0 ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                            {{ number_format($debt) }} đ
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $project->status_color }}">
                                {{ $project->status_label }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                            Không có dữ liệu
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($projects->count() > 0)
                <tfoot class="bg-gray-100 font-semibold">
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-right">Tổng cộng:</td>
                        <td class="px-4 py-3 text-right">{{ number_format($totals['budget']) }} đ</td>
                        <td class="px-4 py-3 text-right text-blue-600">{{ number_format($totals['revenue']) }} đ</td>
                        <td class="px-4 py-3 text-right text-orange-600">{{ number_format($totals['cost']) }} đ</td>
                        <td class="px-4 py-3 text-right {{ $totals['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($totals['profit']) }} đ
                        </td>
                        <td class="px-4 py-3 text-right text-red-600">{{ number_format($totals['debt']) }} đ</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
