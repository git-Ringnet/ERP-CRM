@extends('layouts.app')

@section('title', 'Chi tiết công nợ NCC - ' . $supplier->name)
@section('page-title', 'Chi tiết công nợ NCC')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <div class="flex items-center text-sm text-gray-500 mb-2">
                    <a href="{{ route('supplier-debts.index') }}" class="hover:text-blue-600">Công nợ NCC</a>
                    <i class="fas fa-chevron-right mx-2 text-xs"></i>
                    <span class="text-gray-800">{{ $supplier->name }}</span>
                </div>
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-truck text-emerald-600 mr-2"></i>{{ $supplier->name }}
                    <span class="text-sm font-normal text-gray-500 ml-2">({{ $supplier->code }})</span>
                </h2>
                @if($supplier->tax_code)
                    <p class="text-sm text-gray-500 mt-1">MST: {{ $supplier->tax_code }} | ĐK thanh toán: {{ $supplier->payment_terms ?? 'N/A' }}</p>
                @endif
            </div>
            <div class="flex gap-2">
                <a href="{{ route('supplier-debts.statement', $supplier) }}"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    <i class="fas fa-file-alt mr-2"></i> Sao kê
                </a>
                <a href="{{ route('supplier-debts.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-arrow-left mr-2"></i> Quay lại
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Tổng mua</p>
            <p class="text-xl font-bold text-gray-800">{{ number_format($summary['total_purchases'], 0, ',', '.') }}đ</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Đã thanh toán</p>
            <p class="text-xl font-bold text-green-600">{{ number_format($summary['total_paid'], 0, ',', '.') }}đ</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Còn nợ</p>
            <p class="text-xl font-bold text-red-600">{{ number_format($summary['total_debt'], 0, ',', '.') }}đ</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">PO chưa thanh toán</p>
            <p class="text-xl font-bold text-orange-600">{{ $summary['unpaid_orders'] }}</p>
        </div>
    </div>

    <!-- Purchase Orders Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-file-contract text-blue-500 mr-2"></i>Đơn mua hàng
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã PO</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày đặt</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng tiền</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Đã TT</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Còn nợ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">TT thanh toán</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($purchaseOrders as $po)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium">
                            <a href="{{ route('purchase-orders.show', $po) }}" class="text-blue-600 hover:underline">{{ $po->code }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $po->order_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($po->total, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-green-600">{{ number_format($po->paid_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium {{ $po->debt_amount > 0 ? 'text-red-600' : 'text-gray-500' }}">
                            {{ number_format($po->debt_amount, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $po->payment_status_color }}">
                                {{ $po->payment_status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            @if($po->debt_amount > 0)
                            <button onclick="openPaymentModal({{ $po->id }}, '{{ $po->code }}', {{ $po->debt_amount }}, {{ $po->currency_id ?? 'null' }}, {{ $po->exchange_rate ?? 'null' }})"
                                class="text-emerald-600 hover:text-emerald-800" title="Ghi nhận thanh toán">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                            @else
                            <span class="text-green-500"><i class="fas fa-check-circle"></i></span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">Không có đơn mua hàng</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payment History Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-history text-purple-500 mr-2"></i>Lịch sử thanh toán
            </h3>
        </div>
        @if($paymentHistories->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">PO</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Số tiền (VND)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ngoại tệ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tỷ giá</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phương thức</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã TK</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Xóa</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($paymentHistories as $payment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $payment->payment_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm">
                            <a href="{{ route('purchase-orders.show', $payment->purchase_order_id) }}" class="text-blue-600 hover:underline">
                                {{ $payment->purchaseOrder->code }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-green-600">{{ number_format($payment->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right">
                            @if($payment->currency !== 'VND' && $payment->amount_foreign)
                                {{ number_format($payment->amount_foreign, 2, ',', '.') }} {{ $payment->currency }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            @if($payment->currency !== 'VND')
                                {{ number_format($payment->exchange_rate, 0, ',', '.') }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $payment->payment_method_label }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $payment->reference_number ?: '-' }}</td>
                        <td class="px-4 py-3 text-sm text-center">
                            <form method="POST" action="{{ route('supplier-debts.delete-payment', $payment) }}"
                                onsubmit="return confirm('Xác nhận xóa thanh toán này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700" title="Xóa">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-inbox text-3xl mb-2"></i>
            <p>Chưa có lịch sử thanh toán</p>
        </div>
        @endif
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-credit-card text-emerald-600 mr-2"></i>Ghi nhận thanh toán NCC
                </h3>
                <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <p class="text-sm text-gray-500 mt-1">PO: <span id="modalPoCode" class="font-medium text-gray-800"></span></p>
        </div>
        <form id="paymentForm" method="POST">
            @csrf
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Loại tiền</label>
                        <select name="currency_id" id="paymentCurrency" onchange="handleCurrencyChange()"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            @foreach($currencies as $currency)
                                <option value="{{ $currency->id }}" data-code="{{ $currency->code }}" {{ $currency->id == $baseCurrencyId ? 'selected' : '' }}>
                                    {{ $currency->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền *</label>
                        <input type="number" name="amount" step="0.01" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="0">
                    </div>
                </div>
                <div id="exchangeRateRow" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tỷ giá (1 Ngoại tệ = ? VND)</label>
                    <input type="number" name="exchange_rate" id="paymentExchangeRate" step="0.000001" value="1"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Tỷ giá...">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phương thức *</label>
                        <select name="payment_method" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="bank_transfer">Chuyển khoản</option>
                            <option value="cash">Tiền mặt</option>
                            <option value="card">Thẻ</option>
                            <option value="other">Khác</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ngày thanh toán *</label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mã tham chiếu</label>
                    <input type="text" name="reference_number" placeholder="Mã giao dịch NH..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                    <textarea name="note" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"
                        placeholder="Ghi chú nếu có..."></textarea>
                </div>
                <p class="text-sm text-gray-500">Còn nợ: <span id="modalDebt" class="font-bold text-red-600"></span></p>
            </div>
            <div class="p-6 border-t border-gray-200 flex justify-end gap-2">
                <button type="button" onclick="closePaymentModal()"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Hủy</button>
                <button type="submit"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                    <i class="fas fa-check mr-1"></i> Ghi nhận
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openPaymentModal(poId, poCode, debtAmount, poCurrencyId, poExchangeRate) {
    document.getElementById('paymentModal').classList.remove('hidden');
    document.getElementById('modalPoCode').textContent = poCode;
    document.getElementById('modalDebt').textContent = new Intl.NumberFormat('vi-VN').format(debtAmount) + 'đ';
    document.getElementById('paymentForm').action = '/supplier-debts/' + poId + '/payment';
    
    // Set default currency to PO currency
    const currencySelect = document.getElementById('paymentCurrency');
    if (poCurrencyId) {
        currencySelect.value = poCurrencyId;
    } else {
        currencySelect.value = @json($baseCurrencyId);
    }
    
    document.getElementById('paymentExchangeRate').value = poExchangeRate || 1;
    handleCurrencyChange();
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

function handleCurrencyChange() {
    const currencyId = document.getElementById('paymentCurrency').value;
    const baseCurrencyId = @json($baseCurrencyId);
    const row = document.getElementById('exchangeRateRow');
    const rateInput = document.getElementById('paymentExchangeRate');
    
    if (currencyId == baseCurrencyId) {
        row.classList.add('hidden');
        rateInput.value = 1;
    } else {
        row.classList.remove('hidden');
        // If not base currency, try to fetch latest rate if it's not the same as PO currency?
        // Actually, just let the user edit it. 
        // We can add a fetch here if needed, similar to Sales.
        fetch(`/exchange-rates/latest/${currencyId}`)
            .then(res => res.json())
            .then(data => {
                if (data.rate) rateInput.value = data.rate;
            });
    }
}
</script>
@endsection
