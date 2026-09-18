@extends('layouts.app')
@section('title', 'Marketing Events & Funds')
@section('page-title', 'Quản lý sự kiện Marketing & Quỹ Hãng')

@section('content')
<style>
    [x-cloak] { display: none !important; }
</style>
@php
    $user = auth()->user();
    $isSuperOrMktOrOMOrBOD = $user->hasAnyRole(['super_admin', 'admin', 'marketing', 'marketing_manager', 'order_management', 'director', 'accountant']);
    $currentTab = request('tab', 'events');
    $availableMarketingItemsJson = json_encode($availableMarketingItems->map(fn($item) => [
        'id' => $item->id,
        'code' => $item->code,
        'name' => $item->name,
        'stock' => (int) $item->stock_quantity,
        'unit' => $item->unit ?: 'Cái',
        'category_label' => $item->category_label,
    ])->values());
@endphp

<script>
function marketingEventsPage() {
    return {
        showAddFundModal: false,
        showAllocateGiftModal: false,
        activeTicket: null,
        availableItems: {!! $availableMarketingItemsJson !!},
        giftRows: [{ item_id: '', search: '', open: false, selectedItem: null, quantity: 1 }],
        giftNote: '',
        openAllocateModal(ticket) {
            this.activeTicket = ticket;
            this.giftRows = [{ item_id: '', search: '', open: false, selectedItem: null, quantity: 1 }];
            this.giftNote = '';
            this.showAllocateGiftModal = true;
        },
        addGiftRow() {
            this.giftRows.push({ item_id: '', search: '', open: false, selectedItem: null, quantity: 1 });
        },
        removeGiftRow(index) {
            if (this.giftRows.length > 1) {
                this.giftRows.splice(index, 1);
            }
        },
        selectGiftItem(row, item) {
            row.item_id = item.id;
            row.selectedItem = item;
            row.search = item.name;
            row.open = false;
        },
        clearGiftItem(row) {
            row.item_id = '';
            row.selectedItem = null;
            row.search = '';
            row.open = true;
        },
        removeVietnameseTones(str) {
            if (!str) return '';
            str = str.toLowerCase();
            str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, 'a');
            str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, 'e');
            str = str.replace(/ì|í|ị|ỉ|ĩ/g, 'i');
            str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, 'o');
            str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, 'u');
            str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, 'y');
            str = str.replace(/đ/g, 'd');
            str = str.replace(/[\u0300-\u036f]/g, '');
            return str;
        },
        getFilteredItems(searchTerm) {
            if (!searchTerm || !searchTerm.trim()) {
                return this.availableItems;
            }
            const q = this.removeVietnameseTones(searchTerm.trim());
            return this.availableItems.filter(item => {
                const target = this.removeVietnameseTones((item.name || '') + ' ' + (item.code || '') + ' ' + (item.category_label || ''));
                return target.includes(q);
            });
        },
        submitAllocation(event) {
            for (let i = 0; i < this.giftRows.length; i++) {
                const row = this.giftRows[i];
                if (!row.item_id) {
                    alert('Vui lòng tìm và chọn vật phẩm quà tặng cho dòng thứ ' + (i + 1) + '.');
                    return;
                }
                if (!row.quantity || row.quantity < 1) {
                    alert('Vui lòng nhập số lượng hợp lệ cho dòng thứ ' + (i + 1) + ' (tối thiểu là 1).');
                    return;
                }
                if (row.selectedItem && row.quantity > row.selectedItem.stock) {
                    alert('Vật phẩm "' + row.selectedItem.name + '" chỉ còn tồn kho ' + row.selectedItem.stock + ' ' + row.selectedItem.unit + '. Vui lòng điều chỉnh lại số lượng.');
                    return;
                }
            }
            event.target.submit();
        }
    };
}
</script>

