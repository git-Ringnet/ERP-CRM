@extends('layouts.app')

@section('title', 'Chi tiết nhà cung cấp')
@section('page-title', 'Chi tiết nhà cung cấp')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <a href="{{ route('suppliers.index') }}" 
           class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
        <div class="flex gap-2">
            <a href="{{ route('suppliers.edit', $supplier->id) }}" 
               class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-edit mr-2"></i>Chỉnh sửa
            </a>
            <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" class="inline delete-form">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-danger text-white rounded-lg hover:bg-red-700 transition-colors delete-btn"
                        data-name="{{ $supplier->name }}">
                    <i class="fas fa-trash mr-2"></i>Xóa
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Thống kê tổng quan -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
                    <div class="text-sm text-gray-500 mb-1">Đơn mua hàng</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['total_purchase_orders'] }}</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-green-500">
                    <div class="text-sm text-gray-500 mb-1">Báo giá</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['total_quotations'] }}</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-purple-500">
                    <div class="text-sm text-gray-500 mb-1">Bảng giá</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['total_price_lists'] }}</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-orange-500">
                    <div class="text-sm text-gray-500 mb-1">Phiếu nhập</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['total_imports'] }}</div>
                </div>
            </div>

            <!-- Thông tin cơ bản -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-truck mr-2 text-primary"></i>Thông tin cơ bản
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Mã NCC</label>
                            <p class="text-base font-semibold text-gray-900">{{ $supplier->code }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Tên nhà cung cấp</label>
                            <p class="text-base font-semibold text-gray-900">{{ $supplier->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Email</label>
                            <p class="text-base text-gray-900">
                                <a href="mailto:{{ $supplier->email }}" class="text-primary hover:underline">
                                    <i class="fas fa-envelope mr-1 text-gray-400"></i>{{ $supplier->email }}
                                </a>
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Điện thoại</label>
                            <p class="text-base text-gray-900">
                                <a href="tel:{{ $supplier->phone }}" class="text-primary hover:underline">
                                    <i class="fas fa-phone mr-1 text-gray-400"></i>{{ $supplier->phone }}
                                </a>
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Người liên hệ</label>
                            <p class="text-base text-gray-900">{{ $supplier->contact_person ?: 'Chưa cập nhật' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Mã số thuế</label>
                            <p class="text-base text-gray-900">{{ $supplier->tax_code ?: 'Chưa cập nhật' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-500 mb-1">Địa chỉ</label>
                            <p class="text-base text-gray-900">
                                <i class="fas fa-map-marker-alt mr-1 text-gray-400"></i>
                                {{ $supplier->address ?: 'Chưa cập nhật' }}
                            </p>
                        </div>
                        @if($supplier->website)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Website</label>
                            <p class="text-base text-gray-900">
                                <a href="{{ $supplier->website }}" target="_blank" class="text-primary hover:underline">
                                    <i class="fas fa-globe mr-1 text-gray-400"></i>{{ $supplier->website }}
                                </a>
                            </p>
                        </div>
                        @endif
                        @if($supplier->product_type)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Loại sản phẩm</label>
                            <p class="text-base text-gray-900">{{ $supplier->product_type }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Ghi chú -->
            @if($supplier->note)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-sticky-note mr-2 text-primary"></i>Ghi chú
                    </h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-700 whitespace-pre-line">{{ $supplier->note }}</p>
                </div>
            </div>
            @endif

            <!-- Đơn mua hàng gần đây -->
            @if($supplier->purchaseOrders->count() > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-shopping-cart mr-2 text-primary"></i>Đơn mua hàng gần đây
                    </h2>
                    <span class="text-sm text-gray-500">{{ $stats['total_purchase_orders'] }} đơn</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã đơn</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày đặt</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tổng tiền</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($supplier->purchaseOrders as $po)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('purchase-orders.show', $po->id) }}" class="text-primary hover:underline font-medium">
                                        {{ $po->code }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $po->order_date->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ number_format($po->total, 0, ',', '.') }} đ
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ $po->status_label }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Phiếu nhập kho gần đây -->
            @if($supplier->imports->count() > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-box mr-2 text-primary"></i>Phiếu nhập kho gần đây
                    </h2>
                    <span class="text-sm text-gray-500">{{ $stats['total_imports'] }} phiếu</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã phiếu</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày nhập</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($supplier->imports as $import)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('imports.show', $import->id) }}" class="text-primary hover:underline font-medium">
                                        {{ $import->code }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $import->date->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $import->warehouse->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ number_format($import->total_qty) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        @if($import->status === 'completed') bg-green-100 text-green-800
                                        @elseif($import->status === 'pending') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ $import->status_label }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Báo giá gần đây -->
            @if($supplier->supplierQuotations->count() > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-file-invoice mr-2 text-primary"></i>Báo giá gần đây
                    </h2>
                    <span class="text-sm text-gray-500">{{ $stats['total_quotations'] }} báo giá</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã báo giá</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày báo giá</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hiệu lực đến</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tổng tiền</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($supplier->supplierQuotations as $quotation)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('supplier-quotations.show', $quotation->id) }}" class="text-primary hover:underline font-medium">
                                        {{ $quotation->code }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $quotation->quotation_date->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $quotation->valid_until->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ number_format($quotation->total, 0, ',', '.') }} đ
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        @if($quotation->status === 'selected') bg-green-100 text-green-800
                                        @elseif($quotation->status === 'pending') bg-yellow-100 text-yellow-800
                                        @else bg-red-100 text-red-800
                                        @endif">
                                        {{ $quotation->status_label }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Bảng giá -->
            @if($supplier->supplierPriceLists->count() > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-list-alt mr-2 text-primary"></i>Bảng giá
                    </h2>
                    <span class="text-sm text-gray-500">{{ $stats['total_price_lists'] }} bảng giá</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã bảng giá</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hiệu lực</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($supplier->supplierPriceLists as $priceList)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('supplier-price-lists.show', $priceList->id) }}" class="text-primary hover:underline font-medium">
                                        {{ $priceList->code }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $priceList->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $priceList->effective_date ? $priceList->effective_date->format('d/m/Y') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        {{ $priceList->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $priceList->is_active ? 'Đang dùng' : 'Không dùng' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Điều khoản thanh toán -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-credit-card mr-2 text-primary"></i>Thanh toán
                    </h2>
                </div>
                <div class="p-6">
                    <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                        <label class="block text-sm font-medium text-blue-700 mb-1">Điều khoản thanh toán</label>
                        <p class="text-2xl font-bold text-blue-900">{{ $supplier->payment_terms }} ngày</p>
                    </div>
                </div>
            </div>

            <!-- Thông tin hệ thống -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-info-circle mr-2 text-primary"></i>Thông tin hệ thống
                    </h2>
                </div>
                <div class="p-6 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Ngày tạo</span>
                        <span class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($supplier->created_at)->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Cập nhật lần cuối</span>
                        <span class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($supplier->updated_at)->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Thao tác nhanh</h3>
                <div class="space-y-2">
                    <a href="{{ route('suppliers.edit', $supplier->id) }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                        <i class="fas fa-edit mr-2"></i>Chỉnh sửa
                    </a>
                    <a href="{{ route('suppliers.index') }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-list mr-2"></i>Danh sách
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
