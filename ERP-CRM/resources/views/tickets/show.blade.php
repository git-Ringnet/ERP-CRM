@extends('layouts.app')

@section('title', 'Chi tiết Yêu cầu')
@section('page-title', 'Chi tiết Yêu cầu: ' . $ticket->code)

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-2">
        <a href="{{ route('tickets.index') }}" class="inline-flex items-center text-sm font-semibold text-gray-500 hover:text-gray-700">
            <i class="fas fa-chevron-left mr-1"></i> Quay lại danh sách
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm font-medium">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main details -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <h3 class="text-md font-bold text-gray-800">Danh sách sản phẩm yêu cầu</h3>
                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-{{ $ticket->status_color }}-100 text-{{ $ticket->status_color }}-800 uppercase">
                        {{ $ticket->status_label }}
                    </span>
                </div>
                
                <div class="p-6">
                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-semibold">
                                <tr class="divide-x divide-gray-100 border-b border-gray-200">
                                    <th class="px-4 py-2.5">Sản phẩm</th>
                                    <th class="px-4 py-2.5 w-32 text-center">Số lượng</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($ticket->items as $item)
                                    <tr class="divide-x divide-gray-50">
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900">{{ $item->product->name }}</div>
                                            <div class="text-xs text-gray-400">Mã: {{ $item->product->code }}</div>
                                            @if($item->allocated_item_ids && count($item->allocated_item_ids) > 0)
                                                @php
                                                    $allocatedItems = \App\Models\ProductItem::whereIn('id', $item->allocated_item_ids)->get();
                                                    $realSerials = $allocatedItems->filter(fn($pi) => !str_starts_with($pi->sku, 'NOSKU') && !str_starts_with($pi->sku, 'NOSERIAL') && !str_starts_with($pi->sku, \App\Models\ProductItem::NO_SKU_PREFIX) && !str_starts_with($pi->sku, \App\Models\ProductItem::OLD_NO_SKU_PREFIX));
                                                    $noSkuCount = $allocatedItems->filter(fn($pi) => str_starts_with($pi->sku, 'NOSKU') || str_starts_with($pi->sku, 'NOSERIAL') || str_starts_with($pi->sku, \App\Models\ProductItem::NO_SKU_PREFIX) || str_starts_with($pi->sku, \App\Models\ProductItem::OLD_NO_SKU_PREFIX))->count();
                                                @endphp
                                                @if($realSerials->count() > 0 || $noSkuCount > 0)
                                                    <div class="mt-1.5 flex flex-wrap gap-1.5 items-center">
                                                        @if($realSerials->count() > 0)
                                                            <span class="text-[10px] text-gray-500 uppercase font-bold mr-1">Số Serial:</span>
                                                            @foreach($realSerials as $pItem)
                                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-800 border border-green-200 font-mono">
                                                                    {{ $pItem->sku }}
                                                                </span>
                                                            @endforeach
                                                        @endif
                                                        @if($noSkuCount > 0)
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-600 border border-gray-200">
                                                                + {{ $noSkuCount }} sản phẩm không serial
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center font-bold text-gray-800">
                                            {{ $item->quantity }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($ticket->note)
                        <div class="mt-4 bg-gray-50 p-3 rounded-lg border border-gray-100">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Ghi chú từ Sales:</span>
                            <p class="text-sm text-gray-700 italic">"{{ $ticket->note }}"</p>
                        </div>
                    @endif

                    @if($ticket->reject_reason)
                        <div class="mt-4 bg-red-50 p-3 rounded-lg border border-red-100">
                            <span class="text-xs font-bold text-red-500 uppercase tracking-wider block mb-1">Lý do từ chối:</span>
                            <p class="text-sm text-red-700 italic">"{{ $ticket->reject_reason }}"</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Approval panel (Only visible when authorized and status is pending) -->
            @if($canApprove)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 bg-blue-50/50">
                        <h3 class="text-md font-bold text-gray-800 flex items-center">
                            <i class="fas fa-user-shield text-blue-500 mr-2"></i>
                            Phê duyệt yêu cầu này
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <!-- Reject Reason input -->
                        <div>
                            <label for="reject_reason_input" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Nhập lý do từ chối (bắt buộc khi Từ chối)</label>
                            <textarea id="reject_reason_input" rows="2" placeholder="Nhập lý do..." class="w-full border-gray-200 rounded-lg text-sm focus:border-red-500 focus:ring-red-500"></textarea>
                        </div>

                        <!-- Buttons Group side-by-side -->
                        <div class="flex flex-wrap items-center gap-3 pt-2">
                            <!-- Approve Action -->
                            <form action="{{ route('tickets.approve', $ticket->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-5 py-2.5 bg-green-600 text-white text-sm font-bold rounded-lg hover:bg-green-700 transition-colors shadow-sm flex items-center">
                                    <i class="fas fa-check-circle mr-2"></i> ĐỒNG Ý CHO MƯỢN / DUYỆT ĐẶT
                                </button>
                            </form>

                            <!-- Reject Action -->
                            <form action="{{ route('tickets.reject', $ticket->id) }}" method="POST" onsubmit="return syncRejectReason(this)" class="inline">
                                @csrf
                                <input type="hidden" name="reject_reason" id="reject_reason_hidden">
                                <button type="submit" class="px-5 py-2.5 bg-red-600 text-white text-sm font-bold rounded-lg hover:bg-red-700 transition-colors shadow-sm flex items-center">
                                    <i class="fas fa-times-circle mr-2"></i> TỪ CHỐI YÊU CẦU
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar info panel -->
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
                <h3 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-2">Thông tin chung</h3>
                
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500 text-xs">Mã yêu cầu:</dt>
                        <dd class="font-bold text-gray-900">{{ $ticket->code }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs">Loại yêu cầu:</dt>
                        <dd class="font-semibold text-gray-900">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $ticket->type === 'preload' ? 'blue' : 'purple' }}-50 text-{{ $ticket->type === 'preload' ? 'blue' : 'purple' }}-700">
                                {{ $ticket->type_label }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs">Người yêu cầu:</dt>
                        <dd class="font-medium text-gray-900">{{ $ticket->user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs">Ngày tạo:</dt>
                        <dd class="text-gray-600">{{ $ticket->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    
                    @if($ticket->type === 'borrow')
                        <div class="border-t border-gray-100 pt-3">
                            <dt class="text-gray-500 text-xs">Nguồn mượn hàng:</dt>
                            <dd class="font-semibold text-gray-900 mt-1">
                                @if($ticket->source === 'warehouse')
                                    <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-teal-50 text-teal-700 border border-teal-200">Mượn từ kho</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-orange-50 text-orange-700 border border-orange-200">Mượn từ Sales: {{ $ticket->target_user->name ?? 'N/A' }}</span>
                                @endif
                            </dd>
                        </div>
                    @endif

                    @if($ticket->approver_id)
                        <div class="border-t border-gray-100 pt-3">
                            <dt class="text-gray-500 text-xs">Người duyệt:</dt>
                            <dd class="font-medium text-gray-900">{{ $ticket->approver->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 text-xs">Thời gian duyệt:</dt>
                            <dd class="text-gray-600">{{ $ticket->updated_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>

<script>
    function syncRejectReason(form) {
        const inputVal = document.getElementById('reject_reason_input').value.trim();
        if (!inputVal) {
            alert('Vui lòng nhập lý do từ chối trước khi bấm Từ chối.');
            return false;
        }
        document.getElementById('reject_reason_hidden').value = inputVal;
        return true;
    }
</script>
@endsection
