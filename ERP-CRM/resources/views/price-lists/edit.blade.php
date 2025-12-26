@extends('layouts.app')

@section('title', 'Sửa bảng giá - ' . $priceList->code)
@section('page-title', 'Sửa bảng giá: ' . $priceList->code)

@section('content')
@if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
@endif

<form action="{{ route('price-lists.update', $priceList) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold">Thông tin bảng giá</h3>
            <a href="{{ route('price-lists.show', $priceList) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Quay lại
            </a>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mã bảng giá <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code', $priceList->code) }}" required
                        class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tên bảng giá <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $priceList->name) }}" required
                        class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Loại bảng giá <span class="text-red-500">*</span></label>
                    <select name="type" id="priceListType" required onchange="toggleCustomerField()"
                        class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="standard" {{ old('type', $priceList->type) == 'standard' ? 'selected' : '' }}>Bảng giá chuẩn</option>
                        <option value="customer" {{ old('type', $priceList->type) == 'customer' ? 'selected' : '' }}>Giá theo khách hàng</option>
                        <option value="promotion" {{ old('type', $priceList->type) == 'promotion' ? 'selected' : '' }}>Khuyến mãi</option>
                        <option value="wholesale" {{ old('type', $priceList->type) == 'wholesale' ? 'selected' : '' }}>Giá sỉ</option>
                    </select>
                </div>
                <div id="customerField">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Khách hàng áp dụng</label>
                    <select name="customer_id" class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Tất cả khách hàng</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id', $priceList->customer_id) == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }} ({{ $customer->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ngày bắt đầu</label>
                    <input type="date" name="start_date" value="{{ old('start_date', $priceList->start_date?->format('Y-m-d')) }}"
                        class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ngày kết thúc</label>
                    <input type="date" name="end_date" value="{{ old('end_date', $priceList->end_date?->format('Y-m-d')) }}"
                        class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Chiết khấu chung (%)</label>
                    <input type="number" name="discount_percent" value="{{ old('discount_percent', $priceList->discount_percent) }}" min="0" max="100" step="0.01"
                        class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Độ ưu tiên</label>
                    <input type="number" name="priority" value="{{ old('priority', $priceList->priority) }}" min="0"
                        class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                    <textarea name="description" rows="2" class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $priceList->description) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Products -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold">Chi tiết giá sản phẩm</h3>
            <button type="button" onclick="addProductRow()" class="inline-flex items-center px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-colors">
                <i class="fas fa-plus mr-2"></i> Thêm sản phẩm
            </button>
        </div>

        <div class="overflow-x-auto">
                <table class="min-w-full table-fixed">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-600" style="width: 40%;">Sản phẩm</th>
                            <th class="px-4 py-2 text-right text-sm font-medium text-gray-600" style="width: 20%;">Giá bán</th>
                            <th class="px-4 py-2 text-center text-sm font-medium text-gray-600" style="width: 15%;">SL tối thiểu</th>
                            <th class="px-4 py-2 text-center text-sm font-medium text-gray-600" style="width: 15%;">CK (%)</th>
                            <th class="px-4 py-2 text-center" style="width: 10%;"></th>
                        </tr>
                    </thead>
                    <tbody id="productRows">
                        @foreach($priceList->items as $index => $item)
                        <tr class="product-row border-b">
                            <td class="px-4 py-2">
                                <select name="items[{{ $index }}][product_id]" required
                                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Chọn sản phẩm</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->price }}" {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                            {{ $product->name }} ({{ $product->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-4 py-2">
                                <input type="number" name="items[{{ $index }}][price]" value="{{ $item->price }}" min="0" required
                                    class="w-full border rounded px-3 py-2 text-right">
                            </td>
                            <td class="px-4 py-2">
                                <input type="number" name="items[{{ $index }}][min_quantity]" value="{{ $item->min_quantity }}" min="1"
                                    class="w-full border rounded px-3 py-2 text-center">
                            </td>
                            <td class="px-4 py-2">
                                <input type="number" name="items[{{ $index }}][discount_percent]" value="{{ $item->discount_percent }}" min="0" max="100" step="0.01"
                                    class="w-full border rounded px-3 py-2 text-center">
                            </td>
                            <td class="px-4 py-2 text-center">
                                <button type="button" onclick="removeProductRow(this)" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    <div class="flex justify-end space-x-4">
        <a href="{{ route('price-lists.show', $priceList) }}" class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
            <i class="fas fa-times mr-2"></i> Hủy
        </a>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
            <i class="fas fa-save mr-2"></i> Lưu thay đổi
        </button>
    </div>
</form>

<script>
let rowIndex = {{ count($priceList->items) }};
const products = @json($products);

function toggleCustomerField() {
    const type = document.getElementById('priceListType').value;
    document.getElementById('customerField').style.display = type === 'customer' ? 'block' : 'none';
}

function addProductRow() {
    const tbody = document.getElementById('productRows');
    const row = document.createElement('tr');
    row.className = 'product-row border-b';
    row.innerHTML = `
        <td class="px-4 py-2">
            <select name="items[${rowIndex}][product_id]" required
                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Chọn sản phẩm</option>
                ${products.map(p => `<option value="${p.id}" data-price="${p.price}">${p.name} (${p.code})</option>`).join('')}
            </select>
        </td>
        <td class="px-4 py-2">
            <input type="number" name="items[${rowIndex}][price]" value="0" min="0" required
                class="w-full border rounded px-3 py-2 text-right">
        </td>
        <td class="px-4 py-2">
            <input type="number" name="items[${rowIndex}][min_quantity]" value="1" min="1"
                class="w-full border rounded px-3 py-2 text-center">
        </td>
        <td class="px-4 py-2">
            <input type="number" name="items[${rowIndex}][discount_percent]" value="0" min="0" max="100" step="0.01"
                class="w-full border rounded px-3 py-2 text-center">
        </td>
        <td class="px-4 py-2 text-center">
            <button type="button" onclick="removeProductRow(this)" class="text-red-600 hover:text-red-800">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(row);
    rowIndex++;
}

function removeProductRow(btn) {
    const rows = document.querySelectorAll('.product-row');
    if (rows.length > 1) {
        btn.closest('tr').remove();
    }
}

toggleCustomerField();
</script>
@endsection
