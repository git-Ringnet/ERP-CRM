@extends('layouts.app')

@section('title', 'Sửa báo cáo hư hỏng')
@section('page-title', 'Chỉnh Sửa Báo Cáo: ' . $damagedGood->code)

@section('content')
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Thông tin báo cáo</h2>
            <a href="{{ route('damaged-goods.show', $damagedGood) }}" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
        </div>

        <form action="{{ route('damaged-goods.update', $damagedGood) }}" method="POST" class="p-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Loại <span
                            class="text-red-500">*</span></label>
                    <select name="type" id="type"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('type') border-red-500 @enderror"
                        required>
                        <option value="">-- Chọn loại --</option>
                        <option value="damaged" {{ old('type', $damagedGood->type) == 'damaged' ? 'selected' : '' }}>Hàng hư
                            hỏng</option>
                        <option value="liquidation" {{ old('type', $damagedGood->type) == 'liquidation' ? 'selected' : '' }}>
                            Thanh lý</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm <span
                            class="text-red-500">*</span></label>
                    <select name="product_id" id="product_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('product_id') border-red-500 @enderror"
                        required>
                        <option value="">-- Chọn sản phẩm --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ old('product_id', $damagedGood->product_id) == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} ({{ $product->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Số lượng <span
                            class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="quantity" id="quantity"
                        value="{{ old('quantity', $damagedGood->quantity) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('quantity') border-red-500 @enderror"
                        required>
                    @error('quantity')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="original_value" class="block text-sm font-medium text-gray-700 mb-1">Giá trị gốc <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="original_value" id="original_value"
                        value="{{ old('original_value', number_format($damagedGood->original_value, 0, '', '')) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('original_value') border-red-500 @enderror"
                        required>
                    @error('original_value')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="recovery_value" class="block text-sm font-medium text-gray-700 mb-1">Giá trị thu hồi <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="recovery_value" id="recovery_value"
                        value="{{ old('recovery_value', number_format($damagedGood->recovery_value, 0, '', '')) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('recovery_value') border-red-500 @enderror"
                        required>
                    @error('recovery_value')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="discovery_date" class="block text-sm font-medium text-gray-700 mb-1">Ngày phát hiện <span
                            class="text-red-500">*</span></label>
                    <input type="date" name="discovery_date" id="discovery_date"
                        value="{{ old('discovery_date', $damagedGood->discovery_date->format('Y-m-d')) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('discovery_date') border-red-500 @enderror"
                        required>
                    @error('discovery_date')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="discovered_by" class="block text-sm font-medium text-gray-700 mb-1">Người phát hiện <span
                            class="text-red-500">*</span></label>
                    <select name="discovered_by" id="discovered_by"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('discovered_by') border-red-500 @enderror"
                        required>
                        <option value="">-- Chọn người phát hiện --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('discovered_by', $damagedGood->discovered_by) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('discovered_by')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">Lý do <span
                            class="text-red-500">*</span></label>
                    <textarea name="reason" id="reason" rows="3"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('reason') border-red-500 @enderror"
                        required>{{ old('reason', $damagedGood->reason) }}</textarea>
                    @error('reason')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="solution" class="block text-sm font-medium text-gray-700 mb-1">Giải pháp xử lý</label>
                    <textarea name="solution" id="solution" rows="3"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('solution') border-red-500 @enderror">{{ old('solution', $damagedGood->solution) }}</textarea>
                    @error('solution')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="note" class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                    <textarea name="note" id="note" rows="2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('note') border-red-500 @enderror">{{ old('note', $damagedGood->note) }}</textarea>
                    @error('note')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex gap-2">
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-sm">
                    <i class="fas fa-save mr-1"></i> Cập nhật
                </button>
                <a href="{{ route('damaged-goods.show', $damagedGood) }}"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
                    <i class="fas fa-times mr-1"></i> Hủy
                </a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const currencyInputs = ['original_value', 'recovery_value'];

            function formatCurrency(input) {
                let value = input.value.replace(/\D/g, '');
                if (value === '') return;
                input.value = new Intl.NumberFormat('en-US').format(value);
            }

            currencyInputs.forEach(id => {
                const input = document.getElementById(id);
                if (input) {
                    // Format initial value
                    formatCurrency(input);

                    // Format on input
                    input.addEventListener('input', function () {
                        formatCurrency(this);
                    });
                }
            });

            // Clean before submit
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function () {
                    currencyInputs.forEach(id => {
                        const input = document.getElementById(id);
                        if (input) {
                            input.value = input.value.replace(/,/g, '');
                        }
                    });
                });
            }
        });
    </script>
@endpush