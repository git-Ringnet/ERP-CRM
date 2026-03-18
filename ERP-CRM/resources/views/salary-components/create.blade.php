@extends('layouts.app')

@section('title', 'Thêm Danh mục Phụ cấp/Khấu trừ')
@section('page-title', 'Thêm Danh mục mới')

@section('content')
<div class="">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">Thông tin danh mục</h2>
            <a href="{{ route('salary-components.index') }}" class="text-gray-500 hover:text-gray-700 flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại
            </a>
        </div>

        <form action="{{ route('salary-components.store') }}" method="POST" class="p-6">
            @csrf
            
            <div class="space-y-6">
                <!-- Tên khoản -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tên khoản <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="VD: Phụ cấp ăn trưa, Trách nhiệm, Vi phạm..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary @error('name') border-red-500 @enderror">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Loại -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Loại khoản <span class="text-red-500">*</span></label>
                        <select name="type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="allowance" {{ old('type') == 'allowance' ? 'selected' : '' }}>Phụ cấp (Cộng vào lương)</option>
                            <option value="deduction" {{ old('type') == 'deduction' ? 'selected' : '' }}>Khấu trừ (Trừ vào lương)</option>
                        </select>
                        @error('type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <!-- Cách tính -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cách tính <span class="text-red-500">*</span></label>
                        <select name="amount_type" id="amount_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="fixed" {{ old('amount_type') == 'fixed' ? 'selected' : '' }}>Số tiền cố định (VNĐ)</option>
                            <option value="percentage" {{ old('amount_type') == 'percentage' ? 'selected' : '' }}>Phần trăm (%) lương cơ bản</option>
                        </select>
                        @error('amount_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <!-- Mức mặc định -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mức mặc định <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" step="any" min="0" name="default_amount" id="default_amount" value="{{ old('default_amount', 0) }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary pr-12">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 font-medium" id="amount_suffix">đ</span>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Mức tiền / Phần trăm mặc định áp dụng chung. Khi gán cho nhân viên có thể thay đổi mức này.</p>
                    @error('default_amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <!-- Chịu thuế -->
                <div class="flex items-center p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <input type="checkbox" name="is_taxable" id="is_taxable" value="1" {{ old('is_taxable') ? 'checked' : '' }}
                           class="h-5 w-5 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="is_taxable" class="ml-3 block text-sm font-medium text-gray-700">
                        Tính vào thu nhập chịu thuế TNCN
                        <span class="block text-xs text-gray-500 font-normal mt-0.5">Nếu đánh dấu, khoản này sẽ cộng vào tổng thu nhập để tính thuế thu nhập cá nhân.</span>
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex justify-end pt-4 border-t border-gray-200">
                    <a href="{{ route('salary-components.index') }}" class="mr-3 px-4 py-2 text-sm text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200">Hủy</a>
                    <button type="submit" class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:bg-primary-dark">Lưu danh mục</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const amountTypeSelect = document.getElementById('amount_type');
        const suffixSpan = document.getElementById('amount_suffix');
        const defaultAmountInput = document.getElementById('default_amount');

        function updateSuffix() {
            if (amountTypeSelect.value === 'percentage') {
                suffixSpan.textContent = '%';
                defaultAmountInput.max = "100";
            } else {
                suffixSpan.textContent = 'đ';
                defaultAmountInput.removeAttribute('max');
            }
        }

        amountTypeSelect.addEventListener('change', updateSuffix);
        updateSuffix(); // Run on load
    });
</script>
@endpush
@endsection
