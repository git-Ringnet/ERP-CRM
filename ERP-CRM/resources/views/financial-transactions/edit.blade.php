@extends('layouts.app')

@section('title', isset($financialTransaction) ? 'Sửa giao dịch' : 'Thêm giao dịch mới')
@section('page-title', isset($financialTransaction) ? 'Sửa giao dịch' : 'Thêm giao dịch mới')

@section('content')
<div class="">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6">
            <form action="{{ isset($financialTransaction) ? route('financial-transactions.update', $financialTransaction) : route('financial-transactions.store') }}" method="POST">
                @csrf
                @if(isset($financialTransaction))
                    @method('PUT')
                @endif

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Danh mục <span class="text-red-500">*</span></label>
                        <select name="transaction_category_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary">
                            <option value="">-- Chọn danh mục --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ (old('transaction_category_id', $financialTransaction->transaction_category_id ?? '') == $category->id) ? 'selected' : '' }}>
                                    [{{ $category->type === 'income' ? 'Thu' : 'Chi' }}] {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('transaction_category_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền <span class="text-red-500">*</span></label>
                            <input type="number" name="amount" value="{{ old('amount', $financialTransaction->amount_foreign ?? $financialTransaction->amount) }}" required step="0.01" min="0" placeholder="0"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary font-bold text-lg">
                            @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ngày giao dịch <span class="text-red-500">*</span></label>
                            <input type="date" name="date" value="{{ old('date', isset($financialTransaction) ? $financialTransaction->date->format('Y-m-d') : date('Y-m-d')) }}" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary">
                            @error('date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tiền tệ <span class="text-red-500">*</span></label>
                            <select name="currency_id" id="currencySelect" onchange="onCurrencyChange()" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary">
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->id }}" 
                                        data-is-base="{{ $currency->is_base ? '1' : '0' }}"
                                        data-symbol="{{ $currency->symbol }}"
                                        {{ old('currency_id', $financialTransaction->currency_id ?? $baseCurrencyId) == $currency->id ? 'selected' : '' }}>
                                        {{ $currency->code }} - {{ $currency->name_vi }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div id="exchangeRateGroup" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tỷ giá (1 ngoại tệ = ? VND)</label>
                            <input type="number" name="exchange_rate" id="exchangeRateInput" step="0.01" value="{{ old('exchange_rate', $financialTransaction->exchange_rate ?? 1) }}" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phương thức thanh toán <span class="text-red-500">*</span></label>
                        <div class="flex gap-4 p-2 bg-gray-50 rounded-lg border border-gray-200">
                            <label class="inline-flex items-center">
                                <input type="radio" name="payment_method" value="cash" {{ old('payment_method', $financialTransaction->payment_method ?? 'cash') === 'cash' ? 'checked' : '' }} class="text-primary focus:ring-primary">
                                <span class="ml-2 text-sm text-gray-700">Tiền mặt</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="payment_method" value="bank_transfer" {{ old('payment_method', $financialTransaction->payment_method ?? '') === 'bank_transfer' ? 'checked' : '' }} class="text-primary focus:ring-primary">
                                <span class="ml-2 text-sm text-gray-700">Chuyển khoản</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="payment_method" value="other" {{ old('payment_method', $financialTransaction->payment_method ?? '') === 'other' ? 'checked' : '' }} class="text-primary focus:ring-primary">
                                <span class="ml-2 text-sm text-gray-700">Khác</span>
                            </label>
                        </div>
                        @error('payment_method') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mã tham chiếu (nếu có)</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number', $financialTransaction->reference_number ?? '') }}" placeholder="Mã lệnh chuyển tiền, số hóa đơn..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                        <textarea name="note" rows="3" placeholder="Chi tiết nội dung giao dịch..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary">{{ old('note', $financialTransaction->note ?? '') }}</textarea>
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <a href="{{ route('financial-transactions.index') }}" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 font-medium">
                        Hủy
                    </a>
                    <button type="submit" class="bg-primary text-white px-8 py-2 rounded-lg hover:bg-primary/90 font-bold">
                        {{ isset($financialTransaction) ? 'Cập nhật' : 'Lưu giao dịch' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
function onCurrencyChange() {
    const select = document.getElementById('currencySelect');
    const option = select.options[select.selectedIndex];
    const isBase = option.dataset.isBase === '1';

    if (isBase) {
        document.getElementById('exchangeRateGroup').classList.add('hidden');
        document.getElementById('exchangeRateInput').value = 1;
    } else {
        document.getElementById('exchangeRateGroup').classList.remove('hidden');
        
        // Initial load for edit view: if currency is the same as existing, use existing exchange rate
        const existingCurrencyId = "{{ $financialTransaction->currency_id ?? '' }}";
        if (select.value != existingCurrencyId) {
            fetchExchangeRate(select.value);
        } else {
             document.getElementById('exchangeRateInput').value = "{{ $financialTransaction->exchange_rate ?? 1 }}";
        }
    }
}

async function fetchExchangeRate(currencyId) {
    const dateInput = document.querySelector('input[name="date"]');
    const date = dateInput ? dateInput.value : new Date().toISOString().split('T')[0];
    
    try {
        const response = await fetch(`{{ route('api.exchange-rate') }}?currency_id=${currencyId}&date=${date}`);
        const data = await response.json();
        
        if (data.rate) {
            document.getElementById('exchangeRateInput').value = data.rate;
        }
    } catch (e) {
        console.error('Failed to fetch exchange rate', e);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    onCurrencyChange();
});

// Update exchange rate when date changes (if not base currency)
document.querySelector('input[name="date"]').addEventListener('change', function() {
    const select = document.getElementById('currencySelect');
    if (select.options[select.selectedIndex].dataset.isBase !== '1') {
        fetchExchangeRate(select.value);
    }
});
</script>
@endpush
@endsection
