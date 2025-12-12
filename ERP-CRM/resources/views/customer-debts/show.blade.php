@extends('layouts.app')

@section('title', 'Chi tiết công nợ - ' . $customer->name)
@section('page-title', 'Chi tiết công nợ khách hàng')

@section('content')
<div class="space-y-6">
    <!-- Back Button & Customer Info -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('customer-debts.index') }}" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $customer->name }}</h2>
                <p class="text-sm text-gray-500">{{ $customer->code }} | {{ $customer->phone }}</p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('customers.show', $customer) }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">
                <i class="fas fa-user mr-1"></i> Xem KH
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Tổng mua hàng</p>
            <p class="text-xl font-bold text-gray-800">{{ number_format($summary['total_sales'], 0, ',', '.') }}đ</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Đã thanh toán</p>
            <p class="text-xl font-bold text-green-600">{{ number_format($summary['total_paid'], 0, ',', '.') }}đ</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Công nợ còn lại</p>
            <p class="text-xl font-bold text-red-600">{{ number_format($summary['total_debt'], 0, ',', '.') }}đ</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Đơn chưa thanh toán</p>
            <p class="text-xl font-bold text-orange-600">{{ $summary['unpaid_orders'] }}</p>
        </div>
    </div>

    <!-- Customer Debt Limit Info -->
    @if($customer->debt_limit > 0)
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Hạn mức công nợ</p>
                <p class="text-lg font-semibold">{{ number_format($customer->debt_limit, 0, ',', '.') }}đ</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Số ngày nợ cho phép</p>
                <p class="text-lg font-semibold">{{ $customer->debt_days ?? 30 }} ngày</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Còn lại hạn mức</p>
                @php $remaining = $customer->debt_limit - $summary['total_debt']; @endphp
                <p class="text-lg font-semibold {{ $remaining < 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ number_format($remaining, 0, ',', '.') }}đ
                </p>
            </div>
        </div>
        @if($remaining < 0)
        <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-700">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                Khách hàng đã vượt hạn mức công nợ {{ number_format(abs($remaining), 0, ',', '.') }}đ
            </p>
        </div>
        @endif
    </div>
    @endif

    <!-- Sales with Debt -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Danh sách đơn hàng</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã đơn</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng tiền</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Đã TT</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Còn nợ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($sales as $sale)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-primary">
                            <a href="{{ route('sales.show', $sale) }}">{{ $sale->code }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $sale->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900">{{ number_format($sale->total, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-green-600">{{ number_format($sale->paid_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium {{ $sale->debt_amount > 0 ? 'text-red-600' : 'text-gray-600' }}">
                            {{ number_format($sale->debt_amount, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $sale->payment_status_color }}">
                                {{ $sale->payment_status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($sale->debt_amount > 0)
                            <button onclick="openPaymentModal({{ $sale->id }}, '{{ $sale->code }}', {{ $sale->debt_amount }})" 
                                    class="text-green-600 hover:text-green-800" title="Ghi nhận thanh toán">
                                <i class="fas fa-money-bill-wave"></i>
                            </button>
                            @endif
                            <a href="{{ route('sales.show', $sale) }}" class="text-primary hover:text-primary/80 ml-2" title="Xem đơn">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">Không có đơn hàng</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>


    <!-- Payment History -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Lịch sử thanh toán</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày TT</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã đơn</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Số tiền</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phương thức</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số tham chiếu</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ghi chú</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($paymentHistories as $payment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $payment->payment_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-primary">
                            <a href="{{ route('sales.show', $payment->sale_id) }}">{{ $payment->sale->code }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-green-600 font-medium">
                            +{{ number_format($payment->amount, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $payment->payment_method_label }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $payment->reference_number ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $payment->note ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <form action="{{ route('customer-debts.delete-payment', $payment) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Bạn có chắc muốn xóa bản ghi thanh toán này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">Chưa có lịch sử thanh toán</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Ghi nhận thanh toán</h3>
            <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="paymentForm" method="POST">
            @csrf
            <div class="p-6 space-y-4">
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-sm text-gray-500">Đơn hàng: <span id="modalSaleCode" class="font-medium text-gray-800"></span></p>
                    <p class="text-sm text-gray-500">Công nợ còn lại: <span id="modalDebtAmount" class="font-medium text-red-600"></span></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền thanh toán <span class="text-red-500">*</span></label>
                    <input type="number" name="amount" id="paymentAmount" required min="1" step="1"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phương thức <span class="text-red-500">*</span></label>
                    <select name="payment_method" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="cash">Tiền mặt</option>
                        <option value="bank_transfer">Chuyển khoản</option>
                        <option value="card">Thẻ</option>
                        <option value="other">Khác</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ngày thanh toán <span class="text-red-500">*</span></label>
                    <input type="date" name="payment_date" required value="{{ date('Y-m-d') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Số tham chiếu/chứng từ</label>
                    <input type="text" name="reference_number" maxlength="100"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                    <textarea name="note" rows="2" maxlength="500"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <button type="button" onclick="closePaymentModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Hủy
                </button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                    <i class="fas fa-save mr-1"></i> Lưu
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openPaymentModal(saleId, saleCode, debtAmount) {
    document.getElementById('paymentForm').action = `/customer-debts/payment/${saleId}`;
    document.getElementById('modalSaleCode').textContent = saleCode;
    document.getElementById('modalDebtAmount').textContent = new Intl.NumberFormat('vi-VN').format(debtAmount) + 'đ';
    document.getElementById('paymentAmount').max = debtAmount;
    document.getElementById('paymentAmount').value = debtAmount;
    document.getElementById('paymentModal').classList.remove('hidden');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

// Close modal on outside click
document.getElementById('paymentModal').addEventListener('click', function(e) {
    if (e.target === this) closePaymentModal();
});
</script>
@endpush
@endsection
