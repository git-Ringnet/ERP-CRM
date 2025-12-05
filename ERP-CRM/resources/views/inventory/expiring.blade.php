@extends('layouts.app')

@section('title', 'Sắp hết hạn')
@section('page-title', 'Cảnh báo Sắp hết hạn')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">
            <i class="fas fa-clock text-orange-500 mr-2"></i>
            Sản phẩm sắp hết hạn
        </h2>
        <a href="{{ route('inventory.index') }}" 
           class="px-3 py-1.5 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
            <i class="fas fa-arrow-left mr-1"></i>Quay lại
        </a>
    </div>

    <div class="p-4">
        @if($inventories->count() > 0)
            <div class="mb-4 p-3 bg-orange-50 border border-orange-200 rounded-lg">
                <p class="text-sm text-orange-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    Có <strong>{{ $inventories->count() }}</strong> sản phẩm sắp hết hạn trong vòng 30 ngày tới.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hạn sử dụng</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Còn lại</th>
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
                            <td class="px-4 py-3 text-sm text-gray-900">{{ number_format($inventory->stock) }}</td>
                            <td class="px-4 py-3">
                                <span class="text-sm font-medium text-orange-600">
                                    {{ $inventory->expiry_date->format('d/m/Y') }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $days = $inventory->days_until_expiry;
                                    $color = $days <= 7 ? 'text-red-600' : ($days <= 15 ? 'text-orange-600' : 'text-yellow-600');
                                @endphp
                                <span class="font-medium {{ $color }}">
                                    {{ $days }} ngày
                                </span>
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
                <p class="text-gray-500">Không có sản phẩm nào sắp hết hạn.</p>
            </div>
        @endif
    </div>
</div>
@endsection
