@extends('layouts.app')

@section('title', 'Tạo bảng giá')
@section('page-title', 'Tạo bảng giá mới')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <form action="{{ route('price-lists.store') }}" method="POST" id="priceListForm">
        @csrf
        
        <div class="p-4 sm:p-6 space-y-6">
            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Mã bảng giá <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="code" value="{{ old('code', $code) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('code') border-red-500 @enderror">
                    @error('code')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Tên bảng giá <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="VD: Bảng giá đại lý cấp 1"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Loại bảng giá <span class="text-red-500">*</span>
                    </label>
                    <select name="type" id="priceListType" required onchange="toggleCustomerField()"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="standard" {{ old('type') == 'standard' ? 'selected' : '' }}>Bảng giá chuẩn</option>
                        <option value="customer" {{ old('type') == 'customer' ? 'selected' : '' }}>Giá theo khách hàng</option>
                        <option value="promotion" {{ old('type') == 'promotion' ? 'selected' : '' }}>Khuyến mãi</option>
                        <option value="wholesale" {{ old('type') == 'wholesale' ? 'selected' : '' }}>Giá sỉ</option>
                    </select>
                </div>
                <div id="customerField" style="display: none;">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Khách hàng áp dụng</label>
                    <select name="customer_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">Tất cả khách hàng</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }} ({{ $customer->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ngày bắt đầu</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ngày kết thúc</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Chiết khấu chung (%)</label>
                    <input type="number" name="discount_percent" value="{{ old('discount_percent', 0) }}" min="0" max="100" step="0.01"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Độ ưu tiên</label>
                    <input type="number" name="priority" value="{{ old('priority', 0) }}" min="0"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <p class="text-xs text-gray-500 mt-1">Số cao hơn = ưu tiên cao hơn</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                <textarea name="description" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">{{ old('description') }}</textarea>
            </div>

            <!-- Products Section -->
            <div class="border-t pt-4">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Chi tiết giá sản phẩm</h4>
                
                <div id="productList" class="space-y-3">
                    <div class="product-item bg-gray-50 p-3 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                            <div class="md:col-span-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm <span class="text-red-500">*</span></label>
                                <select name="items[0][product_id]" required onchange="updateOriginalPrice(this, 0)"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary product-select">
                                    <option value="">Chọn sản phẩm</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                                            {{ $product->name }} ({{ $product->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Giá gốc</label>
                                <input type="text" readonly class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 original-price" value="0">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Giá bán <span class="text-red-500">*</span></label>
                                <input type="number" name="items[0][price]" min="0" value="0" required
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary price-input">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">SL tối thiểu</label>
                                <input type="number" name="items[0][min_quantity]" min="1" value="1"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">CK (%)</label>
                                <input type="number" name="items[0][discount_percent]" min="0" max="100" step="0.01" value="0"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                            <div class="md:col-span-1 flex items-end">
                                <button type="button" onclick="removeProductRow(this)" 
                                        class="w-full px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" onclick="addProductRow()" 
                        class="mt-3 inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Thêm sản phẩm
                </button>
            </div>
        </div>

        <!-- Actions -->
        <div class="px-4 sm:px-6 py-4 bg-gray-50 border-t flex flex-col sm:flex-row gap-2 justify-end">
            <a href="{{ route('price-lists.index') }}" 
               class="inline-flex items-center justify-center px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                <i class="fas fa-times mr-2"></i> Hủy
            </a>
            <button type="submit"
                    class="inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-save mr-2"></i> Lưu bảng giá
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
let productIndex = 1;
const products = @json($products);

function toggleCustomerField() {
    const type = document.getElementById('priceListType').value;
    document.getElementById('customerField').style.display = type === 'customer' ? 'block' : 'none';
}

function addProductRow() {
    const productList = document.getElementById('productList');
    const newRow = document.createElement('div');
    newRow.className = 'product-item bg-gray-50 p-3 rounded-lg';
    newRow.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
            <div class="md:col-span-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm</label>
                <select name="items[${productIndex}][product_id]" required onchange="updateOriginalPrice(this, ${productIndex})"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary product-select">
                    <option value="">Chọn sản phẩm</option>
                    ${products.map(p => `<option value="${p.id}" data-price="${p.price}">${p.name} (${p.code})</option>`).join('')}
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Giá gốc</label>
                <input type="text" readonly class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 original-price" value="0">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Giá bán</label>
                <input type="number" name="items[${productIndex}][price]" min="0" value="0" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary price-input">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">SL tối thiểu</label>
                <input type="number" name="items[${productIndex}][min_quantity]" min="1" value="1"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="md:col-span-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">CK (%)</label>
                <input type="number" name="items[${productIndex}][discount_percent]" min="0" max="100" step="0.01" value="0"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
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
    }
}

function updateOriginalPrice(select, index) {
    const option = select.options[select.selectedIndex];
    const price = option.dataset.price || 0;
    const row = select.closest('.product-item');
    row.querySelector('.original-price').value = formatNumber(price);
    row.querySelector('.price-input').value = price;
}

function formatNumber(num) {
    return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// Initialize
toggleCustomerField();
</script>
@endpush
