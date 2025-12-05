@extends('layouts.app')

@section('title', 'Sắp hết hàng')
@section('page-title', 'Cảnh báo Sắp hết hàng')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">
            <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
            Sản phẩm sắp hết hàng
        </h2>
        <a href="{{ route('inventory.index') }}" 
           class="px-3 py-1.5 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
            <i class="fas fa-arrow-left mr-1"></i>Quay lại
        </a>
    </div>

    <div class="p-4">
        @if($inventories->count() > 0)
            <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-sm text-yellow-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    Có <strong>{{ $inventories->count() }}</strong> sản phẩm có số lượng tồn kho thấp hơn mức tối thiểu.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tồn hiện tại</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tồn tối thiểu</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cần nhập</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($inventories as $inventory)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ $inventory->product->name }}</div>
                                <div class="text-sm text-gray-500">{{ $inventory->product->code }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $inventory->warehouse->name }}</td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-yellow-600">{{ number_format($inventory->stock) }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ number_format($inventory->min_stock) }}</td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-red-600">{{ number_format($inventory->min_stock - $inventory->stock) }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('inventory.show', $inventory->id) }}" 
                                   class="text-blue-600 hover:text-blue-900" title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
                <p class="text-lg font-medium text-gray-900">Tuyệt vời!</p>
                <p class="text-gray-500">Không có sản phẩm nào sắp hết hàng.</p>
            </div>
        @endif
    </div>
</div>
@endsection
