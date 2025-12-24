@extends('layouts.app')

@section('title', 'Chi tiết kho')
@section('page-title', 'Chi tiết kho hàng')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">{{ $warehouse->name }}</h2>
            <div class="flex gap-2">
                <a href="{{ route('warehouses.edit', $warehouse->id) }}" 
                   class="px-3 py-1.5 text-sm text-yellow-700 bg-yellow-100 rounded-lg hover:bg-yellow-200 transition-colors">
                    <i class="fas fa-edit mr-1"></i>Sửa
                </a>
                <a href="{{ route('warehouses.index') }}" 
                   class="px-3 py-1.5 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-arrow-left mr-1"></i>Quay lại
                </a>
            </div>
        </div>
        
        <div class="p-4">
            <!-- Status Badge -->
            <div class="mb-4">
                @if($warehouse->status == 'active')
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>{{ $warehouse->status_label }}
                    </span>
                @elseif($warehouse->status == 'maintenance')
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">
                        <i class="fas fa-tools mr-1"></i>{{ $warehouse->status_label }}
                    </span>
                @else
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                        <i class="fas fa-times-circle mr-1"></i>{{ $warehouse->status_label }}
                    </span>
                @endif
                <span class="ml-2 px-3 py-1 text-sm font-semibold rounded-full {{ $warehouse->type == 'physical' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                    <i class="fas fa-warehouse mr-1"></i>{{ $warehouse->type_label }}
                </span>
            </div>

            <!-- Info Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-500">Mã kho</label>
                        <p class="font-medium text-gray-900">{{ $warehouse->code }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Tên kho</label>
                        <p class="font-medium text-gray-900">{{ $warehouse->name }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Người quản lý</label>
                        <p class="font-medium text-gray-900">{{ $warehouse->manager?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Số điện thoại</label>
                        <p class="font-medium text-gray-900">{{ $warehouse->phone ?? '-' }}</p>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-500">Diện tích</label>
                        <p class="font-medium text-gray-900">{{ $warehouse->area ? number_format($warehouse->area, 2) . ' m²' : '-' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Sức chứa</label>
                        <p class="font-medium text-gray-900">{{ $warehouse->capacity ? number_format($warehouse->capacity) : '-' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Loại sản phẩm</label>
                        <p class="font-medium text-gray-900">{{ $warehouse->product_type ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Ngày tạo</label>
                        <p class="font-medium text-gray-900">{{ $warehouse->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Address -->
            <div class="mt-4">
                <label class="text-sm text-gray-500">Địa chỉ</label>
                <p class="font-medium text-gray-900">{{ $warehouse->address ?? '-' }}</p>
            </div>

            <!-- Features -->
            <div class="mt-4">
                <label class="text-sm text-gray-500 block mb-2">Tính năng</label>
                <div class="flex flex-wrap gap-2">
                    @if($warehouse->has_temperature_control)
                        <span class="px-3 py-1 text-sm rounded-full bg-cyan-100 text-cyan-800">
                            <i class="fas fa-thermometer-half mr-1"></i>Kiểm soát nhiệt độ
                        </span>
                    @endif
                    @if($warehouse->has_security_system)
                        <span class="px-3 py-1 text-sm rounded-full bg-indigo-100 text-indigo-800">
                            <i class="fas fa-shield-alt mr-1"></i>Hệ thống an ninh
                        </span>
                    @endif
                    @if(!$warehouse->has_temperature_control && !$warehouse->has_security_system)
                        <span class="text-gray-500">Không có tính năng đặc biệt</span>
                    @endif
                </div>
            </div>

            <!-- Note -->
            @if($warehouse->note)
            <div class="mt-4">
                <label class="text-sm text-gray-500">Ghi chú</label>
                <p class="font-medium text-gray-900 whitespace-pre-line">{{ $warehouse->note }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Inventory Statistics -->
    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                    <i class="fas fa-boxes text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Tổng sản phẩm</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['total_products']) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                    <i class="fas fa-cubes text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Tổng tồn kho</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['total_stock']) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                    <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Sắp hết hàng</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['low_stock_count']) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-red-100 rounded-lg p-3">
                    <i class="fas fa-times-circle text-red-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Hết hàng</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['out_of_stock_count']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory List -->
    <div class="mt-6 bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Danh sách tồn kho</h3>
            
            <!-- Search and Filter -->
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <form action="{{ route('warehouses.show', $warehouse->id) }}" method="GET" class="flex">
                        <input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="Tìm kiếm sản phẩm..." 
                               class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="hidden" name="stock_status" value="{{ request('stock_status') }}">
                    </form>
                </div>
                
                <select name="stock_status" onchange="window.location.href='{{ route('warehouses.show', $warehouse->id) }}?stock_status='+this.value+'&search={{ request('search') }}'" 
                        class="w-full sm:w-auto border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả trạng thái</option>
                    <option value="available" {{ request('stock_status') == 'available' ? 'selected' : '' }}>Còn hàng</option>
                    <option value="low" {{ request('stock_status') == 'low' ? 'selected' : '' }}>Sắp hết</option>
                    <option value="out" {{ request('stock_status') == 'out' ? 'selected' : '' }}>Hết hàng</option>
                </select>
            </div>
        </div>

        <!-- Table - Desktop View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã SP</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên sản phẩm</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Đơn vị</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Tồn kho</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($inventories as $inventory)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500">
                            {{ ($inventories->currentPage() - 1) * $inventories->perPage() + $loop->iteration }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="font-medium text-gray-900">{{ $inventory->product->code }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900">{{ $inventory->product->name }}</div>
                            @if($inventory->product->category)
                                <div class="text-sm text-gray-500">{{ $inventory->product->category }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $inventory->product->unit }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <span class="font-medium {{ $inventory->stock <= 0 ? 'text-red-600' : ($inventory->stock <= $inventory->min_stock ? 'text-yellow-600' : 'text-gray-900') }}">
                                {{ number_format($inventory->stock) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            @if($inventory->stock <= 0)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    <i class="fas fa-times-circle mr-1"></i>Hết hàng
                                </span>
                            @elseif($inventory->stock <= $inventory->min_stock)
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
                            <p>Chưa có sản phẩm nào trong kho này</p>
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
                    @elseif($inventory->stock <= $inventory->min_stock)
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
                    <div><i class="fas fa-boxes w-4"></i> Tồn: {{ number_format($inventory->stock) }} / Min: {{ number_format($inventory->min_stock) }}</div>
                    <div><i class="fas fa-dollar-sign w-4"></i> {{ number_format($inventory->avg_cost) }} đ</div>
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
                <p>Chưa có sản phẩm nào trong kho này</p>
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
