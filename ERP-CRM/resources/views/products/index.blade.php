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
                    @if(request('category'))
                        <input type="hidden" name="category" value="{{ request('category') }}">
                    @endif
                </form>
            </div>
            
            <!-- Filter by Category (A-Z) -->
            <div class="flex items-center gap-2">
                <select name="category" onchange="window.location.href='{{ route('products.index') }}?category='+this.value+'&search={{ request('search') }}'" 
                        class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả danh mục</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
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

    <!-- Table - Simplified: Only basic fields -->
    <!-- Requirements: 6.1, 6.2 -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã SP</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên sản phẩm</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Danh mục</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Đơn vị</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mô tả</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($products as $product)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500">
                        {{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="font-medium text-gray-900">{{ $product->code }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        @if($product->category)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $product->category }}
                            </span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $product->unit }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500">
                        {{ Str::limit($product->description, 50) }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('products.show', $product->id) }}" 
                               class="p-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-colors" 
                               title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('products.edit', $product->id) }}" 
                               class="p-2 text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 hover:text-yellow-700 transition-colors" 
                               title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="inline"
                                  onsubmit="return confirmDelete(this, 'Bạn có chắc chắn muốn xóa sản phẩm {{ $product->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="p-2 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 hover:text-red-700 transition-colors" 
                                        title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
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
