@extends('layouts.app')

@section('title', 'Sửa đơn hàng')
@section('page-title', 'Sửa đơn hàng: ' . $sale->code)

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <form action="{{ route('sales.update', $sale->id) }}" method="POST" id="saleForm">
        @csrf
        @method('PUT')
        
        <div class="p-4 sm:p-6 space-y-6">
            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Mã đơn hàng <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="code" value="{{ old('code', $sale->code) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('code') border-red-500 @enderror">
                    @error('code')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @else
                        <p class="text-xs text-gray-500 mt-1">Có thể sửa mã đơn hàng nếu cần</p>
                    @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Loại đơn hàng <span class="text-red-500">*</span>
                    </label>
                    <select name="type" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="retail" {{ old('type', $sale->type) == 'retail' ? 'selected' : '' }}>Bán lẻ</option>
                        <option value="project" {{ old('type', $sale->type) == 'project' ? 'selected' : '' }}>Bán theo dự án</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Khách hàng <span class="text-red-500">*</span>
                    </label>
                    <select name="customer_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('customer_id') border-red-500 @enderror">
                        <option value="">Chọn khách hàng</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id', $sale->customer_id) == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }} ({{ $customer->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Ngày tạo <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="date" value="{{ old('date', $sale->date->format('Y-m-d')) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ giao hàng</label>
                    <textarea name="delivery_address" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">{{ old('delivery_address', $sale->delivery_address) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Trạng thái <span class="text-red-500">*</span>
                    </label>
                    <select name="status" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="pending" {{ old('status', $sale->status) == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                        <option value="approved" {{ old('status', $sale->status) == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                        <option value="shipping" {{ old('status', $sale->status) == 'shipping' ? 'selected' : '' }}>Đang giao</option>
                        <option value="completed" {{ old('status', $sale->status) == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                        <option value="cancelled" {{ old('status', $sale->status) == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                    </select>
                </div>
            </div>

            <!-- Products Section -->
            <div class="border-t pt-4">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Chi tiết sản phẩm</h4>
                
                <div id="productList" class="space-y-3">
                    @foreach($sale->items as $index => $item)
                    <div class="product-item bg-gray-50 p-3 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                            <div class="md:col-span-5">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm</label>
                                <select name="products[{{ $index }}][product_id]" required onchange="updatePrice(this, {{ $index }})"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary product-select">
                                    <option value="">Chọn sản phẩm</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->price }}" {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng</label>
                                <input type="number" name="products[{{ $index }}][quantity]" min="1" value="{{ $item->quantity }}" required
                                       onchange="calculateRowTotal({{ $index }})"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary quantity-input">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Đơn giá</label>
                                <input type="number" name="products[{{ $index }}][price]" min="0" value="{{ $item->price }}" required
                                       onchange="calculateRowTotal({{ $index }})"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary price-input">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Thành tiền</label>
                                <input type="text" readonly value="{{ number_format($item->total) }} đ"
                                       class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 row-total">
                            </div>
                            <div class="md:col-span-1 flex items-end">
                                <button type="button" onclick="removeProductRow(this)" 
                                        class="w-full px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <button type="button" onclick="addProductRow()" 
                        class="mt-3 inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Thêm sản phẩm
                </button>
            </div>

            <!-- Totals Section -->
            <div class="border-t pt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-lg ml-auto">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tổng tiền hàng</label>
                        <input type="text" id="subtotal" readonly value="{{ number_format($sale->subtotal) }} đ"
                               class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Chiết khấu (%)</label>
                        <input type="number" name="discount" id="discount" value="{{ old('discount', $sale->discount) }}" min="0" max="100"
                               onchange="calculateTotal()"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">VAT (%)</label>
                        <input type="number" name="vat" id="vat" value="{{ old('vat', $sale->vat) }}" min="0"
                               onchange="calculateTotal()"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tổng cộng</label>
                        <input type="text" id="total" readonly value="{{ number_format($sale->total) }} đ"
                               class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 font-bold text-lg">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                <textarea name="note" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">{{ old('note', $sale->note) }}</textarea>
            </div>
        </div>

        <!-- Actions -->
        <div class="px-4 sm:px-6 py-4 bg-gray-50 border-t flex flex-col sm:flex-row gap-2 justify-end">
            <a href="{{ route('sales.index') }}" 
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
@endsection

@push('scripts')
<script>
let productIndex = {{ count($sale->items) }};
const products = @json($products);

function addProductRow() {
    const productList = document.getElementById('productList');
    const newRow = document.createElement('div');
    newRow.className = 'product-item bg-gray-50 p-3 rounded-lg';
    newRow.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
            <div class="md:col-span-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm</label>
                <select name="products[${productIndex}][product_id]" required onchange="updatePrice(this, ${productIndex})"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary product-select">
                    <option value="">Chọn sản phẩm</option>
                    ${products.map(p => `<option value="${p.id}" data-price="${p.price}">${p.name}</option>`).join('')}
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng</label>
                <input type="number" name="products[${productIndex}][quantity]" min="1" value="1" required
                       onchange="calculateRowTotal(${productIndex})"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary quantity-input">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Đơn giá</label>
                <input type="number" name="products[${productIndex}][price]" min="0" required
                       onchange="calculateRowTotal(${productIndex})"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary price-input">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Thành tiền</label>
                <input type="text" readonly
                       class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 row-total">
            </div>
            <div class="md:col-span-1 flex items-end">
                <button type="button" onclick="removeProductRow(this)" 
                        class="w-full px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    productList.appendChild(newRow);
    productIndex++;
}

function removeProductRow(btn) {
    const items = document.querySelectorAll('.product-item');
    if (items.length > 1) {
        btn.closest('.product-item').remove();
        calculateTotal();
    }
}

function updatePrice(select, index) {
    const option = select.options[select.selectedIndex];
    const price = option.dataset.price || 0;
    const row = select.closest('.product-item');
    row.querySelector('.price-input').value = price;
    calculateRowTotal(index);
}

function calculateRowTotal(index) {
    const rows = document.querySelectorAll('.product-item');
    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const total = qty * price;
        row.querySelector('.row-total').value = total.toLocaleString('vi-VN') + ' đ';
    });
    calculateTotal();
}

function calculateTotal() {
    let subtotal = 0;
    document.querySelectorAll('.product-item').forEach(row => {
        const qty = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        subtotal += qty * price;
    });
    
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const vat = parseFloat(document.getElementById('vat').value) || 0;
    
    const discountAmount = subtotal * discount / 100;
    const afterDiscount = subtotal - discountAmount;
    const vatAmount = afterDiscount * vat / 100;
    const total = afterDiscount + vatAmount;
    
    document.getElementById('subtotal').value = subtotal.toLocaleString('vi-VN') + ' đ';
    document.getElementById('total').value = total.toLocaleString('vi-VN') + ' đ';
}

// Calculate on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateTotal();
});
</script>
@endpush
