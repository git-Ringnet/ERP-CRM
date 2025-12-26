@extends('layouts.app')

@section('title', 'Quản lý giá nhập, giá kho')
@section('page-title', 'Quản lý giá nhập, giá kho')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 flex-1">
            <p class="text-sm text-blue-700">
                <i class="fas fa-info-circle mr-2"></i>
                Ghi nhận giá nhập từ NCC, tính toán giá kho bao gồm chi phí vận chuyển, chi phí phục vụ nhập hàng.
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('purchase-pricings.create') }}" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                <i class="fas fa-plus mr-2"></i>Thêm giá nhập
            </a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Sản phẩm</p>
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($stats['total_products']) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-box text-blue-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Giá nhập TB</p>
                    <p class="text-xl font-bold text-green-600">{{ number_format($stats['avg_purchase_price'], 0, ',', '.') }}đ</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-money-bill text-green-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Giá kho TB</p>
                    <p class="text-xl font-bold text-orange-600">{{ number_format($stats['avg_warehouse_price'], 0, ',', '.') }}đ</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-warehouse text-orange-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Tổng CP phục vụ</p>
                    <p class="text-xl font-bold text-red-600">{{ number_format($stats['total_service_cost'], 0, ',', '.') }}đ</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-truck text-red-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" 
                       placeholder="Tìm kiếm sản phẩm..." value="{{ request('search') }}">
            </div>
            <div class="w-48">
                <select name="supplier_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Tất cả NCC</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <select name="pricing_method" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Tất cả PP</option>
                    <option value="fifo" {{ request('pricing_method') == 'fifo' ? 'selected' : '' }}>FIFO</option>
                    <option value="lifo" {{ request('pricing_method') == 'lifo' ? 'selected' : '' }}>LIFO</option>
                    <option value="average" {{ request('pricing_method') == 'average' ? 'selected' : '' }}>Bình quân</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                <i class="fas fa-search mr-1"></i> Lọc
            </button>
        </form>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã SP</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên sản phẩm</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">NCC</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Giá nhập</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">CP phục vụ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Giá kho</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">PP tính giá</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($pricings as $pricing)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $pricing->product->code ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $pricing->product->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $pricing->supplier->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-right">{{ number_format($pricing->purchase_price, 0, ',', '.') }}đ</td>
                            <td class="px-4 py-3 text-sm text-right text-orange-600">{{ number_format($pricing->service_cost_per_unit, 0, ',', '.') }}đ</td>
                            <td class="px-4 py-3 text-sm text-right font-bold text-green-600">{{ number_format($pricing->warehouse_price, 0, ',', '.') }}đ</td>
                            <td class="px-4 py-3 text-center">
                                @if($pricing->pricing_method == 'fifo')
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">FIFO</span>
                                @elseif($pricing->pricing_method == 'lifo')
                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">LIFO</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Bình quân</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center space-x-1">
                                    <a href="{{ route('purchase-pricings.show', $pricing) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded" title="Xem">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('purchase-pricings.edit', $pricing) }}" class="p-1.5 text-yellow-600 hover:bg-yellow-50 rounded" title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('purchase-pricings.destroy', $pricing) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-red-600 hover:bg-red-50 rounded" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">Không có dữ liệu</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t">
            {{ $pricings->links() }}
        </div>
    </div>
</div>
@endsection
