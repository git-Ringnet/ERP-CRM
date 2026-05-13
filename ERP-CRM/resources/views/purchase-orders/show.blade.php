@extends('layouts.app')

@section('title', 'Chi tiết đơn mua hàng')
@section('page-title', 'Chi tiết đơn mua hàng: ' . $purchaseOrder->code)

@section('content')
    @php
        // Calculate order duration and expected arrival
        $orderDate = $purchaseOrder->order_date;
        $daysElapsed = $orderDate->diffInDays(now());
        $weeksElapsed = floor($daysElapsed / 7);

        // Expected arrival: 4-6 weeks from order date
        $expectedMinDate = $orderDate->copy()->addWeeks(4);
        $expectedMaxDate = $orderDate->copy()->addWeeks(6);

        // Status indicators
        $isOverdue = $purchaseOrder->expected_delivery && now()->gt($purchaseOrder->expected_delivery);
        $isNearDelivery = $purchaseOrder->expected_delivery && now()->diffInDays($purchaseOrder->expected_delivery, false) <= 7 && now()->diffInDays($purchaseOrder->expected_delivery, false) >= 0;
        $isLongWaiting = $daysElapsed > 42; // More than 6 weeks
    @endphp

    <div class="w-full space-y-6">
        <!-- Success Animation Overlay (hidden by default) -->
        <div id="approval-success" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-2xl p-8 transform scale-0 transition-all duration-500 ease-out" id="success-card">
                <div class="text-center">
                    <div
                        class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 animate-bounce">
                        <i class="fas fa-check text-green-600 text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">Duyệt thành công!</h3>
                    <p class="text-gray-600">Đơn hàng đã được duyệt</p>
                </div>
            </div>
        </div>

        <!-- Header Actions -->
        <div class="flex justify-between items-center">
            <a href="{{ url()->previous() }}" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-arrow-left mr-2"></i> Quay lại
            </a>
            <div class="flex space-x-2">
                @if($purchaseOrder->status == 'draft')
                    <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}"
                        class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-all duration-200 transform hover:scale-105">
                        <i class="fas fa-edit mr-2"></i> Sửa
                    </a>
                    <form action="{{ route('purchase-orders.submit-approval', $purchaseOrder) }}" method="POST" class="inline"
                        id="submit-approval-form">
                        @csrf
                        <button type="submit"
                            class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-all duration-200 transform hover:scale-105">
                            <i class="fas fa-paper-plane mr-2"></i> Gửi yêu cầu duyệt
                        </button>
                    </form>
                @endif
                @if($purchaseOrder->status == 'pending_approval')
                    <form action="{{ route('purchase-orders.approve', $purchaseOrder) }}" method="POST" class="inline"
                        id="approve-form">
                        @csrf
                        <button type="submit"
                            class="approve-btn px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-200 transform hover:scale-105 hover:shadow-lg">
                            <i class="fas fa-check mr-2"></i> Xác nhận Đã đặt
                        </button>
                    </form>
                    <form action="{{ route('purchase-orders.reject', $purchaseOrder) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-200 transform hover:scale-105"
                            onclick="return confirm('Từ chối đơn hàng này?')">
                            <i class="fas fa-times mr-2"></i> Từ chối
                        </button>
                    </form>
                @endif
                @if($purchaseOrder->status == 'approved')
                    <form action="{{ route('purchase-orders.ship', $purchaseOrder) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-all duration-200 transform hover:scale-105">
                            <i class="fas fa-truck-moving mr-2"></i> Chuyển sang Đang về
                        </button>
                    </form>
                @endif
                @if(in_array($purchaseOrder->status, ['approved', 'shipping', 'partial_received']))
                    <form action="{{ route('purchase-orders.toggle-hold', $purchaseOrder) }}" method="POST" class="inline" id="hold-form">
                        @csrf
                        <input type="hidden" name="hold_reason" id="hold_reason_input">
                        @if($purchaseOrder->is_hold)
                            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all duration-200 transform hover:scale-105">
                                <i class="fas fa-play mr-2"></i> Gỡ Hold
                            </button>
                        @else
                            <button type="button" onclick="const reason = prompt('Nhập lý do Hold đơn hàng:'); if(reason !== null) { document.getElementById('hold_reason_input').value = reason; document.getElementById('hold-form').submit(); }"
                                class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-all duration-200 transform hover:scale-105">
                                <i class="fas fa-pause mr-2"></i> Hold
                            </button>
                        @endif
                    </form>
                @endif
                @if(in_array($purchaseOrder->status, ['approved', 'shipping', 'partial_received']))
                    <div class="inline-flex items-center space-x-2">
                        <input type="hidden" name="warehouse_id" form="receive-form" value="{{ $warehouses->first()->id ?? '' }}">
                        <button type="button" onclick="fillAllRemaining()" 
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-all duration-200 transform hover:scale-105">
                            <i class="fas fa-check-double mr-2"></i> Nhận hết
                        </button>
                        <button type="button" onclick="document.getElementById('receive-form').submit()"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-200 transform hover:scale-105">
                            <i class="fas fa-box mr-2"></i> Xác nhận nhận hàng
                        </button>
                    </div>
                @endif
                <a href="{{ route('purchase-orders.print', $purchaseOrder) }}"
                    class="px-4 py-2 border rounded-lg hover:bg-gray-50 transition-all duration-200" target="_blank">
                    <i class="fas fa-print mr-2"></i> In PO
                </a>

                @if(!in_array($purchaseOrder->status, ['received', 'cancelled']))
                    <form action="{{ route('purchase-orders.cancel', $purchaseOrder) }}" method="POST" class="inline delete-form">
                        @csrf
                        <button type="button" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-all duration-200 transform hover:scale-105"
                            onclick="confirmAction(this.parentElement, 'Xác nhận hủy', 'Bạn có chắc chắn muốn hủy đơn hàng này không?', 'warning', 'Hủy ngay', '#95a5a6')">
                            <i class="fas fa-ban mr-2"></i> Hủy đơn
                        </button>
                    </form>
                @endif

                @if(in_array($purchaseOrder->status, ['draft', 'cancelled']) && auth()->user()->can('delete', $purchaseOrder))
                    <form action="{{ route('purchase-orders.destroy', $purchaseOrder) }}" method="POST" class="inline delete-form">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-200 transform hover:scale-105"
                            onclick="confirmDelete(this.parentElement, 'đơn hàng')">
                            <i class="fas fa-trash mr-2"></i> Xóa
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Order Duration Status Alert -->
        @if($purchaseOrder->status !== 'received' && $purchaseOrder->status !== 'cancelled')
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Days Since Order -->
                <div
                    class="bg-white rounded-lg shadow p-4 {{ $isLongWaiting ? 'border-l-4 border-orange-500' : 'border-l-4 border-blue-500' }}">
                    <div class="flex items-center">
                        <div
                            class="p-3 rounded-full {{ $isLongWaiting ? 'bg-orange-100 text-orange-600' : 'bg-blue-100 text-blue-600' }}">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Đã đặt từ</p>
                            <p class="text-xl font-bold {{ $isLongWaiting ? 'text-orange-600' : 'text-gray-800' }}">
                                {{ $daysElapsed }} ngày
                                <span class="text-sm font-normal text-gray-500">({{ $weeksElapsed }} tuần)</span>
                            </p>
                            <p class="text-xs text-gray-400">{{ $orderDate->format('d/m/Y') }}</p>
                        </div>
                    </div>
                    @if($isLongWaiting)
                        <div class="mt-2 text-xs text-orange-600 bg-orange-50 rounded px-2 py-1">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Đã đặt khá lâu, cần theo dõi!
                        </div>
                    @endif
                </div>

                <!-- Expected Arrival -->
                <div
                    class="bg-white rounded-lg shadow p-4 {{ $isOverdue ? 'border-l-4 border-red-500' : ($isNearDelivery ? 'border-l-4 border-green-500' : 'border-l-4 border-gray-300') }}">
                    <div class="flex items-center">
                        <div
                            class="p-3 rounded-full {{ $isOverdue ? 'bg-red-100 text-red-600' : ($isNearDelivery ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600') }}">
                            <i class="fas fa-truck text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Dự kiến hàng về</p>
                            @if($purchaseOrder->expected_delivery)
                                <p
                                    class="text-xl font-bold {{ $isOverdue ? 'text-red-600' : ($isNearDelivery ? 'text-green-600' : 'text-gray-800') }}">
                                    {{ $purchaseOrder->expected_delivery->format('d/m/Y') }}
                                </p>
                                @if($isOverdue)
                                    <p class="text-xs text-red-500">
                                        <i class="fas fa-exclamation-circle mr-1"></i> Đã quá hạn
                                        {{ now()->diffInDays($purchaseOrder->expected_delivery) }} ngày
                                    </p>
                                @elseif($isNearDelivery)
                                    <p class="text-xs text-green-500">
                                        <i class="fas fa-clock mr-1"></i> Còn
                                        {{ now()->diffInDays($purchaseOrder->expected_delivery, false) }} ngày
                                    </p>
                                @endif
                            @else
                                <p class="text-sm text-gray-600">{{ $expectedMinDate->format('d/m') }} -
                                    {{ $expectedMaxDate->format('d/m/Y') }}
                                </p>
                                <p class="text-xs text-gray-400">4-6 tuần từ ngày đặt</p>
                            @endif
                        </div>
                    </div>
                    @if($isOverdue)
                        <div class="mt-2 text-xs text-red-600 bg-red-50 rounded px-2 py-1 animate-pulse">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Hàng đã quá hạn giao!
                        </div>
                    @elseif($isNearDelivery)
                        <div class="mt-2 text-xs text-green-600 bg-green-50 rounded px-2 py-1">
                            <i class="fas fa-shipping-fast mr-1"></i> Sắp nhận hàng!
                        </div>
                    @endif
                </div>

                <!-- Order Value Summary -->
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-primary">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-primary/10 text-primary">
                            <i class="fas fa-file-invoice-dollar text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Tổng giá trị PO</p>
                            @if($purchaseOrder->currency && !$purchaseOrder->currency->is_base)
                                <p class="text-xl font-bold text-primary">
                                    @php 
                                        $totalVal = $purchaseOrder->total_foreign ?? ($purchaseOrder->total / ($purchaseOrder->exchange_rate ?: 1));
                                        $dispDecimals = (floor($totalVal) == $totalVal) ? 0 : ($purchaseOrder->currency->decimal_places ?? 2);
                                    @endphp
                                    {{ number_format($totalVal, $dispDecimals) }} $
                                </p>
                            @else
                                <p class="text-xl font-bold text-primary">
                                    {{ number_format($purchaseOrder->total) }} đ
                                </p>
                            @endif
                            <p class="text-xs text-gray-400 mt-1">{{ $purchaseOrder->items->count() }} sản phẩm</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Status Timeline -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                @php
                    $statuses = [
                        'pending' => 'Chờ đặt', 
                        'approved' => 'Đã đặt', 
                        'shipping' => 'Đang về', 
                        'received' => 'Đã về – đủ hàng'
                    ];
                    $currentKey = 'pending';
                    if (in_array($purchaseOrder->status, ['draft', 'pending_approval'])) $currentKey = 'pending';
                    elseif ($purchaseOrder->status == 'approved') $currentKey = 'approved';
                    elseif (in_array($purchaseOrder->status, ['shipping', 'partial_received'])) $currentKey = 'shipping';
                    elseif ($purchaseOrder->status == 'received') $currentKey = 'received';
                    elseif ($purchaseOrder->status == 'cancelled') $currentKey = 'cancelled';
                    
                    $currentIndex = array_search($currentKey, array_keys($statuses));
                    if ($currentIndex === false) $currentIndex = -1;
                @endphp
                @foreach($statuses as $key => $label)
                    @php $index = array_search($key, array_keys($statuses)); @endphp
                    <div class="flex flex-col items-center {{ $index <= $currentIndex ? 'text-primary' : 'text-gray-400' }}">
                        <div
                            class="w-8 h-8 rounded-full flex items-center justify-center {{ $index <= $currentIndex ? ($purchaseOrder->is_hold && $index == $currentIndex ? 'bg-orange-500 text-white animate-pulse' : 'bg-primary text-white') : 'bg-gray-200' }}">
                            @if($index < $currentIndex)
                                <i class="fas fa-check text-sm"></i>
                            @else
                                {{ $index + 1 }}
                            @endif
                        </div>
                        <span class="text-xs mt-1">{{ $label }}</span>
                    </div>
                    @if(!$loop->last)
                        <div class="flex-1 h-1 {{ $index < $currentIndex ? 'bg-primary' : 'bg-gray-200' }} mx-2"></div>
                    @endif
                @endforeach
            </div>
            @if($purchaseOrder->is_hold)
                <div class="mt-3 bg-orange-50 border border-orange-200 rounded-lg p-3 flex items-center">
                    <i class="fas fa-pause-circle text-orange-500 text-lg mr-2"></i>
                    <div>
                        <span class="text-sm font-medium text-orange-800">HOLD</span>
                        @if($purchaseOrder->hold_reason)
                            <span class="text-sm text-orange-600 ml-2">Lý do: {{ $purchaseOrder->hold_reason }}</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Info Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div>
                    <p class="text-sm text-gray-500">Mã PO</p>
                    <p class="font-semibold text-primary text-lg">{{ $purchaseOrder->code }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Nhà cung cấp</p>
                    <p class="font-medium">{{ $purchaseOrder->supplier->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Ngày tạo</p>
                    <p class="font-medium">{{ $purchaseOrder->order_date->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Ngày giao dự kiến</p>
                    <p class="font-medium">
                        {{ $purchaseOrder->expected_delivery ? $purchaseOrder->expected_delivery->format('d/m/Y') : '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Tiền tệ</p>
                    <p class="font-medium">
                        {{ $purchaseOrder->currency?->code ?? 'VND' }}
                        @if($purchaseOrder->currency && !$purchaseOrder->currency->is_base)
                            <span class="text-xs text-gray-500">(Tỷ giá: {{ number_format($purchaseOrder->exchange_rate) }})</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Điều khoản thanh toán</p>
                    <p class="font-medium">{{ $purchaseOrder->payment_terms_label }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Địa chỉ giao hàng</p>
                    <p class="font-medium">{{ $purchaseOrder->delivery_address ?: '-' }}</p>
                </div>
                @if($purchaseOrder->actual_delivery)
                    <div>
                        <p class="text-sm text-gray-500">Ngày nhận hàng</p>
                        <p class="font-medium text-green-600">{{ $purchaseOrder->actual_delivery->format('d/m/Y') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Tracking Update Form (only show when PO is not received/cancelled) -->
        @if(!in_array($purchaseOrder->status, ['received', 'cancelled']))
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b bg-gradient-to-r from-blue-50 to-indigo-50">
                <h3 class="font-semibold flex items-center">
                    <i class="fas fa-calendar-check mr-2 text-blue-600"></i>
                    Cập nhật theo dõi đơn hàng
                </h3>
                <p class="text-sm text-gray-500 mt-1">Cập nhật ngày dự kiến sẽ tự động thông báo cho Sales</p>
            </div>
            <form action="{{ route('purchase-orders.update-tracking', $purchaseOrder) }}" method="POST" class="p-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-truck text-blue-500 mr-1"></i>Ngày dự kiến hàng về
                        </label>
                        <input type="date" name="expected_arrival_date" 
                            value="{{ $purchaseOrder->expected_arrival_date?->format('Y-m-d') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-industry text-purple-500 mr-1"></i>Ngày hãng xuất sản phẩm
                        </label>
                        <input type="date" name="manufacturer_release_date" 
                            value="{{ $purchaseOrder->manufacturer_release_date?->format('Y-m-d') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-calendar-alt text-green-500 mr-1"></i>Ngày giao hàng dự kiến
                        </label>
                        <input type="date" name="expected_delivery" 
                            value="{{ $purchaseOrder->expected_delivery?->format('Y-m-d') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-green-500 focus:border-green-500">
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                        <i class="fas fa-save mr-1"></i> Lưu & Thông báo
                    </button>
                </div>
            </form>
        </div>
        @endif

        <!-- Items -->
        @php
            $isForeign = $purchaseOrder->currency && !$purchaseOrder->currency->is_base;
            $rate = $purchaseOrder->exchange_rate ?: 1;
            $decimals = $purchaseOrder->currency->decimal_places ?? 2;
            $symbol = $purchaseOrder->currency->symbol ?? $purchaseOrder->currency->code ?? '';

            // PO stores intermediate values (subtotal, etc.) in foreign currency. 
            // `total` is stored in VND.
            $subtotalForeign = $purchaseOrder->subtotal;
            $subtotalVnd = $isForeign ? round($subtotalForeign * $rate) : $subtotalForeign;

            $discountForeign = $purchaseOrder->discount_amount;
            $discountVnd = $isForeign ? round($discountForeign * $rate) : $discountForeign;

            $shippingForeign = $purchaseOrder->shipping_cost;
            $shippingVnd = $isForeign ? round($shippingForeign * $rate) : $shippingForeign;

            $otherForeign = $purchaseOrder->other_cost;
            $otherVnd = $isForeign ? round($otherForeign * $rate) : $otherForeign;

            $vatForeign = $purchaseOrder->items->sum('vat_amount');
            $vatVnd = $isForeign ? round($vatForeign * $rate) : $vatForeign;

            $totalForeign = $purchaseOrder->total_foreign ?? ($isForeign ? round($subtotalForeign - $discountForeign + $shippingForeign + $otherForeign + $vatForeign, $decimals) : $purchaseOrder->total);
            $totalVnd = $purchaseOrder->total;
        @endphp
        <form id="receive-form" action="{{ route('purchase-orders.receive', $purchaseOrder) }}" method="POST">
            @csrf
            <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b bg-gray-50">
                <h3 class="font-semibold">Chi tiết sản phẩm</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã SO</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase whitespace-nowrap">SL</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase whitespace-nowrap">SL Nhận</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Giá nhập kho</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Giá mua thực tế</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Thành tiền</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($purchaseOrder->items as $index => $item)
                        <tr>
                            <td class="px-4 py-3">{{ $index + 1 }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $item->product_name }}</div>
                                @if($item->unit)
                                    <div class="text-xs text-gray-400">ĐVT: {{ $item->unit }}</div>
                                @endif
                                @if($item->received_quantity > 0)
                                    <div class="text-[10px] text-green-600 font-medium">Đã nhận: {{ number_format($item->received_quantity) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($item->saleOrderRequestItem && $item->saleOrderRequestItem->saleOrderRequest && $item->saleOrderRequestItem->saleOrderRequest->sale)
                                    <a href="{{ route('sales.show', $item->saleOrderRequestItem->saleOrderRequest->sale->id) }}" class="text-blue-600 hover:underline font-medium">
                                        {{ $item->saleOrderRequestItem->saleOrderRequest->sale->code }}
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-medium">{{ number_format($item->quantity) }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($item->remaining_quantity > 0)
                                    <div class="flex items-center justify-end space-x-1">
                                        <input type="number" name="items[{{ $item->id }}]" 
                                            class="receive-input w-20 px-2 py-1 text-right border border-blue-300 rounded focus:ring-1 focus:ring-blue-500"
                                            placeholder="0" step="1" min="0" max="{{ $item->remaining_quantity }}"
                                            data-remaining="{{ $item->remaining_quantity }}">
                                        <button type="button" onclick="fillRemaining(this)" class="text-blue-500 hover:text-blue-700" title="Nhận hết">
                                            <i class="fas fa-arrow-alt-circle-right"></i>
                                        </button>
                                    </div>
                                @else
                                    <span class="text-xs text-green-600 font-bold"><i class="fas fa-check"></i> Đủ</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-gray-500">
                                @if($item->warehouse_unit_price > 0)
                                    {{ number_format($item->warehouse_unit_price, $decimals) }} {{ $symbol }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end group">
                                    <input type="number" step="0.01" value="{{ $item->unit_price }}" 
                                        onchange="updateItemPrice({{ $item->id }}, this.value)"
                                        class="w-24 text-right font-semibold text-blue-700 bg-transparent border-none focus:ring-1 focus:ring-blue-400 rounded px-1 transition-all">
                                    <span class="text-blue-700 ml-0.5">{{ $symbol }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">
                                <span id="item-total-{{ $item->id }}">{{ number_format($item->total, $decimals) }}</span> {{ $symbol }}
                            </td>
                            <td class="px-2 py-3">
                                <div class="flex flex-col items-center space-y-1">
                                    {{-- Status Selector --}}
                                    <div class="relative inline-block w-24">
                                        <select onchange="updateItemStatus({{ $item->id }}, this.value)" 
                                            class="appearance-none w-full text-[9px] font-bold border-none rounded-full px-2 py-1 focus:ring-1 focus:ring-offset-1 transition-all cursor-pointer
                                            {{ $item->status == 'ordered' ? 'bg-gray-100 text-gray-600 focus:ring-gray-400' : '' }}
                                            {{ $item->status == 'shipping' ? 'bg-blue-100 text-blue-600 focus:ring-blue-400' : '' }}
                                            {{ $item->status == 'received' ? 'bg-green-100 text-green-600 focus:ring-green-400' : '' }}
                                            {{ $item->status == 'cancelled' ? 'bg-red-100 text-red-600 focus:ring-red-400' : '' }}">
                                            <option value="ordered" {{ $item->status == 'ordered' ? 'selected' : '' }}>Chờ hàng</option>
                                            <option value="shipping" {{ $item->status == 'shipping' ? 'selected' : '' }}>Đang về</option>
                                            <option value="received" {{ $item->status == 'received' ? 'selected' : '' }}>Đã về</option>
                                            <option value="cancelled" {{ $item->status == 'cancelled' ? 'selected' : '' }}>Hủy</option>
                                        </select>
                                    </div>

                                    {{-- License Upload & Link --}}
                                    <div class="flex items-center justify-center space-x-1">
                                        @if($item->license_file)
                                            <a href="{{ asset('storage/' . $item->license_file) }}" target="_blank" 
                                                class="flex items-center justify-center w-5 h-5 bg-indigo-50 text-indigo-600 rounded hover:bg-indigo-100 transition-colors" title="Xem License">
                                                <i class="fas fa-file-contract text-[10px]"></i>
                                            </a>
                                        @endif
                                        <button type="button" onclick="document.getElementById('license-upload-{{ $item->id }}').click()"
                                            class="flex items-center gap-1 px-1.5 py-0.5 bg-teal-50 text-teal-600 rounded text-[9px] font-bold hover:bg-teal-100 transition-all border border-teal-100" title="Upload License">
                                            <i class="fas fa-upload"></i> Up
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    @php 
                        $footerColspan = 7; 
                    @endphp
                    <tr>
                        <td colspan="{{ $footerColspan }}" class="px-4 py-2 text-right text-gray-600">Tổng tiền hàng:</td>
                        <td class="px-4 py-2 text-right">
                            @if($isForeign)
                                <div class="font-medium text-gray-900">{{ number_format($subtotalForeign, (floor($subtotalForeign) == $subtotalForeign) ? 0 : $decimals) }} $</div>
                            @else
                                <div class="font-medium text-gray-900">{{ number_format($subtotalVnd) }} đ</div>
                            @endif
                        </td>
                        <td></td>
                    </tr>
                    @if($purchaseOrder->discount_percent > 0)
                        <tr>
                            <td colspan="{{ $footerColspan }}" class="px-4 py-2 text-right text-gray-600">Chiết khấu
                                ({{ $purchaseOrder->discount_percent }}%):</td>
                            <td class="px-4 py-2 text-right text-red-600">
                                @if($isForeign)
                                    <div>-{{ number_format($discountForeign, (floor($discountForeign) == $discountForeign) ? 0 : $decimals) }} $</div>
                                @else
                                    -{{ number_format($discountVnd) }} đ
                                @endif
                            </td>
                            <td></td>
                        </tr>
                    @endif
                    @if($purchaseOrder->shipping_cost > 0)
                        <tr>
                            <td colspan="{{ $footerColspan }}" class="px-4 py-2 text-right text-gray-600">Phí vận chuyển:</td>
                            <td class="px-4 py-2 text-right">
                                @if($isForeign)
                                    <div class="font-medium text-gray-900">{{ number_format($shippingForeign, (floor($shippingForeign) == $shippingForeign) ? 0 : $decimals) }} $</div>
                                @else
                                    {{ number_format($shippingVnd) }} đ
                                @endif
                            </td>
                            <td></td>
                        </tr>
                    @endif
                    @if($purchaseOrder->other_cost > 0)
                        <tr>
                            <td colspan="{{ $footerColspan }}" class="px-4 py-2 text-right text-gray-600">Chi phí khác:</td>
                            <td class="px-4 py-2 text-right">
                                @if($isForeign)
                                    <div class="font-medium text-gray-900">{{ number_format($otherForeign, (floor($otherForeign) == $otherForeign) ? 0 : $decimals) }} $</div>
                                @else
                                    {{ number_format($otherVnd) }} đ
                                @endif
                            </td>
                            <td></td>
                        </tr>
                    @endif
                    <tr class="border-t bg-gray-100">
                        <td colspan="{{ $footerColspan }}" class="px-4 py-3 text-right text-lg font-bold text-gray-800">Tổng cộng:</td>
                        <td class="px-4 py-3 text-right text-primary">
                            @if($isForeign)
                                <div class="text-lg font-bold">{{ number_format($totalForeign, (floor($totalForeign) == $totalForeign) ? 0 : $decimals) }} $</div>
                            @else
                                <div class="text-lg font-bold">{{ number_format($totalVnd) }} đ</div>
                            @endif
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        </form>

        @if($purchaseOrder->note)
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold mb-2">Ghi chú</h3>
                <p class="text-gray-700">{{ $purchaseOrder->note }}</p>
            </div>
        @endif

        {{-- Yêu cầu đặt hàng từ Sales --}}
        @if($purchaseOrder->sale && $purchaseOrder->sale->orderRequests->count() > 0)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b bg-gradient-to-r from-teal-50 to-cyan-50">
                <h3 class="font-semibold flex items-center">
                    <i class="fas fa-clipboard-list mr-2 text-teal-600"></i>
                    Yêu cầu đặt hàng từ Sales
                    <a href="{{ route('sales.show', $purchaseOrder->sale_id) }}" class="ml-2 text-sm font-normal text-teal-600 hover:underline">
                        ({{ $purchaseOrder->sale->code }})
                    </a>
                </h3>
            </div>
            @foreach($purchaseOrder->sale->orderRequests as $req)
            <div class="p-4 {{ !$loop->last ? 'border-b' : '' }}">
                <div class="flex items-center gap-3 mb-3">
                    <span class="px-2 py-1 text-xs font-bold rounded bg-teal-100 text-teal-800">{{ $req->code }}</span>
                    <span class="text-sm text-gray-600">
                        <i class="fas fa-user text-gray-400 mr-1"></i>{{ $req->creator->name ?? 'N/A' }}
                    </span>
                    <span class="text-xs text-gray-400">
                        <i class="fas fa-clock mr-1"></i>{{ $req->created_at->format('d/m/Y H:i') }}
                    </span>
                </div>

                {{-- Items --}}
                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="w-full text-xs">
                        <thead class="bg-yellow-200">
                            <tr class="border-b border-gray-300">
                                <th rowspan="2" class="px-2 py-1.5 text-left font-bold text-gray-800 border-r border-gray-300 align-middle">Vendor</th>
                                <th rowspan="2" class="px-2 py-1.5 text-left font-bold text-gray-800 border-r border-gray-300 align-middle">Type</th>
                                <th rowspan="2" class="px-2 py-1.5 text-left font-bold text-gray-800 border-r border-gray-300 align-middle">Part Number</th>
                                <th rowspan="2" class="px-2 py-1.5 text-left font-bold text-gray-800 border-r border-gray-300 align-middle">SN</th>
                                <th rowspan="2" class="px-2 py-1.5 text-left font-bold text-gray-800 border-r border-gray-300 align-middle">Exp date</th>
                                <th rowspan="2" class="px-2 py-1.5 text-left font-bold text-gray-800 border-r border-gray-300 align-middle">SI Name</th>
                                <th colspan="2" class="px-2 py-1.5 text-center font-bold text-gray-800 border-b border-gray-300">Thông tin CQ (Điền tay)</th>
                            </tr>
                            <tr class="border-b border-gray-300">
                                <th class="px-2 py-1.5 text-center font-bold text-gray-800 border-r border-gray-300">EU Name - MST</th>
                                <th class="px-2 py-1.5 text-center font-bold text-gray-800">Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($req->items as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-2 py-1.5 font-medium">{{ $item->vendor }}</td>
                                <td class="px-2 py-1.5"><span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 text-[10px] font-bold">{{ $item->type }}</span></td>
                                <td class="px-2 py-1.5 font-medium text-teal-700">{{ $item->part_number }}</td>
                                <td class="px-2 py-1.5 text-gray-600">{{ $item->serial_number ?: '-' }}</td>
                                <td class="px-2 py-1.5 text-gray-600">{{ $item->exp_date ? $item->exp_date->format('d/m/Y') : '-' }}</td>
                                <td class="px-2 py-1.5">{{ $item->si_name }}</td>
                                <td class="px-2 py-1.5">{{ $item->eu_name_mst }}</td>
                                <td class="px-2 py-1.5 text-gray-600">{{ $item->address ?: '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Note --}}
                @if($req->note)
                <div class="mt-2 bg-yellow-50 rounded-lg p-3 text-sm text-gray-700">
                    <i class="fas fa-sticky-note text-yellow-500 mr-1"></i>Note: {{ $req->note }}
                </div>
                @endif

                {{-- Attachments --}}
                @if($req->attachments->count() > 0)
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach($req->attachments as $att)
                    <a href="{{ route('sales.order-request.attachment.download', [$purchaseOrder->sale_id, $att->id]) }}" 
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-blue-50 rounded-lg text-xs text-gray-700 hover:text-blue-600 transition-colors">
                        <i class="{{ $att->file_icon }}"></i>
                        <span>{{ $att->file_name }}</span>
                        <span class="text-gray-400">({{ $att->file_size_formatted }})</span>
                    </a>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

    </div>

    {{-- Hidden forms for License Upload --}}
    @foreach($purchaseOrder->items as $item)
        <form id="license-form-{{ $item->id }}" action="{{ route('purchase-orders.items.upload-license', $item) }}" 
            method="POST" enctype="multipart/form-data" style="display: none;">
            @csrf
            <input type="file" id="license-upload-{{ $item->id }}" name="license_file" onchange="this.form.submit()">
        </form>
    @endforeach

    @push('scripts')
        <script>
            // JS for Receipt Logic
            window.fillRemaining = function(btn) {
                const parent = btn.parentElement;
                const input = parent.querySelector('.receive-input');
                if (input && input.dataset.remaining) {
                    input.value = input.dataset.remaining;
                }
            };

            window.fillAllRemaining = function() {
                document.querySelectorAll('.receive-input').forEach(input => {
                    if (input.dataset.remaining) {
                        input.value = input.dataset.remaining;
                    }
                });
            };

            // Check if coming from a successful approval
            @if(session('success') && str_contains(session('success'), 'duyệt'))
                // Show success animation
                const overlay = document.getElementById('approval-success');
                const card = document.getElementById('success-card');

                overlay.classList.remove('hidden');
                overlay.classList.add('flex');

                setTimeout(() => {
                    card.classList.remove('scale-0');
                    card.classList.add('scale-100');
                }, 100);

                setTimeout(() => {
                    card.classList.remove('scale-100');
                    card.classList.add('scale-0');
                    setTimeout(() => {
                        overlay.classList.add('hidden');
                        overlay.classList.remove('flex');
                    }, 500);
                }, 2000);
            @endif

            // Add click animation to approve form
            const approveForm = document.getElementById('approve-form');
            if (approveForm) {
                approveForm.addEventListener('submit', function (e) {
                    const btn = this.querySelector('.approve-btn');
                    if (btn) {
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Đang xử lý...';
                        // Disable after a tiny delay to ensure form submits
                        setTimeout(() => btn.disabled = true, 50);
                    }
                });
            }

            // Close overlay on click
            document.getElementById('approval-success')?.addEventListener('click', function () {
                const card = document.getElementById('success-card');
                card.classList.remove('scale-100');
                card.classList.add('scale-0');
                setTimeout(() => {
                    this.classList.add('hidden');
                    this.classList.remove('flex');
                }, 500);
            });
            function updateItemPrice(itemId, price) {
                fetch(`/purchase-orders/items/${itemId}/update-price`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ unit_price: price })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Cập nhật lại giao diện (tùy chọn)
                        if (window.Toast) {
                            Toast.fire({ icon: 'success', title: 'Đã cập nhật giá mua' });
                        }
                        location.reload(); // Reload để cập nhật tổng tiền footer
                    }
                })
                .catch(error => console.error('Error:', error));
            }
            function updateItemStatus(itemId, status) {
                fetch(`/purchase-orders/items/${itemId}/update-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ status: status })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (window.Toast) {
                            Toast.fire({ icon: 'success', title: 'Đã cập nhật trạng thái' });
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        </script>
    @endpush
@endsection