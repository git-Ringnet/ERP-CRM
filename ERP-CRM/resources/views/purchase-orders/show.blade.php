@extends('layouts.app')

@section('title', 'Chi tiết đơn mua hàng')
@section('page-title', 'Chi tiết đơn mua hàng: ' . $purchaseOrder->code)

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Header Actions -->
    <div class="flex justify-between items-center">
        <a href="{{ route('purchase-orders.index') }}" class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại
        </a>
        <div class="flex space-x-2">
            @if($purchaseOrder->status == 'draft')
                <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                    <i class="fas fa-edit mr-2"></i> Sửa
                </a>
                <form action="{{ route('purchase-orders.submit-approval', $purchaseOrder) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                        <i class="fas fa-paper-plane mr-2"></i> Gửi duyệt
                    </button>
                </form>
            @endif
            @if($purchaseOrder->status == 'pending_approval')
                <form action="{{ route('purchase-orders.approve', $purchaseOrder) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fas fa-check mr-2"></i> Duyệt
                    </button>
                </form>
                <form action="{{ route('purchase-orders.reject', $purchaseOrder) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700" onclick="return confirm('Từ chối đơn hàng này?')">
                        <i class="fas fa-times mr-2"></i> Từ chối
                    </button>
                </form>
            @endif
            @if($purchaseOrder->status == 'approved')
                <form action="{{ route('purchase-orders.send', $purchaseOrder) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        <i class="fas fa-envelope mr-2"></i> Gửi cho NCC
                    </button>
                </form>
            @endif
            @if($purchaseOrder->status == 'sent')
                <form action="{{ route('purchase-orders.confirm', $purchaseOrder) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                        <i class="fas fa-handshake mr-2"></i> NCC đã xác nhận
                    </button>
                </form>
            @endif
            @if(in_array($purchaseOrder->status, ['confirmed', 'shipping']))
                <form action="{{ route('purchase-orders.receive', $purchaseOrder) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fas fa-box mr-2"></i> Xác nhận nhận hàng
                    </button>
                </form>
            @endif
            <a href="{{ route('purchase-orders.print', $purchaseOrder) }}" class="px-4 py-2 border rounded-lg hover:bg-gray-50" target="_blank">
                <i class="fas fa-print mr-2"></i> In PO
            </a>
        </div>
    </div>

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
                    <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $index <= $currentIndex ? 'bg-primary text-white' : 'bg-gray-200' }}">
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
                <p class="font-medium">{{ $purchaseOrder->expected_delivery ? $purchaseOrder->expected_delivery->format('d/m/Y') : '-' }}</p>
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
                    <td colspan="4" class="px-4 py-2 text-right text-gray-600">Chiết khấu ({{ $purchaseOrder->discount_percent }}%):</td>
                    <td class="px-4 py-2 text-right text-red-600">-{{ number_format($purchaseOrder->discount_amount) }}đ</td>
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
                    <td colspan="4" class="px-4 py-2 text-right text-gray-600">VAT ({{ $purchaseOrder->vat_percent }}%):</td>
                    <td class="px-4 py-2 text-right">{{ number_format($purchaseOrder->vat_amount) }}đ</td>
                </tr>
                <tr class="font-bold">
                    <td colspan="4" class="px-4 py-3 text-right text-lg">Tổng cộng:</td>
                    <td class="px-4 py-3 text-right text-lg text-primary">{{ number_format($purchaseOrder->total) }}đ</td>
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
</div>
@endsection
