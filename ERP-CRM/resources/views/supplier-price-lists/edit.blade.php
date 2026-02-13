@extends('layouts.app')

@section('page-title', 'Chỉnh sửa Bảng giá')

@section('content')
<div class="">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h2 class="text-lg font-medium text-gray-900 flex items-center">
                <i class="fas fa-edit text-blue-500 mr-2"></i>
                {{ __('Chỉnh sửa Bảng giá') }}
            </h2>
            <a href="{{ route('supplier-price-lists.index') }}" class="text-gray-500 hover:text-gray-700 text-sm flex items-center">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
        </div>

        <div class="p-6">
            <form method="POST" action="{{ route('supplier-price-lists.update', $supplierPriceList) }}">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">{{ __('Tên bảng giá') }} <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input id="name" type="text" name="name" value="{{ old('name', $supplierPriceList->name) }}" required autofocus
                                class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md @error('name') border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500 @enderror">
                        </div>
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Supplier -->
                    <div>
                        <label for="supplier_id" class="block text-sm font-medium text-gray-700">{{ __('Nhà cung cấp') }} <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <select id="supplier_id" name="supplier_id" required
                                class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md @error('supplier_id') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror">
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ old('supplier_id', $supplierPriceList->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('supplier_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                        <!-- Price Type -->
                        <div>
                            <label for="price_type" class="block text-sm font-medium text-gray-700">{{ __('Loại giá') }} <span class="text-red-500">*</span></label>
                            <div class="mt-1">
                                <select id="price_type" name="price_type" required
                                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    <option value="list" {{ old('price_type', $supplierPriceList->price_type) == 'list' ? 'selected' : '' }}>Giá niêm yết (List Price)</option>
                                    <option value="partner" {{ old('price_type', $supplierPriceList->price_type) == 'partner' ? 'selected' : '' }}>Giá đại lý (Partner Price)</option>
                                    <option value="cost" {{ old('price_type', $supplierPriceList->price_type) == 'cost' ? 'selected' : '' }}>Giá vốn (Cost Price)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Currency -->
                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-700">{{ __('Tiền tệ') }} <span class="text-red-500">*</span></label>
                            <div class="mt-1">
                                <select id="currency" name="currency" required onchange="updateExchangeRate()"
                                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    <option value="VND" {{ old('currency', $supplierPriceList->currency) == 'VND' ? 'selected' : '' }}>VND</option>
                                    <option value="USD" {{ old('currency', $supplierPriceList->currency) == 'USD' ? 'selected' : '' }}>USD</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Exchange Rate -->
                    <div>
                        <label for="exchange_rate" class="block text-sm font-medium text-gray-700">{{ __('Tỷ giá quy đổi (sang VND)') }} <span class="text-red-500">*</span></label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <input id="exchange_rate" type="number" step="any" name="exchange_rate" value="{{ old('exchange_rate', $supplierPriceList->exchange_rate) }}" required
                                class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md" placeholder="1.0">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm" id="exchange-rate-suffix">VND</span>
                            </div>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">Ví dụ: USD = 25,000, VND = 1.</p>
                        @error('exchange_rate')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                        <!-- Effective Date -->
                        <div>
                            <label for="effective_date" class="block text-sm font-medium text-gray-700">{{ __('Ngày hiệu lực') }}</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-calendar text-gray-400"></i>
                                </div>
                                <input id="effective_date" type="date" name="effective_date" value="{{ old('effective_date', $supplierPriceList->effective_date ? $supplierPriceList->effective_date->format('Y-m-d') : '') }}"
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md">
                            </div>
                        </div>

                        <!-- Expiry Date -->
                        <div>
                            <label for="expiry_date" class="block text-sm font-medium text-gray-700">{{ __('Ngày hết hạn') }}</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-calendar-times text-gray-400"></i>
                                </div>
                                <input id="expiry_date" type="date" name="expiry_date" value="{{ old('expiry_date', $supplierPriceList->expiry_date ? $supplierPriceList->expiry_date->format('Y-m-d') : '') }}"
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end space-x-3">
                    <a href="{{ route('supplier-price-lists.index') }}" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        {{ __('Hủy') }}
                    </a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-save mr-2 mt-0.5"></i> {{ __('Lưu thay đổi') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateExchangeRate() {
    const currency = document.getElementById('currency').value;
    const exchangeRateInput = document.getElementById('exchange_rate');
    const suffix = document.getElementById('exchange-rate-suffix');
    
    if (suffix) {
        suffix.textContent = currency;
    }
    
    if (currency === 'VND') {
        exchangeRateInput.value = 1;
    } else if (currency === 'USD') {
        // Only set default if current value is 1 (avoid overwriting custom rate)
        if (exchangeRateInput.value == 1) {
            exchangeRateInput.value = 25000;
        }
    }
}

// Run on load to set initial state
document.addEventListener('DOMContentLoaded', function() {
    updateExchangeRate();
});
</script>
@endsection
