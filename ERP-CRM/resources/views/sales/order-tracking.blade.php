@extends('layouts.app')

@section('title', 'Theo dõi tiến độ đơn hàng')
@section('page-title', 'Theo dõi tiến độ đơn hàng')

@section('content')
<div class="space-y-6">
    <!-- Summary Stats -->
    @php
        $allRows = $rows->items();
        $countWaiting = collect($allRows)->where('status', 'waiting')->count();
        $countOrdering = collect($allRows)->where('status', 'ordering')->count();
        $countInTransit = collect($allRows)->where('status', 'in_transit')->count();
        $countCompleted = collect($allRows)->where('status', 'completed')->count();
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-gray-400">
            <div class="text-2xl font-bold text-gray-600">{{ $countWaiting }}</div>
            <div class="text-xs text-gray-500 uppercase font-medium mt-1"><i class="fas fa-clock mr-1"></i>Chờ đặt hàng</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <div class="text-2xl font-bold text-blue-600">{{ $countOrdering }}</div>
            <div class="text-xs text-gray-500 uppercase font-medium mt-1"><i class="fas fa-shopping-cart mr-1"></i>Đang đặt hàng</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-orange-500">
            <div class="text-2xl font-bold text-orange-600">{{ $countInTransit }}</div>
            <div class="text-xs text-gray-500 uppercase font-medium mt-1"><i class="fas fa-truck mr-1"></i>Đang về hàng</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <div class="text-2xl font-bold text-green-600">{{ $countCompleted }}</div>
            <div class="text-xs text-gray-500 uppercase font-medium mt-1"><i class="fas fa-check-circle mr-1"></i>Đã đủ hàng</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('sales.order-tracking') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mã Sale Order</label>
                <input type="text" name="sale_code" value="{{ request('sale_code') }}" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="SO-...">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Part Number</label>
                <input type="text" name="part_number" value="{{ request('part_number') }}" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Nhập part number...">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nhà cung cấp</label>
                <select name="vendor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Tất cả --</option>
                    @foreach($vendors as $v)
                        <option value="{{ $v->id }}" {{ request('vendor_id') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                <select name="status_filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Tất cả --</option>
                    <option value="waiting" {{ request('status_filter') == 'waiting' ? 'selected' : '' }}>Chờ đặt hàng</option>
                    <option value="ordering" {{ request('status_filter') == 'ordering' ? 'selected' : '' }}>Đang đặt hàng</option>
                    <option value="in_transit" {{ request('status_filter') == 'in_transit' ? 'selected' : '' }}>Đang về hàng</option>
                    <option value="completed" {{ request('status_filter') == 'completed' ? 'selected' : '' }}>Đã đủ hàng</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-search mr-2"></i> Lọc
                </button>
                <a href="{{ route('sales.order-tracking') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    Xóa
                </a>
            </div>
        </form>
    </div>

    <!-- Results Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b bg-gradient-to-r from-teal-50 to-cyan-50">
            <h3 class="font-semibold text-gray-800 flex items-center">
                <i class="fas fa-boxes mr-2 text-teal-600"></i>
                Theo dõi hàng về — Group theo Sale Order + Sản phẩm
            </h3>
            <p class="text-xs text-gray-500 mt-1">Dữ liệu được tổng hợp từ tất cả các yêu cầu đặt hàng (PR)</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sale Order</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm (Part Number)</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">SL Yêu cầu</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">SL Đã đặt</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">SL Đã về</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Còn thiếu</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Các đơn mua (PO)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($rows as $row)
                        <tr class="hover:bg-gray-50 transition-colors {{ $row['status'] === 'completed' ? 'bg-green-50/30' : '' }}">
                            <td class="px-4 py-3">
                                <div class="text-sm font-bold text-blue-600">
                                    <a href="{{ route('sales.show', $row['sale_id']) }}" class="hover:underline">
                                        <i class="fas fa-file-invoice mr-1"></i>{{ $row['sale_code'] }}
                                    </a>
                                </div>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($row['pr_codes'] as $prCode)
                                        <span class="text-[10px] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded">PR: {{ $prCode }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-900 font-medium">{{ $row['part_number'] }}</div>
                                <div class="text-xs text-gray-500">{{ $row['vendor_name'] }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                    {{ $row['requested'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $row['ordered'] >= $row['requested'] ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $row['ordered'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $row['received'] >= $row['requested'] ? 'bg-green-100 text-green-800' : ($row['received'] > 0 ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-400') }}">
                                    {{ $row['received'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($row['remaining'] > 0)
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                        {{ $row['remaining'] }}
                                    </span>
                                @else
                                    <span class="text-green-600 font-bold text-xs">
                                        <i class="fas fa-check"></i> 0
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $row['status_color'] }}">
                                    <i class="{{ $row['status_icon'] }} mr-1"></i> {{ $row['status_label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($row['po_links'] as $po)
                                        <a href="{{ route('purchase-orders.show', $po['id']) }}" 
                                            class="inline-block px-1.5 py-0.5 bg-indigo-50 text-indigo-600 rounded text-[10px] hover:bg-indigo-100 border border-indigo-200"
                                            title="{{ $po['status_label'] }}">
                                            {{ $po['code'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                                <i class="fas fa-search text-4xl mb-3"></i>
                                <p>Không tìm thấy dữ liệu yêu cầu đặt hàng nào.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())
            <div class="px-4 py-3 border-t">
                {{ $rows->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
