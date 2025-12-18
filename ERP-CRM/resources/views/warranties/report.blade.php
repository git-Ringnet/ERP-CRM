@extends('layouts.app')

@section('title', 'Báo cáo bảo hành')
@section('page-title', 'Báo cáo bảo hành')

@section('content')
<div class="space-y-6">
    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form method="GET" action="{{ route('warranties.report') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Từ ngày bán</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                       class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Đến ngày bán</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                       class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sắp hết hạn (ngày)</label>
                <input type="number" name="expiring_days" value="{{ $filters['expiring_days'] ?? 30 }}" min="1" max="365"
                       class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-sm">
                <i class="fas fa-filter mr-1"></i> Lọc
            </button>
            <a href="{{ route('warranties.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
            <p class="text-sm text-gray-500">Tổng đã bán</p>
            <p class="text-2xl font-bold text-blue-600">{{ number_format($summary['total_sold']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-purple-500">
            <p class="text-sm text-gray-500">Có bảo hành</p>
            <p class="text-2xl font-bold text-purple-600">{{ number_format($summary['with_warranty']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-green-500">
            <p class="text-sm text-gray-500">Đang bảo hành</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($summary['active']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-red-500">
            <p class="text-sm text-gray-500">Hết hạn</p>
            <p class="text-2xl font-bold text-red-600">{{ number_format($summary['expired']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-yellow-500">
            <p class="text-sm text-gray-500">Sắp hết hạn</p>
            <p class="text-2xl font-bold text-yellow-600">{{ number_format($summary['expiring_soon']) }}</p>
            <p class="text-xs text-gray-400">trong {{ $summary['expiring_days'] }} ngày</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-gray-500">
            <p class="text-sm text-gray-500">Không BH</p>
            <p class="text-2xl font-bold text-gray-600">{{ number_format($summary['no_warranty']) }}</p>
        </div>
    </div>


    <!-- Tabs -->
    <div x-data="{ tab: 'customer' }" class="bg-white rounded-lg shadow-sm">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button @click="tab = 'customer'" :class="tab === 'customer' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="px-6 py-3 border-b-2 font-medium text-sm">
                    <i class="fas fa-users mr-1"></i> Theo khách hàng
                </button>
                <button @click="tab = 'product'" :class="tab === 'product' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="px-6 py-3 border-b-2 font-medium text-sm">
                    <i class="fas fa-box mr-1"></i> Theo sản phẩm
                </button>
            </nav>
        </div>

        <!-- By Customer Tab -->
        <div x-show="tab === 'customer'" class="p-4">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SĐT</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tổng SP</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Đang BH</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Hết hạn</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Không BH</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($byCustomer as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium">{{ $row->customer_name }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row->customer_phone ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-center">{{ $row->total_items }}</td>
                                <td class="px-4 py-3 text-sm text-center text-green-600">{{ $row->active_count }}</td>
                                <td class="px-4 py-3 text-sm text-center text-red-600">{{ $row->expired_count }}</td>
                                <td class="px-4 py-3 text-sm text-center text-gray-500">{{ $row->no_warranty_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- By Product Tab -->
        <div x-show="tab === 'product'" x-cloak class="p-4">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã SP</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên sản phẩm</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">BH mặc định</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Đã bán</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Đang BH</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Hết hạn</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Không BH</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($byProduct as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium">{{ $row->product_code }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row->product_name }}</td>
                                <td class="px-4 py-3 text-sm text-center">{{ $row->default_warranty ?? 0 }} tháng</td>
                                <td class="px-4 py-3 text-sm text-center">{{ $row->total_sold }}</td>
                                <td class="px-4 py-3 text-sm text-center text-green-600">{{ $row->active_count }}</td>
                                <td class="px-4 py-3 text-sm text-center text-red-600">{{ $row->expired_count }}</td>
                                <td class="px-4 py-3 text-sm text-center text-gray-500">{{ $row->no_warranty_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
