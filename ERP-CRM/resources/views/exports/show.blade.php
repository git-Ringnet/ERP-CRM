@extends('layouts.app')

@section('title', 'Chi tiết phiếu xuất')
@section('page-title', 'Chi tiết Phiếu Xuất Kho')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">{{ $export->code }}</h2>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('exports.export-excel', $export) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-file-excel mr-2"></i> Xuất Excel
            </a>
            @if($export->status === 'pending')
                <a href="{{ route('exports.edit', $export) }}" 
                   class="px-3 py-1.5 text-sm text-white bg-blue-500 rounded-lg hover:bg-blue-600">
                    <i class="fas fa-edit mr-1"></i>Chỉnh sửa
                </a>
                <button onclick="confirmApprove('{{ route('exports.approve', $export) }}', 'phiếu xuất kho')"
                        class="px-3 py-1.5 text-sm text-white bg-green-500 rounded-lg hover:bg-green-600">
                    <i class="fas fa-check mr-1"></i>Duyệt phiếu
                </button>
                <button onclick="confirmReject('{{ route('exports.reject', $export) }}', 'phiếu xuất kho')"
                        class="px-3 py-1.5 text-sm text-white bg-red-500 rounded-lg hover:bg-red-600">
                    <i class="fas fa-times mr-1"></i>Từ chối
                </button>
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
            @elseif($export->status === 'rejected')
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
                    <p class="font-medium text-gray-900">{{ $export->code }}</p>
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
                @if($export->customer)
                <div>
                    <label class="text-sm text-gray-500">Khách hàng</label>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('customers.show', $export->customer) }}" 
                           class="font-medium text-blue-600 hover:text-blue-800 hover:underline">
                            {{ $export->customer->code }} - {{ $export->customer->name }}
                        </a>
                    </div>
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

        <!-- Items Table - Grouped by Warehouse -->
        <div class="border-t border-gray-200 pt-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Chi tiết sản phẩm</h3>
            
            @php
                // Group items by warehouse_id
                // Note: warehouse_id is stored in transaction, but items can be from different warehouses
                // We need to get warehouse from ProductItem for each item
                $itemsByWarehouse = collect();
                
                foreach($export->items as $item) {
                    // Get warehouse from first serial or use transaction warehouse as fallback
                    $warehouseId = $export->warehouse_id;
                    $warehouseName = $export->warehouse->name ?? 'N/A';
                    
                    // Try to get warehouse from serials
                    if ($export->status === 'completed') {
                        $firstSerial = ($exportedItems[$item->product_id] ?? collect())->first();
                        if ($firstSerial) {
                            $warehouseId = $firstSerial->warehouse_id;
                            $warehouseName = $firstSerial->warehouse->name ?? $warehouseName;
                        }
                    } else {
                        // For pending, get from serial_number JSON
                        if (!empty($item->serial_number)) {
                            $productItemIds = json_decode($item->serial_number, true);
                            if (is_array($productItemIds) && !empty($productItemIds)) {
                                $firstSerial = \App\Models\ProductItem::find($productItemIds[0]);
                                if ($firstSerial) {
                                    $warehouseId = $firstSerial->warehouse_id;
                                    $warehouseName = $firstSerial->warehouse->name ?? $warehouseName;
                                }
                            }
                        }
                    }
                    
                    if (!isset($itemsByWarehouse[$warehouseId])) {
                        $itemsByWarehouse[$warehouseId] = [
                            'name' => $warehouseName,
                            'items' => collect()
                        ];
                    }
                    
                    $itemsByWarehouse[$warehouseId]['items']->push($item);
                }
            @endphp
            
            @php $overallTotal = 0; @endphp
            @foreach($itemsByWarehouse as $warehouseId => $warehouseData)
                @php $subTotal = 0; $stt = 1; @endphp
            <div class="mb-6 {{ !$loop->last ? 'pb-6 border-b border-gray-200' : '' }}">
                <div class="overflow-x-auto">
                    <table class="w-full table-fixed">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-16">STT</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-32">Kho xuất</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-32">Mã sản phẩm</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên sản phẩm</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">SL Yêu cầu</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">SL Thực xuất</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-32">Đơn giá</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-32">Thành tiền</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-48">Serial đã xuất</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-32">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                             @foreach($warehouseData['items'] as $item)
                            @php
                                // Use stored price/total if available, fallback to avg_cost for legacy records
                                $displayUnitPrice = $item->unit_price ?? (\App\Models\Inventory::where('product_id', $item->product_id)
                                    ->where('warehouse_id', $item->warehouse_id ?? $warehouseId)
                                    ->first()->avg_cost ?? 0);
                                $displayTotal = $item->total ?? ($displayUnitPrice * $item->quantity);
                                $subTotal += $displayTotal;
                                $overallTotal += $displayTotal;
                                
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
                                <td class="px-4 py-3 text-center">
                                    <span class="text-sm font-medium text-gray-600">{{ $stt++ }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm font-medium text-gray-700 truncate block">{{ $warehouseData['name'] }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-mono text-sm font-medium text-blue-600 truncate block">{{ $item->product->code }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 break-words">{{ $item->product->name }}</div>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @if($item->requested_quantity)
                                        <span class="px-3 py-1 text-sm font-medium bg-blue-100 text-blue-800 rounded-full">
                                            {{ number_format($item->requested_quantity) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-3 py-1 text-sm font-bold bg-orange-100 text-orange-800 rounded-full">
                                        {{ number_format($item->quantity) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-right whitespace-nowrap">
                                    @if($displayUnitPrice > 0)
                                        <span class="font-medium text-gray-800">{{ number_format($displayUnitPrice, $displayUnitPrice == floor($displayUnitPrice) ? 0 : 2, '.', ',') }} đ</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-right whitespace-nowrap">
                                    @if($displayTotal > 0)
                                        <span class="font-semibold text-blue-700">{{ number_format($displayTotal, $displayTotal == floor($displayTotal) ? 0 : 2, '.', ',') }} đ</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
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
                            <tr class="bg-gray-50 border-t border-gray-200">
                                <td colspan="7" class="px-4 py-3 text-right text-sm font-bold text-gray-700 uppercase">Cộng kho:</td>
                                <td class="px-4 py-3 text-right text-sm font-bold text-blue-700 whitespace-nowrap">
                                    {{ number_format($subTotal, $subTotal == floor($subTotal) ? 0 : 2, '.', ',') }} đ
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
            
            @if(isset($overallTotal) && $overallTotal > 0)
            <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-100 flex justify-between items-center shadow-sm">
                <span class="text-sm font-bold text-blue-800 uppercase tracking-wider">Tổng cộng toàn bộ phiếu:</span>
                <span class="text-xl font-black text-blue-700">
                    {{ number_format($overallTotal, $overallTotal == floor($overallTotal) ? 0 : 2, '.', ',') }} đ
                </span>
            </div>
            @endif
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

@include('accounting.journal._widget', ['journalType' => 'export', 'journalReferenceId' => $export->id])
@endsection
