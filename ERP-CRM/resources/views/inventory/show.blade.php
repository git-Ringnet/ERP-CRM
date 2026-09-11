@extends('layouts.app')

@section('title', 'Chi tiết tồn kho')
@section('page-title', 'Chi tiết tồn kho')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">{{ $inventory->product->name }}</h2>
            <a href="{{ $backUrl }}"
               class="px-3 py-1.5 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-1"></i>Quay lại
            </a>
        </div>
        
        <div class="p-4">
            <!-- Status Badges -->
            <div class="mb-4 flex flex-wrap gap-2">
                @if($inventory->stock <= 0)
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                        <i class="fas fa-times-circle mr-1"></i>Hết hàng
                    </span>
                @elseif($inventory->is_low_stock)
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Sắp hết hàng
                    </span>
                @else
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>Còn hàng
                    </span>
                @endif

                @if($inventory->is_expiring_soon)
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-orange-100 text-orange-800">
                        <i class="fas fa-clock mr-1"></i>Sắp hết hạn ({{ $inventory->days_until_expiry }} ngày)
                    </span>
                @endif
            </div>

            <!-- Info Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-500">Sản phẩm</label>
                        <p class="font-medium text-gray-900">{{ $inventory->product->name }}</p>
                        <p class="text-sm text-gray-500">{{ $inventory->product->code }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Kho</label>
                        <p class="font-medium text-gray-900">{{ $inventory->warehouse->name }}</p>
                        <p class="text-sm text-gray-500">{{ $inventory->warehouse->code }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Số lượng tồn kho</label>
                        <p class="text-2xl font-bold {{ $inventory->stock <= 0 ? 'text-red-600' : ($inventory->is_low_stock ? 'text-yellow-600' : 'text-green-600') }}">
                            {{ number_format($inventory->stock) }}
                        </p>
                        
                        @if($inventory->stock > 0)
                        <div class="mt-2 text-sm text-gray-600 space-y-1 bg-gray-50 p-3 rounded-lg">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Chi tiết tồn kho:</p>
                            @foreach($inventory->stock_breakdown as $status => $count)
                                @if($count > 0 && $status != 'sold' && $status !== 'transferred')
                                <div class="flex items-center justify-between max-w-[200px]">
                                    <span>
                                        @switch($status)
                                            @case('in_stock') <i class="fas fa-check-circle text-green-500 mr-2 w-4"></i>Mới: @break
                                            @case('damaged') <i class="fas fa-times-circle text-red-500 mr-2 w-4"></i>Hỏng: @break
                                            @case('liquidation') <i class="fas fa-tag text-purple-500 mr-2 w-4"></i>Thanh lý: @break
                                            @default <i class="fas fa-box text-gray-400 mr-2 w-4"></i>{{ ucfirst($status) }}:
                                        @endswitch
                                    </span>
                                    <span class="font-bold text-gray-900">{{ $count }}</span>
                                </div>
                                @endif
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-500">Hạn sử dụng</label>
                        <p class="font-medium {{ $inventory->is_expiring_soon ? 'text-orange-600' : 'text-gray-900' }}">
                            {{ $inventory->expiry_date ? $inventory->expiry_date->format('d/m/Y') : '-' }}
                        </p>
                        @if($inventory->days_until_expiry !== null)
                            <p class="text-sm text-gray-500">
                                {{ $inventory->days_until_expiry >= 0 ? 'Còn ' . $inventory->days_until_expiry . ' ngày' : 'Đã hết hạn' }}
                            </p>
                        @endif
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Bảo hành</label>
                        <p class="font-medium text-gray-900">
                            {{ $inventory->warranty_months ? $inventory->warranty_months . ' tháng' : '-' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Timestamps -->
            <div class="mt-6 pt-4 border-t border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-500">
                    <div>
                        <label class="text-xs text-gray-400">Cập nhật lần cuối</label>
                        <p>{{ $inventory->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Ngày tạo</label>
                        <p>{{ $inventory->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Operational trace: procurement and sales allocation -->
            <div class="mt-6 pt-5 border-t border-gray-200">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">Nguồn hàng & truy vết sử dụng</h3>
                        <p class="text-xs text-gray-500 mt-1">Theo dõi PO nhập hàng, nhà cung cấp, Sales/đơn bán và dự án liên quan.</p>
                    </div>
                    <span class="text-xs font-semibold text-gray-500">{{ $traceItems->count() }} lô/serial</span>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-3 py-2 text-left">Serial / lô</th>
                                <th class="px-3 py-2 text-center">SL</th>
                                <th class="px-3 py-2 text-left">Nguồn nhập</th>
                                <th class="px-3 py-2 text-left">PO & nhà cung cấp</th>
                                <th class="px-3 py-2 text-left">Sales / đơn bán / dự án</th>
                                <th class="px-3 py-2 text-left">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($traceItems as $item)
                                @php
                                    $po = $item->import?->purchaseOrder;
                                    $sale = $po?->sale ?: $item->export?->sale;
                                    $project = $sale?->project ?: $item->export?->project;
                                @endphp
                                <tr class="hover:bg-gray-50 align-top">
                                    <td class="px-3 py-2">
                                        <div class="font-mono text-xs text-gray-800">{{ $item->sku ?: 'Không serial' }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">Cập nhật: {{ optional($item->updated_at)->format('d/m/Y H:i') }}</div>
                                    </td>
                                    <td class="px-3 py-2 text-center font-semibold">{{ number_format($item->quantity) }}</td>
                                    <td class="px-3 py-2">
                                        @if($item->import)
                                            <div class="font-medium text-gray-800">{{ $item->import->code }}</div>
                                            <div class="text-xs text-gray-500">{{ optional($item->import->date)->format('d/m/Y') ?: '-' }}</div>
                                        @else
                                            <span class="text-gray-400">Chưa có phiếu nhập</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        @if($po)
                                            <a href="{{ route('purchase-orders.show', $po) }}" class="font-medium text-primary hover:underline">{{ $po->code }}</a>
                                            <div class="text-xs text-gray-500">{{ $po->supplier?->name ?: $item->import?->supplier?->name ?: '-' }}</div>
                                        @else
                                            <span class="text-gray-400">Không liên kết PO</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        @if($sale)
                                            <a href="{{ route('sales.show', $sale) }}" class="font-medium text-primary hover:underline">{{ $sale->code }}</a>
                                            <div class="text-xs text-gray-600">Sales: {{ $sale->user?->name ?: '-' }}</div>
                                        @endif
                                        @if($project)
                                            <div class="text-xs text-gray-500 mt-0.5">Dự án: {{ $project->code }}{{ $project->name ? ' - ' . $project->name : '' }}</div>
                                        @elseif(!$sale)
                                            <span class="text-gray-400">Chưa gán đơn bán/dự án</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $item->status === 'in_stock' ? 'bg-green-100 text-green-800' : ($item->status === 'sold' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700') }}">
                                            {{ \App\Models\ProductItem::getStatuses()[$item->status] ?? $item->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-8 text-center text-gray-500">Chưa có lịch sử lô/serial cho sản phẩm này.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
