@extends('layouts.app')

@section('title', 'Tồn kho')
@section('page-title', 'Quản lý Tồn kho')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="p-3 sm:p-4 border-b border-gray-200 space-y-3">
        <div class="flex flex-col sm:flex-row gap-3">
            <!-- Search -->
            <div class="relative flex-1">
                <form action="{{ route('inventory.index') }}" method="GET" class="flex">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Tìm kiếm sản phẩm..." 
                           class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="hidden" name="warehouse_id" value="{{ request('warehouse_id') }}">
                    <input type="hidden" name="stock_status" value="{{ request('stock_status') }}">
                    <input type="hidden" name="expiry_filter" value="{{ request('expiry_filter') }}">
                </form>
            </div>
            
            <!-- Filter by Warehouse -->
            <select name="warehouse_id" onchange="window.location.href='{{ route('inventory.index') }}?warehouse_id='+this.value+'&stock_status={{ request('stock_status') }}&expiry_filter={{ request('expiry_filter') }}&search={{ request('search') }}'" 
                    class="w-full sm:w-auto border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">Tất cả kho</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                        {{ $warehouse->name }}
                    </option>
                @endforeach
            </select>

            <!-- Filter by Stock Status -->
            <select name="stock_status" onchange="window.location.href='{{ route('inventory.index') }}?stock_status='+this.value+'&warehouse_id={{ request('warehouse_id') }}&expiry_filter={{ request('expiry_filter') }}&search={{ request('search') }}'" 
                    class="w-full sm:w-auto border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">Tất cả trạng thái</option>
                <option value="available" {{ request('stock_status') == 'available' ? 'selected' : '' }}>Còn hàng</option>
                <option value="low" {{ request('stock_status') == 'low' ? 'selected' : '' }}>Sắp hết</option>
                <option value="out" {{ request('stock_status') == 'out' ? 'selected' : '' }}>Hết hàng</option>
            </select>

            <!-- Filter by Expiry -->
            <select name="expiry_filter" onchange="window.location.href='{{ route('inventory.index') }}?expiry_filter='+this.value+'&warehouse_id={{ request('warehouse_id') }}&stock_status={{ request('stock_status') }}&search={{ request('search') }}'" 
                    class="w-full sm:w-auto border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">Tất cả hạn sử dụng</option>
                <option value="expiring" {{ request('expiry_filter') == 'expiring' ? 'selected' : '' }}>Sắp hết hạn</option>
                <option value="expired" {{ request('expiry_filter') == 'expired' ? 'selected' : '' }}>Đã hết hạn</option>
            </select>
        </div>
        
        <div class="flex gap-2">
            <a href="{{ route('inventory.low-stock') }}" 
               class="inline-flex items-center justify-center px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors text-sm">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span class="hidden sm:inline">Sắp hết hàng</span>
                <span class="sm:hidden">Sắp hết</span>
            </a>
            <a href="{{ route('inventory.expiring') }}" 
               class="inline-flex items-center justify-center px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors text-sm">
                <i class="fas fa-clock mr-2"></i>
                <span class="hidden sm:inline">Sắp hết hạn</span>
                <span class="sm:hidden">Hết hạn</span>
            </a>
        </div>
    </div>

    <!-- Table - Desktop View -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản phẩm</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kho</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tồn kho</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hạn sử dụng</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($inventories as $inventory)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500">
                        {{ ($inventories->currentPage() - 1) * $inventories->perPage() + $loop->iteration }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm font-medium text-gray-900">{{ $inventory->product->name }}</div>
                        <div class="text-sm text-gray-500">{{ $inventory->product->code }}</div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                        {{ $inventory->warehouse->name }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="font-medium {{ $inventory->stock <= 0 ? 'text-red-600' : ($inventory->is_low_stock ? 'text-yellow-600' : 'text-gray-900') }}">
                            {{ number_format($inventory->stock) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                        @if($inventory->expiry_date)
                            <span class="{{ $inventory->is_expiring_soon ? 'text-orange-600 font-medium' : 'text-gray-500' }}">
                                {{ $inventory->expiry_date->format('d/m/Y') }}
                            </span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($inventory->stock <= 0)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-1"></i>Hết hàng
                            </span>
                        @elseif($inventory->is_low_stock)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                <i class="fas fa-exclamation-triangle mr-1"></i>Sắp hết
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Còn hàng
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <a href="{{ route('inventory.show', $inventory->id) }}" 
                           class="inline-block p-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-colors" 
                           title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-boxes text-4xl mb-2"></i>
                        <p>Không có dữ liệu tồn kho</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Card View - Mobile -->
    <div class="md:hidden divide-y divide-gray-200">
        @forelse($inventories as $inventory)
        <div class="p-4 hover:bg-gray-50">
            <div class="flex justify-between items-start mb-2">
                <div class="flex-1">
                    <div class="font-medium text-gray-900">{{ $inventory->product->name }}</div>
                    <div class="text-sm text-gray-500">{{ $inventory->product->code }}</div>
                </div>
                @if($inventory->stock <= 0)
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                        Hết hàng
                    </span>
                @elseif($inventory->is_low_stock)
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                        Sắp hết
                    </span>
                @else
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                        Còn hàng
                    </span>
                @endif
            </div>
            <div class="space-y-1 text-sm text-gray-600 mb-3">
                <div><i class="fas fa-warehouse w-4"></i> {{ $inventory->warehouse->name }}</div>
                <div><i class="fas fa-boxes w-4"></i> Tồn: {{ number_format($inventory->stock) }} / Min: {{ number_format($inventory->min_stock) }}</div>
                <div><i class="fas fa-dollar-sign w-4"></i> {{ number_format($inventory->avg_cost) }} đ</div>
                @if($inventory->expiry_date)
                    <div><i class="fas fa-calendar w-4"></i> HSD: {{ $inventory->expiry_date->format('d/m/Y') }}</div>
                @endif
            </div>
            <div class="flex gap-2">
                <a href="{{ route('inventory.show', $inventory->id) }}" 
                   class="flex-1 text-center px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-sm">
                    <i class="fas fa-eye mr-1"></i>Xem
                </a>
            </div>
        </div>
        @empty
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-boxes text-4xl mb-2"></i>
            <p>Không có dữ liệu tồn kho</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($inventories->hasPages())
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $inventories->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
