@extends('layouts.app')

@section('title', 'Kho hàng')
@section('page-title', 'Quản lý Kho hàng')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="p-3 sm:p-4 border-b border-gray-200 space-y-3">
        <div class="flex flex-col sm:flex-row gap-3">
            <!-- Search -->
            <div class="relative flex-1">
                <form action="{{ route('warehouses.index') }}" method="GET" class="flex">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Tìm kiếm theo mã, tên kho..." 
                           class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="hidden" name="type" value="{{ request('type') }}">
                </form>
            </div>
            
            <!-- Filter by Status -->
            <select name="status" onchange="window.location.href='{{ route('warehouses.index') }}?status='+this.value+'&type={{ request('type') }}&search={{ request('search') }}'" 
                    class="w-full sm:w-auto border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">Tất cả trạng thái</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang hoạt động</option>
                <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Đang bảo trì</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Ngừng hoạt động</option>
            </select>

            <!-- Filter by Type -->
            <select name="type" onchange="window.location.href='{{ route('warehouses.index') }}?type='+this.value+'&status={{ request('status') }}&search={{ request('search') }}'" 
                    class="w-full sm:w-auto border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">Tất cả loại</option>
                <option value="physical" {{ request('type') == 'physical' ? 'selected' : '' }}>Kho vật lý</option>
                <option value="virtual" {{ request('type') == 'virtual' ? 'selected' : '' }}>Kho ảo</option>
            </select>
        </div>
        
        <div class="flex justify-end">
            <a href="{{ route('warehouses.create') }}" 
               class="inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm">
                <i class="fas fa-plus mr-2"></i>
                <span class="hidden sm:inline">Thêm kho mới</span>
                <span class="sm:hidden">Thêm</span>
            </a>
        </div>
    </div>

    <!-- Table - Desktop View -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã kho</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên kho</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Địa chỉ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quản lý</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($warehouses as $warehouse)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="font-medium text-gray-900">{{ $warehouse->code }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm font-medium text-gray-900">{{ $warehouse->name }}</div>
                        @if($warehouse->product_type)
                            <div class="text-sm text-gray-500">{{ $warehouse->product_type }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $warehouse->type == 'physical' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                            {{ $warehouse->type_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">{{ $warehouse->address ?? '-' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                        {{ $warehouse->manager?->name ?? '-' }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($warehouse->status == 'active')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>{{ $warehouse->status_label }}
                            </span>
                        @elseif($warehouse->status == 'maintenance')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                <i class="fas fa-tools mr-1"></i>{{ $warehouse->status_label }}
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-1"></i>{{ $warehouse->status_label }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('warehouses.show', $warehouse->id) }}" 
                               class="p-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-colors" 
                               title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('warehouses.edit', $warehouse->id) }}" 
                               class="p-2 text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 hover:text-yellow-700 transition-colors" 
                               title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('warehouses.destroy', $warehouse->id) }}" method="POST" class="inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="p-2 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 hover:text-red-700 transition-colors delete-btn" 
                                        data-name="{{ $warehouse->name }}" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-warehouse text-4xl mb-2"></i>
                        <p>Không có dữ liệu kho hàng</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Card View - Mobile -->
    <div class="md:hidden divide-y divide-gray-200">
        @forelse($warehouses as $warehouse)
        <div class="p-4 hover:bg-gray-50">
            <div class="flex justify-between items-start mb-2">
                <div class="flex-1">
                    <div class="font-medium text-gray-900">{{ $warehouse->name }}</div>
                    <div class="text-sm text-gray-500">{{ $warehouse->code }}</div>
                </div>
                @if($warehouse->status == 'active')
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                        {{ $warehouse->status_label }}
                    </span>
                @elseif($warehouse->status == 'maintenance')
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                        {{ $warehouse->status_label }}
                    </span>
                @else
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                        {{ $warehouse->status_label }}
                    </span>
                @endif
            </div>
            <div class="space-y-1 text-sm text-gray-600 mb-3">
                <div><i class="fas fa-tag w-4"></i> {{ $warehouse->type_label }}</div>
                @if($warehouse->address)
                    <div><i class="fas fa-map-marker-alt w-4"></i> {{ $warehouse->address }}</div>
                @endif
                @if($warehouse->manager)
                    <div><i class="fas fa-user w-4"></i> {{ $warehouse->manager->name }}</div>
                @endif
                @if($warehouse->phone)
                    <div><i class="fas fa-phone w-4"></i> {{ $warehouse->phone }}</div>
                @endif
            </div>
            <div class="flex gap-2">
                <a href="{{ route('warehouses.show', $warehouse->id) }}" 
                   class="flex-1 text-center px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-sm">
                    <i class="fas fa-eye mr-1"></i>Xem
                </a>
                <a href="{{ route('warehouses.edit', $warehouse->id) }}" 
                   class="flex-1 text-center px-3 py-2 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 text-sm">
                    <i class="fas fa-edit mr-1"></i>Sửa
                </a>
                <form action="{{ route('warehouses.destroy', $warehouse->id) }}" method="POST" class="flex-1 delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="w-full px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm delete-btn"
                            data-name="{{ $warehouse->name }}">
                        <i class="fas fa-trash mr-1"></i>Xóa
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-warehouse text-4xl mb-2"></i>
            <p>Không có dữ liệu kho hàng</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($warehouses->hasPages())
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $warehouses->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
