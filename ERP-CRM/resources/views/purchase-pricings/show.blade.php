@extends('layouts.app')

@section('title', 'Chi tiết giá nhập')
@section('page-title', 'Chi tiết giá nhập - ' . ($purchasePricing->product->name ?? 'N/A'))

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <a href="{{ route('purchase-pricings.index') }}" class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
        <a href="{{ route('purchase-pricings.edit', $purchasePricing) }}" class="inline-flex items-center px-3 py-1.5 text-sm bg-yellow-100 text-yellow-700 rounded-md hover:bg-yellow-200">
            <i class="fas fa-edit mr-2"></i>Sửa
        </a>
    </div>

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
                            <p class="text-sm text-gray-600">Mã sản phẩm</p>
                            <p class="font-medium">{{ $purchasePricing->product->code ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Tên sản phẩm</p>
                            <p class="font-medium">{{ $purchasePricing->product->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Nhà cung cấp</p>
                            <p class="font-medium">{{ $purchasePricing->supplier->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Số lượng</p>
                            <p class="font-medium">{{ number_format($purchasePricing->quantity) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Đơn mua hàng</p>
                            <p class="font-medium">{{ $purchasePricing->purchaseOrder->code ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Người tạo</p>
                            <p class="font-medium">{{ $purchasePricing->creator->name ?? 'N/A' }}</p>
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
                            <p class="text-sm text-gray-600">Giá nhập gốc</p>
                            <p class="font-medium">{{ number_format($purchasePricing->purchase_price, 0, ',', '.') }}đ</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Chiết khấu</p>
                            <p class="font-medium">{{ $purchasePricing->discount_percent }}%</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Giá sau chiết khấu</p>
                            <p class="font-medium">{{ number_format($purchasePricing->price_after_discount, 0, ',', '.') }}đ</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">VAT</p>
                            <p class="font-medium">{{ $purchasePricing->vat_percent }}%</p>
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <p class="text-sm text-gray-600">Chi phí vận chuyển</p>
                            <p class="font-medium">{{ number_format($purchasePricing->shipping_cost, 0, ',', '.') }}đ</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Chi phí bốc xếp</p>
                            <p class="font-medium">{{ number_format($purchasePricing->loading_cost, 0, ',', '.') }}đ</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Chi phí kiểm tra</p>
                            <p class="font-medium">{{ number_format($purchasePricing->inspection_cost, 0, ',', '.') }}đ</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Chi phí khác</p>
                            <p class="font-medium">{{ number_format($purchasePricing->other_cost, 0, ',', '.') }}đ</p>
                        </div>
                    </div>
                    <div class="border-t pt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Tổng chi phí phục vụ</p>
                            <p class="font-bold text-orange-600">{{ number_format($purchasePricing->total_service_cost, 0, ',', '.') }}đ</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">CP phục vụ/đơn vị</p>
                            <p class="font-bold text-orange-600">{{ number_format($purchasePricing->service_cost_per_unit, 0, ',', '.') }}đ</p>
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
                <div class="p-4 text-center">
                    <p class="text-sm text-gray-600 mb-2">Phương pháp tính giá</p>
                    @if($purchasePricing->pricing_method == 'fifo')
                        <span class="inline-block px-3 py-1 text-sm font-medium bg-green-100 text-green-800 rounded-full">FIFO</span>
                    @elseif($purchasePricing->pricing_method == 'lifo')
                        <span class="inline-block px-3 py-1 text-sm font-medium bg-yellow-100 text-yellow-800 rounded-full">LIFO</span>
                    @else
                        <span class="inline-block px-3 py-1 text-sm font-medium bg-blue-100 text-blue-800 rounded-full">Bình quân gia quyền</span>
                    @endif
                    <div class="border-t mt-4 pt-4">
                        <p class="text-3xl font-bold text-green-600">{{ number_format($purchasePricing->warehouse_price, 0, ',', '.') }}đ</p>
                        <p class="text-sm text-gray-500">Giá kho/đơn vị</p>
                    </div>
                </div>
            </div>

            <!-- Price History -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-history mr-2 text-primary"></i>Lịch sử giá</h2>
                </div>
                <div class="p-4">
                    @if($priceHistory->count() > 0)
                        <ul class="divide-y divide-gray-200">
                            @foreach($priceHistory as $history)
                                <li class="py-2 flex justify-between items-center {{ $history->id == $purchasePricing->id ? 'bg-gray-50 -mx-4 px-4' : '' }}">
                                    <span class="text-sm text-gray-600">{{ $history->created_at->format('d/m/Y') }}</span>
                                    <span class="font-bold text-sm">{{ number_format($history->warehouse_price, 0, ',', '.') }}đ</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-500">Chưa có lịch sử giá</p>
                    @endif
                </div>
            </div>

            <!-- Note -->
            @if($purchasePricing->note)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-sticky-note mr-2 text-primary"></i>Ghi chú</h2>
                </div>
                <div class="p-4">
                    <p class="text-sm text-gray-700">{{ $purchasePricing->note }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
