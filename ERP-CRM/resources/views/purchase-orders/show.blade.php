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

    <div class="max-w-5xl mx-auto space-y-6">
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
            <a href="{{ route('purchase-orders.index') }}" class="text-gray-600 hover:text-gray-800">
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
                            <i class="fas fa-paper-plane mr-2"></i> Gửi duyệt
                        </button>
                    </form>
                @endif
                @if($purchaseOrder->status == 'pending_approval')
                    <form action="{{ route('purchase-orders.approve', $purchaseOrder) }}" method="POST" class="inline"
                        id="approve-form">
                        @csrf
                        <button type="submit"
                            class="approve-btn px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-200 transform hover:scale-105 hover:shadow-lg">
                            <i class="fas fa-check mr-2"></i> Duyệt
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
                    <form action="{{ route('purchase-orders.send', $purchaseOrder) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-all duration-200 transform hover:scale-105">
                            <i class="fas fa-envelope mr-2"></i> Gửi cho NCC
                        </button>
                    </form>
                @endif
                @if($purchaseOrder->status == 'sent')
                    <form action="{{ route('purchase-orders.confirm', $purchaseOrder) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-all duration-200 transform hover:scale-105">
                            <i class="fas fa-handshake mr-2"></i> NCC đã xác nhận
                        </button>
                    </form>
                @endif
                @if(in_array($purchaseOrder->status, ['confirmed', 'shipping']))
                    <form action="{{ route('purchase-orders.receive', $purchaseOrder) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-200 transform hover:scale-105">
                            <i class="fas fa-box mr-2"></i> Xác nhận nhận hàng
                        </button>
                    </form>
                @endif
                <a href="{{ route('purchase-orders.print', $purchaseOrder) }}"
                    class="px-4 py-2 border rounded-lg hover:bg-gray-50 transition-all duration-200" target="_blank">
                    <i class="fas fa-print mr-2"></i> In PO
                </a>
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
                                    {{ $expectedMaxDate->format('d/m/Y') }}</p>
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
                            <p class="text-xl font-bold text-primary">{{ number_format($purchaseOrder->total) }}đ</p>
                            <p class="text-xs text-gray-400">{{ $purchaseOrder->items->count() }} sản phẩm</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Status Timeline -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                @php
                    $statuses = ['draft' => 'Nháp', 'pending_approval' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'sent' => 'Đã gửi NCC', 'confirmed' => 'NCC xác nhận', 'received' => 'Đã nhận hàng'];
                    $currentIndex = array_search($purchaseOrder->status, array_keys($statuses));
                @endphp
                @foreach($statuses as $key => $label)
                    @php $index = array_search($key, array_keys($statuses)); @endphp
                    <div class="flex flex-col items-center {{ $index <= $currentIndex ? 'text-primary' : 'text-gray-400' }}">
                        <div
                            class="w-8 h-8 rounded-full flex items-center justify-center {{ $index <= $currentIndex ? 'bg-primary text-white' : 'bg-gray-200' }}">
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
                @if($purchaseOrder->sent_at)
                    <div>
                        <p class="text-sm text-gray-500">Ngày gửi NCC</p>
                        <p class="font-medium">{{ $purchaseOrder->sent_at->format('d/m/Y H:i') }}</p>
                    </div>
                @endif
                @if($purchaseOrder->actual_delivery)
                    <div>
                        <p class="text-sm text-gray-500">Ngày nhận hàng</p>
                        <p class="font-medium text-green-600">{{ $purchaseOrder->actual_delivery->format('d/m/Y') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Items -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b bg-gray-50">
                <h3 class="font-semibold">Chi tiết sản phẩm</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Đơn giá</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thành tiền</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($purchaseOrder->items as $index => $item)
                        <tr>
                            <td class="px-4 py-3">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-medium">{{ $item->product_name }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($item->quantity) }} {{ $item->unit }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($item->unit_price) }}đ</td>
                            <td class="px-4 py-3 text-right font-medium">{{ number_format($item->total) }}đ</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-right text-gray-600">Tổng tiền hàng:</td>
                        <td class="px-4 py-2 text-right font-medium">{{ number_format($purchaseOrder->subtotal) }}đ</td>
                    </tr>
                    @if($purchaseOrder->discount_percent > 0)
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right text-gray-600">Chiết khấu
                                ({{ $purchaseOrder->discount_percent }}%):</td>
                            <td class="px-4 py-2 text-right text-red-600">-{{ number_format($purchaseOrder->discount_amount) }}đ
                            </td>
                        </tr>
                    @endif
                    @if($purchaseOrder->shipping_cost > 0)
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right text-gray-600">Phí vận chuyển:</td>
                            <td class="px-4 py-2 text-right">{{ number_format($purchaseOrder->shipping_cost) }}đ</td>
                        </tr>
                    @endif
                    @if($purchaseOrder->other_cost > 0)
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right text-gray-600">Chi phí khác:</td>
                            <td class="px-4 py-2 text-right">{{ number_format($purchaseOrder->other_cost) }}đ</td>
                        </tr>
                    @endif
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-right text-gray-600">VAT ({{ $purchaseOrder->vat_percent }}%):
                        </td>
                        <td class="px-4 py-2 text-right">{{ number_format($purchaseOrder->vat_amount) }}đ</td>
                    </tr>
                    <tr class="font-bold">
                        <td colspan="4" class="px-4 py-3 text-right text-lg">Tổng cộng:</td>
                        <td class="px-4 py-3 text-right text-lg text-primary">{{ number_format($purchaseOrder->total) }}đ
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if($purchaseOrder->note)
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold mb-2">Ghi chú</h3>
                <p class="text-gray-700">{{ $purchaseOrder->note }}</p>
            </div>
        @endif

        <!-- Cost Breakdown / P&L Section -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b bg-gradient-to-r from-green-50 to-blue-50">
                <h3 class="font-semibold flex items-center">
                    <i class="fas fa-chart-pie mr-2 text-green-600"></i>
                    Chi phí đơn hàng (P/L Reference)
                </h3>
                <p class="text-sm text-gray-500 mt-1">Tổng quan chi phí để tính toán lợi nhuận khi bán</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Cost Summary -->
                    <div class="space-y-3">
                        <h4 class="font-medium text-gray-800 border-b pb-2">Cơ cấu chi phí</h4>
                        
                        <div class="flex justify-between items-center py-2 border-b border-dashed">
                            <span class="text-gray-600"><i class="fas fa-box mr-2 text-blue-500"></i>Giá vốn hàng hóa</span>
                            <span class="font-medium">{{ number_format($purchaseOrder->subtotal) }}đ</span>
                        </div>
                        
                        @if($purchaseOrder->discount_amount > 0)
                        <div class="flex justify-between items-center py-2 border-b border-dashed">
                            <span class="text-gray-600"><i class="fas fa-percentage mr-2 text-green-500"></i>Chiết khấu NCC</span>
                            <span class="font-medium text-green-600">-{{ number_format($purchaseOrder->discount_amount) }}đ</span>
                        </div>
                        @endif
                        
                        <div class="flex justify-between items-center py-2 border-b border-dashed">
                            <span class="text-gray-600"><i class="fas fa-truck mr-2 text-orange-500"></i>Phí vận chuyển</span>
                            <span class="font-medium">{{ number_format($purchaseOrder->shipping_cost) }}đ</span>
                        </div>
                        
                        <div class="flex justify-between items-center py-2 border-b border-dashed">
                            <span class="text-gray-600"><i class="fas fa-receipt mr-2 text-purple-500"></i>Chi phí khác</span>
                            <span class="font-medium">{{ number_format($purchaseOrder->other_cost) }}đ</span>
                        </div>
                        
                        <div class="flex justify-between items-center py-2 border-b border-dashed">
                            <span class="text-gray-600"><i class="fas fa-file-invoice mr-2 text-red-500"></i>Thuế VAT ({{ $purchaseOrder->vat_percent }}%)</span>
                            <span class="font-medium">{{ number_format($purchaseOrder->vat_amount) }}đ</span>
                        </div>
                        
                        <div class="flex justify-between items-center py-3 bg-blue-50 rounded-lg px-3 mt-2">
                            <span class="font-bold text-gray-800"><i class="fas fa-calculator mr-2 text-blue-600"></i>TỔNG CHI PHÍ MUA</span>
                            <span class="font-bold text-xl text-blue-600">{{ number_format($purchaseOrder->total) }}đ</span>
                        </div>
                    </div>
                    
                    <!-- Per Item Cost -->
                    <div class="space-y-3">
                        <h4 class="font-medium text-gray-800 border-b pb-2">Giá vốn trung bình/sản phẩm</h4>
                        
                        @php
                            $totalQty = $purchaseOrder->items->sum('quantity');
                            $avgCostPerItem = $totalQty > 0 ? $purchaseOrder->total / $totalQty : 0;
                            $totalCostBeforeVat = $purchaseOrder->subtotal - $purchaseOrder->discount_amount + $purchaseOrder->shipping_cost + $purchaseOrder->other_cost;
                        @endphp
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-gray-600">Tổng số lượng:</span>
                                <span class="font-medium">{{ number_format($totalQty) }} sản phẩm</span>
                            </div>
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-gray-600">Giá vốn trước VAT/sp:</span>
                                <span class="font-medium">{{ number_format($totalQty > 0 ? $totalCostBeforeVat / $totalQty : 0) }}đ</span>
                            </div>
                            <div class="flex justify-between items-center py-2 px-3 bg-orange-100 rounded-lg">
                                <span class="font-semibold text-orange-800">Giá vốn sau VAT/sp:</span>
                                <span class="font-bold text-orange-600">{{ number_format($avgCostPerItem) }}đ</span>
                            </div>
                        </div>
                        
                        <div class="mt-4 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                            <h5 class="font-medium text-yellow-800 mb-2">
                                <i class="fas fa-lightbulb mr-1"></i> Gợi ý định giá bán
                            </h5>
                            <div class="grid grid-cols-3 gap-2 text-sm">
                                <div class="text-center p-2 bg-white rounded">
                                    <p class="text-gray-500">Margin 20%</p>
                                    <p class="font-bold text-green-600">{{ number_format($avgCostPerItem / 0.8) }}đ</p>
                                </div>
                                <div class="text-center p-2 bg-white rounded">
                                    <p class="text-gray-500">Margin 30%</p>
                                    <p class="font-bold text-green-600">{{ number_format($avgCostPerItem / 0.7) }}đ</p>
                                </div>
                                <div class="text-center p-2 bg-white rounded">
                                    <p class="text-gray-500">Margin 40%</p>
                                    <p class="font-bold text-green-600">{{ number_format($avgCostPerItem / 0.6) }}đ</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

// Add click animation to approve button
document.querySelectorAll('.approve-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Đang xử lý...';
        this.disabled = true;
    });
});

// Close overlay on click
document.getElementById('approval-success')?.addEventListener('click', function() {
    const card = document.getElementById('success-card');
    card.classList.remove('scale-100');
    card.classList.add('scale-0');
    setTimeout(() => {
        this.classList.add('hidden');
        this.classList.remove('flex');
    }, 500);
});
</script>
@endpush
@endsection