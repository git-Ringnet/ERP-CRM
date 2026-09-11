@extends('layouts.app')
@section('title', 'Lịch sử xuất nhập - ' . $marketingItem->name)
@section('page-title', 'Lịch sử giao dịch vật phẩm Marketing')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('marketing-items.index') }}" class="w-9 h-9 rounded-lg bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                    {{ $marketingItem->name }}
                    <span class="text-xs font-mono font-bold bg-purple-50 text-purple-700 px-2 py-0.5 rounded">{{ $marketingItem->code }}</span>
                </h1>
                <p class="text-xs text-gray-500">Phân loại: {{ $marketingItem->category_label }} | Tồn kho hiện tại: <strong class="text-gray-800">{{ number_format($marketingItem->stock_quantity) }} {{ $marketingItem->unit }}</strong></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-5 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-history text-purple-600"></i> Nhật ký biến động tồn kho
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-xs font-bold uppercase text-gray-500 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3.5">Thời gian</th>
                        <th class="px-4 py-3.5">Loại giao dịch</th>
                        <th class="px-4 py-3.5 text-right">Số lượng thay đổi</th>
                        <th class="px-4 py-3.5 text-right">Tồn kho sau GD</th>
                        <th class="px-4 py-3.5">Mã tham chiếu</th>
                        <th class="px-4 py-3.5">Liên kết / Mục đích</th>
                        <th class="px-4 py-3.5">Ghi chú</th>
                        <th class="px-4 py-3.5">Người thực hiện</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-3.5 font-mono text-xs text-gray-500">
                                {{ $tx->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $tx->type === 'import' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                                    {{ $tx->type_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold {{ $tx->type === 'import' ? 'text-green-600' : 'text-blue-600' }}">
                                {{ $tx->type === 'import' ? '+' : '-' }}{{ number_format($tx->quantity) }} {{ $marketingItem->unit }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono font-bold text-gray-900">
                                {{ number_format($tx->remaining_stock) }}
                            </td>
                            <td class="px-4 py-3.5 font-mono text-xs text-gray-500">
                                {{ $tx->reference_code ?: '—' }}
                            </td>
                            <td class="px-4 py-3.5 text-xs">
                                @if($tx->opportunity)
                                    <a href="{{ route('opportunities.show', $tx->opportunity_id) }}" class="text-purple-600 font-semibold hover:underline">
                                        <i class="fas fa-bullseye mr-1"></i>Cơ hội: {{ $tx->opportunity->name }}
                                    </a>
                                @elseif($tx->marketingEvent)
                                    <span class="text-blue-600 font-semibold">
                                        <i class="fas fa-calendar mr-1"></i>Sự kiện: {{ $tx->marketingEvent->name }}
                                    </span>
                                @else
                                    <span class="text-gray-400">Xuất/Nhập trực tiếp</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-xs text-gray-600">
                                {{ $tx->note ?: '—' }}
                            </td>
                            <td class="px-4 py-3.5 text-xs text-gray-600">
                                {{ $tx->creator?->name ?: 'Hệ thống' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-gray-400">
                                Chưa có giao dịch nào cho vật phẩm này.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
