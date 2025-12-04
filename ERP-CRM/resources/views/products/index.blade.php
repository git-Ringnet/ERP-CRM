@extends('layouts.app')

@section('title', 'Sản phẩm')
@section('page-title', 'Quản lý Sản phẩm')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex flex-col sm:flex-row gap-4 flex-1">
            <!-- Search -->
            <div class="relative flex-1 max-w-md">
                <form action="{{ route('products.index') }}" method="GET" class="flex">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Tìm kiếm theo mã, tên sản phẩm..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </form>
            </div>
            
            <!-- Filter by Management Type -->
            <div class="flex items-center gap-2">
                <select name="management_type" onchange="window.location.href='{{ route('products.index') }}?management_type='+this.value+'&search={{ request('search') }}'" 
                        class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả loại quản lý</option>
                    <option value="normal" {{ request('management_type') == 'normal' ? 'selected' : '' }}>Thường</option>
                    <option value="serial" {{ request('management_type') == 'serial' ? 'selected' : '' }}>Serial</option>
                    <option value="lot" {{ request('management_type') == 'lot' ? 'selected' : '' }}>Lot</option>
                </select>
            </div>
        </div>
        
        <div class="flex gap-2">
            <a href="{{ route('products.export') }}?{{ http_build_query(request()->query()) }}" 
               class="inline-flex items-center px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-colors">
                <i class="fas fa-file-excel mr-2"></i>
                Export Excel
            </a>
            <a href="{{ route('products.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Thêm sản phẩm
            </a>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã SP</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên sản phẩm</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Đơn vị</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Giá bán</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Giá vốn</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Tồn kho</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Quản lý</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($products as $product)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="font-medium text-gray-900">{{ $product->code }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                        @if($product->category)
                            <div class="text-sm text-gray-500">{{ $product->category }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $product->unit }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                        {{ number_format($product->price) }} đ
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 text-right">
                        {{ number_format($product->cost) }} đ
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <span class="text-sm font-semibold {{ $product->stock <= $product->min_stock ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $product->stock }}
                        </span>
                        @if($product->stock <= $product->min_stock)
                            <i class="fas fa-exclamation-triangle text-red-500 ml-1" title="Dưới mức tồn kho tối thiểu"></i>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        @if($product->management_type == 'serial')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                <i class="fas fa-barcode mr-1"></i>Serial
                            </span>
                        @elseif($product->management_type == 'lot')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                <i class="fas fa-layer-group mr-1"></i>Lot
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                <i class="fas fa-box mr-1"></i>Thường
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('products.show', $product->id) }}" 
                               class="text-blue-600 hover:text-blue-900" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('products.edit', $product->id) }}" 
                               class="text-yellow-600 hover:text-yellow-900" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="inline"
                                  onsubmit="return confirmDelete(this, 'Bạn có chắc chắn muốn xóa sản phẩm {{ $product->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Không có dữ liệu sản phẩm</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($products->hasPages())
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $products->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
