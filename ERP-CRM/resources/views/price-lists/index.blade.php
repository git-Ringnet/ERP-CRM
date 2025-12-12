@extends('layouts.app')

@section('title', 'Bảng giá')
@section('page-title', 'Quản lý Bảng giá')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex flex-col sm:flex-row gap-4 flex-1">
            <!-- Search -->
            <div class="relative flex-1 max-w-md">
                <form action="{{ route('price-lists.index') }}" method="GET" class="flex">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Tìm kiếm bảng giá..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="hidden" name="type" value="{{ request('type') }}">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                </form>
            </div>
            
            <!-- Filter by Type -->
            <div class="flex items-center gap-2">
                <select name="type" onchange="window.location.href='{{ route('price-lists.index') }}?type='+this.value+'&status={{ request('status') }}&search={{ request('search') }}'" 
                        class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả loại</option>
                    <option value="standard" {{ request('type') == 'standard' ? 'selected' : '' }}>Bảng giá chuẩn</option>
                    <option value="customer" {{ request('type') == 'customer' ? 'selected' : '' }}>Theo khách hàng</option>
                    <option value="promotion" {{ request('type') == 'promotion' ? 'selected' : '' }}>Khuyến mãi</option>
                    <option value="wholesale" {{ request('type') == 'wholesale' ? 'selected' : '' }}>Giá sỉ</option>
                </select>
            </div>

            <!-- Filter by Status -->
            <div class="flex items-center gap-2">
                <select name="status" onchange="window.location.href='{{ route('price-lists.index') }}?status='+this.value+'&type={{ request('type') }}&search={{ request('search') }}'" 
                        class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang hoạt động</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tạm dừng</option>
                </select>
            </div>
        </div>
        
        <div class="flex gap-2">
            <a href="{{ route('price-lists.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Tạo bảng giá
            </a>
        </div>
    </div>

    <!-- Info Box -->
    <div class="mx-4 mt-4 bg-blue-50 border-l-4 border-blue-500 p-3 rounded">
        <p class="text-blue-700 text-sm"><i class="fas fa-info-circle mr-2"></i>Tạo nhiều bảng giá theo khách hàng, khuyến mãi, giá sỉ. Hệ thống tự động áp dụng bảng giá phù hợp.</p>
    </div>

    <!-- Table - Desktop View -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên bảng giá</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Khách hàng</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Số SP</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($priceLists as $priceList)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500">
                        {{ ($priceLists->currentPage() - 1) * $priceLists->perPage() + $loop->iteration }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <a href="{{ route('price-lists.show', $priceList) }}" class="font-medium text-blue-600 hover:text-blue-800">
                            {{ $priceList->code }}
                        </a>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm font-medium text-gray-900">{{ $priceList->name }}</div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $priceList->type_color }}">
                            {{ $priceList->type_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                        {{ $priceList->customer ? $priceList->customer->name : 'Tất cả' }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                        @if($priceList->start_date || $priceList->end_date)
                            {{ $priceList->start_date ? $priceList->start_date->format('d/m/Y') : '...' }}
                            - {{ $priceList->end_date ? $priceList->end_date->format('d/m/Y') : '...' }}
                        @else
                            <span class="text-gray-400">Không giới hạn</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500">
                        {{ $priceList->items()->count() }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        @if($priceList->is_active && $priceList->isValid())
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Hoạt động</span>
                        @elseif(!$priceList->is_active)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Tạm dừng</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Hết hạn</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('price-lists.show', $priceList) }}" 
                               class="p-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-colors" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('price-lists.edit', $priceList) }}" 
                               class="p-2 text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 hover:text-yellow-700 transition-colors" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('price-lists.toggle', $priceList) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="p-2 {{ $priceList->is_active ? 'text-gray-600 bg-gray-50 hover:bg-gray-100' : 'text-green-600 bg-green-50 hover:bg-green-100' }} rounded-lg transition-colors" 
                                        title="{{ $priceList->is_active ? 'Tắt' : 'Bật' }}">
                                    <i class="fas {{ $priceList->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-tags text-4xl mb-2"></i>
                        <p>Chưa có bảng giá nào</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Card View - Mobile -->
    <div class="md:hidden divide-y divide-gray-200">
        @forelse($priceLists as $priceList)
        <div class="p-4 hover:bg-gray-50">
            <div class="flex justify-between items-start mb-2">
                <div class="flex-1">
                    <a href="{{ route('price-lists.show', $priceList) }}" class="font-medium text-blue-600 hover:text-blue-800">{{ $priceList->code }}</a>
                    <div class="text-sm text-gray-900">{{ $priceList->name }}</div>
                </div>
                @if($priceList->is_active && $priceList->isValid())
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Hoạt động</span>
                @elseif(!$priceList->is_active)
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Tạm dừng</span>
                @else
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Hết hạn</span>
                @endif
            </div>
            <div class="space-y-1 text-sm text-gray-600 mb-3">
                <div><i class="fas fa-tag w-4"></i> {{ $priceList->type_label }}</div>
                <div><i class="fas fa-user w-4"></i> {{ $priceList->customer ? $priceList->customer->name : 'Tất cả khách hàng' }}</div>
                <div><i class="fas fa-box w-4"></i> {{ $priceList->items()->count() }} sản phẩm</div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('price-lists.show', $priceList) }}" 
                   class="flex-1 text-center px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-sm">
                    <i class="fas fa-eye mr-1"></i>Xem
                </a>
                <a href="{{ route('price-lists.edit', $priceList) }}" 
                   class="flex-1 text-center px-3 py-2 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 text-sm">
                    <i class="fas fa-edit mr-1"></i>Sửa
                </a>
                <form action="{{ route('price-lists.toggle', $priceList) }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" 
                            class="w-full px-3 py-2 {{ $priceList->is_active ? 'bg-gray-100 text-gray-700' : 'bg-green-100 text-green-700' }} rounded-lg hover:opacity-80 text-sm">
                        <i class="fas {{ $priceList->is_active ? 'fa-toggle-off' : 'fa-toggle-on' }} mr-1"></i>{{ $priceList->is_active ? 'Tắt' : 'Bật' }}
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-tags text-4xl mb-2"></i>
            <p>Chưa có bảng giá nào</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($priceLists->hasPages())
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $priceLists->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
