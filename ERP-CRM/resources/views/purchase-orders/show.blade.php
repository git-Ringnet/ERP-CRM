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

        // Status indicators - ưu tiên expected_arrival_date từ tracking
        $deliveryDate = $purchaseOrder->expected_arrival_date ?? $purchaseOrder->expected_delivery;
        $isOverdue = $deliveryDate && now()->startOfDay()->gt($deliveryDate) && $purchaseOrder->status !== 'received' && $purchaseOrder->status !== 'cancelled';
        $isNearDelivery = $deliveryDate && now()->startOfDay()->diffInDays($deliveryDate, false) <= 7 && now()->startOfDay()->diffInDays($deliveryDate, false) >= 0 && $purchaseOrder->status !== 'received' && $purchaseOrder->status !== 'cancelled';
        $isLongWaiting = $daysElapsed > 42 && $purchaseOrder->status !== 'received' && $purchaseOrder->status !== 'cancelled'; // More than 6 weeks
    @endphp

    <div class="w-full space-y-6 pb-20 md:pb-0">
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
                @if(in_array($purchaseOrder->status, ['draft', 'pending_approval']))
                    <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}"
                        class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-all duration-200 transform hover:scale-105">
                        <i class="fas fa-edit mr-2"></i> Sửa
                    </a>
                @endif
                @if($purchaseOrder->status == 'draft')
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
                    <form action="{{ route('purchase-orders.reject', $purchaseOrder) }}" method="POST" class="inline" id="reject-form">
                        @csrf
                        <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-200 transform hover:scale-105"
                            onclick="return confirm('Từ chối đơn hàng này?')">
                            <i class="fas fa-times mr-2"></i> Từ chối
                        </button>
                    </form>
                @endif
                @php
                    $hasOrderedItems = $purchaseOrder->items->where('status', 'ordered')->count() > 0;
                    $hasShippingItems = $purchaseOrder->items->where('status', 'shipping')->count() > 0;
                @endphp
                @if($hasOrderedItems)
                    <form action="{{ route('purchase-orders.ship', $purchaseOrder) }}" method="POST" class="inline" id="ship-form">
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
                @if($hasShippingItems)
                    <form action="{{ route('purchase-orders.confirm-received', $purchaseOrder) }}" method="POST" class="inline" id="confirm-received-form">
                        @csrf
                        <button type="submit" onclick="return confirm('Xác nhận nhận hàng cho tất cả sản phẩm đang ở trạng thái Đang về?')"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-200 transform hover:scale-105">
                            <i class="fas fa-box-open mr-2"></i> Xác nhận nhận hàng
                        </button>
                    </form>
                @endif
                <a href="{{ route('purchase-orders.print', $purchaseOrder) }}"
                    class="px-4 py-2 border rounded-lg hover:bg-gray-50 transition-all duration-200" target="_blank">
                    <i class="fas fa-print mr-2"></i> In PO
                </a>
                <a href="{{ route('purchase-orders.export-single', $purchaseOrder) }}"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-all duration-200 transform hover:scale-105">
                    <i class="fas fa-file-excel mr-2"></i> Xuất Excel
                </a>

                @if(!in_array($purchaseOrder->status, ['received', 'cancelled']))
                    <form action="{{ route('purchase-orders.cancel', $purchaseOrder) }}" method="POST" class="inline delete-form" id="cancel-form">
                        @csrf
                        <button type="button" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-all duration-200 transform hover:scale-105"
                            onclick="confirmAction(this.parentElement, 'Xác nhận hủy', 'Bạn có chắc chắn muốn hủy đơn hàng này không?', 'warning', 'Hủy ngay', '#95a5a6')">
                            <i class="fas fa-ban mr-2"></i> Hủy đơn
                        </button>
                    </form>
                @endif

                @if(in_array($purchaseOrder->status, ['draft', 'cancelled']) && auth()->user()->can('delete', $purchaseOrder))
                    <form action="{{ route('purchase-orders.destroy', $purchaseOrder) }}" method="POST" class="inline delete-form" id="destroy-form">
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

                <!-- Expected Arrival - chỉ hiển khi có expected_arrival_date từ tracking -->
                @if($purchaseOrder->expected_arrival_date)
                <div
                    class="bg-white rounded-lg shadow p-4 {{ $isOverdue ? 'border-l-4 border-red-500' : ($isNearDelivery ? 'border-l-4 border-green-500' : 'border-l-4 border-gray-300') }}">
                    <div class="flex items-center">
                        <div
                            class="p-3 rounded-full {{ $isOverdue ? 'bg-red-100 text-red-600' : ($isNearDelivery ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600') }}">
                            <i class="fas fa-truck text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Dự kiến hàng về</p>
                            <p
                                class="text-xl font-bold {{ $isOverdue ? 'text-red-600' : ($isNearDelivery ? 'text-green-600' : 'text-gray-800') }}">
                                {{ \Carbon\Carbon::parse($deliveryDate)->format('d/m/Y') }}
                            </p>
                            <p class="text-[10px] text-gray-400">Từ theo dõi đơn hàng</p>
                            @if($isOverdue)
                                <p class="text-xs text-red-500">
                                    <i class="fas fa-exclamation-circle mr-1"></i> Đã quá hạn
                                    {{ now()->diffInDays($deliveryDate) }} ngày
                                </p>
                            @elseif($isNearDelivery)
                                <p class="text-xs text-green-500">
                                    <i class="fas fa-clock mr-1"></i> Còn
                                    {{ now()->diffInDays($deliveryDate, false) }} ngày
                                </p>
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
                @endif

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
                    <p class="text-sm text-gray-500">CPQ đơn hàng</p>
                    <p class="font-medium text-gray-800 text-lg">{{ $purchaseOrder->cpq_number ?: '-' }}</p>
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

            @if($purchaseOrder->note)
            <div class="border-t border-gray-100 mt-6 pt-4">
                <p class="text-sm text-gray-500 font-medium mb-1">Ghi chú</p>
                <p class="text-sm text-gray-700 bg-gray-50 rounded-lg p-3 border border-gray-200 whitespace-pre-line">{{ $purchaseOrder->note }}</p>
            </div>
            @endif
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
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Giá nhập kho (USD)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Giá mua thực tế (USD)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Thành tiền (USD)</th>
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
                            <td class="px-4 py-3 text-right text-gray-500">
                                @php
                                    $pnlCostUsd = null;
                                    if ($item->saleOrderRequestItem && $item->saleOrderRequestItem->saleItem) {
                                        $pnlCostUsd = $item->saleOrderRequestItem->saleItem->estimated_cost_usd;
                                    }
                                @endphp
                                @if($pnlCostUsd && $pnlCostUsd > 0)
                                    {{ number_format($pnlCostUsd, 2) }} $
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end group">
                                    <input type="number" step="0.01" value="{{ $item->unit_price }}" 
                                        data-item-id="{{ $item->id }}"
                                        data-quantity="{{ $item->quantity }}"
                                        oninput="calculateItemTotal({{ $item->id }}, this.value, {{ $item->quantity }})"
                                        onchange="updateItemPrice({{ $item->id }}, this.value)"
                                        class="w-36 text-right font-semibold text-blue-700 bg-transparent border-none focus:ring-1 focus:ring-blue-400 rounded px-1 transition-all">
                                    <span class="text-blue-700 ml-0.5">$</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">
                                <span id="item-total-{{ $item->id }}">{{ number_format($item->total, 2) }}</span> $
                            </td>
                            <td class="px-2 py-3">
                                <div class="flex flex-col items-center space-y-1">
                                    {{-- Status Selector --}}
                                    <div class="relative inline-block w-24">
                                        <select onchange="updateItemStatus({{ $item->id }}, this.value)" 
                                            {{ $item->status == 'received' ? 'disabled' : '' }}
                                            class="appearance-none w-full text-[9px] font-bold border-none rounded-full px-2 py-1 focus:ring-1 focus:ring-offset-1 transition-all {{ $item->status == 'received' ? 'cursor-not-allowed opacity-80' : 'cursor-pointer' }}
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
                                    <div class="flex flex-wrap items-center justify-center gap-1.5">
                                        @php
                                            $licenseFiles = [];
                                            if ($item->license_file) {
                                                $decoded = json_decode($item->license_file, true);
                                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                    $licenseFiles = $decoded;
                                                } else {
                                                    $licenseFiles = [$item->license_file];
                                                }
                                            }
                                        @endphp
                                        
                                        @foreach($licenseFiles as $index => $file)
                                            <div class="relative group inline-block">
                                                <a href="javascript:void(0)" onclick="openFilePreviewModal('{{ route('purchase-orders.items.preview-license', [$item->id, $index]) }}', '{{ basename($file) }}')" 
                                                    class="flex items-center justify-center w-5 h-5 bg-indigo-50 text-indigo-600 rounded hover:bg-indigo-100 transition-colors border border-indigo-100 shadow-sm cursor-pointer" 
                                                    title="Xem License {{ count($licenseFiles) > 1 ? $index + 1 : '' }} ({{ basename($file) }})">
                                                    <i class="fas fa-file-contract text-[10px]"></i>
                                                </a>
                                                <!-- Delete button on hover -->
                                                <button type="button" onclick="confirmDeleteLicenseFile({{ $item->id }}, {{ $index }}, '{{ basename($file) }}')" 
                                                    class="absolute -top-1.5 -right-1.5 hidden group-hover:flex items-center justify-center w-3.5 h-3.5 bg-red-500 text-white rounded-full hover:bg-red-600 transition-all border border-white text-[8px] font-black cursor-pointer shadow animate-fade-in" 
                                                    title="Xóa file này">
                                                    &times;
                                                </button>
                                            </div>
                                        @endforeach
                                        
                                        <button type="button" onclick="document.getElementById('license-upload-{{ $item->id }}').click()"
                                            class="flex items-center gap-1 px-1.5 py-0.5 bg-teal-50 text-teal-600 rounded text-[9px] font-bold hover:bg-teal-100 transition-all border border-teal-100" title="Upload License (Có thể chọn nhiều file)">
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
                        $footerColspan = 6;
                    @endphp
                    <tr>
                        <td colspan="{{ $footerColspan }}" class="px-4 py-2 text-right text-gray-600">Tổng tiền hàng:</td>
                        <td class="px-4 py-2 text-right">
                            <div class="font-medium text-gray-900">{{ number_format($subtotalVnd, 2) }} $</div>
                        </td>
                        <td></td>
                    </tr>
                    @if($purchaseOrder->discount_percent > 0)
                        <tr>
                            <td colspan="{{ $footerColspan }}" class="px-4 py-2 text-right text-gray-600">Chiết khấu
                                ({{ $purchaseOrder->discount_percent }}%):</td>
                            <td class="px-4 py-2 text-right text-red-600">
                                <div>-{{ number_format($discountVnd, 2) }} $</div>
                            </td>
                            <td></td>
                        </tr>
                    @endif
                    @if($purchaseOrder->shipping_cost > 0)
                        <tr>
                            <td colspan="{{ $footerColspan }}" class="px-4 py-2 text-right text-gray-600">Phí vận chuyển:</td>
                            <td class="px-4 py-2 text-right">
                                <div class="font-medium text-gray-900">{{ number_format($shippingVnd, 2) }} $</div>
                            </td>
                            <td></td>
                        </tr>
                    @endif
                    @if($purchaseOrder->other_cost > 0)
                        <tr>
                            <td colspan="{{ $footerColspan }}" class="px-4 py-2 text-right text-gray-600">Chi phí khác:</td>
                            <td class="px-4 py-2 text-right">
                                <div class="font-medium text-gray-900">{{ number_format($otherVnd, 2) }} $</div>
                            </td>
                            <td></td>
                        </tr>
                    @endif
                    <tr class="border-t bg-gray-100">
                        <td colspan="{{ $footerColspan }}" class="px-4 py-3 text-right text-lg font-bold text-gray-800">Tổng cộng:</td>
                        <td class="px-4 py-3 text-right text-primary">
                            <div class="text-lg font-bold">{{ number_format($totalVnd, 2) }} $</div>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>


        {{-- Yêu cầu đặt hàng từ Sales --}}
        @php
            $orderRequests = collect();
            if ($purchaseOrder->sale && $purchaseOrder->sale->orderRequests) {
                $orderRequests = $orderRequests->merge($purchaseOrder->sale->orderRequests);
            }
            foreach ($purchaseOrder->items as $item) {
                if ($item->saleOrderRequestItem && $item->saleOrderRequestItem->saleOrderRequest) {
                    $orderRequests->push($item->saleOrderRequestItem->saleOrderRequest);
                }
            }
            $orderRequests = $orderRequests->unique('id');
        @endphp

        @if($orderRequests->count() > 0)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b bg-gradient-to-r from-teal-50 to-cyan-50">
                <h3 class="font-semibold flex items-center">
                    <i class="fas fa-clipboard-list mr-2 text-teal-600"></i>
                    Yêu cầu đặt hàng từ Sales
                    <span class="ml-2 text-sm font-normal text-teal-600">
                        ({{ $purchaseOrder->linked_so_codes }})
                    </span>
                </h3>
            </div>
            @foreach($orderRequests as $req)
            <div class="p-4 {{ !$loop->last ? 'border-b' : '' }}">
                <div class="flex items-center gap-3 mb-3">
                    <span class="px-2 py-1 text-xs font-bold rounded bg-teal-100 text-teal-800">{{ $req->code }}</span>
                    @if($req->sale)
                        <span class="text-sm font-semibold text-gray-700">
                            SO: <a href="{{ route('sales.show', $req->sale_id) }}" target="_blank" class="text-teal-600 hover:underline">{{ $req->sale->code }}</a>
                        </span>
                    @endif
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
                                <th colspan="2" class="px-2 py-1.5 text-center font-bold text-gray-800 border-b border-gray-300">Thông tin CQ</th>
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
                    <a href="{{ route('sales.order-request.attachment.download', [$req->sale_id, $att->id]) }}" 
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
            <input type="file" id="license-upload-{{ $item->id }}" name="license_files[]" multiple onchange="this.form.submit()">
        </form>
    @endforeach

    {{-- Hidden form for License Deletion --}}
    <form id="delete-license-form" action="" method="POST" style="display: none;">
        @csrf
        <input type="hidden" name="file_index" id="delete-license-file-index">
    </form>

    @push('scripts')
        <script>
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
            
            // Tính toán real-time Thành tiền (USD) khi nhập Giá mua thực tế
            function calculateItemTotal(itemId, unitPrice, quantity) {
                const price = parseFloat(unitPrice) || 0;
                const qty = parseFloat(quantity) || 0;
                const total = price * qty;
                
                const totalElement = document.getElementById(`item-total-${itemId}`);
                if (totalElement) {
                    totalElement.textContent = total.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            }
            
            function updateItemPrice(itemId, price) {
                fetch(`/purchase-orders/items/${itemId}/update-price`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ unit_price: parseFloat(price) })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (window.Toast) {
                            Toast.fire({ icon: 'success', title: 'Đã cập nhật giá mua' });
                        }
                        location.reload();
                    }
                })
                .catch(error => console.error('Error:', error));
            }
            function updateItemStatus(itemId, status) {
                // Nếu cancel → xác nhận trước
                if (status === 'cancelled') {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Hủy sản phẩm',
                            text: 'Sản phẩm sẽ bị xóa khỏi PO và trả về Gom đơn cần đặt. Tiếp tục?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: 'Xác nhận hủy',
                            cancelButtonText: 'Giữ lại',
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                doUpdateItemStatus(itemId, status);
                            } else {
                                location.reload(); // Reload to reset dropdown
                            }
                        });
                    } else if (confirm('Sản phẩm sẽ bị xóa khỏi PO và trả về Gom đơn cần đặt. Tiếp tục?')) {
                        doUpdateItemStatus(itemId, status);
                    } else {
                        location.reload();
                    }
                    return;
                }
                doUpdateItemStatus(itemId, status);
            }

            function doUpdateItemStatus(itemId, status) {
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
                        if (data.po_status_updated) {
                            // PO status changed - reload to reflect
                            if (window.Toast) {
                                Toast.fire({ icon: 'success', title: data.message || 'Đã cập nhật!' });
                            }
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            if (window.Toast) {
                                Toast.fire({ icon: 'success', title: data.message || 'Đã cập nhật trạng thái' });
                            }
                            // Reload for cancelled items since they get deleted
                            if (status === 'cancelled') {
                                setTimeout(() => location.reload(), 800);
                            }
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
            }

            function confirmDeleteLicenseFile(itemId, fileIndex, fileName) {
                const deleteUrl = `/purchase-orders/items/${itemId}/delete-license`;
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Xác nhận xóa file?',
                        text: `Bạn có chắc chắn muốn xóa file license "${fileName}" không?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Đồng ý xóa!',
                        cancelButtonText: 'Hủy bỏ',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const form = document.getElementById('delete-license-form');
                            form.action = deleteUrl;
                            document.getElementById('delete-license-file-index').value = fileIndex;
                            form.submit();
                        }
                    });
                } else {
                    if (confirm(`Bạn có chắc chắn muốn xóa file license "${fileName}" không?`)) {
                        const form = document.getElementById('delete-license-form');
                        form.action = deleteUrl;
                        document.getElementById('delete-license-file-index').value = fileIndex;
                        form.submit();
                    }
                }
            }

            function toggleMobileMoreMenu() {
                const menu = document.getElementById('mobile-more-menu');
                const card = menu.querySelector('.mobile-menu-card');
                if (menu.classList.contains('hidden')) {
                    menu.classList.remove('hidden');
                    setTimeout(() => {
                        menu.classList.remove('opacity-0');
                        card.classList.remove('translate-y-full');
                    }, 50);
                } else {
                    menu.classList.add('opacity-0');
                    card.classList.add('translate-y-full');
                    setTimeout(() => {
                        menu.classList.add('hidden');
                    }, 300);
                }
            }
        </script>
    @endpush

    <!-- Bottom Action Bar for Mobile/Tablet -->
    <div class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-gray-100 px-4 py-3 shadow-[0_-8px_30px_rgb(0,0,0,0.08)] md:hidden flex items-center justify-between gap-3 rounded-t-2xl">
        <!-- Back button -->
        <a href="{{ url()->previous() }}" class="flex items-center justify-center w-10 h-10 rounded-xl bg-gray-50 text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>

        <div class="flex-1 flex items-center justify-end gap-2">
            @if(in_array($purchaseOrder->status, ['draft', 'pending_approval']))
                <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}"
                    class="flex-1 max-w-[100px] text-center px-3 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold rounded-xl text-xs transition-all shadow-sm">
                    <i class="fas fa-edit mr-1"></i>Sửa
                </a>
            @endif
            @if($purchaseOrder->status == 'draft')
                <button type="button" onclick="document.getElementById('submit-approval-form')?.querySelector('button[type=submit]')?.click()"
                    class="flex-1 px-4 py-2 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white font-semibold rounded-xl text-xs transition-all shadow-md">
                    <i class="fas fa-paper-plane mr-1"></i>Gửi duyệt
                </button>
            @endif

            @if($purchaseOrder->status == 'pending_approval')
                <button type="button" onclick="document.getElementById('reject-form')?.querySelector('button[type=submit]')?.click()"
                    class="flex-1 max-w-[100px] px-3 py-2 bg-rose-50 border border-rose-200 text-rose-600 font-semibold rounded-xl text-xs transition-all hover:bg-rose-100">
                    <i class="fas fa-times mr-1"></i>Từ chối
                </button>
                <button type="button" onclick="document.getElementById('approve-form')?.querySelector('button[type=submit]')?.click()"
                    class="flex-1 px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-xl text-xs transition-all shadow-md">
                    <i class="fas fa-check mr-1"></i>Xác nhận Đặt
                </button>
            @endif

            @if($hasOrderedItems)
                <button type="button" onclick="document.getElementById('ship-form')?.querySelector('button[type=submit]')?.click()"
                    class="flex-1 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-xl text-xs transition-all shadow-md">
                    <i class="fas fa-truck-moving mr-1"></i>Đang về
                </button>
            @endif

            @if(in_array($purchaseOrder->status, ['approved', 'shipping', 'partial_received']))
                @if($purchaseOrder->is_hold)
                    <button type="button" onclick="document.getElementById('hold-form')?.querySelector('button[type=submit]')?.click()"
                        class="flex-1 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-xl text-xs transition-all">
                        <i class="fas fa-play mr-1"></i>Gỡ Hold
                    </button>
                @else
                    <button type="button" onclick="document.getElementById('hold-form')?.querySelector('button[type=button]')?.click()"
                        class="flex-1 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-xl text-xs transition-all">
                        <i class="fas fa-pause mr-1"></i>Hold
                    </button>
                @endif
            @endif

            @if($hasShippingItems)
                <button type="button" onclick="document.getElementById('confirm-received-form')?.querySelector('button[type=submit]')?.click()"
                    class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl text-xs transition-all">
                    <i class="fas fa-box-open mr-1"></i>Nhận hàng
                </button>
            @endif
            
            <!-- More action trigger -->
            <button type="button" onclick="toggleMobileMoreMenu()"
                class="flex items-center justify-center w-10 h-10 rounded-xl bg-gray-50 text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                <i class="fas fa-ellipsis-v"></i>
            </button>
        </div>
    </div>

    <!-- Mobile More Menu Popup -->
    <div id="mobile-more-menu" class="fixed inset-0 z-50 hidden bg-black/40 backdrop-blur-sm transition-opacity duration-300" onclick="toggleMobileMoreMenu()">
        <div class="mobile-menu-card absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl p-6 space-y-4 shadow-[0_-10px_40px_rgba(0,0,0,0.12)] transform translate-y-full transition-transform duration-300 ease-out" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <h4 class="font-bold text-gray-800 text-sm">Hành động khác</h4>
                <button type="button" onclick="toggleMobileMoreMenu()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
            
            <div class="grid grid-cols-2 gap-3 pt-2">
                <a href="{{ route('purchase-orders.print', $purchaseOrder) }}" target="_blank"
                    class="flex flex-col items-center justify-center p-3 bg-gray-50 hover:bg-gray-100 rounded-2xl transition-colors text-gray-700">
                    <i class="fas fa-print text-lg text-blue-500 mb-1"></i>
                    <span class="text-[10px] font-semibold">In PO</span>
                </a>
                
                <a href="{{ route('purchase-orders.export-single', $purchaseOrder) }}"
                    class="flex flex-col items-center justify-center p-3 bg-gray-50 hover:bg-gray-100 rounded-2xl transition-colors text-gray-700">
                    <i class="fas fa-file-excel text-lg text-green-600 mb-1"></i>
                    <span class="text-[10px] font-semibold">Xuất Excel</span>
                </a>

                @if(!in_array($purchaseOrder->status, ['received', 'cancelled']))
                    <button type="button" onclick="toggleMobileMoreMenu(); document.getElementById('cancel-form')?.querySelector('button')?.click()"
                        class="flex flex-col items-center justify-center p-3 bg-gray-50 hover:bg-gray-100 rounded-2xl transition-colors text-gray-700">
                        <i class="fas fa-ban text-lg text-amber-500 mb-1"></i>
                        <span class="text-[10px] font-semibold">Hủy đơn</span>
                    </button>
                @endif

                @if(in_array($purchaseOrder->status, ['draft', 'cancelled']) && auth()->user()->can('delete', $purchaseOrder))
                    <button type="button" onclick="toggleMobileMoreMenu(); document.getElementById('destroy-form')?.querySelector('button')?.click()"
                        class="flex flex-col items-center justify-center p-3 bg-red-50 hover:bg-red-100 rounded-2xl transition-colors text-red-700">
                        <i class="fas fa-trash text-lg text-red-500 mb-1"></i>
                        <span class="text-[10px] font-semibold">Xóa PO</span>
                    </button>
                @endif
            </div>
        </div>
    </div>
@endsection