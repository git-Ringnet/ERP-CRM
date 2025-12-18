@extends('layouts.app')

@section('title', 'Sản phẩm sắp hết hạn bảo hành')
@section('page-title', 'Sản phẩm sắp hết hạn bảo hành')

@section('content')
<div class="space-y-4">
    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form method="GET" action="{{ route('warranties.expiring') }}" class="flex items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hết hạn trong (ngày)</label>
                <input type="number" name="days" value="{{ $days }}" min="1" max="365"
                       class="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-sm">
                <i class="fas fa-filter mr-1"></i> Áp dụng
            </button>
            <a href="{{ route('warranties.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
        </form>
    </div>

    <!-- Summary -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-yellow-500 text-2xl mr-3"></i>
            <div>
                <h3 class="font-semibold text-yellow-800">{{ $warranties->count() }} sản phẩm sắp hết hạn bảo hành</h3>
                <p class="text-sm text-yellow-600">Trong vòng {{ $days }} ngày tới</p>
            </div>
        </div>
    </div>

    <!-- Expiring Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã đơn</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SĐT</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã SP</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên sản phẩm</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ngày hết hạn</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Còn lại</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($warranties as $item)
                        @php
                            $daysRemaining = $item->warranty_days_remaining;
                            $rowClass = '';
                            if ($daysRemaining <= 3) {
                                $rowClass = 'bg-red-50';
                            } elseif ($daysRemaining <= 7) {
                                $rowClass = 'bg-yellow-50';
                            }
                        @endphp
                        <tr class="{{ $rowClass }} hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">{{ $item->sale_code }}</td>
                            <td class="px-4 py-3 text-sm font-medium">{{ $item->customer_name }}</td>
                            <td class="px-4 py-3 text-sm">{{ $item->customer_phone ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm font-medium">{{ $item->product_code }}</td>
                            <td class="px-4 py-3 text-sm">{{ $item->product_name }}</td>
                            <td class="px-4 py-3 text-sm text-center">
                                {{ $item->warranty_end_date ? $item->warranty_end_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($daysRemaining <= 3)
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800 font-semibold">
                                        {{ $daysRemaining }} ngày
                                    </span>
                                @elseif($daysRemaining <= 7)
                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800 font-semibold">
                                        {{ $daysRemaining }} ngày
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                        {{ $daysRemaining }} ngày
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('warranties.show', $item->id) }}" class="text-primary hover:text-primary-dark">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-check-circle text-green-500 text-3xl mb-2"></i>
                                <p>Không có sản phẩm nào sắp hết hạn bảo hành trong {{ $days }} ngày tới</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
