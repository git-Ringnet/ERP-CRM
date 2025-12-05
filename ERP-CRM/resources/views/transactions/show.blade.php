@extends('layouts.app')

@section('title', 'Chi tiết giao dịch')
@section('page-title', 'Chi tiết giao dịch')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">{{ $transaction->code }}</h2>
            <div class="flex gap-2">
                @if($transaction->status === 'pending')
                    <a href="{{ route('transactions.edit', $transaction) }}" 
                       class="px-3 py-1.5 text-sm text-white bg-blue-500 rounded-lg hover:bg-blue-600 transition-colors">
                        <i class="fas fa-edit mr-1"></i>Chỉnh sửa
                    </a>
                    <form action="{{ route('transactions.approve', $transaction) }}" method="POST" class="inline"
                          onsubmit="return confirm('Bạn có chắc muốn duyệt phiếu này? Sau khi duyệt sẽ không thể chỉnh sửa.')">
                        @csrf
                        <button type="submit" 
                                class="px-3 py-1.5 text-sm text-white bg-green-500 rounded-lg hover:bg-green-600 transition-colors">
                            <i class="fas fa-check mr-1"></i>Duyệt phiếu
                        </button>
                    </form>
                @endif
                <a href="{{ route('transactions.index') }}" 
                   class="px-3 py-1.5 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-arrow-left mr-1"></i>Quay lại
                </a>
            </div>
        </div>
        
        <div class="p-4">
            <!-- Status Badges -->
            <div class="mb-4 flex flex-wrap gap-2">
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-{{ $transaction->type_color }}-100 text-{{ $transaction->type_color }}-800">
                    <i class="fas fa-{{ $transaction->type === 'import' ? 'arrow-down' : ($transaction->type === 'export' ? 'arrow-up' : 'exchange-alt') }} mr-1"></i>
                    {{ $transaction->type_label }}
                </span>
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-{{ $transaction->status_color }}-100 text-{{ $transaction->status_color }}-800">
                    {{ $transaction->status_label }}
                </span>
            </div>

            <!-- Info Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-500">Mã giao dịch</label>
                        <p class="font-medium text-gray-900">{{ $transaction->code }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Kho {{ $transaction->type === 'export' ? 'xuất' : 'nhập' }}</label>
                        <p class="font-medium text-gray-900">{{ $transaction->warehouse->name }}</p>
                    </div>
                    @if($transaction->type === 'transfer' && $transaction->toWarehouse)
                    <div>
                        <label class="text-sm text-gray-500">Kho đích</label>
                        <p class="font-medium text-gray-900">{{ $transaction->toWarehouse->name }}</p>
                    </div>
                    @endif
                </div>
                
                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-500">Ngày giao dịch</label>
                        <p class="font-medium text-gray-900">{{ $transaction->date->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Nhân viên</label>
                        <p class="font-medium text-gray-900">{{ $transaction->employee?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Tổng số lượng</label>
                        <p class="text-xl font-bold text-blue-600">{{ number_format($transaction->total_qty) }}</p>
                    </div>
                </div>
            </div>

            @if($transaction->note)
            <div class="mb-6">
                <label class="text-sm text-gray-500">Ghi chú</label>
                <p class="font-medium text-gray-900">{{ $transaction->note }}</p>
            </div>
            @endif

            <!-- Items Table -->
            <div class="border-t border-gray-200 pt-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Chi tiết sản phẩm</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Đơn vị</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Serial</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Giá vốn</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($transaction->items as $item)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900">{{ $item->product->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $item->product->code }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ number_format($item->quantity) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $item->unit ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $item->serial_number ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ number_format($item->cost ?? 0) }} đ</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ number_format($item->total_value) }} đ</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Timestamps -->
            <div class="mt-6 pt-4 border-t border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-500">
                    <div>
                        <label class="text-xs text-gray-400">Ngày tạo</label>
                        <p>{{ $transaction->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Cập nhật lần cuối</label>
                        <p>{{ $transaction->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
