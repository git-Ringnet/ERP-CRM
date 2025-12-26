@extends('layouts.app')

@section('title', 'Theo dõi bảo hành')
@section('page-title', 'Theo dõi bảo hành')

@section('content')
<div class="space-y-4">
    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form method="GET" action="{{ route('warranties.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" 
                       placeholder="Mã SP, tên SP, khách hàng..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                    <option value="">Tất cả</option>
                    @foreach($statusLabels as $key => $label)
                        <option value="{{ $key }}" {{ ($filters['status'] ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hết hạn từ</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hết hạn đến</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-sm">
                    <i class="fas fa-search mr-1"></i> Lọc
                </button>
                <a href="{{ route('warranties.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
                    <i class="fas fa-redo mr-1"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-between items-center">
        <div class="flex gap-2">
            <a href="{{ route('warranties.expiring') }}" class="px-4 py-2 bg-warning text-white rounded-lg hover:bg-yellow-600 text-sm">
                <i class="fas fa-clock mr-1"></i> Sắp hết hạn
            </a>
            <a href="{{ route('warranties.report') }}" class="px-4 py-2 bg-cyan-500 text-white rounded-lg hover:bg-cyan-600 text-sm">
                <i class="fas fa-chart-bar mr-1"></i> Báo cáo
            </a>
        </div>
        <a href="{{ route('warranties.export', request()->query()) }}" class="px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 text-sm">
            <i class="fas fa-file-excel mr-1"></i> Xuất Excel
        </a>
    </div>


    <!-- Warranty Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã đơn</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã SP</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên sản phẩm</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">BH (tháng)</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ngày bắt đầu</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ngày hết hạn</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Còn lại</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($warranties as $item)
                        @php
                            $daysRemaining = $item->warranty_days_remaining;
                            $rowClass = '';
                            if ($item->warranty_status === 'active' && $daysRemaining !== null) {
                                if ($daysRemaining <= 3) {
                                    $rowClass = 'bg-red-50';
                                } elseif ($daysRemaining <= 7) {
                                    $rowClass = 'bg-yellow-50';
                                }
                            }
                        @endphp
                        <tr class="{{ $rowClass }} hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">{{ $item->sale_code }}</td>
                            <td class="px-4 py-3 text-sm">
                                <div>{{ $item->customer_name }}</div>
                                <div class="text-xs text-gray-500">{{ $item->customer_phone ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium">{{ $item->product_code }}</td>
                            <td class="px-4 py-3 text-sm">{{ $item->product_name }}</td>
                            <td class="px-4 py-3 text-sm text-center">{{ $item->warranty_months }}</td>
                            <td class="px-4 py-3 text-sm text-center">
                                {{ $item->warranty_start_date ? $item->warranty_start_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                {{ $item->warranty_end_date ? $item->warranty_end_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                @if($daysRemaining !== null)
                                    <span class="{{ $daysRemaining < 0 ? 'text-red-600' : ($daysRemaining <= 7 ? 'text-yellow-600' : 'text-green-600') }}">
                                        {{ $daysRemaining }} ngày
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php $color = $statusColors[$item->warranty_status] ?? 'gray'; @endphp
                                <span class="px-2 py-1 text-xs rounded-full 
                                    {{ $color === 'green' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $color === 'red' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $color === 'gray' ? 'bg-gray-100 text-gray-800' : '' }}">
                                    {{ $statusLabels[$item->warranty_status] ?? $item->warranty_status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('warranties.show', $item->id) }}" class="text-primary hover:text-primary-dark">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                                Không có dữ liệu bảo hành
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($warranties->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $warranties->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
