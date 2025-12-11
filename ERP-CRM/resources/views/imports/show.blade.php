@extends('layouts.app')

@section('title', 'Chi tiết phiếu nhập')
@section('page-title', 'Chi tiết Phiếu Nhập Kho')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">{{ $import->code }}</h2>
        <div class="flex gap-2">
            @if($import->status === 'pending')
                <a href="{{ route('imports.edit', $import) }}" 
                   class="px-3 py-1.5 text-sm text-white bg-blue-500 rounded-lg hover:bg-blue-600 transition-colors">
                    <i class="fas fa-edit mr-1"></i>Chỉnh sửa
                </a>
                <form action="{{ route('imports.approve', $import) }}" method="POST" class="inline"
                      onsubmit="return confirm('Bạn có chắc muốn duyệt phiếu này? Sau khi duyệt sẽ không thể chỉnh sửa.')">
                    @csrf
                    <button type="submit" 
                            class="px-3 py-1.5 text-sm text-white bg-green-500 rounded-lg hover:bg-green-600 transition-colors">
                        <i class="fas fa-check mr-1"></i>Duyệt phiếu
                    </button>
                </form>
            @endif
            <a href="{{ route('imports.index') }}" 
               class="px-3 py-1.5 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-1"></i>Quay lại
            </a>
        </div>
    </div>
    
    <div class="p-4">
        <!-- Status Badges -->
        <div class="mb-4 flex flex-wrap gap-2">
            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">
                <i class="fas fa-arrow-down mr-1"></i>Nhập kho
            </span>
            @if($import->status === 'pending')
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">Chờ xử lý</span>
            @else
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">Hoàn thành</span>
            @endif
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-500">Mã phiếu</label>
                    <p class="font-medium text-gray-900">{{ $import->code }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Kho nhập</label>
                    <p class="font-medium text-gray-900">{{ $import->warehouse->name }}</p>
                </div>
            </div>
            
            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-500">Ngày nhập</label>
                    <p class="font-medium text-gray-900">{{ $import->date->format('d/m/Y') }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Nhân viên</label>
                    <p class="font-medium text-gray-900">{{ $import->employee?->name ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Tổng số lượng</label>
                    <p class="text-xl font-bold text-blue-600">{{ number_format($import->total_qty) }}</p>
                </div>
            </div>
        </div>

        @if($import->note)
        <div class="mb-6">
            <label class="text-sm text-gray-500">Ghi chú</label>
            <p class="font-medium text-gray-900">{{ $import->note }}</p>
        </div>
        @endif

        <!-- Items Table -->
        <div class="border-t border-gray-200 pt-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Chi tiết sản phẩm</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Đơn vị</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mô tả</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($import->items as $item)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ $item->product->name }}</div>
                                <div class="text-sm text-gray-500">{{ $item->product->code }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 text-sm font-semibold bg-blue-100 text-blue-800 rounded">
                                    {{ number_format($item->quantity) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $item->unit ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $item->description ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $item->comments ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Product Items (SKUs) Section -->
        @if($productItems->count() > 0)
        <div class="border-t border-gray-200 pt-4 mt-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">
                <i class="fas fa-barcode mr-2"></i>Danh sách SKU đã tạo
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Giá nhập (USD)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gói giá</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($productItems as $pItem)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="font-medium {{ Str::startsWith($pItem->sku, 'NOSKU') ? 'text-gray-400 italic' : 'text-gray-900' }}">
                                    {{ $pItem->sku }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $pItem->product->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-right font-medium">${{ number_format($pItem->cost_usd, 2) }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if($pItem->price_tiers && is_array($pItem->price_tiers))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($pItem->price_tiers as $tier)
                                            <span class="px-2 py-1 text-xs bg-blue-50 text-blue-700 rounded">
                                                {{ $tier['name'] ?? 'N/A' }}: ${{ number_format($tier['price'] ?? 0, 2) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @switch($pItem->status)
                                    @case('in_stock')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Trong kho</span>
                                        @break
                                    @case('sold')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Đã bán</span>
                                        @break
                                    @default
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">{{ $pItem->status }}</span>
                                @endswitch
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Timestamps -->
        <div class="mt-6 pt-4 border-t border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-500">
                <div>
                    <label class="text-xs text-gray-400">Ngày tạo</label>
                    <p>{{ $import->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <label class="text-xs text-gray-400">Cập nhật lần cuối</label>
                    <p>{{ $import->updated_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
