@extends('layouts.app')

@section('title', 'Báo cáo hư hỏng')
@section('page-title', 'Báo Cáo Hàng Hư Hỏng / Thanh Lý')

@section('content')
<div class="space-y-4">
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form method="GET" action="{{ route('reports.damaged-goods-report') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Loại</label>
                <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả</option>
                    <option value="damaged" {{ request('type') == 'damaged' ? 'selected' : '' }}>Hàng hư hỏng</option>
                    <option value="liquidation" {{ request('type') == 'liquidation' ? 'selected' : '' }}>Thanh lý</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Từ chối</option>
                    <option value="processed" {{ request('status') == 'processed' ? 'selected' : '' }}>Đã xử lý</option>
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
                <a href="{{ route('reports.damaged-goods-report') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
                    <i class="fas fa-redo mr-1"></i> Đặt lại
                </a>
            </div>
            <div class="flex items-end">
                <a href="{{ route('reports.damaged-goods-report.export') }}?{{ http_build_query(request()->query()) }}" 
                   class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                    <i class="fas fa-file-excel mr-1"></i> Export Excel
                </a>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-blue-500 text-white rounded-lg p-4">
            <div class="text-sm opacity-80">Tổng Báo Cáo</div>
            <div class="text-2xl font-bold">{{ number_format($totalRecords) }}</div>
        </div>
        <div class="bg-red-500 text-white rounded-lg p-4">
            <div class="text-sm opacity-80">Giá Trị Gốc</div>
            <div class="text-2xl font-bold">{{ number_format($totalOriginalValue, 0) }}đ</div>
        </div>
        <div class="bg-green-500 text-white rounded-lg p-4">
            <div class="text-sm opacity-80">Thu Hồi</div>
            <div class="text-2xl font-bold">{{ number_format($totalRecoveryValue, 0) }}đ</div>
        </div>
        <div class="bg-yellow-500 text-white rounded-lg p-4">
            <div class="text-sm opacity-80">Tổn Thất</div>
            <div class="text-2xl font-bold">{{ number_format($totalLoss, 0) }}đ</div>
            <div class="text-xs opacity-80">Tỷ lệ thu hồi: {{ number_format($recoveryRate, 1) }}%</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Doughnut Chart - By Type -->
        <div class="bg-white rounded-lg shadow-sm p-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Phân loại theo Loại</h3>
            <div class="h-64">
                <canvas id="typeChart"></canvas>
            </div>
        </div>
        
        <!-- Bar Chart - Top Products -->
        <div class="bg-white rounded-lg shadow-sm p-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top SP Tổn Thất Cao</h3>
            <div class="h-64">
                <canvas id="topProductsChart"></canvas>
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số Báo Cáo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Giá Trị Gốc</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thu Hồi</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tổn Thất</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($byType as $type => $data)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $type === 'damaged' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $type === 'damaged' ? 'Hàng hư hỏng' : 'Thanh lý' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($data['count']) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($data['original_value'], 0) }}đ</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($data['recovery_value'], 0) }}đ</td>
                            <td class="px-4 py-3 text-sm text-red-600 font-medium">{{ number_format($data['loss'], 0) }}đ</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- By Status Table -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Thống Kê Theo Trạng Thái</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số Báo Cáo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tổn Thất</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($byStatus as $status => $data)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                @if($status === 'pending')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Chờ duyệt</span>
                                @elseif($status === 'approved')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Đã duyệt</span>
                                @elseif($status === 'rejected')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Từ chối</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Đã xử lý</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($data['count']) }}</td>
                            <td class="px-4 py-3 text-sm text-red-600 font-medium">{{ number_format($data['total_loss'], 0) }}đ</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Damaged Products -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Top 10 Sản Phẩm Hư Hỏng Nhiều Nhất</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số Lần</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tổng Số Lượng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tổng Tổn Thất</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($topProducts as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $item['product']->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($item['count']) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($item['total_quantity'], 2) }}</td>
                            <td class="px-4 py-3 text-sm text-red-600 font-medium">{{ number_format($item['total_loss'], 0) }}đ</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">Không có dữ liệu</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Type Doughnut Chart
const byType = @json($byType);
const typeLabels = Object.keys(byType).map(t => t === 'damaged' ? 'Hàng hư hỏng' : 'Thanh lý');
const typeLosses = Object.values(byType).map(d => d.loss);

new Chart(document.getElementById('typeChart'), {
    type: 'doughnut',
    data: {
        labels: typeLabels,
        datasets: [{
            data: typeLosses,
            backgroundColor: ['#EF4444', '#F59E0B'],
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

// Top Products Bar Chart
const topProducts = @json($topProducts->values());
const productLabels = topProducts.map(item => item.product?.name?.substring(0, 15) || 'N/A');
const productLosses = topProducts.map(item => item.total_loss);

new Chart(document.getElementById('topProductsChart'), {
    type: 'bar',
    data: {
        labels: productLabels,
        datasets: [{
            label: 'Tổn thất (đ)',
            data: productLosses,
            backgroundColor: '#EF4444',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: { beginAtZero: true }
        }
    }
});
</script>
@endpush
@endsection
