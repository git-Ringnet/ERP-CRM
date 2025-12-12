@extends('layouts.app')

@section('title', 'Báo cáo tuổi nợ')
@section('page-title', 'Báo cáo tuổi nợ khách hàng')

@section('content')
<div class="space-y-6">
    <!-- Back Button -->
    <div class="flex items-center gap-4">
        <a href="{{ route('customer-debts.index') }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Báo cáo phân tích tuổi nợ</h2>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="w-64">
                <label class="block text-sm font-medium text-gray-700 mb-1">Khách hàng</label>
                <select name="customer_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả khách hàng</option>
                    @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ $customerId == $customer->id ? 'selected' : '' }}>
                        {{ $customer->code }} - {{ $customer->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90">
                <i class="fas fa-filter mr-1"></i> Lọc
            </button>
        </form>
    </div>

    <!-- Aging Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <p class="text-sm text-gray-500">0 - 30 ngày</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($aging['current'], 0, ',', '.') }}đ</p>
            <p class="text-xs text-gray-400 mt-1">Nợ trong hạn</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
            <p class="text-sm text-gray-500">31 - 60 ngày</p>
            <p class="text-2xl font-bold text-yellow-600">{{ number_format($aging['days_31_60'], 0, ',', '.') }}đ</p>
            <p class="text-xs text-gray-400 mt-1">Cần theo dõi</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-orange-500">
            <p class="text-sm text-gray-500">61 - 90 ngày</p>
            <p class="text-2xl font-bold text-orange-600">{{ number_format($aging['days_61_90'], 0, ',', '.') }}đ</p>
            <p class="text-xs text-gray-400 mt-1">Cảnh báo</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
            <p class="text-sm text-gray-500">Trên 90 ngày</p>
            <p class="text-2xl font-bold text-red-600">{{ number_format($aging['over_90'], 0, ',', '.') }}đ</p>
            <p class="text-xs text-gray-400 mt-1">Nợ xấu</p>
        </div>
    </div>

    <!-- Aging Chart -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Biểu đồ phân bổ tuổi nợ</h3>
        <div class="h-64">
            <canvas id="agingChart"></canvas>
        </div>
    </div>

    <!-- Detail Table -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Chi tiết công nợ theo tuổi</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã đơn</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày đơn</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Công nợ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Số ngày</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Phân loại</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($sales as $sale)
                    @php
                        $daysPast = now()->diffInDays($sale->date);
                        if ($daysPast <= 30) {
                            $category = '0-30 ngày';
                            $categoryColor = 'bg-green-100 text-green-800';
                        } elseif ($daysPast <= 60) {
                            $category = '31-60 ngày';
                            $categoryColor = 'bg-yellow-100 text-yellow-800';
                        } elseif ($daysPast <= 90) {
                            $category = '61-90 ngày';
                            $categoryColor = 'bg-orange-100 text-orange-800';
                        } else {
                            $category = '>90 ngày';
                            $categoryColor = 'bg-red-100 text-red-800';
                        }
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-primary">
                            <a href="{{ route('sales.show', $sale) }}">{{ $sale->code }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            <a href="{{ route('customer-debts.show', $sale->customer) }}" class="hover:text-primary">
                                {{ $sale->customer->name }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $sale->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-red-600 font-medium">
                            {{ number_format($sale->debt_amount, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-center text-gray-600">{{ $daysPast }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $categoryColor }}">
                                {{ $category }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">Không có công nợ</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('agingChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['0-30 ngày', '31-60 ngày', '61-90 ngày', '>90 ngày'],
            datasets: [{
                data: [
                    {{ $aging['current'] }},
                    {{ $aging['days_31_60'] }},
                    {{ $aging['days_61_90'] }},
                    {{ $aging['over_90'] }}
                ],
                backgroundColor: ['#10B981', '#F59E0B', '#F97316', '#EF4444'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + new Intl.NumberFormat('vi-VN').format(context.raw) + 'đ';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
@endsection
