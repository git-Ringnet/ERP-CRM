@extends('layouts.app')

@section('title', 'Chi tiết đơn hàng')
@section('page-title', 'Chi tiết đơn hàng: ' . $sale->code)

@section('content')
<div class="space-y-4">
    <!-- Actions -->
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('sales.index') }}" 
           class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại
        </a>
        <a href="{{ route('sales.edit', $sale->id) }}" 
           class="inline-flex items-center px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors">
            <i class="fas fa-edit mr-2"></i> Sửa
        </a>
        <a href="{{ route('sales.pdf', $sale->id) }}" target="_blank"
           class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
            <i class="fas fa-file-pdf mr-2"></i> Xuất hóa đơn
        </a>
        <form action="{{ route('sales.email', $sale->id) }}" method="POST" class="inline">
            @csrf
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                <i class="fas fa-envelope mr-2"></i> Gửi Email
            </button>
        </form>
        @if($sale->debt_amount > 0)
        <button onclick="openPaymentModal()" 
                class="inline-flex items-center px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors">
            <i class="fas fa-money-bill mr-2"></i> Ghi nhận thanh toán
        </button>
        @endif
    </div>

    <!-- Sale Info -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Thông tin đơn hàng</h3>
                <dl class="space-y-2">
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Mã đơn:</dt>
                        <dd class="font-medium text-gray-900">{{ $sale->code }}</dd>
                    </div>
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Loại:</dt>
                        <dd>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $sale->type == 'project' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $sale->type_label }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Ngày tạo:</dt>
                        <dd class="text-gray-900">{{ $sale->date->format('d/m/Y') }}</dd>
                    </div>
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Trạng thái:</dt>
                        <dd>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $sale->status_color }}">
                                {{ $sale->status_label }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
            
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Thông tin khách hàng</h3>
                <dl class="space-y-2">
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Khách hàng:</dt>
                        <dd class="font-medium text-gray-900">{{ $sale->customer_name }}</dd>
                    </div>
                    @if($sale->customer)
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Email:</dt>
                        <dd class="text-gray-900">{{ $sale->customer->email }}</dd>
                    </div>
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Điện thoại:</dt>
                        <dd class="text-gray-900">{{ $sale->customer->phone }}</dd>
                    </div>
                    @endif
                    @if($sale->delivery_address)
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Địa chỉ giao:</dt>
                        <dd class="text-gray-900">{{ $sale->delivery_address }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <!-- Products -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900">Chi tiết sản phẩm</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Đơn giá</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thành tiền</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($sale->items as $index => $item)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $index + 1 }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $item->product_name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($item->quantity) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($item->price) }} đ</td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right font-medium">{{ number_format($item->total) }} đ</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-sm font-medium text-gray-700 text-right">Tổng tiền hàng:</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 text-right">{{ number_format($sale->subtotal) }} đ</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-sm font-medium text-gray-700 text-right">Chiết khấu ({{ $sale->discount }}%):</td>
                        <td class="px-4 py-3 text-sm font-medium text-red-600 text-right">-{{ number_format($sale->subtotal * $sale->discount / 100) }} đ</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-sm font-medium text-gray-700 text-right">VAT ({{ $sale->vat }}%):</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 text-right">{{ number_format(($sale->subtotal - $sale->subtotal * $sale->discount / 100) * $sale->vat / 100) }} đ</td>
                    </tr>
                    <tr class="border-t-2 border-gray-300">
                        <td colspan="4" class="px-4 py-3 text-base font-bold text-gray-900 text-right">Tổng cộng:</td>
                        <td class="px-4 py-3 text-base font-bold text-primary text-right">{{ number_format($sale->total) }} đ</td>
                    </tr>
                    @if($sale->cost > 0)
                    <tr class="bg-yellow-50">
                        <td colspan="4" class="px-4 py-3 text-sm font-medium text-gray-700 text-right">Chi phí bán hàng:</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 text-right">{{ number_format($sale->cost) }} đ</td>
                    </tr>
                    <tr class="bg-green-50">
                        <td colspan="4" class="px-4 py-3 text-sm font-bold text-gray-900 text-right">Lợi nhuận (Margin):</td>
                        <td class="px-4 py-3 text-sm font-bold text-green-700 text-right">{{ number_format($sale->margin) }} đ ({{ number_format($sale->margin_percent, 2) }}%)</td>
                    </tr>
                    @endif
                    @if($sale->paid_amount > 0 || $sale->debt_amount > 0)
                    <tr class="bg-blue-50 border-t">
                        <td colspan="4" class="px-4 py-3 text-sm font-medium text-gray-700 text-right">Đã thanh toán:</td>
                        <td class="px-4 py-3 text-sm font-medium text-green-700 text-right">{{ number_format($sale->paid_amount) }} đ</td>
                    </tr>
                    <tr class="bg-red-50">
                        <td colspan="4" class="px-4 py-3 text-sm font-bold text-gray-900 text-right">Công nợ còn lại:</td>
                        <td class="px-4 py-3 text-sm font-bold text-red-700 text-right">{{ number_format($sale->debt_amount) }} đ</td>
                    </tr>
                    @endif
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Note -->
    @if($sale->note)
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Ghi chú</h3>
        <p class="text-gray-700">{{ $sale->note }}</p>
    </div>
    @endif

    <!-- Payment Modal -->
    <div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Ghi nhận thanh toán</h3>
                <form action="{{ route('sales.payment', $sale->id) }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền thanh toán <span class="text-red-500">*</span></label>
                            <input type="number" name="amount" required min="0" max="{{ $sale->debt_amount }}" step="0.01"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            <p class="text-xs text-gray-500 mt-1">Công nợ hiện tại: {{ number_format($sale->debt_amount) }} đ</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ngày thanh toán <span class="text-red-500">*</span></label>
                            <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phương thức <span class="text-red-500">*</span></label>
                            <select name="payment_method" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="cash">Tiền mặt</option>
                                <option value="bank_transfer">Chuyển khoản</option>
                                <option value="credit_card">Thẻ tín dụng</option>
                                <option value="other">Khác</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                            <textarea name="note" rows="2"
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                        </div>
                    </div>
                    <div class="flex gap-2 mt-6">
                        <button type="button" onclick="closePaymentModal()"
                                class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                            Hủy
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                            Xác nhận
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openPaymentModal() {
    document.getElementById('paymentModal').classList.remove('hidden');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('paymentModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closePaymentModal();
    }
});
</script>
@endpush
@endsection
