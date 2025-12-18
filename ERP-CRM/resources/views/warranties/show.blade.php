@extends('layouts.app')

@section('title', 'Chi tiết bảo hành')
@section('page-title', 'Chi tiết bảo hành')

@section('content')
<div class=" mx-auto space-y-6">
    <!-- Back Button -->
    <div>
        <a href="{{ route('warranties.index') }}" class="text-primary hover:text-primary-dark">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại danh sách
        </a>
    </div>

    <!-- Warranty Status Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-shield-alt mr-2 text-primary"></i>Thông tin bảo hành
            </h2>
            @php $color = $statusColors[$saleItem->warranty_status] ?? 'gray'; @endphp
            <span class="px-3 py-1 text-sm rounded-full 
                {{ $color === 'green' ? 'bg-green-100 text-green-800' : '' }}
                {{ $color === 'red' ? 'bg-red-100 text-red-800' : '' }}
                {{ $color === 'gray' ? 'bg-gray-100 text-gray-800' : '' }}">
                {{ $statusLabels[$saleItem->warranty_status] ?? $saleItem->warranty_status }}
            </span>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-500">Thời gian bảo hành</p>
                    <p class="text-lg font-semibold">{{ $saleItem->warranty_months ?? 0 }} tháng</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Ngày bắt đầu</p>
                    <p class="text-lg font-semibold">
                        {{ $saleItem->warranty_start_date ? $saleItem->warranty_start_date->format('d/m/Y') : '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Ngày hết hạn</p>
                    <p class="text-lg font-semibold">
                        {{ $saleItem->warranty_end_date ? $saleItem->warranty_end_date->format('d/m/Y') : '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Thời gian còn lại</p>
                    @if($saleItem->warranty_days_remaining !== null)
                        <p class="text-lg font-semibold {{ $saleItem->warranty_days_remaining < 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ $saleItem->warranty_days_remaining }} ngày
                        </p>
                    @else
                        <p class="text-lg font-semibold text-gray-400">-</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Product Info -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-box mr-2 text-primary"></i>Thông tin sản phẩm
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-500">Mã sản phẩm</p>
                    <p class="font-semibold">{{ $saleItem->product->code ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Tên sản phẩm</p>
                    <p class="font-semibold">{{ $saleItem->product_name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Số lượng</p>
                    <p class="font-semibold">{{ $saleItem->quantity }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">BH mặc định của SP</p>
                    <p class="font-semibold">{{ $saleItem->product->warranty_months ?? 0 }} tháng</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sale & Customer Info -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-file-invoice mr-2 text-primary"></i>Thông tin đơn hàng & Khách hàng
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-500">Mã đơn hàng</p>
                    <p class="font-semibold">
                        <a href="{{ route('sales.show', $saleItem->sale_id) }}" class="text-primary hover:underline">
                            {{ $saleItem->sale->code ?? '-' }}
                        </a>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Ngày bán</p>
                    <p class="font-semibold">{{ $saleItem->sale->date ? $saleItem->sale->date->format('d/m/Y') : '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Khách hàng</p>
                    <p class="font-semibold">{{ $saleItem->sale->customer_name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Số điện thoại</p>
                    <p class="font-semibold">{{ $saleItem->sale->customer->phone ?? '-' }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-500">Địa chỉ giao hàng</p>
                    <p class="font-semibold">{{ $saleItem->sale->delivery_address ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
