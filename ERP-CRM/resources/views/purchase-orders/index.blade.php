@extends('layouts.app')

@section('title', 'Đơn mua hàng')
@section('page-title', 'Quản lý đơn mua hàng (PO)')

@section('content')
<div class="space-y-6">
    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Chờ duyệt</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Đang xử lý</p>
            <p class="text-2xl font-bold text-blue-600">{{ $stats['sent'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Đã nhận hàng</p>
            <p class="text-2xl font-bold text-green-600">{{ $stats['received'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Tổng giá trị PO</p>
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['total_value']) }}đ</p>
        </div>
    </div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <p class="text-gray-600">Quản lý đơn đặt hàng gửi cho nhà cung cấp</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('purchase-requests.index') }}" class="inline-flex items-center px-4 py-2 border rounded-lg hover:bg-gray-50">
                <i class="fas fa-file-invoice mr-2"></i> Yêu cầu báo giá
            </a>
            <a href="{{ route('supplier-quotations.index') }}" class="inline-flex items-center px-4 py-2 border rounded-lg hover:bg-gray-50">
                <i class="fas fa-tags mr-2"></i> Báo giá NCC
            </a>
            <a href="{{ route('purchase-orders.export', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600">
                <i class="fas fa-file-excel mr-2"></i> Xuất Excel
            </a>
            <a href="{{ route('purchase-orders.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                <i class="fas fa-plus mr-2"></i> Tạo PO
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 border-b border-gray-200 bg-gray-50">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm..." 
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            <select name="status" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Nháp</option>
                <option value="pending_approval" {{ request('status') == 'pending_approval' ? 'selected' : '' }}>Chờ duyệt</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Đã gửi NCC</option>
                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>NCC xác nhận</option>
                <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Đã nhận hàng</option>
            </select>
            <select name="supplier_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                <option value="">-- Tất cả NCC --</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                <i class="fas fa-search mr-2"></i> Lọc
            </button>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã PO</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nhà cung cấp</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày đặt</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thời gian đặt</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày giao DK</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng tiền</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($orders as $order)
                    @php
                        // Calculate order duration and status
                        $orderDate = $order->order_date;
                        $daysElapsed = $orderDate->diffInDays(now());
                        $weeksElapsed = floor($daysElapsed / 7);
                        
                        // Expected arrival: 4-6 weeks if not set
                        $expectedMinDate = $orderDate->copy()->addWeeks(4);
                        $expectedMaxDate = $orderDate->copy()->addWeeks(6);
                        
                        // Status indicators
                        $isOverdue = $order->expected_delivery && now()->gt($order->expected_delivery) && $order->status !== 'received' && $order->status !== 'cancelled';
                        $isNearDelivery = $order->expected_delivery && now()->diffInDays($order->expected_delivery, false) <= 7 && now()->diffInDays($order->expected_delivery, false) >= 0 && $order->status !== 'received' && $order->status !== 'cancelled';
                        $isLongWaiting = $daysElapsed > 42 && $order->status !== 'received' && $order->status !== 'cancelled';
                        
                        // Row class
                        $rowClass = $isOverdue ? 'bg-red-50 hover:bg-red-100' : ($isLongWaiting ? 'bg-orange-50 hover:bg-orange-100' : ($isNearDelivery ? 'bg-green-50 hover:bg-green-100' : 'hover:bg-gray-50'));
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="px-4 py-3 font-medium text-primary">{{ $order->code }}</td>
                        <td class="px-4 py-3">{{ $order->supplier->name }}</td>
                        <td class="px-4 py-3">{{ $order->order_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            @if($order->status !== 'received' && $order->status !== 'cancelled')
                                <div class="flex items-center">
                                    <span class="text-sm font-medium {{ $isLongWaiting ? 'text-orange-600' : 'text-gray-600' }}">
                                        {{ $daysElapsed }} ngày
                                    </span>
                                    <span class="text-xs text-gray-400 ml-1">({{ $weeksElapsed }}w)</span>
                                </div>
                                @if($isLongWaiting)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-orange-100 text-orange-700 mt-1">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Đã lâu
                                    </span>
                                @elseif($isNearDelivery)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-green-100 text-green-700 mt-1">
                                        <i class="fas fa-shipping-fast mr-1"></i> Sắp về
                                    </span>
                                @elseif($isOverdue)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-red-100 text-red-700 mt-1 animate-pulse">
                                        <i class="fas fa-exclamation-circle mr-1"></i> Quá hạn
                                    </span>
                                @endif
                            @else
                                <span class="text-gray-400 text-sm">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($order->expected_delivery)
                                <div class="{{ $isOverdue ? 'text-red-600 font-medium' : ($isNearDelivery ? 'text-green-600' : 'text-gray-600') }}">
                                    {{ $order->expected_delivery->format('d/m/Y') }}
                                </div>
                                @if($isOverdue)
                                    <span class="text-xs text-red-500">Quá {{ now()->diffInDays($order->expected_delivery) }} ngày</span>
                                @elseif($isNearDelivery)
                                    <span class="text-xs text-green-500">Còn {{ now()->diffInDays($order->expected_delivery, false) }} ngày</span>
                                @endif
                            @elseif($order->status !== 'received' && $order->status !== 'cancelled')
                                <div class="text-gray-400 text-sm">{{ $expectedMinDate->format('d/m') }} - {{ $expectedMaxDate->format('d/m') }}</div>
                                <span class="text-xs text-gray-400">4-6 tuần</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-semibold">{{ number_format($order->total) }}đ</td>
                        <td class="px-4 py-3">
                            @switch($order->status)
                                @case('draft')
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Nháp</span>
                                    @break
                                @case('pending_approval')
                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Chờ duyệt</span>
                                    @break
                                @case('approved')
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Đã duyệt</span>
                                    @break
                                @case('sent')
                                    <span class="px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-800">Đã gửi NCC</span>
                                    @break
                                @case('confirmed')
                                    <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">NCC xác nhận</span>
                                    @break
                                @case('received')
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Đã nhận hàng</span>
                                    @break
                                @case('cancelled')
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Đã hủy</span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center space-x-1">
                                <a href="{{ route('purchase-orders.show', $order) }}" class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all" title="Xem">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(in_array($order->status, ['draft', 'pending_approval']))
                                    <a href="{{ route('purchase-orders.edit', $order) }}" class="inline-flex items-center justify-center w-8 h-8 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-all" title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif
                                @if($order->status == 'draft')
                                    <form action="{{ route('purchase-orders.submit-approval', $order) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 bg-orange-100 text-orange-600 rounded-lg hover:bg-orange-200 transition-all" title="Gửi duyệt">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </form>
                                @endif
                                @if($order->status == 'pending_approval')
                                    <form action="{{ route('purchase-orders.approve', $order) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all transform hover:scale-110" title="Duyệt">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                @endif
                                @if($order->status == 'approved')
                                    <form action="{{ route('purchase-orders.send', $order) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 bg-indigo-100 text-indigo-600 rounded-lg hover:bg-indigo-200 transition-all" title="Gửi NCC">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('purchase-orders.print', $order) }}" class="inline-flex items-center justify-center w-8 h-8 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-all" title="In" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">Chưa có đơn mua hàng nào</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
