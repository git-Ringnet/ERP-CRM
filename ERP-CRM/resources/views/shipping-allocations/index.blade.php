@extends('layouts.app')

@section('title', 'Phân bổ chi phí vận chuyển kho')
@section('page-title', 'Phân bổ chi phí vận chuyển kho')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 flex-1">
            <p class="text-sm text-blue-700">
                <i class="fas fa-info-circle mr-2"></i>
                Phân bổ chi phí vận chuyển cho từng sản phẩm theo các phương pháp: theo giá trị, số lượng, trọng lượng, hoặc thể tích.
            </p>
        </div>
        <a href="{{ route('shipping-allocations.create') }}" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
            <i class="fas fa-plus mr-2"></i>Tạo phân bổ mới
        </a>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Phiếu phân bổ</p>
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($stats['total_allocations']) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-file-invoice text-blue-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Tổng CP vận chuyển</p>
                    <p class="text-xl font-bold text-green-600">{{ number_format($stats['total_shipping_cost'], 0, ',', '.') }}đ</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-truck text-green-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">SP được phân bổ</p>
                    <p class="text-2xl font-bold text-orange-600">{{ number_format($stats['total_products']) }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-box text-orange-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Kho hàng</p>
                    <p class="text-2xl font-bold text-purple-600">{{ number_format($stats['total_warehouses']) }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-warehouse text-purple-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" 
                       placeholder="Tìm kiếm..." value="{{ request('search') }}">
            </div>
            <div class="w-40">
                <select name="warehouse_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Tất cả kho</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <select name="allocation_method" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Tất cả PP</option>
                    <option value="value" {{ request('allocation_method') == 'value' ? 'selected' : '' }}>Theo giá trị</option>
                    <option value="quantity" {{ request('allocation_method') == 'quantity' ? 'selected' : '' }}>Theo số lượng</option>
                    <option value="weight" {{ request('allocation_method') == 'weight' ? 'selected' : '' }}>Theo trọng lượng</option>
                    <option value="volume" {{ request('allocation_method') == 'volume' ? 'selected' : '' }}>Theo thể tích</option>
                </select>
            </div>
            <div class="w-36">
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Tất cả TT</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Nháp</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã phiếu</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Đơn mua hàng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho nhận</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng CP vận chuyển</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">PP phân bổ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Số SP</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Đã sử dụng</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($allocations as $allocation)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $allocation->code }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $allocation->purchaseOrder->code ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $allocation->warehouse->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-right font-medium">{{ number_format($allocation->total_shipping_cost, 0, ',', '.') }}đ</td>
                            <td class="px-4 py-3 text-center">
                                @if($allocation->allocation_method == 'value')
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Theo giá trị</span>
                                @elseif($allocation->allocation_method == 'quantity')
                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Theo SL</span>
                                @elseif($allocation->allocation_method == 'weight')
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Theo TL</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Theo TT</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-center">{{ $allocation->items->count() }} SP</td>
                            <td class="px-4 py-3 text-center">
                                @if($allocation->status == 'draft')
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Nháp</span>
                                @elseif($allocation->status == 'approved')
                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Đã duyệt</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Hoàn thành</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $importsCount = $allocation->imports()->count();
                                @endphp
                                @if($importsCount > 0)
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                        <i class="fas fa-check-circle mr-1"></i>{{ $importsCount }} phiếu
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">
                                        <i class="fas fa-minus-circle mr-1"></i>Chưa dùng
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center space-x-1">
                                    <a href="{{ route('shipping-allocations.show', $allocation) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded" title="Xem">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($allocation->status == 'draft')
                                        <a href="{{ route('shipping-allocations.edit', $allocation) }}" class="p-1.5 text-yellow-600 hover:bg-yellow-50 rounded" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('shipping-allocations.approve', $allocation) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="p-1.5 text-green-600 hover:bg-green-50 rounded" title="Duyệt" onclick="return confirm('Duyệt phiếu này?')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('shipping-allocations.destroy', $allocation) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-red-600 hover:bg-red-50 rounded" title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
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
            {{ $allocations->links() }}
        </div>
    </div>
</div>
@endsection
