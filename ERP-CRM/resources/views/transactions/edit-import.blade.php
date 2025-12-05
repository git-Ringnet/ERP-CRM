@extends('layouts.app')

@section('title', 'Sửa phiếu nhập')
@section('page-title', 'Chỉnh Sửa Phiếu Nhập Kho')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-900">Chỉnh sửa: {{ $transaction->code }}</h2>
        <a href="{{ route('transactions.show', $transaction) }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại
        </a>
    </div>
    
    <form action="{{ route('transactions.update', $transaction) }}" method="POST" class="p-4" id="transactionForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="type" value="import">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Mã giao dịch -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mã phiếu nhập</label>
                <input type="text" value="{{ $transaction->code }}" readonly
                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-gray-50">
            </div>

            <!-- Ngày nhập -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Ngày nhập <span class="text-red-500">*</span>
                </label>
                <input type="date" name="date" value="{{ old('date', $transaction->date->format('Y-m-d')) }}" required
                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg @error('date') border-red-500 @enderror">
                @error('date')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Kho nhập -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Kho nhập <span class="text-red-500">*</span>
                </label>
                <select name="warehouse_id" required
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg @error('warehouse_id') border-red-500 @enderror">
                    <option value="">-- Chọn kho --</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ old('warehouse_id', $transaction->warehouse_id) == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
                @error('warehouse_id')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Nhân viên -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nhân viên nhập</label>
                <select name="employee_id"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                    <option value="">-- Chọn nhân viên --</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('employee_id', $transaction->employee_id) == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Ghi chú -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                <textarea name="note" rows="2"
                          class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">{{ old('note', $transaction->note) }}</textarea>
            </div>
        </div>

        <!-- Items Section -->
        <div class="border-t border-gray-200 pt-4">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-900">Danh sách sản phẩm nhập</h3>
                <button type="button" onclick="addItem()" class="px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark">
                    <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full" id="itemsTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm *</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Số lượng *</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Đơn vị</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Giá nhập</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase w-20">Xóa</th>
                        </tr>
                    </thead>
                    <tbody id="itemsContainer" class="divide-y divide-gray-200">
                        <!-- Items will be added here -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
            <a href="{{ route('transactions.show', $transaction) }}" 
               class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                <i class="fas fa-times mr-1"></i> Hủy
            </a>
            <button type="submit" 
                    class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:bg-primary-dark">
                <i class="fas fa-save mr-1"></i> Cập nhật phiếu
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
let itemIndex = 0;
const products = @json($products);
const existingItems = @json($transaction->items);

function addItem(item = null) {
    const container = document.getElementById('itemsContainer');
    const row = document.createElement('tr');
    row.className = 'item-row';
    row.dataset.index = itemIndex;
    row.innerHTML = `
        <td class="px-4 py-2">
            <select name="items[${itemIndex}][product_id]" required class="w-full px-2 py-1 text-sm border border-gray-300 rounded">
                <option value="">-- Chọn sản phẩm --</option>
                ${products.map(p => `<option value="${p.id}" ${item && item.product_id == p.id ? 'selected' : ''}>${p.name} (${p.code})</option>`).join('')}
            </select>
        </td>
        <td class="px-4 py-2">
            <input type="number" name="items[${itemIndex}][quantity]" required min="0.01" step="0.01" 
                   value="${item ? item.quantity : ''}"
                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded" placeholder="0">
        </td>
        <td class="px-4 py-2">
            <input type="text" name="items[${itemIndex}][unit]" 
                   value="${item ? (item.unit || '') : ''}"
                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded" placeholder="Cái, Hộp...">
        </td>
        <td class="px-4 py-2">
            <input type="number" name="items[${itemIndex}][cost]" min="0" step="0.01" 
                   value="${item ? (item.cost || '') : ''}"
                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded" placeholder="0">
        </td>
        <td class="px-4 py-2 text-center">
            <button type="button" onclick="removeItem(${itemIndex})" 
                    class="px-2 py-1 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    container.appendChild(row);
    itemIndex++;
}

function removeItem(index) {
    const row = document.querySelector(`[data-index="${index}"]`);
    if (row) row.remove();
}

// Load existing items
document.addEventListener('DOMContentLoaded', function() {
    if (existingItems.length > 0) {
        existingItems.forEach(item => addItem(item));
    } else {
        addItem();
    }
});
</script>
@endpush
@endsection
