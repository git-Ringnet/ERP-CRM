@extends('layouts.app')

@section('title', 'Chi tiết phiếu chuyển')
@section('page-title', 'Chi tiết Phiếu Chuyển Kho')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">{{ $transfer->code }}</h2>
        <div class="flex gap-2">
            @if($transfer->status === 'pending')
                <a href="{{ route('transfers.edit', $transfer) }}" 
                   class="px-3 py-1.5 text-sm text-white bg-blue-500 rounded-lg hover:bg-blue-600">
                    <i class="fas fa-edit mr-1"></i>Chỉnh sửa
                </a>
                <form action="{{ route('transfers.approve', $transfer) }}" method="POST" class="inline"
                      onsubmit="return confirm('Bạn có chắc muốn duyệt phiếu này?')">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 text-sm text-white bg-green-500 rounded-lg hover:bg-green-600">
                        <i class="fas fa-check mr-1"></i>Duyệt phiếu
                    </button>
                </form>
            @endif
            <a href="{{ route('transfers.index') }}" 
               class="px-3 py-1.5 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-1"></i>Quay lại
            </a>
        </div>
    </div>
    
    <div class="p-4">
        <div class="mb-4 flex flex-wrap gap-2">
            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-purple-100 text-purple-800">
                <i class="fas fa-exchange-alt mr-1"></i>Chuyển kho
            </span>
            @if($transfer->status === 'pending')
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">Chờ xử lý</span>
            @else
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">Hoàn thành</span>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-500">Mã phiếu</label>
                    <p class="font-medium text-gray-900">{{ $transfer->code }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Kho nguồn</label>
                    <p class="font-medium text-gray-900">{{ $transfer->warehouse->name }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Kho đích</label>
                    <p class="font-medium text-gray-900">{{ $transfer->toWarehouse->name ?? '-' }}</p>
                </div>
            </div>
            
            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-500">Ngày chuyển</label>
                    <p class="font-medium text-gray-900">{{ $transfer->date->format('d/m/Y') }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Nhân viên</label>
                    <p class="font-medium text-gray-900">{{ $transfer->employee?->name ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Tổng số lượng</label>
                    <p class="text-xl font-bold text-purple-600">{{ number_format($transfer->total_qty) }}</p>
                </div>
            </div>
        </div>

        @if($transfer->note)
        <div class="mb-6">
            <label class="text-sm text-gray-500">Ghi chú</label>
            <p class="font-medium text-gray-900">{{ $transfer->note }}</p>
        </div>
        @endif

        <div class="border-t border-gray-200 pt-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Chi tiết sản phẩm</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Đơn vị</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($transfer->items as $item)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ $item->product->name }}</div>
                                <div class="text-sm text-gray-500">{{ $item->product->code }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 text-sm font-semibold bg-purple-100 text-purple-800 rounded">
                                    {{ number_format($item->quantity) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $item->unit ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6 pt-4 border-t border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-500">
                <div>
                    <label class="text-xs text-gray-400">Ngày tạo</label>
                    <p>{{ $transfer->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <label class="text-xs text-gray-400">Cập nhật lần cuối</label>
                    <p>{{ $transfer->updated_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
