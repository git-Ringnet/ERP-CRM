@extends('layouts.app')

@section('title', 'Sửa công thức chi phí')
@section('page-title', 'Sửa công thức: ' . $costFormula->name)

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <form action="{{ route('cost-formulas.update', $costFormula->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="p-4 sm:p-6 space-y-6">
            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Mã công thức <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="code" value="{{ old('code', $costFormula->code) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('code') border-red-500 @enderror">
                    @error('code')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Tên công thức <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $costFormula->name) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Loại chi phí <span class="text-red-500">*</span>
                    </label>
                    <select name="type" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="shipping" {{ old('type', $costFormula->type) == 'shipping' ? 'selected' : '' }}>Vận chuyển</option>
                        <option value="marketing" {{ old('type', $costFormula->type) == 'marketing' ? 'selected' : '' }}>Marketing</option>
                        <option value="commission" {{ old('type', $costFormula->type) == 'commission' ? 'selected' : '' }}>Hoa hồng</option>
                        <option value="other" {{ old('type', $costFormula->type) == 'other' ? 'selected' : '' }}>Khác</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Cách tính <span class="text-red-500">*</span>
                    </label>
                    <select name="calculation_type" id="calculationType" required onchange="toggleCalculationFields()"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="fixed" {{ old('calculation_type', $costFormula->calculation_type) == 'fixed' ? 'selected' : '' }}>Cố định</option>
                        <option value="percentage" {{ old('calculation_type', $costFormula->calculation_type) == 'percentage' ? 'selected' : '' }}>Phần trăm</option>
                        <option value="formula" {{ old('calculation_type', $costFormula->calculation_type) == 'formula' ? 'selected' : '' }}>Công thức</option>
                    </select>
                </div>
            </div>

            <!-- Calculation Fields -->
            <div id="fixedField" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền cố định (VNĐ)</label>
                <input type="number" name="fixed_amount" value="{{ old('fixed_amount', $costFormula->fixed_amount) }}" min="0"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
            </div>

            <div id="percentageField" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Phần trăm (%)</label>
                <input type="number" name="percentage" value="{{ old('percentage', $costFormula->percentage) }}" min="0" max="100" step="0.01"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                <p class="text-xs text-gray-500 mt-1">Tính theo % doanh thu đơn hàng</p>
            </div>

            <div id="formulaField" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Công thức tính</label>
                <input type="text" name="formula" value="{{ old('formula', $costFormula->formula) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary font-mono">
                <p class="text-xs text-gray-500 mt-1">
                    Biến có thể dùng: <code class="bg-gray-100 px-1 rounded">revenue</code>, 
                    <code class="bg-gray-100 px-1 rounded">quantity</code>, 
                    <code class="bg-gray-100 px-1 rounded">distance</code>, 
                    <code class="bg-gray-100 px-1 rounded">weight</code>
                </p>
            </div>

            <!-- Apply Conditions -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Áp dụng cho <span class="text-red-500">*</span>
                </label>
                <select name="apply_to" id="applyTo" required onchange="toggleApplyFields()"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="all" {{ old('apply_to', $costFormula->apply_to) == 'all' ? 'selected' : '' }}>Tất cả đơn hàng</option>
                    <option value="product" {{ old('apply_to', $costFormula->apply_to) == 'product' ? 'selected' : '' }}>Theo sản phẩm</option>
                    <option value="customer" {{ old('apply_to', $costFormula->apply_to) == 'customer' ? 'selected' : '' }}>Theo khách hàng</option>
                </select>
            </div>

            @php
                $selectedProducts = old('apply_conditions.product_ids', $costFormula->apply_conditions['product_ids'] ?? []);
                $selectedCustomers = old('apply_conditions.customer_ids', $costFormula->apply_conditions['customer_ids'] ?? []);
            @endphp

            <div id="productField" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Chọn sản phẩm</label>
                <select name="apply_conditions[product_ids][]" multiple size="5"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ in_array($product->id, $selectedProducts) ? 'selected' : '' }}>
                            {{ $product->name }} ({{ $product->code }})
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Giữ Ctrl để chọn nhiều sản phẩm</p>
            </div>

            <div id="customerField" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Chọn khách hàng</label>
                <select name="apply_conditions[customer_ids][]" multiple size="5"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ in_array($customer->id, $selectedCustomers) ? 'selected' : '' }}>
                            {{ $customer->name }} ({{ $customer->code }})
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Giữ Ctrl để chọn nhiều khách hàng</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                <textarea name="description" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">{{ old('description', $costFormula->description) }}</textarea>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $costFormula->is_active) ? 'checked' : '' }}
                       class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                <label for="is_active" class="ml-2 text-sm text-gray-700">Kích hoạt công thức</label>
            </div>
        </div>

        <!-- Actions -->
        <div class="px-4 sm:px-6 py-4 bg-gray-50 border-t flex flex-col sm:flex-row gap-2 justify-end">
            <a href="{{ route('cost-formulas.index') }}" 
               class="inline-flex items-center justify-center px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                <i class="fas fa-times mr-2"></i> Hủy
            </a>
            <button type="submit" 
                    class="inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-save mr-2"></i> Cập nhật
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function toggleCalculationFields() {
    const type = document.getElementById('calculationType').value;
    document.getElementById('fixedField').classList.add('hidden');
    document.getElementById('percentageField').classList.add('hidden');
    document.getElementById('formulaField').classList.add('hidden');
    
    if (type === 'fixed') {
        document.getElementById('fixedField').classList.remove('hidden');
    } else if (type === 'percentage') {
        document.getElementById('percentageField').classList.remove('hidden');
    } else if (type === 'formula') {
        document.getElementById('formulaField').classList.remove('hidden');
    }
}

function toggleApplyFields() {
    const applyTo = document.getElementById('applyTo').value;
    document.getElementById('productField').classList.add('hidden');
    document.getElementById('customerField').classList.add('hidden');
    
    if (applyTo === 'product') {
        document.getElementById('productField').classList.remove('hidden');
    } else if (applyTo === 'customer') {
        document.getElementById('customerField').classList.remove('hidden');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleCalculationFields();
    toggleApplyFields();
});
</script>
@endpush
@endsection
