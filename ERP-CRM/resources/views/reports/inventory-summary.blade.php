@extends('layouts.app')

@section('title', 'Báo cáo tồn kho')
@section('page-title', 'Báo Cáo Tổng Hợp Tồn Kho')

@section('content')
<div class="space-y-4">
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form method="GET" action="{{ route('reports.inventory-summary') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kho</label>
                <select name="warehouse_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm</label>
                <select name="product_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái tồn kho</label>
                <select name="stock_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả</option>
                    <option value="low" {{ request('stock_status') == 'low' ? 'selected' : '' }}>Tồn kho thấp</option>
                    <option value="normal" {{ request('stock_status') == 'normal' ? 'selected' : '' }}>Bình thường</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-sm">
                    <i class="fas fa-search mr-1"></i> Lọc
                </button>
                <a href="{{ route('reports.inventory-summary') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
                    <i class="fas fa-redo mr-1"></i> Đặt lại
                </a>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-blue-500 text-white rounded-lg p-4">
            <div class="text-sm opacity-80">Tổng Sản Phẩm</div>
            <div class="text-2xl font-bold">{{ number_format($totalProducts) }}</div>
        </div>
        <div class="bg-green-500 text-white rounded-lg p-4">
            <div class="text-sm opacity-80">Tổng Số Lượng</div>
            <div class="text-2xl font-bold">{{ number_format($totalStock, 2) }}</div>
        </div>
        <div class="bg-cyan-500 text-white rounded-lg p-4">
            <div class="text-sm opacity-80">Tổng Giá Trị</div>
            <div class="text-2xl font-bold">{{ number_format($totalValue, 0) }}đ</div>
        </div>
        <div class="bg-yellow-500 text-white rounded-lg p-4">
            <div class="text-sm opacity-80">Tồn Kho Thấp</div>
            <div class="text-2xl font-bold">{{ number_format($lowStockCount) }}</div>
        </div>
    </div>

    <!-- By Warehouse -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Tồn Kho Theo Kho</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số Sản Phẩm</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tổng Số Lượng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tổng Giá Trị</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($byWarehouse as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $item['warehouse']->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($item['product_count']) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($item['total_stock'], 2) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($item['total_value'], 0) }}đ</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">Không có dữ liệu</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Detailed Inventory -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Chi Tiết Tồn Kho</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tồn kho</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tồn tối thiểu</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Giá vốn TB</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Giá trị</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($inventories as $inventory)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $inventory->product->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $inventory->warehouse->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($inventory->stock, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($inventory->min_stock, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($inventory->avg_cost, 0) }}đ</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($inventory->stock * $inventory->avg_cost, 0) }}đ</td>
                            <td class="px-4 py-3">
                                @if($inventory->stock <= $inventory->min_stock)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Tồn thấp</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Bình thường</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Không có dữ liệu</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
