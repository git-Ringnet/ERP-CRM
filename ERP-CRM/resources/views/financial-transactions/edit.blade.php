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

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền <span class="text-red-500">*</span></label>
                            <input type="number" name="amount" value="{{ old('amount', $financialTransaction->amount ?? '') }}" required step="0.01" min="0" placeholder="0"
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
@endsection
