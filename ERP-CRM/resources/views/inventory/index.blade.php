@extends('layouts.app')

@section('title', 'Tồn kho')
@section('page-title', 'Quản lý Tồn kho')

@section('content')
    <div class="bg-white rounded-lg shadow-sm">
        <!-- Header -->
        <div class="p-3 sm:p-4 border-b border-gray-200 space-y-3">
            <div class="flex flex-col sm:flex-row gap-3">
                <!-- Search -->
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tìm kiếm</label>
                    <div class="relative">
                        <form action="{{ route('inventory.index') }}" method="GET" class="flex">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm sản phẩm..."
                                class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="hidden" name="warehouse_id" value="{{ request('warehouse_id') }}">
                            <input type="hidden" name="stock_status" value="{{ request('stock_status') }}">
                            <input type="hidden" name="expiry_filter" value="{{ request('expiry_filter') }}">
                        </form>
                    </div>
                </div>

                <!-- Filter by Warehouse -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Kho</label>
                    <select name="warehouse_id"
                        onchange="window.location.href='{{ route('inventory.index') }}?warehouse_id='+this.value+'&stock_status={{ request('stock_status') }}&expiry_filter={{ request('expiry_filter') }}&search={{ request('search') }}'"
                        class="w-full sm:w-auto border border-gray-300 rounded-lg px-3 py-2 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-primary appearance-none bg-white">
                        <option value="">Tất cả kho</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter by Stock Status -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Trạng thái</label>
                    <select name="stock_status"
                        onchange="window.location.href='{{ route('inventory.index') }}?stock_status='+this.value+'&warehouse_id={{ request('warehouse_id') }}&expiry_filter={{ request('expiry_filter') }}&search={{ request('search') }}'"
                        class="w-full sm:w-auto border border-gray-300 rounded-lg px-3 py-2 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-primary appearance-none bg-white">
                        <option value="">Tất cả trạng thái</option>
                        <option value="available" {{ request('stock_status') == 'available' ? 'selected' : '' }}>Còn hàng</option>
                        <option value="low" {{ request('stock_status') == 'low' ? 'selected' : '' }}>Sắp hết</option>
                        <option value="out" {{ request('stock_status') == 'out' ? 'selected' : '' }}>Hết hàng</option>
                    </select>
                </div>

                <!-- Filter by Expiry -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Hạn sử dụng</label>
                    <select name="expiry_filter"
                        onchange="window.location.href='{{ route('inventory.index') }}?expiry_filter='+this.value+'&warehouse_id={{ request('warehouse_id') }}&stock_status={{ request('stock_status') }}&search={{ request('search') }}'"
                        class="w-full sm:w-auto border border-gray-300 rounded-lg px-3 py-2 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-primary appearance-none bg-white">
                        <option value="">Tất cả hạn sử dụng</option>
                        <option value="expiring" {{ request('expiry_filter') == 'expiring' ? 'selected' : '' }}>Sắp hết hạn
                        </option>
                        <option value="expired" {{ request('expiry_filter') == 'expired' ? 'selected' : '' }}>Đã hết hạn</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('inventory.export', request()->query()) }}"
                    class="inline-flex items-center justify-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-emerald-600 transition-colors text-sm">
                    <i class="fas fa-file-excel mr-2"></i>Xuất Excel
                </a>
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
        <div class="hidden md:block overflow-x-auto" x-data="{ expanded: null }">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-10"></th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-12">STT</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[300px]">Sản phẩm</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Kho</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Tồn kho</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Hạn sử dụng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Bảo hành</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Đơn giá TB</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Tổng giá trị</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap w-24">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($inventories as $inventory)
                        <tr class="hover:bg-gray-50 cursor-pointer" @click="expanded === {{ $inventory->id }} ? expanded = null : expanded = {{ $inventory->id }}">
                            <td class="px-4 py-3 text-center">
                                <i class="fas fa-chevron-right transition-transform duration-200" :class="expanded === {{ $inventory->id }} ? 'rotate-90 text-primary' : 'text-gray-400'"></i>
                            </td>
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
                                <div class="flex flex-col">
                                    <span
                                        class="font-medium {{ $inventory->stock <= 0 ? 'text-red-600' : ($inventory->is_low_stock ? 'text-yellow-600' : 'text-gray-900') }}">
                                        {{ number_format($inventory->stock) }}
                                    </span>
                                    @foreach($inventory->stock_breakdown as $status => $count)
                                        @if($count > 0 && $status !== 'sold' && $status !== 'transferred')
                                            <span class="text-xs text-gray-500">
                                                @switch($status)
                                                    @case('in_stock') Mới: @break
                                                    @case('damaged') Hỏng: @break
                                                    @case('liquidation') TL: @break
                                                    @default {{ ucfirst($status) }}:
                                                @endswitch
                                                {{ $count }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                @if($inventory->expiry_date)
                                    <span
                                        class="{{ $inventory->is_expiring_soon ? 'text-orange-600 font-medium' : 'text-gray-500' }}">
                                        {{ $inventory->expiry_date->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                @if($inventory->warranty_months)
                                    {{ $inventory->warranty_months }} tháng
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right">
                                @if($inventory->avg_cost > 0)
                                    <span class="font-medium text-gray-800">{{ number_format($inventory->avg_cost, 0, ',', '.') }} đ</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right">
                                @if($inventory->stock > 0 && $inventory->avg_cost > 0)
                                    <span class="font-semibold text-blue-700">{{ number_format($inventory->stock * $inventory->avg_cost, 0, ',', '.') }} đ</span>
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
                            <td class="px-4 py-3 whitespace-nowrap text-center" @click.stop>
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('inventory.show', $inventory->id) }}"
                                        class="inline-block p-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-colors"
                                        title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <!-- Details Row (Hidden by default) -->
                        <tr x-show="expanded === {{ $inventory->id }}" x-cloak class="bg-gray-50">
                            <td colspan="11" class="px-8 py-4">
                                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="space-y-3">
                                            <div>
                                                <label class="text-sm text-gray-500">Sản phẩm</label>
                                                <p class="font-medium text-gray-900">{{ $inventory->product->name }}</p>
                                                <p class="text-sm text-gray-500">{{ $inventory->product->code }}</p>
                                            </div>
                                            <div>
                                                <label class="text-sm text-gray-500">Kho</label>
                                                <p class="font-medium text-gray-900">{{ $inventory->warehouse->name }}</p>
                                            </div>
                                            @if($inventory->stock > 0)
                                            <div>
                                                <label class="text-sm text-gray-500 mb-1 block">Chi tiết tồn kho:</label>
                                                <div class="bg-gray-50 p-3 rounded-lg text-sm">
                                                    @foreach($inventory->stock_breakdown as $status => $count)
                                                        @if($count > 0 && $status != 'sold' && $status !== 'transferred')
                                                        <div class="flex items-center justify-between max-w-[200px]">
                                                            <span>
                                                                @switch($status)
                                                                    @case('in_stock') <i class="fas fa-check-circle text-green-500 mr-2 w-4"></i>Mới: @break
                                                                    @case('damaged') <i class="fas fa-times-circle text-red-500 mr-2 w-4"></i>Hỏng: @break
                                                                    @case('liquidation') <i class="fas fa-tag text-purple-500 mr-2 w-4"></i>Thanh lý: @break
                                                                    @default <i class="fas fa-box text-gray-400 mr-2 w-4"></i>{{ ucfirst($status) }}:
                                                                @endswitch
                                                            </span>
                                                            <span class="font-bold text-gray-900">{{ $count }}</span>
                                                        </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                        <div class="space-y-3">
                                            <div>
                                                <label class="text-sm text-gray-500">Hạn sử dụng</label>
                                                <p class="font-medium {{ $inventory->is_expiring_soon ? 'text-orange-600' : 'text-gray-900' }}">
                                                    {{ $inventory->expiry_date ? $inventory->expiry_date->format('d/m/Y') : '-' }}
                                                </p>
                                                @if($inventory->days_until_expiry !== null)
                                                    <p class="text-sm text-gray-500">
                                                        {{ $inventory->days_until_expiry >= 0 ? 'Còn ' . $inventory->days_until_expiry . ' ngày' : 'Đã hết hạn' }}
                                                    </p>
                                                @endif
                                            </div>
                                            <div>
                                                <label class="text-sm text-gray-500">Bảo hành</label>
                                                <p class="font-medium text-gray-900">
                                                    {{ $inventory->warranty_months ? $inventory->warranty_months . ' tháng' : '-' }}
                                                </p>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2 mt-2 pt-2 border-t border-gray-100">
                                                <div>
                                                    <label class="text-xs text-gray-400">Cập nhật lần cuối</label>
                                                    <p class="text-xs text-gray-600">{{ $inventory->updated_at->format('d/m/Y H:i') }}</p>
                                                </div>
                                                <div>
                                                    <label class="text-xs text-gray-400">Ngày tạo</label>
                                                    <p class="text-xs text-gray-600">{{ $inventory->created_at->format('d/m/Y H:i') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                    @if($inventories->count() > 0)
                        <tr class="bg-blue-50 border-t-2 border-blue-200">
                            <td colspan="5" class="px-4 py-3 text-right text-sm font-bold text-blue-800">
                                Tổng giá trị tồn kho:
                            </td>
                            <td colspan="2" class="px-4 py-3 text-right text-sm font-bold text-blue-800">
                                {{ number_format($inventories->sum(fn($inv) => $inv->stock * $inv->avg_cost), 0, ',', '.') }} đ
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Card View - Mobile -->
        <div class="md:hidden divide-y divide-gray-200" x-data="{ expanded: null }">
            @forelse($inventories as $inventory)
                <div class="p-4 hover:bg-gray-50">
                    <div class="flex justify-between items-start mb-2" @click="expanded === {{ $inventory->id }} ? expanded = null : expanded = {{ $inventory->id }}">
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">{{ $inventory->product->name }}</div>
                            <div class="text-sm text-gray-500">{{ $inventory->product->code }}</div>
                        </div>
                        <div class="flex items-center gap-2">
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
                            <i class="fas fa-chevron-down transition-transform duration-200" :class="expanded === {{ $inventory->id }} ? 'rotate-180 text-primary' : 'text-gray-400'"></i>
                        </div>
                    </div>
                    <div class="space-y-1 text-sm text-gray-600 mb-3" x-show="expanded !== {{ $inventory->id }}">
                        <div><i class="fas fa-warehouse w-4"></i> {{ $inventory->warehouse->name }}</div>
                        <div><i class="fas fa-boxes w-4"></i> Tồn: {{ number_format($inventory->stock) }} / Min:
                            {{ number_format($inventory->min_stock) }}</div>
                        <div><i class="fas fa-dollar-sign w-4"></i> {{ number_format($inventory->avg_cost) }} đ</div>
                        @if($inventory->expiry_date)
                            <div><i class="fas fa-calendar w-4"></i> HSD: {{ $inventory->expiry_date->format('d/m/Y') }}</div>
                        @endif
                        @if($inventory->warranty_months)
                            <div><i class="fas fa-shield-alt w-4"></i> BH: {{ $inventory->warranty_months }} tháng</div>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('inventory.show', $inventory->id) }}"
                            class="flex-1 text-center px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-sm">
                            <i class="fas fa-eye mr-1"></i>Xem
                        </a>
                    </div>
                    <div x-show="expanded === {{ $inventory->id }}" x-cloak class="mt-3 p-3 bg-gray-50 rounded-lg text-sm border border-gray-100">
                        @if($inventory->stock > 0)
                        <div class="mb-2">
                            <span class="font-medium text-gray-700 block mb-1">Chi tiết tồn kho:</span>
                            @foreach($inventory->stock_breakdown as $status => $count)
                                @if($count > 0 && $status != 'sold' && $status !== 'transferred')
                                    <div class="flex justify-between py-1 border-b border-gray-200 border-dashed last:border-0">
                                        <span class="text-gray-600">
                                            @switch($status)
                                                @case('in_stock') Mới @break
                                                @case('damaged') Hỏng @break
                                                @case('liquidation') Thanh lý @break
                                                @default {{ ucfirst($status) }}
                                            @endswitch
                                        </span>
                                        <span class="font-medium">{{ $count }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        @endif
                        <div class="text-xs text-gray-500 mt-2">
                            Cập nhật: {{ $inventory->updated_at->format('d/m/y H:i') }}
                        </div>
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