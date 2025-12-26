@extends('layouts.app')

@section('title', 'Thêm giá nhập mới')
@section('page-title', 'Thêm giá nhập mới')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <a href="{{ route('purchase-pricings.index') }}" class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
    </div>

    <form action="{{ route('purchase-pricings.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 space-y-4">
                <!-- Product Info -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-box mr-2 text-primary"></i>Thông tin sản phẩm</h2>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm <span class="text-red-500">*</span></label>
                                <select name="product_id" required class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('product_id') border-red-500 @enderror">
                                    <option value="">Chọn sản phẩm</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                            {{ $product->code }} - {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('product_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nhà cung cấp <span class="text-red-500">*</span></label>
                                <select name="supplier_id" required class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('supplier_id') border-red-500 @enderror">
                                    <option value="">Chọn NCC</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('supplier_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Đơn mua hàng</label>
                                <select name="purchase_order_id" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="">Chọn đơn mua hàng (nếu có)</option>
                                    @foreach($purchaseOrders as $po)
                                        <option value="{{ $po->id }}" {{ old('purchase_order_id') == $po->id ? 'selected' : '' }}>
                                            {{ $po->code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng nhập <span class="text-red-500">*</span></label>
                                <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" required
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('quantity') border-red-500 @enderror">
                                @error('quantity')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Purchase Price -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-money-bill mr-2 text-primary"></i>Giá nhập</h2>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Giá nhập gốc (VNĐ) <span class="text-red-500">*</span></label>
                                <input type="number" name="purchase_price" id="purchase_price" value="{{ old('purchase_price') }}" min="0" required onchange="calculatePrices()"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('purchase_price') border-red-500 @enderror">
                                @error('purchase_price')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Chiết khấu NCC (%)</label>
                                <input type="number" name="discount_percent" id="discount_percent" value="{{ old('discount_percent', 0) }}" min="0" max="100" step="0.1" onchange="calculatePrices()"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Giá sau chiết khấu</label>
                                <input type="text" id="price_after_discount" readonly class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-md bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">VAT (%)</label>
                                <input type="number" name="vat_percent" id="vat_percent" value="{{ old('vat_percent', 10) }}" min="0" max="100" onchange="calculatePrices()"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Service Costs -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-truck mr-2 text-primary"></i>Chi phí phục vụ nhập hàng</h2>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Chi phí vận chuyển (VNĐ)</label>
                                <input type="number" name="shipping_cost" id="shipping_cost" value="{{ old('shipping_cost', 0) }}" min="0" onchange="calculatePrices()"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Chi phí bốc xếp (VNĐ)</label>
                                <input type="number" name="loading_cost" id="loading_cost" value="{{ old('loading_cost', 0) }}" min="0" onchange="calculatePrices()"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Chi phí kiểm tra (VNĐ)</label>
                                <input type="number" name="inspection_cost" id="inspection_cost" value="{{ old('inspection_cost', 0) }}" min="0" onchange="calculatePrices()"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Chi phí khác (VNĐ)</label>
                                <input type="number" name="other_cost" id="other_cost" value="{{ old('other_cost', 0) }}" min="0" onchange="calculatePrices()"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tổng chi phí phục vụ</label>
                                <input type="text" id="total_service_cost" readonly class="w-full px-3 py-1.5 text-sm border border-orange-200 rounded-md bg-orange-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">CP phục vụ/đơn vị</label>
                                <input type="text" id="service_cost_per_unit" readonly class="w-full px-3 py-1.5 text-sm border border-orange-200 rounded-md bg-orange-50">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <!-- Warehouse Price -->
                <div class="bg-white rounded-lg shadow-sm border-2 border-green-500">
                    <div class="px-4 py-3 bg-green-500 text-white rounded-t-lg">
                        <h2 class="text-base font-semibold"><i class="fas fa-warehouse mr-2"></i>Giá kho</h2>
                    </div>
                    <div class="p-4">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phương pháp tính giá <span class="text-red-500">*</span></label>
                            <select name="pricing_method" required class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                                <option value="average" {{ old('pricing_method', 'average') == 'average' ? 'selected' : '' }}>Bình quân gia quyền</option>
                                <option value="fifo" {{ old('pricing_method') == 'fifo' ? 'selected' : '' }}>FIFO (Nhập trước xuất trước)</option>
                                <option value="lifo" {{ old('pricing_method') == 'lifo' ? 'selected' : '' }}>LIFO (Nhập sau xuất trước)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Giá kho (VNĐ)</label>
                            <input type="text" id="warehouse_price" readonly class="w-full px-3 py-2 text-xl font-bold text-green-600 border border-green-200 rounded-md bg-green-50">
                        </div>
                    </div>
                </div>

                <!-- Note -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-sticky-note mr-2 text-primary"></i>Ghi chú</h2>
                    </div>
                    <div class="p-4">
                        <textarea name="note" rows="4" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">{{ old('note') }}</textarea>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white rounded-lg shadow-sm p-4 space-y-2">
                    <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors font-medium text-sm">
                        <i class="fas fa-save mr-2"></i>Lưu
                    </button>
                    <a href="{{ route('purchase-pricings.index') }}" class="w-full inline-block text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 text-sm">
                        Hủy
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function calculatePrices() {
    const quantity = parseInt(document.querySelector('[name="quantity"]').value) || 1;
    const purchasePrice = parseFloat(document.getElementById('purchase_price').value) || 0;
    const discountPercent = parseFloat(document.getElementById('discount_percent').value) || 0;
    const vatPercent = parseFloat(document.getElementById('vat_percent').value) || 0;
    
    const priceAfterDiscount = purchasePrice * (1 - discountPercent / 100);
    document.getElementById('price_after_discount').value = formatNumber(priceAfterDiscount) + 'đ';
    
    const shippingCost = parseFloat(document.getElementById('shipping_cost').value) || 0;
    const loadingCost = parseFloat(document.getElementById('loading_cost').value) || 0;
    const inspectionCost = parseFloat(document.getElementById('inspection_cost').value) || 0;
    const otherCost = parseFloat(document.getElementById('other_cost').value) || 0;
    
    const totalServiceCost = shippingCost + loadingCost + inspectionCost + otherCost;
    document.getElementById('total_service_cost').value = formatNumber(totalServiceCost) + 'đ';
    
    const serviceCostPerUnit = quantity > 0 ? totalServiceCost / quantity : 0;
    document.getElementById('service_cost_per_unit').value = formatNumber(serviceCostPerUnit) + 'đ';
    
    const priceWithVat = priceAfterDiscount * (1 + vatPercent / 100);
    const warehousePrice = priceWithVat + serviceCostPerUnit;
    document.getElementById('warehouse_price').value = formatNumber(warehousePrice) + 'đ';
}

function formatNumber(num) {
    return Math.round(num).toLocaleString('vi-VN');
}

document.addEventListener('DOMContentLoaded', calculatePrices);
</script>
@endsection
