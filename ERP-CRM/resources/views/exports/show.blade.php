@extends('layouts.app')

@section('title', 'Chi tiết phiếu xuất')
@section('page-title', 'Chi tiết Phiếu Xuất Kho')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">{{ $export->code }}</h2>
        <div class="flex gap-2">
            @if($export->status === 'pending')
                <a href="{{ route('exports.edit', $export) }}" 
                   class="px-3 py-1.5 text-sm text-white bg-blue-500 rounded-lg hover:bg-blue-600">
                    <i class="fas fa-edit mr-1"></i>Chỉnh sửa
                </a>
                <form action="{{ route('exports.approve', $export) }}" method="POST" class="inline"
                      onsubmit="return confirm('Bạn có chắc muốn duyệt phiếu này? Sau khi duyệt sẽ trừ tồn kho.')">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 text-sm text-white bg-green-500 rounded-lg hover:bg-green-600">
                        <i class="fas fa-check mr-1"></i>Duyệt phiếu
                    </button>
                </form>
            @endif
            <a href="{{ route('exports.index') }}" 
               class="px-3 py-1.5 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-1"></i>Quay lại
            </a>
        </div>
    </div>
    
    <div class="p-4">
        <!-- Status Badges -->
        <div class="mb-4 flex flex-wrap gap-2">
            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-orange-100 text-orange-800">
                <i class="fas fa-arrow-up mr-1"></i>Xuất kho
            </span>
            @if($export->status === 'pending')
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
                    <p class="font-medium text-gray-900">{{ $export->code }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Kho xuất</label>
                    <p class="font-medium text-gray-900">{{ $export->warehouse->name }}</p>
                </div>
                @if($export->project)
                <div>
                    <label class="text-sm text-gray-500">Dự án</label>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('projects.show', $export->project) }}" 
                           class="font-medium text-blue-600 hover:text-blue-800 hover:underline">
                            {{ $export->project->code }} - {{ $export->project->name }}
                        </a>
                    </div>
                    @if($export->project->customer_name)
                        <p class="text-xs text-gray-500 mt-1">Khách hàng: {{ $export->project->customer_name }}</p>
                    @endif
                </div>
                @endif
            </div>
            
            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-500">Ngày xuất</label>
                    <p class="font-medium text-gray-900">{{ $export->date->format('d/m/Y') }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Nhân viên</label>
                    <p class="font-medium text-gray-900">{{ $export->employee?->name ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Tổng số lượng</label>
                    <p class="text-xl font-bold text-orange-600">{{ number_format($export->total_qty) }}</p>
                </div>
            </div>
        </div>

        @if($export->note)
        <div class="mb-6">
            <label class="text-sm text-gray-500">Ghi chú</label>
            <p class="font-medium text-gray-900">{{ $export->note }}</p>
        </div>
        @endif

        <!-- Items Table -->
        <div class="border-t border-gray-200 pt-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Chi tiết sản phẩm</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã sản phẩm</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên sản phẩm</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Serial đã xuất</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($export->items as $item)
                        @php
                            // Get serials: from serial_number JSON (pending) or ProductItem (completed)
                            $serialsWithSku = collect();
                            $noSkuCount = 0;
                            
                            if ($export->status === 'completed') {
                                // Completed: get from ProductItem
                                $exportedSerials = $exportedItems[$item->product_id] ?? collect();
                                $serialsWithSku = $exportedSerials->filter(fn($pi) => !str_starts_with($pi->sku, 'NOSKU'));
                                $noSkuCount = $exportedSerials->filter(fn($pi) => str_starts_with($pi->sku, 'NOSKU'))->count();
                            } else {
                                // Pending: get from serial_number JSON (stores product_item_ids)
                                if (!empty($item->serial_number)) {
                                    $productItemIds = json_decode($item->serial_number, true);
                                    if (is_array($productItemIds) && !empty($productItemIds)) {
                                        $serialsWithSku = \App\Models\ProductItem::whereIn('id', $productItemIds)->get();
                                    }
                                }
                                // Calculate noSkuCount for pending
                                $noSkuCount = $item->quantity - $serialsWithSku->count();
                            }
                        @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <span class="font-mono text-sm font-medium text-blue-600">{{ $item->product->code }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ $item->product->name }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-3 py-1 text-sm font-bold bg-orange-100 text-orange-800 rounded-full">
                                    {{ number_format($item->quantity) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($serialsWithSku->count() > 0)
                                    <div class="flex flex-wrap gap-1 max-w-md">
                                        @foreach($serialsWithSku as $serial)
                                            <span class="px-2 py-0.5 text-xs font-mono bg-blue-100 text-blue-700 rounded">
                                                {{ $serial->sku }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                @if($noSkuCount > 0)
                                    <span class="text-xs text-gray-500 {{ $serialsWithSku->count() > 0 ? 'mt-1 block' : '' }}">
                                        + {{ $noSkuCount }} sản phẩm không serial
                                    </span>
                                @endif
                                @if($serialsWithSku->count() === 0 && $noSkuCount === 0)
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $item->comments ?: '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Timestamps -->
        <div class="mt-6 pt-4 border-t border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-500">
                <div>
                    <label class="text-xs text-gray-400">Ngày tạo</label>
                    <p>{{ $export->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <label class="text-xs text-gray-400">Cập nhật lần cuối</label>
                    <p>{{ $export->updated_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
