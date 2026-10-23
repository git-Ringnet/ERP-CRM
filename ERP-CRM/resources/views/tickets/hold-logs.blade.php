@extends('layouts.app')

@section('title', 'Lịch sử giữ hàng')
@section('page-title', 'Lịch sử giữ hàng')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('tickets.index') }}"
            class="inline-flex items-center text-sm font-semibold text-gray-500 hover:text-gray-700">
            <i class="fas fa-chevron-left mr-1"></i> Danh sách ticket
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'runrate']) }}"
            class="inline-flex items-center text-sm font-semibold text-gray-500 hover:text-gray-700">
            <i class="fas fa-warehouse mr-1"></i> Tồn kho runrate
        </a>
    </div>

    <!-- Filter form -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <form method="GET" action="{{ route('tickets.hold-logs') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            @if($selectedProduct)
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Sản phẩm</label>
                    <input type="hidden" name="product_id" value="{{ $selectedProduct->id }}">
                    <div class="flex items-center gap-2 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm">
                        <span class="font-mono font-semibold text-gray-800 truncate" title="{{ $selectedProduct->name }}">{{ $selectedProduct->code }}</span>
                        <a href="{{ route('tickets.hold-logs', request()->except(['product_id', 'page'])) }}" class="ml-auto text-gray-400 hover:text-red-500" title="Bỏ lọc sản phẩm">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            @endif
            <div>
                <label for="user_id" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Nhân viên (chuyển đi / nhận)</label>
                <select name="user_id" id="user_id" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <option value="">Tất cả</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ (string) request('user_id') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="date_from" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Từ ngày</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
            </div>
            <div>
                <label for="date_to" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Đến ngày</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full md:w-auto px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition-colors">
                    Lọc dữ liệu
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <h3 class="text-md font-bold text-gray-800 flex items-center">
                <i class="fas fa-history text-purple-500 mr-2"></i>
                Lịch sử chuyển giữ hàng runrate
            </h3>
            <span class="bg-purple-100 text-purple-800 text-xs font-bold px-2 py-0.5 rounded-full">{{ $logs->total() }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-semibold">
                    <tr class="divide-x divide-gray-100 border-b border-gray-200">
                        <th class="px-4 py-3 whitespace-nowrap">Thời gian</th>
                        <th class="px-4 py-3 whitespace-nowrap">Mã SP</th>
                        <th class="px-4 py-3">Tên SP</th>
                        <th class="px-4 py-3 whitespace-nowrap">Từ</th>
                        <th class="px-4 py-3 whitespace-nowrap">Đến</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Số lượng</th>
                        <th class="px-4 py-3 whitespace-nowrap">Ticket</th>
                        <th class="px-4 py-3">Ghi chú</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50/50 divide-x divide-gray-50">
                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 font-mono text-gray-700 whitespace-nowrap">{{ $log->product_item?->product?->code ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $log->product_item?->product?->name ?? '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($log->from_user)
                                    <span class="text-orange-700 font-medium">{{ $log->from_user->name }}</span>
                                @else
                                    <span class="text-teal-700">Kho</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($log->to_user)
                                    <span class="text-orange-700 font-medium">{{ $log->to_user->name }}</span>
                                @else
                                    <span class="text-teal-700">Kho</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-gray-800">{{ $log->product_item?->quantity ?: 1 }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($log->ticket)
                                    <a href="{{ route('tickets.show', $log->ticket->id) }}" class="font-mono text-xs text-primary hover:underline">{{ $log->ticket->code }}</a>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $log->note }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">Chưa có lịch sử giữ hàng</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
