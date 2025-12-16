@extends('layouts.app')

@section('title', 'Chi tiết yêu cầu báo giá')
@section('page-title', 'Chi tiết yêu cầu báo giá: ' . $purchaseRequest->code)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header Actions -->
    <div class="flex justify-between items-center">
        <a href="{{ route('purchase-requests.index') }}" class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại
        </a>
        <div class="flex space-x-2">
            @if($purchaseRequest->status == 'draft')
                <a href="{{ route('purchase-requests.edit', $purchaseRequest) }}" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                    <i class="fas fa-edit mr-2"></i> Sửa
                </a>
                <form action="{{ route('purchase-requests.send', $purchaseRequest) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700" onclick="return confirm('Gửi yêu cầu báo giá cho các NCC?')">
                        <i class="fas fa-paper-plane mr-2"></i> Gửi NCC
                    </button>
                </form>
            @endif
            @if(in_array($purchaseRequest->status, ['sent', 'received']))
                <a href="{{ route('supplier-quotations.create', ['purchase_request_id' => $purchaseRequest->id]) }}" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                    <i class="fas fa-plus mr-2"></i> Nhập báo giá NCC
                </a>
            @endif
        </div>
    </div>

    <!-- Info Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div>
                <p class="text-sm text-gray-500">Mã yêu cầu</p>
                <p class="font-semibold text-primary">{{ $purchaseRequest->code }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Trạng thái</p>
                @if($purchaseRequest->status == 'draft')
                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Nháp</span>
                @elseif($purchaseRequest->status == 'sent')
                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Đã gửi NCC</span>
                @elseif($purchaseRequest->status == 'received')
                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Đã nhận báo giá</span>
                @elseif($purchaseRequest->status == 'converted')
                    <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">Đã chuyển PO</span>
                @endif
            </div>
            <div>
                <p class="text-sm text-gray-500">Hạn báo giá</p>
                <p class="font-medium">{{ $purchaseRequest->deadline->format('d/m/Y') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Mức ưu tiên</p>
                @if($purchaseRequest->priority == 'urgent')
                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Khẩn cấp</span>
                @elseif($purchaseRequest->priority == 'high')
                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Cao</span>
                @else
                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Bình thường</span>
                @endif
            </div>
        </div>

        <div class="mb-6">
            <p class="text-sm text-gray-500 mb-1">Tiêu đề</p>
            <p class="font-medium">{{ $purchaseRequest->title }}</p>
        </div>

        <div class="mb-6">
            <p class="text-sm text-gray-500 mb-2">Nhà cung cấp được gửi</p>
            <div class="flex flex-wrap gap-2">
                @foreach($purchaseRequest->suppliers as $supplier)
                    <span class="px-3 py-1 bg-gray-100 rounded-full text-sm">
                        <i class="fas fa-building mr-1 text-gray-400"></i> {{ $supplier->name }}
                    </span>
                @endforeach
            </div>
        </div>

        @if($purchaseRequest->requirements)
        <div class="mb-6">
            <p class="text-sm text-gray-500 mb-1">Yêu cầu đặc biệt</p>
            <p class="text-gray-700">{{ $purchaseRequest->requirements }}</p>
        </div>
        @endif
    </div>

    <!-- Items -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b bg-gray-50">
            <h3 class="font-semibold">Danh sách sản phẩm cần báo giá</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Đơn vị</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quy cách</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($purchaseRequest->items as $index => $item)
                <tr>
                    <td class="px-4 py-3">{{ $index + 1 }}</td>
                    <td class="px-4 py-3 font-medium">{{ $item->product_name }}</td>
                    <td class="px-4 py-3">{{ number_format($item->quantity) }}</td>
                    <td class="px-4 py-3">{{ $item->unit }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $item->specifications ?: '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Quotations received -->
    @if($purchaseRequest->quotations->count() > 0)
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
            <h3 class="font-semibold">Báo giá đã nhận ({{ $purchaseRequest->quotations->count() }})</h3>
            @if($purchaseRequest->quotations->count() >= 2)
                <a href="{{ route('supplier-quotations.compare', ['ids' => $purchaseRequest->quotations->pluck('id')->toArray()]) }}" class="text-primary hover:text-primary-dark text-sm">
                    <i class="fas fa-balance-scale mr-1"></i> So sánh báo giá
                </a>
            @endif
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nhà cung cấp</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày báo giá</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tổng tiền</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($purchaseRequest->quotations as $quotation)
                <tr>
                    <td class="px-4 py-3 font-medium text-primary">{{ $quotation->code }}</td>
                    <td class="px-4 py-3">{{ $quotation->supplier->name }}</td>
                    <td class="px-4 py-3">{{ $quotation->quotation_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 font-semibold text-green-600">{{ number_format($quotation->total) }}đ</td>
                    <td class="px-4 py-3">
                        @if($quotation->status == 'pending')
                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Chờ xử lý</span>
                        @elseif($quotation->status == 'selected')
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Đã chọn</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Từ chối</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('supplier-quotations.show', $quotation) }}" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if($quotation->status == 'pending')
                            <form action="{{ route('supplier-quotations.select', $quotation) }}" method="POST" class="inline ml-2">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-800" title="Chọn báo giá này">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
