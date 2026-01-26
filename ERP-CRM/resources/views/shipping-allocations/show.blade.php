@extends('layouts.app')

@section('title', 'Chi tiết phân bổ chi phí vận chuyển')
@section('page-title', 'Chi tiết phân bổ - ' . $shippingAllocation->code)

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <a href="{{ route('shipping-allocations.index') }}" class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
        <div class="flex gap-2">
            @if($shippingAllocation->status == 'draft')
                <a href="{{ route('shipping-allocations.edit', $shippingAllocation) }}" class="inline-flex items-center px-3 py-1.5 text-sm bg-yellow-100 text-yellow-700 rounded-md hover:bg-yellow-200">
                    <i class="fas fa-edit mr-2"></i>Sửa
                </a>
                <form action="{{ route('shipping-allocations.approve', $shippingAllocation) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" onclick="return confirm('Duyệt phiếu này?')" class="inline-flex items-center px-3 py-1.5 text-sm bg-green-500 text-white rounded-md hover:bg-green-600">
                        <i class="fas fa-check mr-2"></i>Duyệt
                    </button>
                </form>
            @elseif($shippingAllocation->status == 'approved')
                <form action="{{ route('shipping-allocations.complete', $shippingAllocation) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" onclick="return confirm('Hoàn thành phiếu này?')" class="inline-flex items-center px-3 py-1.5 text-sm bg-primary text-white rounded-md hover:bg-primary-dark">
                        <i class="fas fa-check-double mr-2"></i>Hoàn thành
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <!-- Basic Info -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-info-circle mr-2 text-primary"></i>Thông tin cơ bản</h2>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Mã phiếu</p>
                            <p class="font-medium">{{ $shippingAllocation->code }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Đơn mua hàng</p>
                            <p class="font-medium">{{ $shippingAllocation->purchaseOrder->code ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Nhà cung cấp</p>
                            <p class="font-medium">{{ $shippingAllocation->purchaseOrder->supplier->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Kho nhận</p>
                            <p class="font-medium">{{ $shippingAllocation->warehouse->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Ngày phân bổ</p>
                            <p class="font-medium">{{ $shippingAllocation->allocation_date->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Phương pháp</p>
                            @if($shippingAllocation->allocation_method == 'value')
                                <span class="inline-block px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">Theo giá trị</span>
                            @elseif($shippingAllocation->allocation_method == 'quantity')
                                <span class="inline-block px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">Theo số lượng</span>
                            @elseif($shippingAllocation->allocation_method == 'weight')
                                <span class="inline-block px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">Theo trọng lượng</span>
                            @else
                                <span class="inline-block px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Theo thể tích</span>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Trạng thái</p>
                            @if($shippingAllocation->status == 'draft')
                                <span class="inline-block px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Nháp</span>
                            @elseif($shippingAllocation->status == 'approved')
                                <span class="inline-block px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">Đã duyệt</span>
                            @else
                                <span class="inline-block px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">Hoàn thành</span>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Người tạo</p>
                            <p class="font-medium">{{ $shippingAllocation->creator->name ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Items -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-boxes mr-2 text-primary"></i>Chi tiết phân bổ theo sản phẩm</h2>
                </div>
                <div class="p-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Sản phẩm</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">SL</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Đơn giá</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Tổng giá trị</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">CP phân bổ</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">CP/đơn vị</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($shippingAllocation->items as $item)
                                <tr>
                                    <td class="px-3 py-2">{{ $item->product->name ?? 'N/A' }}</td>
                                    <td class="px-3 py-2 text-center">{{ number_format($item->quantity) }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($item->unit_value, 0, ',', '.') }}đ</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($item->total_value, 0, ',', '.') }}đ</td>
                                    <td class="px-3 py-2 text-right text-green-600 font-bold">{{ number_format($item->allocated_cost, 0, ',', '.') }}đ</td>
                                    <td class="px-3 py-2 text-right text-blue-600">{{ number_format($item->allocated_cost_per_unit, 0, ',', '.') }}đ</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium" colspan="3">Tổng cộng</th>
                                <th class="px-3 py-2 text-right font-medium">{{ number_format($shippingAllocation->total_value, 0, ',', '.') }}đ</th>
                                <th class="px-3 py-2 text-right font-medium text-green-600">{{ number_format($shippingAllocation->total_allocated, 0, ',', '.') }}đ</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <!-- Summary -->
            <div class="bg-white rounded-lg shadow-sm border-2 border-green-500">
                <div class="px-4 py-3 bg-green-500 text-white rounded-t-lg">
                    <h2 class="text-base font-semibold"><i class="fas fa-calculator mr-2"></i>Tổng kết</h2>
                </div>
                <div class="p-4 space-y-4">
                    <div>
                        <p class="text-sm text-gray-600">Tổng CP vận chuyển</p>
                        <p class="text-xl font-bold text-primary">{{ number_format($shippingAllocation->total_shipping_cost, 0, ',', '.') }}đ</p>
                    </div>
                    <div class="border-t pt-4">
                        <p class="text-sm text-gray-600">Tổng giá trị hàng</p>
                        <p class="font-bold">{{ number_format($shippingAllocation->total_value, 0, ',', '.') }}đ</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Tổng CP đã phân bổ</p>
                        <p class="font-bold text-green-600">{{ number_format($shippingAllocation->total_allocated, 0, ',', '.') }}đ</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Chênh lệch</p>
                        @php $difference = $shippingAllocation->total_shipping_cost - $shippingAllocation->total_allocated; @endphp
                        <p class="font-bold {{ abs($difference) < 1 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($difference, 0, ',', '.') }}đ</p>
                    </div>
                </div>
            </div>

            <!-- Approval Info -->
            @if($shippingAllocation->approved_at)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-check-circle mr-2 text-primary"></i>Thông tin duyệt</h2>
                </div>
                <div class="p-4">
                    <p class="text-sm text-gray-600">Người duyệt</p>
                    <p class="font-medium mb-2">{{ $shippingAllocation->approver->name ?? 'N/A' }}</p>
                    <p class="text-sm text-gray-600">Ngày duyệt</p>
                    <p class="font-medium">{{ $shippingAllocation->approved_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
            @endif

            <!-- Related Imports -->
            @if($shippingAllocation->imports->count() > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-800">
                        <i class="fas fa-file-import mr-2 text-blue-500"></i>Phiếu nhập đã sử dụng phân bổ này
                    </h2>
                </div>
                <div class="p-4">
                    <div class="space-y-2">
                        @foreach($shippingAllocation->imports as $import)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-arrow-down text-blue-500"></i>
                                <div>
                                    <a href="{{ route('imports.show', $import) }}" class="font-medium text-blue-600 hover:underline">
                                        {{ $import->code }}
                                    </a>
                                    <p class="text-xs text-gray-500">
                                        {{ $import->date->format('d/m/Y') }} - 
                                        {{ $import->items->sum('quantity') }} sản phẩm
                                    </p>
                                </div>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                {{ $import->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $import->status_label }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @else
            <div class="bg-white rounded-lg shadow-sm border-2 border-dashed border-gray-300">
                <div class="p-6 text-center">
                    <i class="fas fa-info-circle text-gray-400 text-3xl mb-3"></i>
                    <p class="text-sm font-medium text-gray-700 mb-2">Chưa có phiếu nhập nào sử dụng phân bổ này</p>
                    <p class="text-xs text-gray-500 mb-4">Phân bổ này có thể được chọn khi tạo phiếu nhập kho</p>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-left max-w-md mx-auto">
                        <p class="text-xs font-semibold text-blue-800 mb-2">
                            <i class="fas fa-lightbulb mr-1"></i>Cách sử dụng:
                        </p>
                        <ol class="text-xs text-blue-700 space-y-1 list-decimal list-inside">
                            <li>Vào menu <strong>Nhập kho</strong> → <strong>Tạo phiếu nhập</strong></li>
                            <li>Tick chọn <strong>"Sử dụng phân bổ chi phí vận chuyển"</strong></li>
                            <li>Chọn phiếu phân bổ này: <strong>{{ $shippingAllocation->code }}</strong></li>
                            <li>Chi phí sẽ tự động phân bổ theo phương pháp: <strong>{{ $shippingAllocation->method_label }}</strong></li>
                        </ol>
                        <div class="mt-3 pt-3 border-t border-blue-200">
                            <a href="{{ route('imports.create') }}" class="inline-flex items-center text-xs font-medium text-blue-600 hover:text-blue-800">
                                <i class="fas fa-plus-circle mr-1"></i>Tạo phiếu nhập ngay
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Note -->
            @if($shippingAllocation->note)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-sticky-note mr-2 text-primary"></i>Ghi chú</h2>
                </div>
                <div class="p-4">
                    <p class="text-sm text-gray-700">{{ $shippingAllocation->note }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
