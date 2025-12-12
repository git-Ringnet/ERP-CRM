@extends('layouts.app')

@section('title', 'Đơn hàng bán')
@section('page-title', 'Quản lý Đơn hàng bán')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex flex-col sm:flex-row gap-4 flex-1">
            <!-- Search -->
            <div class="relative flex-1 max-w-md">
                <form action="{{ route('sales.index') }}" method="GET" class="flex">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Tìm kiếm đơn hàng..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </form>
            </div>
            
            <!-- Filter by Status -->
            <div class="flex items-center gap-2">
                <select name="status" onchange="window.location.href='{{ route('sales.index') }}?status='+this.value+'&type={{ request('type') }}&search={{ request('search') }}'" 
                        class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                    <option value="shipping" {{ request('status') == 'shipping' ? 'selected' : '' }}>Đang giao</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                </select>
            </div>

            <!-- Filter by Type -->
            <div class="flex items-center gap-2">
                <select name="type" onchange="window.location.href='{{ route('sales.index') }}?type='+this.value+'&status={{ request('status') }}&search={{ request('search') }}'" 
                        class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Loại đơn hàng</option>
                    <option value="retail" {{ request('type') == 'retail' ? 'selected' : '' }}>Bán lẻ</option>
                    <option value="project" {{ request('type') == 'project' ? 'selected' : '' }}>Bán theo dự án</option>
                </select>
            </div>
        </div>
        
        <div class="flex gap-2">
            <a href="{{ route('sales.export') }}?{{ http_build_query(request()->query()) }}" 
               class="inline-flex items-center px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-colors">
                <i class="fas fa-file-excel mr-2"></i>
                Xuất Excel
            </a>
            <a href="{{ route('sales.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Tạo đơn hàng
            </a>
        </div>
    </div>

    <!-- Table - Desktop View -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã đơn</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Khách hàng</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày tạo</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tổng tiền</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Margin</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thanh toán</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($sales as $sale)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500">
                        {{ ($sales->currentPage() - 1) * $sales->perPage() + $loop->iteration }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="font-medium text-gray-900">{{ $sale->code }}</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $sale->type == 'project' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ $sale->type_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm font-medium text-gray-900">{{ $sale->customer_name }}</div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                        {{ $sale->date->format('d/m/Y') }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                        {{ number_format($sale->total) }} đ
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right">
                        @if($sale->cost > 0)
                            <span class="font-medium {{ $sale->margin_color }}">
                                {{ $sale->margin >= 0 ? '+' : '' }}{{ number_format($sale->margin) }} đ
                            </span>
                            <div class="text-xs {{ $sale->margin_color }}">
                                ({{ number_format($sale->margin_percent, 1) }}%)
                            </div>
                            @if($sale->margin < 0)
                                <div class="text-xs text-red-600">
                                    <i class="fas fa-exclamation-triangle"></i> Lỗ
                                </div>
                            @endif
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $sale->payment_status_color }}">
                            {{ $sale->payment_status_label }}
                        </span>
                        @if($sale->debt_amount > 0)
                            <div class="text-xs text-red-600 mt-1">Nợ: {{ number_format($sale->debt_amount) }} đ</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $sale->status_color }}">
                            {{ $sale->status_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('sales.show', $sale->id) }}" 
                               class="p-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-colors" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('sales.edit', $sale->id) }}" 
                               class="p-2 text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 hover:text-yellow-700 transition-colors" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('sales.destroy', $sale->id) }}" method="POST" class="inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 hover:text-red-700 transition-colors delete-btn" 
                                        data-name="{{ $sale->code }}" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Không có dữ liệu đơn hàng</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Card View - Mobile -->
    <div class="md:hidden divide-y divide-gray-200">
        @forelse($sales as $sale)
        <div class="p-4 hover:bg-gray-50">
            <div class="flex justify-between items-start mb-2">
                <div class="flex-1">
                    <div class="font-medium text-gray-900">{{ $sale->code }}</div>
                    <div class="text-sm text-gray-500">{{ $sale->customer_name }}</div>
                </div>
                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $sale->status_color }}">
                    {{ $sale->status_label }}
                </span>
            </div>
            <div class="space-y-1 text-sm text-gray-600 mb-3">
                <div><i class="fas fa-tag w-4"></i> {{ $sale->type_label }}</div>
                <div><i class="fas fa-calendar w-4"></i> {{ $sale->date->format('d/m/Y') }}</div>
                <div><i class="fas fa-money-bill w-4"></i> {{ number_format($sale->total) }} đ</div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('sales.show', $sale->id) }}" 
                   class="flex-1 text-center px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-sm">
                    <i class="fas fa-eye mr-1"></i>Xem
                </a>
                <a href="{{ route('sales.edit', $sale->id) }}" 
                   class="flex-1 text-center px-3 py-2 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 text-sm">
                    <i class="fas fa-edit mr-1"></i>Sửa
                </a>
                <form action="{{ route('sales.destroy', $sale->id) }}" method="POST" class="flex-1 delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="w-full px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm delete-btn"
                            data-name="{{ $sale->code }}">
                        <i class="fas fa-trash mr-1"></i>Xóa
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-inbox text-4xl mb-2"></i>
            <p>Không có dữ liệu đơn hàng</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($sales->hasPages())
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $sales->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
