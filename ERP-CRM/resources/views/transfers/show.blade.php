@extends('layouts.app')

@section('title', 'Chi tiết phiếu chuyển')
@section('page-title', 'Chi tiết Phiếu Chuyển Kho')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">{{ $transfer->code }}</h2>
        <div class="flex gap-2">
            @if($transfer->status === 'pending')
                <a href="{{ route('transfers.edit', $transfer) }}" 
                   class="px-3 py-1.5 text-sm text-white bg-blue-500 rounded-lg hover:bg-blue-600">
                    <i class="fas fa-edit mr-1"></i>Chỉnh sửa
                </a>
                <button onclick="confirmApprove('{{ route('transfers.approve', $transfer) }}', 'phiếu chuyển kho')"
                        class="px-3 py-1.5 text-sm text-white bg-green-500 rounded-lg hover:bg-green-600">
                    <i class="fas fa-check mr-1"></i>Duyệt phiếu
                </button>
                <button onclick="confirmReject('{{ route('transfers.reject', $transfer) }}', 'phiếu chuyển kho')"
                        class="px-3 py-1.5 text-sm text-white bg-red-500 rounded-lg hover:bg-red-600">
                    <i class="fas fa-times mr-1"></i>Từ chối
                </button>
            @endif
            <a href="{{ route('transfers.index') }}" 
               class="px-3 py-1.5 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-1"></i>Quay lại
            </a>
        </div>
    </div>
    
    <div class="p-4">
        <div class="mb-4 flex flex-wrap gap-2">
            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-purple-100 text-purple-800">
                <i class="fas fa-exchange-alt mr-1"></i>Chuyển kho
            </span>
            @if($transfer->status === 'pending')
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">Chờ xử lý</span>
            @elseif($transfer->status === 'rejected')
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">Đã từ chối</span>
            @else
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">Hoàn thành</span>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-500">Mã phiếu</label>
                    <p class="font-medium text-gray-900">{{ $transfer->code }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Kho nguồn</label>
                    <p class="font-medium text-gray-900">{{ $transfer->warehouse->name }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Kho đích</label>
                    <p class="font-medium text-gray-900">{{ $transfer->toWarehouse->name ?? '-' }}</p>
                </div>
            </div>
            
            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-500">Ngày chuyển</label>
                    <p class="font-medium text-gray-900">{{ $transfer->date->format('d/m/Y') }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Nhân viên</label>
                    <p class="font-medium text-gray-900">{{ $transfer->employee?->name ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Tổng số lượng</label>
                    <p class="text-xl font-bold text-purple-600">{{ number_format($transfer->total_qty) }}</p>
                </div>
            </div>
        </div>

        @if($transfer->note)
        <div class="mb-6">
            <label class="text-sm text-gray-500">Ghi chú</label>
            <p class="font-medium text-gray-900">{{ $transfer->note }}</p>
        </div>
        @endif

        <div class="border-t border-gray-200 pt-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold text-gray-800">Chi tiết sản phẩm chuyển</h3>
                <span class="text-xs font-semibold text-gray-500">{{ $transfer->items->count() }} dòng sản phẩm</span>
            </div>
            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs font-bold text-gray-600 uppercase border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left">Mã sản phẩm</th>
                            <th class="px-4 py-3 text-left">Tên sản phẩm</th>
                            <th class="px-4 py-3 text-left">PO & Nhà cung cấp</th>
                            <th class="px-4 py-3 text-left">Đơn hàng SO & Dự án</th>
                            <th class="px-4 py-3 text-left">Sales phụ trách</th>
                            <th class="px-4 py-3 text-center">Số lượng</th>
                            <th class="px-4 py-3 text-left">Serial chuyển</th>
                            <th class="px-4 py-3 text-left">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($transfer->items as $item)
                        @php
                            // Get serials from serial_number JSON
                            $serialsWithSku = collect();
                            $noSkuCount = 0;
                            
                            if (!empty($item->serial_number)) {
                                $productItemIds = json_decode($item->serial_number, true);
                                if (is_array($productItemIds) && !empty($productItemIds)) {
                                    $serialsWithSku = \App\Models\ProductItem::with([
                                        'import.purchaseOrder.supplier',
                                        'import.purchaseOrder.items.saleOrderRequestItem.saleOrderRequest.sale.user',
                                        'import.purchaseOrder.items.saleOrderRequestItem.saleOrderRequest.sale.project',
                                        'import.purchaseOrder.sale.user',
                                        'import.purchaseOrder.sale.project',
                                    ])->whereIn('id', $productItemIds)->get();
                                }
                            }
                            // Calculate noSkuCount
                            $noSkuCount = $item->quantity - $serialsWithSku->count();

                            // Fallback if no serial IDs stored (e.g. legacy transfers or no-serial items)
                            $tracedItems = $serialsWithSku;
                            if ($tracedItems->isEmpty()) {
                                $tracedItems = \App\Models\ProductItem::with([
                                    'import.purchaseOrder.supplier',
                                    'import.purchaseOrder.items.saleOrderRequestItem.saleOrderRequest.sale.user',
                                    'import.purchaseOrder.items.saleOrderRequestItem.saleOrderRequest.sale.project',
                                    'import.purchaseOrder.sale.user',
                                    'import.purchaseOrder.sale.project',
                                ])
                                ->where('product_id', $item->product_id)
                                ->where(function($q) use ($transfer) {
                                    $q->where('warehouse_id', $transfer->to_warehouse_id)
                                      ->orWhere('warehouse_id', $transfer->from_warehouse_id);
                                })
                                ->latest('updated_at')
                                ->take(max(1, (int)$item->quantity))
                                ->get();
                            }

                            $poCodes = $tracedItems->map(fn($pi) => $pi->trace_info['po_code'])->filter(fn($c) => $c && $c !== '-')->unique();
                            $suppliers = $tracedItems->map(fn($pi) => $pi->trace_info['supplier_name'])->filter(fn($s) => $s && $s !== '-')->unique();
                            $soCodes = $tracedItems->map(fn($pi) => $pi->trace_info['sale_code'])->filter(fn($c) => $c && $c !== '-')->unique();
                            $projects = $tracedItems->map(fn($pi) => $pi->trace_info['project_name'])->filter(fn($p) => $p && $p !== '-')->unique();
                            $salespeople = $tracedItems->map(fn($pi) => $pi->trace_info['sales_name'])->filter(fn($s) => $s && $s !== '-')->unique();
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <span class="font-mono text-sm font-bold text-blue-600">{{ $item->product->code }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-semibold text-gray-900">{{ $item->product->name }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if($poCodes->isNotEmpty())
                                    <div class="font-semibold text-gray-900">{{ $poCodes->join(', ') }}</div>
                                    @if($suppliers->isNotEmpty())
                                        <div class="text-gray-500 text-[11px]">{{ $suppliers->join(', ') }}</div>
                                    @endif
                                @else
                                    <span class="text-gray-400">Chưa xác định</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if($soCodes->isNotEmpty())
                                    <div class="font-semibold text-purple-700">{{ $soCodes->join(', ') }}</div>
                                @endif
                                @if($projects->isNotEmpty())
                                    <div class="text-gray-600 text-[11px]">{{ $projects->join(', ') }}</div>
                                @elseif($soCodes->isEmpty())
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if($salespeople->isNotEmpty())
                                    <span class="font-medium text-gray-800 bg-gray-100 px-2 py-0.5 rounded">{{ $salespeople->join(', ') }}</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-3 py-1 text-sm font-bold bg-purple-100 text-purple-800 rounded-full">
                                    {{ number_format($item->quantity) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if($serialsWithSku->count() > 0)
                                    <div class="flex flex-wrap gap-1 max-w-md">
                                        @foreach($serialsWithSku as $serial)
                                            <span class="px-2 py-0.5 text-xs font-mono font-medium bg-blue-50 text-blue-700 border border-blue-200 rounded" title="PO: {{ $serial->trace_info['po_code'] }} | SO: {{ $serial->trace_info['sale_code'] }}">
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
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $item->comments ?: '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6 pt-4 border-t border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-500">
                <div>
                    <label class="text-xs text-gray-400">Ngày tạo</label>
                    <p>{{ $transfer->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <label class="text-xs text-gray-400">Cập nhật lần cuối</label>
                    <p>{{ $transfer->updated_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

@include('accounting.journal._widget', ['journalType' => 'transfer', 'journalReferenceId' => $transfer->id, 'hideAmounts' => true])
@endsection
