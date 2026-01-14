@extends('layouts.app')

@section('title', 'Báo cáo xuất nhập')
@section('page-title', 'Báo Cáo Xuất Nhập Kho')

@section('content')
<div class="space-y-4">
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form method="GET" action="{{ route('reports.transaction-report') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Loại giao dịch</label>
                <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả</option>
                    <option value="import" {{ request('type') == 'import' ? 'selected' : '' }}>Nhập kho</option>
                    <option value="export" {{ request('type') == 'export' ? 'selected' : '' }}>Xuất kho</option>
                    <option value="transfer" {{ request('type') == 'transfer' ? 'selected' : '' }}>Chuyển kho</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kho</label>
                <select name="warehouse_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Từ ngày</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" 
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Đến ngày</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" 
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-sm">
                    <i class="fas fa-search mr-1"></i> Lọc
                </button>
                <a href="{{ route('reports.transaction-report') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
                    <i class="fas fa-redo mr-1"></i> Đặt lại
                </a>
            </div>
            <div class="flex items-end">
                <a href="{{ route('reports.transaction-report.export') }}?{{ http_build_query(request()->query()) }}" 
                   class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                    <i class="fas fa-file-excel mr-1"></i> Export Excel
                </a>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-blue-500 text-white rounded-lg p-4">
            <div class="text-sm opacity-80">Tổng Giao Dịch</div>
            <div class="text-2xl font-bold">{{ number_format($totalTransactions) }}</div>
        </div>
        <div class="bg-green-500 text-white rounded-lg p-4">
            <div class="text-sm opacity-80">Nhập Kho</div>
            <div class="text-2xl font-bold">{{ number_format($importCount) }}</div>
        </div>
        <div class="bg-red-500 text-white rounded-lg p-4">
            <div class="text-sm opacity-80">Xuất Kho</div>
            <div class="text-2xl font-bold">{{ number_format($exportCount) }}</div>
        </div>
        <div class="bg-purple-500 text-white rounded-lg p-4">
            <div class="text-sm opacity-80">Chuyển Kho</div>
            <div class="text-2xl font-bold">{{ number_format($transferCount) }}</div>
        </div>
        <div class="bg-cyan-500 text-white rounded-lg p-4">
            <div class="text-sm opacity-80">Tổng SL</div>
            <div class="text-2xl font-bold">{{ number_format($totalQuantity) }}</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Doughnut Chart - By Type -->
        <div class="bg-white rounded-lg shadow-sm p-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Tỷ lệ theo Loại</h3>
            <div class="h-64">
                <canvas id="typeChart"></canvas>
            </div>
        </div>
        
        <!-- Line Chart - Trend -->
        <div class="bg-white rounded-lg shadow-sm p-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Xu hướng theo Ngày</h3>
            <div class="h-64">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- By Type Table -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Thống Kê Theo Loại</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loại</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số Giao Dịch</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tổng Số Lượng</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($byType as $type => $data)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                @if($type === 'import')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Nhập kho</span>
                                @elseif($type === 'export')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Xuất kho</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">Chuyển kho</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($data['count']) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($data['total_qty'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Detailed Transactions -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Chi Tiết Giao Dịch</h3>
            <span class="text-sm text-gray-500">
                Hiển thị {{ $transactions->firstItem() ?? 0 }} - {{ $transactions->lastItem() ?? 0 }} / {{ $transactions->total() }} giao dịch
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loại</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nhân viên</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                @php
                                    $showRoute = match($transaction->type) {
                                        'import' => route('imports.show', $transaction),
                                        'export' => route('exports.show', $transaction),
                                        'transfer' => route('transfers.show', $transaction),
                                        default => '#'
                                    };
                                @endphp
                                <a href="{{ $showRoute }}" class="text-primary hover:underline font-medium">
                                    {{ $transaction->code }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                @if($transaction->type === 'import')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Nhập kho</span>
                                @elseif($transaction->type === 'export')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">Xuất kho</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">Chuyển kho</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                @if($transaction->type === 'transfer')
                                    {{ $transaction->fromWarehouse->name ?? 'N/A' }}
                                    @if($transaction->toWarehouse)
                                        <span class="text-gray-400">→</span> {{ $transaction->toWarehouse->name }}
                                    @endif
                                @else
                                    {{ $transaction->warehouse->name ?? 'N/A' }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $transaction->date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($transaction->total_qty, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $transaction->employee->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                @if($transaction->status === 'completed')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Hoàn thành</span>
                                @elseif($transaction->status === 'pending')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Chờ xử lý</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Đã hủy</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Không có dữ liệu</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($transactions->hasPages())
        <div class="p-4 border-t border-gray-200">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Type Doughnut Chart
const typeData = {
    import: {{ $importCount }},
    export: {{ $exportCount }},
    transfer: {{ $transferCount }}
};

new Chart(document.getElementById('typeChart'), {
    type: 'doughnut',
    data: {
        labels: ['Nhập kho', 'Xuất kho', 'Chuyển kho'],
        datasets: [{
            data: [typeData.import, typeData.export, typeData.transfer],
            backgroundColor: ['#10B981', '#EF4444', '#8B5CF6'],
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right' }
        }
    }
});

// Trend Line Chart
const byDate = @json($byDate);
const dateLabels = Object.keys(byDate).slice(-14); // Last 14 days
const dateValues = dateLabels.map(date => byDate[date]?.total_qty || 0);

new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: dateLabels.map(d => {
            const parts = d.split('-');
            return parts[2] + '/' + parts[1];
        }),
        datasets: [{
            label: 'Số lượng',
            data: dateValues,
            borderColor: '#3B82F6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
@endpush
@endsection