<div class="space-y-4" x-data="marketingEventsPage()">
    {{-- Tabs Navigation --}}
    @if($isSuperOrMktOrOMOrBOD)
    <div class="bg-white rounded-lg shadow-sm p-2 flex border-b border-gray-100">
        <a href="{{ route('marketing-events.index', ['tab' => 'events']) }}"
           class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg transition-all {{ $currentTab === 'events' ? 'bg-purple-50 text-purple-700' : 'text-gray-500 hover:text-purple-600 hover:bg-gray-50' }}">
            <i class="fas fa-calendar-alt text-base"></i> Sự kiện Marketing
        </a>
        <a href="{{ route('marketing-events.index', ['tab' => 'funds']) }}"
           class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg transition-all {{ $currentTab === 'funds' ? 'bg-purple-50 text-purple-700' : 'text-gray-500 hover:text-purple-600 hover:bg-gray-50' }}">
            <i class="fas fa-wallet text-base"></i> Quản lý Quỹ Hãng & Công nợ
        </a>
        <a href="{{ route('marketing-events.index', ['tab' => 'requests']) }}"
           class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg transition-all {{ $currentTab === 'requests' ? 'bg-purple-50 text-purple-700' : 'text-gray-500 hover:text-purple-600 hover:bg-gray-50' }}">
            <i class="fas fa-ticket-alt text-base"></i> Ticket từ Sales
        </a>
        <a href="{{ route('marketing-items.index') }}"
           class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg transition-all text-gray-500 hover:text-purple-600 hover:bg-gray-50">
            <i class="fas fa-boxes text-base"></i> Kho vật phẩm & Quà tặng
        </a>
    </div>
    @endif

    @if($currentTab === 'requests')
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800"><i class="fas fa-gift text-purple-500 mr-2"></i>Ticket quà tặng / phối hợp từ Sales</h2>
                    <p class="text-sm text-gray-500 mt-1">Các yêu cầu được tạo tự động sau khi BOD/Manager duyệt quà tặng cho hoạt động cơ hội.</p>
                </div>
                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-purple-50 text-purple-700">{{ $directRequests->count() }} ticket</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Mã ticket</th>
                            <th class="px-4 py-3 text-left">Hoạt động / khách hàng</th>
                            <th class="px-4 py-3 text-left min-w-[320px]">Nội dung cần xử lý & Quà tặng</th>
                            <th class="px-4 py-3 text-left">Hạn</th>
                            <th class="px-4 py-3 text-center">Trạng thái</th>
                            <th class="px-4 py-3 text-left">Người phụ trách</th>
                            <th class="px-4 py-3 text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($directRequests as $marketingRequest)
                            @php
                                $allocatedTxs = $marketingRequest->allocated_item_transactions->where('type', 'export');
                                $ticketCode = $marketingRequest->ticket?->code ?: $marketingRequest->code;
                                $targetName = $marketingRequest->opportunity?->name ?: ($marketingRequest->event?->title ?: 'Ticket quà tặng');
                                $customerName = $marketingRequest->opportunity?->customer_display_name ?: '-';
                                $modalPayload = [
                                    'id' => $marketingRequest->id,
                                    'code' => $ticketCode,
                                    'name' => $targetName,
                                    'customer' => $customerName,
                                    'description' => $marketingRequest->description ?: 'Không có ghi chú thêm',
                                    'actionUrl' => route('marketing-requests.allocate-items', $marketingRequest),
                                    'allocatedItems' => $allocatedTxs->map(fn($t) => [
                                        'id' => $t->id,
                                        'item_name' => $t->marketingItem?->name ?? 'Vật phẩm',
                                        'quantity' => $t->quantity,
                                        'unit' => $t->marketingItem?->unit ?? 'Cái',
                                        'deleteUrl' => route('marketing-requests.remove-item', ['marketingRequest' => $marketingRequest->id, 'transaction' => $t->id])
                                    ])->values(),
                                ];
                            @endphp
                            <tr class="hover:bg-purple-50/30">
                                <td class="px-4 py-3 font-semibold text-purple-700 whitespace-nowrap">
                                    {{ $ticketCode }}
                                </td>
                                <td class="px-4 py-3 min-w-[200px]">
                                    @if($marketingRequest->opportunity_id)
                                        <a class="font-medium text-gray-800 hover:text-purple-700 flex items-center gap-1.5" href="{{ route('opportunities.show', $marketingRequest->opportunity_id) }}">
                                            <i class="fas fa-bullseye text-indigo-500 text-xs"></i> {{ $targetName }}
                                        </a>
                                    @elseif($marketingRequest->marketing_event_id)
                                        <a class="font-medium text-gray-800 hover:text-purple-700 flex items-center gap-1.5" href="{{ route('marketing-events.show', $marketingRequest->marketing_event_id) }}">
                                            <i class="fas fa-calendar-alt text-purple-500 text-xs"></i> {{ $targetName }}
                                        </a>
                                    @else
                                        <span class="font-medium text-gray-800">{{ $targetName }}</span>
                                    @endif
                                    <div class="text-xs text-gray-500 mt-1"><i class="fas fa-building text-gray-400 mr-1"></i>{{ $customerName }}</div>
                                </td>
                                <td class="px-4 py-3 max-w-lg">
                                    <div class="space-y-2">
                                        {{-- Yêu cầu từ Sales --}}
                                        <div>
                                            <div class="text-xs font-semibold text-gray-700 mb-1 flex items-center gap-1">
                                                <i class="fas fa-comment-dots text-purple-500"></i> Yêu cầu từ Sales:
                                            </div>
                                            <div class="bg-gray-50 p-2 rounded text-xs border border-gray-100 text-gray-600 whitespace-pre-line leading-relaxed">{{ $marketingRequest->description }}</div>
                                        </div>

                                        {{-- Tình trạng phân bổ quà từ kho --}}
                                        @if($allocatedTxs->count() > 0)
                                            <div class="p-2.5 rounded-lg bg-emerald-50/70 border border-emerald-200">
                                                <div class="flex items-center justify-between mb-1.5">
                                                    <span class="text-[11px] font-bold text-emerald-800 uppercase flex items-center gap-1">
                                                        <i class="fas fa-box-check text-emerald-600"></i> Đã xuất kho ({{ $allocatedTxs->count() }} loại quà):
                                                    </span>
                                                    <button type="button" @click="openAllocateModal({{ Js::from($modalPayload) }})" class="text-[11px] text-purple-700 hover:text-purple-900 font-bold hover:underline inline-flex items-center gap-1">
                                                        <i class="fas fa-plus-circle"></i> Xuất thêm / Quản lý
                                                    </button>
                                                </div>
                                                <div class="flex flex-wrap gap-1.5">
                                                    @foreach($allocatedTxs as $tx)
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-white text-emerald-900 text-xs font-bold border border-emerald-300 shadow-2xs">
                                                            <i class="fas fa-gift text-emerald-600"></i> {{ $tx->marketingItem?->name }}: <span class="text-purple-700">{{ $tx->quantity }} {{ $tx->marketingItem?->unit }}</span>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <div class="p-2.5 rounded-lg bg-amber-50/80 border border-amber-300 flex items-center justify-between gap-2">
                                                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-900">
                                                    <i class="fas fa-exclamation-triangle text-amber-500 text-sm"></i> Chưa phân bổ quà từ kho
                                                </span>
                                                <button type="button" @click="openAllocateModal({{ Js::from($modalPayload) }})" class="px-2.5 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded text-xs font-bold shadow-xs transition inline-flex items-center gap-1">
                                                    <i class="fas fa-plus"></i> Phân bổ quà ngay
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                    {{ $marketingRequest->deadline?->format('d/m/Y') ?: '-' }}
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @if($marketingRequest->status === 'completed')
                                        <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200">
                                            <i class="fas fa-check-circle mr-1"></i>Đã bàn giao quà
                                        </span>
                                    @elseif($marketingRequest->status === 'in_progress')
                                        <span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold border border-blue-200">
                                            <i class="fas fa-spinner fa-spin mr-1"></i>Đang chuẩn bị quà
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200">
                                            <i class="fas fa-clock mr-1"></i>Chờ xử lý
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                    @if($marketingRequest->assignee)
                                        <div class="flex items-center gap-1.5">
                                            <div class="w-6 h-6 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-xs font-bold">
                                                {{ substr($marketingRequest->assignee->name, 0, 1) }}
                                            </div>
                                            <span class="font-medium text-gray-800">{{ $marketingRequest->assignee->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-xs text-red-500 italic"><i class="fas fa-exclamation-triangle mr-1"></i>Chưa phân công</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="flex flex-col gap-2 items-center justify-center">
                                        {{-- Nút Phân bổ Quà tặng từ kho --}}
                                        <button type="button" @click="openAllocateModal({{ Js::from($modalPayload) }})" class="w-full px-2.5 py-1.5 text-xs font-bold bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 rounded-lg transition-colors shadow-2xs inline-flex items-center justify-center gap-1.5" title="Phân bổ / Xuất quà từ kho cho Ticket này">
                                            <i class="fas fa-gift text-purple-600"></i> Phân bổ quà
                                        </button>

                                        <div class="flex items-center justify-center gap-1 w-full">
                                            @if($user->hasAnyRole(['super_admin', 'admin', 'director', 'marketing', 'marketing_manager']))
                                                <form method="POST" action="{{ route('marketing-requests.assign', $marketingRequest) }}" class="flex items-center gap-1">
                                                    @csrf
                                                    <select name="assigned_to" class="max-w-[110px] border border-gray-300 rounded px-1.5 py-1 text-xs" required title="Chọn người phụ trách">
                                                        <option value="">Phân công...</option>
                                                        @foreach($marketingAssignees as $assignee)
                                                            <option value="{{ $assignee->id }}" {{ $marketingRequest->assigned_to === $assignee->id ? 'selected' : '' }}>{{ $assignee->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="px-2 py-1 text-xs bg-purple-600 text-white rounded hover:bg-purple-700 shadow-sm" title="Lưu phân công"><i class="fas fa-save"></i></button>
                                                </form>
                                            @endif

                                            @if($marketingRequest->status !== 'completed')
                                                @if($marketingRequest->status === 'received')
                                                    <form method="POST" action="{{ route('marketing-requests.status.update', $marketingRequest) }}" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="status" value="in_progress">
                                                        <button type="submit" class="px-2 py-1 text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200 rounded hover:bg-blue-100 transition-colors shadow-sm" title="Bắt đầu chuẩn bị quà">
                                                            <i class="fas fa-play mr-1"></i>Nhận
                                                        </button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('marketing-requests.status.update', $marketingRequest) }}" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="status" value="completed">
                                                        <input type="hidden" name="comment" value="Đã chuẩn bị và bàn giao quà tặng đầy đủ cho Sales.">
                                                        <button type="submit" onclick="return confirm('Xác nhận đã đóng gói và bàn giao quà cho Sales?')" class="px-2 py-1 text-xs font-bold bg-emerald-600 text-white rounded hover:bg-emerald-700 transition-colors shadow-sm" title="Bàn giao quà cho Sales">
                                                            <i class="fas fa-check mr-1"></i>Bàn giao
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                <span class="inline-flex items-center text-xs text-emerald-600 font-bold px-2 py-1 bg-emerald-50 rounded">
                                                    <i class="fas fa-check-double mr-1"></i>Xong
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400">Chưa có ticket Marketing trực tiếp từ Sales.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- MODAL PHÂN BỔ QUÀ TẶNG TỪ KHO MARKETING --}}
        <div x-show="showAllocateGiftModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden border border-purple-100"
                 @click.outside="showAllocateGiftModal = false">
                <div class="px-6 py-4 bg-gradient-to-r from-purple-700 to-indigo-700 text-white flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center text-lg">
                            <i class="fas fa-gift"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold">Phân bổ Quà tặng từ Kho Marketing</h3>
                            <p class="text-xs text-purple-100 mt-0.5">
                                Ticket: <strong x-text="activeTicket?.code"></strong>
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="showAllocateGiftModal = false" class="text-white/80 hover:text-white text-lg">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-6 space-y-5 max-h-[80vh] overflow-y-auto">
                    {{-- Thông tin ticket --}}
                    <div class="bg-purple-50/50 rounded-xl p-4 border border-purple-100 space-y-2">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div>
                                <span class="text-gray-500 font-semibold block uppercase">Cơ hội / Hoạt động:</span>
                                <span class="font-bold text-gray-900" x-text="activeTicket?.name"></span>
                            </div>
                            <div>
                                <span class="text-gray-500 font-semibold block uppercase">Khách hàng:</span>
                                <span class="font-bold text-gray-900" x-text="activeTicket?.customer"></span>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-purple-100/60 text-xs">
                            <span class="text-gray-500 font-semibold block uppercase mb-0.5">Yêu cầu quà từ Sales:</span>
                            <div class="text-gray-700 font-medium bg-white p-2 rounded-lg border border-purple-100/80 whitespace-pre-line" x-text="activeTicket?.description"></div>
                        </div>
                    </div>

                    {{-- Danh sách vật phẩm đã xuất kho trước đó (nếu có) --}}
                    <div>
                        <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <i class="fas fa-boxes text-purple-600"></i> Quà tặng đã phân bổ cho Ticket này:
                        </h4>
                        
                        <template x-if="activeTicket?.allocatedItems && activeTicket.allocatedItems.length > 0">
                            <div class="border border-emerald-200 rounded-xl overflow-hidden bg-emerald-50/30">
                                <table class="w-full text-xs">
                                    <thead class="bg-emerald-100/60 text-emerald-900 font-bold uppercase text-[11px]">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Tên vật phẩm quà tặng</th>
                                            <th class="px-3 py-2 text-center">Số lượng</th>
                                            <th class="px-3 py-2 text-center">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-emerald-100">
                                        <template x-for="item in activeTicket.allocatedItems" :key="item.id">
                                            <tr>
                                                <td class="px-3 py-2 font-semibold text-gray-800" x-text="item.item_name"></td>
                                                <td class="px-3 py-2 text-center font-bold text-emerald-800">
                                                    <span x-text="item.quantity"></span> <span x-text="item.unit"></span>
                                                </td>
                                                <td class="px-3 py-2 text-center">
                                                    <form :action="item.deleteUrl" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc muốn hoàn trả vật phẩm này về lại kho?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="px-2 py-1 bg-red-50 text-red-600 hover:bg-red-100 rounded text-[11px] font-bold transition-colors" title="Hoàn trả về kho">
                                                            <i class="fas fa-undo mr-1"></i>Hoàn về kho
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>

                        <template x-if="!activeTicket?.allocatedItems || activeTicket.allocatedItems.length === 0">
                            <div class="text-center py-3 bg-amber-50/60 border border-dashed border-amber-300 rounded-xl text-xs text-amber-800 font-medium">
                                <i class="fas fa-info-circle mr-1 text-amber-600"></i> Chưa có vật phẩm nào được xuất kho cho ticket này. Vui lòng chọn bên dưới để xuất quà.
                            </div>
                        </template>
                    </div>

                    {{-- Form thêm quà từ kho --}}
                    <form :action="activeTicket?.actionUrl" method="POST" @submit.prevent="submitAllocation($event)" class="space-y-4 pt-3 border-t border-gray-100">
                        @csrf
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                    <i class="fas fa-plus-circle text-purple-600"></i> Chọn vật phẩm xuất từ Kho Marketing <span class="text-red-500">*</span>
                                </label>
                                <button type="button" @click="addGiftRow()" class="px-2.5 py-1 bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 rounded-lg text-xs font-bold transition-colors">
                                    <i class="fas fa-plus mr-1"></i>Thêm món
                                </button>
                            </div>

                            <div class="space-y-3">
                                <template x-for="(row, idx) in giftRows" :key="idx">
                                    <div class="flex items-start gap-2 p-2.5 bg-gray-50 rounded-xl border border-gray-200">
                                        {{-- Searchable Dropdown --}}
                                        <div class="relative flex-1" @click.outside="row.open = false">
                                            <div class="relative flex items-center">
                                                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs">
                                                    <i class="fas fa-search"></i>
                                                </div>
                                                <input type="text"
                                                       x-model="row.search"
                                                       @focus="row.open = true"
                                                       @click="row.open = true"
                                                       @input="row.open = true; if(row.selectedItem && row.search !== row.selectedItem.name) { row.item_id = ''; row.selectedItem = null; }"
                                                       placeholder="Gõ tìm theo tên, mã hoặc loại quà tặng..."
                                                       class="w-full text-xs rounded-lg border border-gray-300 focus:border-purple-500 focus:ring-2 focus:ring-purple-200 pl-8 pr-16 py-2 bg-white transition-all shadow-xs"
                                                       autocomplete="off">
                                                
                                                <input type="hidden" :name="'items[' + idx + '][item_id]'" :value="row.item_id">

                                                <div class="absolute right-2.5 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                                                    <button type="button" 
                                                            x-show="row.search || row.item_id" 
                                                            @click="clearGiftItem(row)" 
                                                            class="w-5 h-5 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-400 hover:text-gray-600 flex items-center justify-center text-[10px] transition-colors"
                                                            title="Xóa lựa chọn">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <button type="button" 
                                                            @click="row.open = !row.open" 
                                                            class="text-gray-400 hover:text-purple-600 text-xs px-1">
                                                        <i class="fas fa-chevron-down transition-transform duration-150" :class="{'rotate-180': row.open}"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Dropdown kết quả tìm kiếm --}}
                                            <div x-show="row.open"
                                                 x-transition:enter="transition ease-out duration-100"
                                                 x-transition:enter-start="opacity-0 scale-95"
                                                 x-transition:enter-end="opacity-100 scale-100"
                                                 x-transition:leave="transition ease-in duration-75"
                                                 x-transition:leave-start="opacity-100 scale-100"
                                                 x-transition:leave-end="opacity-0 scale-95"
                                                 class="absolute z-50 left-0 right-0 top-full mt-1 bg-white border border-purple-200 rounded-xl shadow-xl max-h-56 overflow-y-auto divide-y divide-gray-100"
                                                 style="display: none;">
                                                
                                                <template x-for="item in getFilteredItems(row.search)" :key="item.id">
                                                    <div @click="selectGiftItem(row, item)"
                                                         class="p-2.5 hover:bg-purple-50/80 cursor-pointer transition-colors flex items-center justify-between gap-3 text-xs group"
                                                         :class="{'bg-purple-50 border-l-4 border-purple-600': row.item_id === item.id}">
                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-center gap-2 mb-0.5">
                                                                <span class="font-bold text-gray-900 group-hover:text-purple-700 truncate" x-text="item.name"></span>
                                                                <span class="text-[10px] font-mono px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded" x-text="item.code"></span>
                                                            </div>
                                                            <div class="text-[11px] text-gray-500 flex items-center gap-2">
                                                                <span class="inline-flex items-center gap-1">
                                                                    <i class="fas fa-tag text-[10px] text-purple-400"></i>
                                                                    <span x-text="item.category_label"></span>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="text-right shrink-0">
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold"
                                                                  :class="item.stock > 10 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'">
                                                                <i class="fas fa-cubes text-[10px]"></i>
                                                                <span>Tồn: <strong x-text="item.stock"></strong> <span x-text="item.unit"></span></span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </template>

                                                <div x-show="getFilteredItems(row.search).length === 0" class="p-4 text-center text-xs text-gray-500">
                                                    <i class="fas fa-search mr-1 text-gray-400"></i> Không tìm thấy vật phẩm quà tặng nào phù hợp.
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Số lượng --}}
                                        <div class="w-28 shrink-0">
                                            <div class="relative">
                                                <input type="number" 
                                                       :name="'items[' + idx + '][quantity]'" 
                                                       x-model.number="row.quantity" 
                                                       min="1" 
                                                       :max="row.selectedItem ? row.selectedItem.stock : null"
                                                       required 
                                                       placeholder="SL" 
                                                       class="w-full text-xs rounded-lg border border-gray-300 focus:border-purple-500 px-3 py-2 bg-white text-center font-bold">
                                                <span x-show="row.selectedItem" 
                                                      class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 font-semibold pointer-events-none"
                                                      x-text="row.selectedItem?.unit">
                                                </span>
                                            </div>
                                        </div>

                                        {{-- Nút xóa dòng --}}
                                        <button type="button" @click="removeGiftRow(idx)" class="w-8 h-8 mt-0.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 flex items-center justify-center text-xs transition-colors shrink-0" title="Xóa dòng" x-show="giftRows.length > 1">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ghi chú xuất quà (tuỳ chọn)</label>
                            <input type="text" name="note" x-model="giftNote" placeholder="Ví dụ: Đã đóng gói túi quà kèm brochure..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-purple-400">
                        </div>

                        <div class="flex items-center justify-between pt-3 border-t">
                            <span class="text-xs text-gray-500 italic">
                                <i class="fas fa-sync-alt mr-1"></i>Tồn kho sẽ tự động trừ ngay sau khi xác nhận.
                            </span>
                            <div class="flex gap-2">
                                <button type="button" @click="showAllocateGiftModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-xs font-bold transition-colors">
                                    Đóng
                                </button>
                                <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-lg shadow-sm transition-all flex items-center gap-1.5">
                                    <i class="fas fa-check-circle"></i> Xác nhận xuất quà & Lưu Ticket
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @elseif($currentTab === 'events')
        {{-- Header for Events --}}
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-calendar-alt text-purple-500 mr-2"></i>Danh sách sự kiện Marketing
                </h2>
                <div class="flex flex-wrap gap-2">
                    <form action="{{ route('marketing-events.index') }}" method="GET" class="flex gap-2">
                        <input type="hidden" name="tab" value="events">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm..."
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                        <select name="status" onchange="this.form.submit()"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                            <option value="">Tất cả trạng thái</option>
                            <option value="draft"    {{ request('status') === 'draft'    ? 'selected' : '' }}>Nháp</option>
                            <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Chờ duyệt</option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Từ chối</option>
                        </select>
                    </form>
                    @can('create_marketing_events')
                    <a href="{{ route('marketing-events.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium">
                        <i class="fas fa-plus mr-2"></i> Tạo sự kiện
                    </a>
                    @endcan
                </div>
            </div>
        </div>

        {{-- Events Table --}}
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sự kiện</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Địa điểm</th>
                            @if($isSuperOrMktOrOMOrBOD)
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">NS dự toán</th>
                            @endif
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">KH mời</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Người tạo</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($events as $event)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('marketing-events.show', $event) }}" class="font-medium text-purple-600 hover:underline">
                                    {{ $event->title }}
                                </a>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    @if($event->is_public_to_sales)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-700">
                                            <i class="fas fa-globe text-[9px]"></i> Hãng lớn / Mở rộng
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">
                                            <i class="fas fa-lock text-[9px]"></i> Chỉ định riêng
                                        </span>
                                    @endif
                                </div>
                                @if($event->description)
                                <div class="text-xs text-gray-500 truncate max-w-xs mt-0.5">{{ $event->description }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $event->event_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $event->location ?? '—' }}</td>
                            @if($isSuperOrMktOrOMOrBOD)
                            <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ number_format($event->budget) }} đ</td>
                            @endif
                            <td class="px-4 py-3 text-center text-sm">{{ $event->customers_count ?? $event->customers->count() }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $event->status_color }}">
                                    {{ $event->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-center text-gray-600">{{ $event->creator->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('marketing-events.show', $event) }}" 
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 transition-colors" 
                                       title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    @php
                                        $canApprove = false;
                                        if ($mktWorkflow && $event->status === 'pending') {
                                            $pendingHist = $event->approvalHistories->where('action', 'pending')->sortBy('level')->first();
                                            if ($pendingHist) {
                                                $level = $mktWorkflow->levels->where('level', $pendingHist->level)->first();
                                                $canApprove = $level?->canApprove(auth()->user(), (float)$event->budget) ?? false;
                                            }
                                        }
                                    @endphp

                                    @if($canApprove)
                                    <div class="flex items-center gap-1">
                                        <form action="{{ route('marketing-events.approve', $event) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                onclick="return confirm('Duyệt ngân sách sự kiện này?')"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors" 
                                                title="Duyệt nhanh">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <a href="{{ route('marketing-events.show', $event) }}?reject=1" 
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors" 
                                            title="Từ chối">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </div>
                                    @endif

                                    @if($event->isEditable())
                                    <a href="{{ route('marketing-events.edit', $event) }}" 
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors" 
                                       title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endif

                                    @if($event->isEditable() || $event->status === 'cancelled')
                                    <form action="{{ route('marketing-events.destroy', $event) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" 
                                            onclick="return confirm('Xóa sự kiện này?')"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors" 
                                            title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Chưa có sự kiện nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $events->links() }}</div>
        </div>
    @elseif($currentTab === 'funds' && $isSuperOrMktOrOMOrBOD)
        {{-- Funds statistics cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm p-4 border border-purple-100">
                <div class="text-xs font-bold text-gray-400 uppercase">Tổng quỹ đã nhận</div>
                <div class="text-2xl font-black text-purple-700 mt-1">
                    {{ number_format($supplierFunds->sum('amount')) }} đ
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-emerald-100">
                <div class="text-xs font-bold text-gray-400 uppercase">Đã sử dụng thực tế</div>
                <div class="text-2xl font-black text-emerald-700 mt-1">
                    {{ number_format($supplierFunds->sum('used_amount')) }} đ
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-blue-100">
                <div class="text-xs font-bold text-gray-400 uppercase">Số dư còn lại</div>
                <div class="text-2xl font-black text-blue-700 mt-1">
                    {{ number_format($supplierFunds->sum('remaining_amount')) }} đ
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-red-100">
                <div class="text-xs font-bold text-gray-400 uppercase">Công nợ hãng chờ thu</div>
                <div class="text-2xl font-black text-red-700 mt-1">
                    {{ number_format($transactions->where('type', 'receivable')->where('status', 'pending')->sum('amount')) }} đ
                </div>
            </div>
        </div>

        {{-- Funds Management block --}}
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-md font-bold text-gray-800">
                    <i class="fas fa-wallet text-purple-500 mr-2"></i>Quản lý Nguồn Quỹ từ Hãng
                </h3>
                @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('marketing'))
                <button @click="showAddFundModal = true"
                    class="inline-flex items-center px-3.5 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-xs font-bold shadow-sm">
                    <i class="fas fa-plus mr-1.5"></i> Khai báo quỹ mới
                </button>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-bold text-gray-600">Hãng</th>
                            <th class="px-4 py-2.5 text-left font-bold text-gray-600">Tên Quỹ Hỗ Trợ</th>
                            <th class="px-4 py-2.5 text-center font-bold text-gray-600">Thời gian</th>
                            <th class="px-4 py-2.5 text-right font-bold text-gray-600">Tổng Tiền Quỹ</th>
                            <th class="px-4 py-2.5 text-right font-bold text-gray-600">Đã Dùng</th>
                            <th class="px-4 py-2.5 text-right font-bold text-gray-600">Số Dư Còn Lại</th>
                            <th class="px-4 py-2.5 text-left font-bold text-gray-600">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($supplierFunds as $fund)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-2.5 font-semibold text-gray-900">{{ $fund->supplier->name ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-purple-700 font-medium">{{ $fund->name }}</td>
                            <td class="px-4 py-2.5 text-center text-gray-600">{{ $fund->quarter }} - {{ $fund->year }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold text-gray-800">{{ number_format($fund->amount) }} đ</td>
                            <td class="px-4 py-2.5 text-right text-red-600">{{ number_format($fund->used_amount) }} đ</td>
                            <td class="px-4 py-2.5 text-right text-blue-600 font-bold">{{ number_format($fund->remaining_amount) }} đ</td>
                            <td class="px-4 py-2.5 text-gray-500 text-xs truncate max-w-xs">{{ $fund->note ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">Chưa khai báo nguồn quỹ nào của hãng.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Supplier Debt Ledger --}}
        <div class="bg-white rounded-lg shadow-sm p-4">
            <h3 class="text-md font-bold text-gray-800 mb-3">
                <i class="fas fa-hand-holding-usd text-red-500 mr-2"></i>Theo dõi Công nợ Hãng & Thu hồi
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-bold text-gray-600">Hãng</th>
                            <th class="px-4 py-2.5 text-left font-bold text-gray-600">Nội dung công nợ / Sự kiện</th>
                            <th class="px-4 py-2.5 text-right font-bold text-gray-600">Số tiền hỗ trợ</th>
                            <th class="px-4 py-2.5 text-center font-bold text-gray-600">Loại giao dịch</th>
                            <th class="px-4 py-2.5 text-center font-bold text-gray-600">Trạng thái</th>
                            <th class="px-4 py-2.5 text-center font-bold text-gray-600">Ngày ghi nhận</th>
                            <th class="px-4 py-2.5 text-center font-bold text-gray-600">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($transactions->whereIn('type', ['receivable', 'collected']) as $tx)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-2.5 font-semibold text-gray-900">{{ $tx->supplier->name ?? '—' }}</td>
                            <td class="px-4 py-2.5">
                                <div>{{ $tx->note }}</div>
                                @if($tx->event)
                                <div class="text-[10px] text-purple-500 font-medium">Sự kiện: {{ $tx->event->title }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-right font-semibold text-gray-800">{{ number_format($tx->amount) }} đ</td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $tx->type === 'receivable' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' }}">
                                    {{ $tx->type_label }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                @if($tx->type === 'receivable')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $tx->status === 'collected' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $tx->status === 'collected' ? 'Đã thu nợ' : 'Hãng chưa trả' }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-800">Hoàn tất</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-center text-gray-500 text-xs">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2.5 text-center">
                                @if($tx->type === 'receivable' && $tx->status === 'pending')
                                    @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('marketing') || auth()->user()->hasRole('accountant'))
                                    <form action="{{ route('marketing-events.transactions.collect', $tx) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" onclick="return confirm('Xác nhận hãng đã thanh toán khoản tiền hỗ trợ này?')"
                                            class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-bold transition-all shadow-sm">
                                            <i class="fas fa-check-double mr-1"></i> Xác nhận đã thu
                                        </button>
                                    </form>
                                    @endif
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">Không có lịch sử công nợ hãng phát sinh.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Add Fund Modal --}}
        <div x-show="showAddFundModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 overflow-y-auto p-4" x-transition>
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden border border-gray-100" @click.away="showAddFundModal = false">
                <div class="bg-purple-700 px-4 py-3 flex justify-between items-center">
                    <h4 class="text-white font-bold text-sm">Khai báo Nguồn Quỹ Mới của Hãng</h4>
                    <button @click="showAddFundModal = false" class="text-white hover:text-purple-200 focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form action="{{ route('marketing-events.funds.store') }}" method="POST" class="p-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Chọn Hãng cấp quỹ <span class="text-red-500">*</span></label>
                        <select name="supplier_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 bg-white">
                            <option value="">-- Chọn nhà cung cấp / Hãng --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Tên chương trình quỹ / Tên quỹ <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required placeholder="Ví dụ: Fortinet MDF Q3-2026, Cisco Support"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Chọn Quý <span class="text-red-500">*</span></label>
                            <select name="quarter" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 bg-white">
                                <option value="Q1">Quý 1 (Q1)</option>
                                <option value="Q2">Quý 2 (Q2)</option>
                                <option value="Q3">Quý 3 (Q3)</option>
                                <option value="Q4">Quý 4 (Q4)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Năm <span class="text-red-500">*</span></label>
                            <input type="number" name="year" value="{{ date('Y') }}" required min="2020" max="2100"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Số tiền quỹ cấp (VND) <span class="text-red-500">*</span></label>
                        <input type="text" name="amount" required placeholder="Bằng số, VD: 150000000"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Ghi chú</label>
                        <textarea name="note" rows="3" placeholder="Ghi chú về điều kiện chi tiêu quỹ, chính sách..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button type="button" @click="showAddFundModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-xs font-bold">Huỷ</button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-xs font-bold">Khai báo</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
