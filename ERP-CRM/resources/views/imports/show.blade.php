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
                <button onclick="confirmApprove('{{ route('imports.approve', $import) }}', 'phiếu nhập kho')"
                        class="px-3 py-1.5 text-sm text-white bg-green-500 rounded-lg hover:bg-green-600 transition-colors">
                    <i class="fas fa-check mr-1"></i>Duyệt phiếu
                </button>
                <button onclick="confirmReject('{{ route('imports.reject', $import) }}', 'phiếu nhập kho')"
                        class="px-3 py-1.5 text-sm text-white bg-red-500 rounded-lg hover:bg-red-600 transition-colors">
                    <i class="fas fa-times mr-1"></i>Từ chối
                </button>
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
            @elseif($import->status === 'rejected')
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">Đã từ chối</span>
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
                @if($import->supplier)
                <div>
                    <label class="text-sm text-gray-500">Nhà cung cấp</label>
                    <p class="font-medium text-gray-900">
                        <a href="{{ route('suppliers.show', $import->supplier) }}" class="text-blue-600 hover:underline">
                            {{ $import->supplier->name }}
                        </a>
                    </p>
                </div>
                @endif
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

        <!-- Service Costs Section -->
        @if($import->total_service_cost > 0 || $import->shippingAllocation)
        <div class="mb-6 p-4 bg-orange-50 border border-orange-200 rounded-lg">
            <h3 class="text-sm font-semibold text-gray-800 mb-3">
                <i class="fas fa-calculator text-orange-500 mr-2"></i>Chi phí phục vụ nhập hàng
            </h3>
            
            @if($import->shippingAllocation)
            <!-- Shipping Allocation Info -->
            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-semibold text-blue-800">
                        <i class="fas fa-truck mr-1"></i>Sử dụng phân bổ chi phí vận chuyển
                    </span>
                    <a href="{{ route('shipping-allocations.show', $import->shippingAllocation) }}" 
                       class="text-xs text-blue-600 hover:underline" target="_blank">
                        Xem chi tiết <i class="fas fa-external-link-alt ml-1"></i>
                    </a>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div>
                        <span class="text-gray-600">Mã phiếu:</span>
                        <p class="font-medium">{{ $import->shippingAllocation->code }}</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Phương pháp:</span>
                        <p class="font-medium">{{ $import->shippingAllocation->method_label }}</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Tổng chi phí:</span>
                        <p class="font-medium text-orange-600">{{ number_format($import->shippingAllocation->total_shipping_cost, 0, ',', '.') }} ₫</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Đơn mua hàng:</span>
                        <p class="font-medium">{{ $import->shippingAllocation->purchaseOrder->code ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
            @endif
            
            @if($import->total_service_cost > 0)
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-3">
                @if($import->shipping_cost > 0)
                <div>
                    <label class="text-xs text-gray-600">Chi phí vận chuyển</label>
                    <p class="font-medium text-gray-900">{{ number_format($import->shipping_cost, 0, ',', '.') }} ₫</p>
                </div>
                @endif
                @if($import->loading_cost > 0)
                <div>
                    <label class="text-xs text-gray-600">Chi phí bốc xếp</label>
                    <p class="font-medium text-gray-900">{{ number_format($import->loading_cost, 0, ',', '.') }} ₫</p>
                </div>
                @endif
                @if($import->inspection_cost > 0)
                <div>
                    <label class="text-xs text-gray-600">Chi phí kiểm định</label>
                    <p class="font-medium text-gray-900">{{ number_format($import->inspection_cost, 0, ',', '.') }} ₫</p>
                </div>
                @endif
                @if($import->other_cost > 0)
                <div>
                    <label class="text-xs text-gray-600">Chi phí khác</label>
                    <p class="font-medium text-gray-900">{{ number_format($import->other_cost, 0, ',', '.') }} ₫</p>
                </div>
                @endif
            </div>
            <div class="pt-3 border-t border-orange-300">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700">Tổng chi phí phục vụ:</span>
                    <span class="text-lg font-bold text-orange-600">{{ number_format($import->total_service_cost, 0, ',', '.') }} ₫</span>
                </div>
                <div class="flex justify-between items-center mt-2">
                    <span class="text-sm text-gray-600">Chi phí phục vụ / đơn vị:</span>
                    <span class="text-sm font-semibold text-gray-700">{{ number_format($import->getServiceCostPerUnit(), 0, ',', '.') }} ₫</span>
                </div>
            </div>
            @endif
        </div>
        @endif

        @if($import->note)
        <div class="mb-6">
            <label class="text-sm text-gray-500">Ghi chú</label>
            <p class="font-medium text-gray-900">{{ $import->note }}</p>
        </div>
        @endif

        <!-- Items Table - Grouped by Product -->
        <div class="border-t border-gray-200 pt-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Chi tiết sản phẩm</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã sản phẩm</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên sản phẩm</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho nhập</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Danh sách Serial</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($import->items as $item)
                            @php
                                $product = $item->product;
                                
                                // Get serials: from ProductItem if approved, from serial_number JSON if pending
                                if ($import->status === 'completed' && $productItems->count() > 0) {
                                    // Approved: get from ProductItem for this specific item
                                    $serials = $productItems->where('product_id', $item->product_id)
                                        ->where('warehouse_id', $item->warehouse_id ?? $import->warehouse_id)
                                        ->pluck('sku')->toArray();
                                } else {
                                    // Pending: get from serial_number JSON
                                    $serials = [];
                                    if (!empty($item->serial_number)) {
                                        $decoded = json_decode($item->serial_number, true);
                                        if (is_array($decoded)) {
                                            $serials = $decoded;
                                        } elseif (is_string($item->serial_number) && !empty(trim($item->serial_number))) {
                                            $serials = [$item->serial_number];
                                        }
                                    }
                                }
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <span class="font-mono text-sm font-medium text-blue-600">{{ $product->code }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-900">{{ $item->warehouse?->name ?? ($import->warehouse?->name ?? '-') }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-3 py-1 text-sm font-bold bg-blue-100 text-blue-800 rounded-full">
                                        {{ number_format($item->quantity) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $serialCollection = collect($serials);
                                        $realSerials = $serialCollection->filter(fn($s) => !str_starts_with($s, 'NOSKU'));
                                        $noSkuCount = $serialCollection->filter(fn($s) => str_starts_with($s, 'NOSKU'))->count();
                                        
                                        // For pending: calculate how many items will be NOSKU
                                        if ($import->status === 'pending') {
                                            $noSkuCount = $item->quantity - $realSerials->count();
                                        }
                                    @endphp
                                    @if($realSerials->count() > 0)
                                        <div class="flex flex-wrap gap-1 max-w-md">
                                            @foreach($realSerials as $serial)
                                                <span class="px-2 py-0.5 text-xs font-mono bg-blue-100 text-blue-700 rounded">
                                                    {{ $serial }}
                                                </span>
                                            @endforeach
                                        </div>
                                        @if($realSerials->count() > 5)
                                            <button type="button" class="mt-1 text-xs text-blue-600 hover:text-blue-800" 
                                                    onclick="this.previousElementSibling.classList.toggle('max-w-md'); this.textContent = this.textContent === 'Xem thêm...' ? 'Thu gọn' : 'Xem thêm...'">
                                                Xem thêm...
                                            </button>
                                        @endif
                                    @endif
                                    @if($noSkuCount > 0)
                                        <span class="text-xs text-gray-500 {{ $realSerials->count() > 0 ? 'mt-1 block' : '' }}">
                                            + {{ $noSkuCount }} sản phẩm không serial
                                        </span>
                                    @endif
                                    @if($realSerials->count() === 0 && $noSkuCount === 0)
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
