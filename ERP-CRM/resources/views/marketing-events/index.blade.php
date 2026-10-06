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
        showImportFundModal: false,
        showEditFundModal: false,
        showFundHistoryModal: false,
        showAllocateGiftModal: false,
        showCompleteModal: false,
        activeTicket: null,
        selectedFund: null,
        fundHistoryList: [],
        fundHistoryLoading: false,
        editFundData: {
            id: null,
            supplier_id: '',
            name: '',
            quarter: 'Q1',
            year: new Date().getFullYear(),
            amount: 0,
            used_amount: 0,
            remaining_amount: 0,
            top_up_amount: 0,
            update_mode: 'top_up',
            adjustment_reason: '',
            note: '',
            actionUrl: ''
        },
        openEditFundModal(fund) {
            const rem = parseFloat(fund.remaining_amount) || 0;
            this.editFundData = {
                id: fund.id,
                supplier_id: fund.supplier_id,
                name: fund.name,
                quarter: fund.quarter,
                year: fund.year,
                amount: parseFloat(fund.amount) || 0,
                used_amount: parseFloat(fund.used_amount) || 0,
                remaining_amount: rem,
                top_up_amount: rem < 0 ? Math.abs(rem) : 0,
                update_mode: rem < 0 ? 'top_up' : 'top_up',
                adjustment_reason: rem < 0 ? 'Bổ sung ngân sách bù quỹ âm (Hãng thanh toán / rót thêm tiền)' : '',
                note: fund.note || '',
                actionUrl: '/marketing-events/funds/' + fund.id
            };
            this.showEditFundModal = true;
        },
        openFundHistoryModal(fund) {
            this.selectedFund = fund;
            this.fundHistoryList = fund.transactions || [];
            this.showFundHistoryModal = true;
            this.fundHistoryLoading = true;
            fetch('/marketing-events/funds/' + fund.id + '/transactions')
                .then(res => res.json())
                .then(data => {
                    if (data && data.fund) {
                        this.selectedFund = data.fund;
                        this.fundHistoryList = data.transactions || [];
                    }
                })
                .catch(err => console.error('Error fetching fund transactions:', err))
                .finally(() => {
                    this.fundHistoryLoading = false;
                });
        },
        completeData: {
            eventId: null,
            eventCode: '',
            eventTitle: '',
            budget: 0,
            actualCost: 0,
            varianceSource: 'Ngân sách công ty bù',
            note: '',
            sources: [],
            actionUrl: ''
        },
        availableItems: {!! $availableMarketingItemsJson !!},
        giftRows: [{ item_id: '', search: '', open: false, selectedItem: null, quantity: 1 }],
        giftNote: '',
        openCompleteModal(ev) {
            this.completeData.eventId = ev.id;
            this.completeData.eventCode = ev.code;
            this.completeData.eventTitle = ev.title;
            this.completeData.budget = ev.budget || 0;
            this.completeData.actualCost = ev.actual_cost && ev.actual_cost > 0 ? ev.actual_cost : (ev.budget || 0);
            this.completeData.varianceSource = ev.variance_funding_source || 'Ngân sách công ty bù';
            this.completeData.note = ev.completion_note || '';
            this.completeData.actionUrl = '/marketing-events/' + ev.id + '/complete';
            
            let sources = [];
            if (ev.actual_funding_sources && Array.isArray(ev.actual_funding_sources) && ev.actual_funding_sources.length > 0) {
                sources = JSON.parse(JSON.stringify(ev.actual_funding_sources));
            } else if (ev.funding_sources && Array.isArray(ev.funding_sources) && ev.funding_sources.length > 0) {
                sources = JSON.parse(JSON.stringify(ev.funding_sources));
            } else {
                sources = [
                    { name: ev.funding_source || 'Nguồn tài trợ chính', planned_amount: ev.budget || 0, actual_amount: ev.budget || 0 }
                ];
            }
            this.completeData.sources = sources;
            this.showCompleteModal = true;
        },
        getCompletionTotalFunding() {
            let total = 0;
            if (this.completeData.sources) {
                this.completeData.sources.forEach(s => {
                    const amt = parseFloat((s.actual_amount + '').replace(/[^\d.-]/g, '')) || 0;
                    total += amt;
                });
            }
            return total;
        },
        getCompletionVariance() {
            const cost = parseFloat((this.completeData.actualCost + '').replace(/[^\d.-]/g, '')) || 0;
            return cost - this.getCompletionTotalFunding();
        },
        formatMoneyNumber(val) {
            if (val === null || val === undefined || val === '') return '0';
            const str = (val + '').replace(/[^\d.-]/g, '');
            const num = parseFloat(str) || 0;
            return (num < 0 ? '-' : '') + new Intl.NumberFormat('en-US').format(Math.abs(Math.round(num)));
        },
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
    <div class="bg-white rounded-lg shadow-sm p-2 flex border-b border-gray-100 flex-wrap gap-1">
        <a href="{{ route('marketing-events.index', ['tab' => 'events']) }}"
           class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg transition-all {{ $currentTab === 'events' ? 'bg-purple-50 text-purple-700' : 'text-gray-500 hover:text-purple-600 hover:bg-gray-50' }}">
            <i class="fas fa-calendar-alt text-base"></i> Sự kiện Marketing
        </a>
        <a href="{{ route('marketing-events.index', ['tab' => 'payments']) }}"
           class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg transition-all {{ $currentTab === 'payments' ? 'bg-purple-50 text-purple-700' : 'text-gray-500 hover:text-purple-600 hover:bg-gray-50' }}">
            <i class="fas fa-money-check-alt text-base"></i> Yêu cầu thanh toán Marketing
            @if(($pendingPaymentApprovalCount + $pendingPaymentCount) > 0)
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                    {{ $pendingPaymentApprovalCount + $pendingPaymentCount }}
                </span>
            @endif
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
                                    <div class="flex flex-col gap-1.5 items-center justify-center">
                                        @if($marketingRequest->status !== 'completed')
                                            {{-- Bước 1: Nếu chưa phân bổ quà -> Nút Phân bổ quà từ kho --}}
                                            @if($allocatedTxs->count() === 0)
                                                <button type="button" @click="openAllocateModal({{ Js::from($modalPayload) }})" 
                                                    class="w-full px-3 py-1.5 text-xs font-bold bg-purple-600 text-white hover:bg-purple-700 rounded-lg transition-colors shadow-sm inline-flex items-center justify-center gap-1.5" 
                                                    title="Chọn và xuất quà từ kho cho Ticket này">
                                                    <i class="fas fa-gift"></i> Phân bổ quà
                                                </button>
                                            @else
                                                {{-- Bước 2: Đã phân bổ quà -> Nút Bàn giao cho Sales --}}
                                                <form method="POST" action="{{ route('marketing-requests.status.update', $marketingRequest) }}" class="w-full">
                                                    @csrf
                                                    <input type="hidden" name="status" value="completed">
                                                    <input type="hidden" name="comment" value="Đã chuẩn bị và bàn giao quà tặng đầy đủ cho Sales.">
                                                    <button type="submit" onclick="return confirm('Xác nhận đã đóng gói và bàn giao quà tặng cho Sales?')" 
                                                        class="w-full px-3 py-1.5 text-xs font-bold bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors shadow-sm inline-flex items-center justify-center gap-1.5" 
                                                        title="Xác nhận đã bàn giao quà cho Sales">
                                                        <i class="fas fa-check-circle"></i> Bàn giao quà
                                                    </button>
                                                </form>

                                                <button type="button" @click="openAllocateModal({{ Js::from($modalPayload) }})" 
                                                    class="text-[11px] text-purple-700 hover:text-purple-900 font-semibold hover:underline inline-flex items-center gap-1">
                                                    <i class="fas fa-plus-circle"></i> Xuất thêm / Quản lý
                                                </button>
                                            @endif

                                            {{-- Phân công người phụ trách --}}
                                            @if($user->hasAnyRole(['super_admin', 'admin', 'director', 'marketing', 'marketing_manager']))
                                                <form method="POST" action="{{ route('marketing-requests.assign', $marketingRequest) }}" class="flex items-center gap-1 mt-0.5">
                                                    @csrf
                                                    <select name="assigned_to" class="max-w-[110px] border border-gray-300 rounded px-1.5 py-0.5 text-xs" required title="Chọn người phụ trách">
                                                        <option value="">Phân công...</option>
                                                        @foreach($marketingAssignees as $assignee)
                                                            <option value="{{ $assignee->id }}" {{ $marketingRequest->assigned_to === $assignee->id ? 'selected' : '' }}>{{ $assignee->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="px-2 py-0.5 text-xs bg-purple-600 text-white rounded hover:bg-purple-700 shadow-xs" title="Lưu phân công"><i class="fas fa-save"></i></button>
                                                </form>
                                            @endif
                                        @else
                                            {{-- Đã hoàn thành bàn giao (Không hiện badge Xong rườm rà) --}}
                                            @if($allocatedTxs->count() > 0)
                                                <button type="button" @click="openAllocateModal({{ Js::from($modalPayload) }})" 
                                                    class="text-xs text-purple-700 hover:text-purple-900 font-medium hover:underline inline-flex items-center gap-1"
                                                    title="Xem danh sách quà đã xuất">
                                                    <i class="fas fa-eye"></i> Xem quà đã xuất
                                                </button>
                                            @endif
                                        @endif
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
    @elseif($currentTab === 'payments')
        {{-- Payments Statistics Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm p-4 border border-purple-100 flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-gray-500 uppercase">Tổng đề nghị thanh toán</div>
                    <div class="text-2xl font-black text-purple-700 mt-1">{{ method_exists($paymentRequests, 'total') ? $paymentRequests->total() : $paymentRequests->count() }} phiếu</div>
                </div>
                <div class="w-10 h-10 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-amber-100 flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-gray-500 uppercase">Chờ BOD duyệt chi</div>
                    <div class="text-2xl font-black text-amber-600 mt-1">{{ $pendingPaymentApprovalCount }} phiếu</div>
                </div>
                <div class="w-10 h-10 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                    <i class="fas fa-user-check"></i>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-blue-100 flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-gray-500 uppercase">Chờ Kế toán chi tiền</div>
                    <div class="text-2xl font-black text-blue-600 mt-1">{{ $pendingPaymentCount }} phiếu</div>
                </div>
                <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-emerald-100 flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-gray-500 uppercase">Đã thanh toán hoàn tất</div>
                    <div class="text-2xl font-black text-emerald-700 mt-1">{{ number_format($paidTotalAmount) }} đ</div>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        {{-- Filter & Header --}}
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-money-check-alt text-purple-600"></i> Quản lý Yêu cầu thanh toán Marketing
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">Theo dõi luồng duyệt chi BOD và giải ngân chuyển khoản từ Kế toán cho các sự kiện & hoạt động Marketing</p>
                </div>
                <form action="{{ route('marketing-events.index') }}" method="GET" class="flex flex-wrap gap-2">
                    <input type="hidden" name="tab" value="payments">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo mã, nội dung..."
                        class="border border-gray-300 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-purple-400">
                    <select name="event_id" onchange="this.form.submit()"
                        class="border border-gray-300 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-purple-400 bg-white">
                        <option value="">Tất cả sự kiện</option>
                        @foreach($allEventsForSelect as $ev)
                            <option value="{{ $ev->id }}" {{ request('event_id') == $ev->id ? 'selected' : '' }}>{{ $ev->code }} - {{ $ev->title }}</option>
                        @endforeach
                    </select>
                    <select name="payment_status" onchange="this.form.submit()"
                        class="border border-gray-300 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-purple-400 bg-white">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending_approval" {{ request('payment_status') === 'pending_approval' ? 'selected' : '' }}>Chờ BOD duyệt</option>
                        <option value="pending_payment"  {{ request('payment_status') === 'pending_payment' ? 'selected' : '' }}>Chờ Kế toán chi</option>
                        <option value="completed"        {{ request('payment_status') === 'completed' ? 'selected' : '' }}>Đã thanh toán</option>
                        <option value="rejected"         {{ request('payment_status') === 'rejected' ? 'selected' : '' }}>Từ chối</option>
                    </select>
                </form>
            </div>
        </div>

        {{-- Table Payments --}}
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left">Mã phiếu</th>
                            <th class="px-4 py-3 text-left">Sự kiện liên kết</th>
                            <th class="px-4 py-3 text-left min-w-[220px]">Nội dung thanh toán / Tạm ứng</th>
                            <th class="px-4 py-3 text-right">Số tiền (VND)</th>
                            <th class="px-4 py-3 text-left">Nguồn tiền / Quỹ</th>
                            <th class="px-4 py-3 text-center">Trạng thái</th>
                            <th class="px-4 py-3 text-center">Người lập / Ngày</th>
                            <th class="px-4 py-3 text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($paymentRequests as $req)
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-4 py-3 font-bold text-purple-700 whitespace-nowrap">
                                    {{ $req->code }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($req->event)
                                        <a href="{{ route('marketing-events.show', $req->event) }}" class="font-semibold text-gray-800 hover:text-purple-600 block text-xs">
                                            {{ $req->event->code }}
                                        </a>
                                        <span class="text-[11px] text-gray-400 truncate max-w-[150px] block">{{ $req->event->title }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">Hoạt động MKT chung</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 text-xs">{{ $req->description }}</div>
                                    @if($req->amount_in_words)
                                        <div class="text-[11px] text-gray-500 italic mt-0.5">Bằng chữ: {{ $req->amount_in_words }}</div>
                                    @endif
                                    @if(!empty($req->attachment_path) && is_array($req->attachment_path))
                                        <div class="flex items-center gap-1 mt-1">
                                            @foreach($req->attachment_path as $file)
                                                <a href="{{ $file['url'] ?? asset('storage/' . ($file['path'] ?? '')) }}" target="_blank"
                                                   class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] bg-gray-100 text-purple-700 hover:bg-purple-100">
                                                    <i class="fas fa-paperclip text-[9px]"></i> {{ $file['name'] ?? 'Chứng từ' }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <span class="text-sm font-black text-gray-900">{{ number_format($req->amount) }} đ</span>
                                </td>
                                <td class="px-4 py-3 text-xs whitespace-nowrap">
                                    @if(!empty($req->funding_allocations) && is_array($req->funding_allocations))
                                        <div class="space-y-1">
                                            @foreach($req->funding_allocations as $alloc)
                                                <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 block">
                                                    <i class="fas fa-wallet text-[9px]"></i>
                                                    <span>{{ $alloc['name'] ?? 'Quỹ' }}: {{ number_format($alloc['amount'] ?? 0) }} đ</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif($req->fund)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            <i class="fas fa-wallet text-[9px]"></i> {{ $req->fund->supplier->name ?? 'Quỹ' }}: {{ $req->fund->name }}
                                        </span>
                                    @elseif($req->funding_source)
                                        <span class="font-medium text-gray-700">{{ $req->funding_source }}</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                    @if($req->supplier_debt_checked)
                                        <div class="text-[10px] text-red-600 font-bold mt-0.5"><i class="fas fa-exclamation-circle text-[9px]"></i> Ghi nhận công nợ hãng</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $req->status_color }}">
                                        {{ $req->status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap text-xs text-gray-600">
                                    <div>{{ $req->ticket?->creator->name ?? '—' }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $req->created_at->format('d/m/Y H:i') }}</div>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1">
                                        @if($req->status === 'pending_approval' && (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('director')))
                                            <form action="{{ route('marketing-requests.status.update', $req) }}" method="POST" class="inline">
                                                @csrf
                                                <input type="hidden" name="status" value="pending_payment">
                                                <button type="submit" onclick="return confirm('BOD duyệt chi khoản thanh toán này sang Kế toán?')"
                                                    class="px-2.5 py-1 bg-emerald-600 text-white rounded text-xs font-bold hover:bg-emerald-700 shadow-xs" title="BOD Duyệt chi">
                                                    <i class="fas fa-check mr-1"></i>Duyệt chi
                                                </button>
                                            </form>
                                            <form action="{{ route('marketing-requests.status.update', $req) }}" method="POST" class="inline">
                                                @csrf
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" onclick="return confirm('Từ chối yêu cầu thanh toán này?')"
                                                    class="px-2.5 py-1 bg-red-50 text-red-600 rounded text-xs font-bold hover:bg-red-100" title="Từ chối">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        @elseif($req->status === 'pending_payment' && (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('accountant')))
                                            <form action="{{ route('marketing-requests.status.update', $req) }}" method="POST" class="inline">
                                                @csrf
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" onclick="return confirm('Xác nhận Kế toán đã chuyển khoản / giải ngân tiền?')"
                                                    class="px-2.5 py-1 bg-blue-600 text-white rounded text-xs font-bold hover:bg-blue-700 shadow-xs" title="Xác nhận đã chi">
                                                    <i class="fas fa-receipt mr-1"></i>Đã chi tiền
                                                </button>
                                            </form>
                                        @endif
                                        @if($req->event)
                                            <a href="{{ route('marketing-events.show', $req->event) }}" 
                                               class="w-7 h-7 rounded-lg bg-gray-100 hover:bg-purple-100 text-gray-600 hover:text-purple-700 inline-flex items-center justify-center text-xs" title="Xem chi tiết sự kiện">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">Chưa có yêu cầu thanh toán Marketing nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(method_exists($paymentRequests, 'links'))
                <div class="p-4">{{ $paymentRequests->links() }}</div>
            @endif
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
                            <option value="draft"     {{ request('status') === 'draft'     ? 'selected' : '' }}>Nháp</option>
                            <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Chờ duyệt</option>
                            <option value="approved"  {{ request('status') === 'approved'  ? 'selected' : '' }}>Đã duyệt</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Đã hoàn thành</option>
                            <option value="rejected"  {{ request('status') === 'rejected'  ? 'selected' : '' }}>Từ chối</option>
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
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày & Địa điểm</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hãng / Nguồn tiền</th>
                            @if($isSuperOrMktOrOMOrBOD)
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">NS dự toán</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">NS thực tế</th>
                            @endif
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">KH / CBNV</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Người tạo</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($events as $event)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('marketing-events.show', $event) }}" class="font-bold text-purple-700 hover:underline">
                                    {{ $event->title }}
                                </a>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-700">
                                        {{ $event->code }}
                                    </span>
                                    @if($event->scope === 'internal')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            <i class="fas fa-users text-[9px]"></i> Nội bộ
                                        </span>
                                    @elseif($event->is_public_to_sales)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-700">
                                            <i class="fas fa-globe text-[9px]"></i> Toàn công ty
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">
                                            <i class="fas fa-lock text-[9px]"></i> Riêng tư
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                <div class="font-medium">{{ $event->event_date->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500 truncate max-w-[150px]">{{ $event->location ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1 max-w-[180px]">
                                    @forelse($event->suppliers as $sup)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            {{ $sup->name }}
                                        </span>
                                    @empty
                                        @if($event->vendor)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                {{ $event->vendor->name }}
                                            </span>
                                        @elseif($event->funding_source)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-700">
                                                {{ $event->funding_source }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    @endforelse
                                </div>
                            </td>
                            @if($isSuperOrMktOrOMOrBOD)
                            <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 whitespace-nowrap">
                                {{ number_format($event->budget) }} đ
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if($event->actual_cost > 0 || $event->isCompleted())
                                    <div class="text-sm font-bold text-gray-900">{{ number_format($event->actual_cost) }} đ</div>
                                    @if($event->variance_amount != 0)
                                        <div class="text-[10px] font-bold mt-0.5 {{ $event->variance_amount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                            {{ $event->variance_amount > 0 ? '▲ Thiếu ' . number_format($event->variance_amount) . ' đ' : '▼ Dư ' . number_format(abs($event->variance_amount)) . ' đ' }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-400 italic">Chưa chốt</span>
                                @endif
                            </td>
                            @endif
                            <td class="px-4 py-3 text-center text-sm font-medium">
                                {{ $event->customers_count ?? $event->customers->count() ?: ($event->target_audience_count ?: '0') }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $event->status_color }}">
                                    {{ $event->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-center text-gray-600 whitespace-nowrap">{{ $event->creator->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('marketing-events.show', $event) }}" 
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 transition-colors" 
                                       title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    {{-- Nút Hoàn thành & Nghiệm thu chi phí --}}
                                    @if(in_array($event->status, ['approved', 'completed']))
                                        <button type="button" @click="openCompleteModal({{ Js::from([
                                            'id' => $event->id,
                                            'code' => $event->code,
                                            'title' => $event->title,
                                            'budget' => (float)$event->budget,
                                            'actual_cost' => (float)$event->actual_cost,
                                            'variance_funding_source' => $event->variance_funding_source,
                                            'completion_note' => $event->completion_note,
                                            'funding_sources' => $event->funding_sources,
                                            'actual_funding_sources' => $event->actual_funding_sources,
                                            'funding_source' => $event->funding_source,
                                        ]) }})" 
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg {{ $event->isCompleted() ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-blue-50 text-blue-700 hover:bg-blue-100' }} transition-colors" 
                                        title="{{ $event->isCompleted() ? 'Xem / Cập nhật quyết toán' : 'Hoàn thành sự kiện & Nghiệm thu chi phí' }}">
                                            <i class="fas {{ $event->isCompleted() ? 'fa-clipboard-check' : 'fa-flag-checkered' }}"></i>
                                        </button>
                                    @endif

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
                        <tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">Chưa có sự kiện nào.</td></tr>
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
            @php
                $remSum = $supplierFunds->sum('remaining_amount');
                $negFunds = $supplierFunds->where('remaining_amount', '<', 0);
                $totalNegAmount = $negFunds->sum(fn($f) => abs($f->remaining_amount));
                $pendingDebt = $transactions->where('type', 'receivable')->where('status', 'pending')->sum('amount');
            @endphp
            <div class="bg-white rounded-xl shadow-sm p-4 border {{ $remSum < 0 ? 'border-rose-200 bg-rose-50/20' : 'border-blue-100' }}">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-bold text-gray-400 uppercase">Số dư còn lại</div>
                    @if($negFunds->count() > 0)
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">
                            {{ $negFunds->count() }} quỹ âm
                        </span>
                    @endif
                </div>
                <div class="text-2xl font-black mt-1 {{ $remSum < 0 ? 'text-rose-600' : 'text-blue-700' }}">
                    {{ number_format($remSum) }} đ
                </div>
                @if($negFunds->count() > 0)
                    <div class="text-[11px] text-rose-600 mt-0.5 font-medium">
                        Tổng âm (Hãng nợ): -{{ number_format($totalNegAmount) }} đ
                    </div>
                @endif
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-red-100">
                <div class="text-xs font-bold text-gray-400 uppercase">Công nợ hãng chờ thu</div>
                <div class="text-2xl font-black text-red-700 mt-1">
                    {{ number_format($pendingDebt) }} đ
                </div>
                @if($totalNegAmount > 0 && $pendingDebt > 0)
                    <div class="text-[11px] text-gray-500 mt-0.5">
                        Bao gồm công nợ xác nhận & chi vượt quỹ
                    </div>
                @endif
            </div>
        </div>

        {{-- Funds Management block --}}
            <div class="flex flex-wrap justify-between items-center gap-2 mb-3">
                <h3 class="text-md font-bold text-gray-800">
                    <i class="fas fa-wallet text-purple-500 mr-2"></i>Quản lý Nguồn Quỹ từ Hãng
                </h3>
                <div class="flex items-center gap-2">
                    <a href="{{ route('marketing-events.funds.template') }}"
                        class="inline-flex items-center px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-300 rounded-lg hover:bg-emerald-100 transition-colors text-xs font-semibold shadow-2xs"
                        title="Tải file mẫu Excel (.xlsx) chuẩn để nhập liệu quỹ">
                        <i class="fas fa-file-excel mr-1.5 text-emerald-600"></i> Tải mẫu Excel
                    </a>
                    @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('marketing') || auth()->user()->hasRole('accountant') || auth()->user()->hasRole('director') || auth()->user()->hasRole('admin'))
                    <button type="button" @click="showImportFundModal = true"
                        class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 border border-indigo-300 rounded-lg hover:bg-indigo-100 transition-colors text-xs font-semibold shadow-2xs"
                        title="Import danh sách quỹ hãng từ file Excel">
                        <i class="fas fa-file-import mr-1.5 text-indigo-600"></i> Import Quỹ Hãng
                    </button>
                    @endif
                    @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('marketing'))
                    <button @click="showAddFundModal = true"
                        class="inline-flex items-center px-3.5 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-xs font-bold shadow-sm">
                        <i class="fas fa-plus mr-1.5"></i> Khai báo quỹ mới
                    </button>
                    @endif
                </div>
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
                            <th class="px-4 py-2.5 text-center font-bold text-gray-600">Thao tác</th>
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
                            <td class="px-4 py-2.5 text-right font-bold whitespace-nowrap">
                                @if($fund->remaining_amount < 0)
                                    <span class="text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded text-xs inline-flex items-center gap-1 font-black">
                                        <i class="fas fa-exclamation-circle text-rose-500 text-[10px]"></i>
                                        {{ number_format($fund->remaining_amount) }} đ
                                        <span class="text-[10px] text-rose-600 font-semibold">(Âm / Hãng nợ)</span>
                                    </span>
                                @else
                                    <span class="text-blue-600">{{ number_format($fund->remaining_amount) }} đ</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-gray-500 text-xs truncate max-w-xs">{{ $fund->note ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" @click="openFundHistoryModal({{ json_encode($fund) }})"
                                        class="px-2.5 py-1 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg text-xs font-semibold transition-colors inline-flex items-center gap-1"
                                        title="Xem toàn bộ lịch sử giao dịch của quỹ này">
                                        <i class="fas fa-history text-[11px]"></i> Lịch sử GD ({{ $fund->transactions->count() }})
                                    </button>
                                    @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('marketing') || auth()->user()->hasRole('accountant') || auth()->user()->hasRole('director') || auth()->user()->hasRole('admin'))
                                    <button type="button" @click="openEditFundModal({{ json_encode($fund) }})"
                                        class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg text-xs font-semibold transition-colors inline-flex items-center gap-1"
                                        title="Cập nhật thông tin hoặc bổ sung ngân sách quỹ">
                                        <i class="fas fa-edit text-[11px]"></i> Cập nhật
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="px-4 py-6 text-center text-gray-400">Chưa khai báo nguồn quỹ nào của hãng.</td></tr>
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

        {{-- Import Funds Modal --}}
        <div x-show="showImportFundModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 overflow-y-auto p-4" x-transition>
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden border border-gray-100" @click.away="showImportFundModal = false">
                <div class="bg-gradient-to-r from-indigo-700 to-purple-700 px-5 py-3.5 flex justify-between items-center text-white">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center text-sm font-bold">
                            <i class="fas fa-file-excel"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm">Import Quỹ Hãng từ Excel</h4>
                            <p class="text-indigo-200 text-[10px]">Tải dữ liệu danh sách quỹ nhanh chóng</p>
                        </div>
                    </div>
                    <button @click="showImportFundModal = false" class="text-white/80 hover:text-white focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form action="{{ route('marketing-events.funds.import') }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4">
                    @csrf
                    <div>
                        <p class="text-xs text-gray-600 mb-2">
                            Vui lòng sử dụng file theo đúng định dạng mẫu để hệ thống nhận diện chính xác các cột Hãng, Quý, Năm và Số tiền quỹ:
                        </p>
                        <a href="{{ route('marketing-events.funds.template') }}" class="inline-flex items-center gap-2 px-3 py-2 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-xs font-bold hover:bg-emerald-100 transition-colors w-full justify-center shadow-2xs">
                            <i class="fas fa-download text-emerald-600"></i> Tải file mẫu Excel (.xlsx) chuẩn
                        </a>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Chọn file Excel dữ liệu <span class="text-red-500">*</span></label>
                        <input type="file" name="file" required accept=".xlsx,.xls,.csv"
                            class="block w-full text-xs text-gray-700 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:mr-3 file:py-2 file:px-3 file:rounded-l-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700">
                        <p class="text-[11px] text-gray-400 mt-1">Hỗ trợ các định dạng .xlsx, .xls, .csv. Dung lượng tối đa 10MB.</p>
                    </div>

                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-[11px] space-y-1">
                        <div class="font-bold flex items-center gap-1">
                            <i class="fas fa-info-circle text-amber-600"></i> Cơ chế đồng bộ thông minh:
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 text-amber-900/80">
                            <li>Nếu Hãng chưa có trong hệ thống, hệ thống sẽ <strong>tự động tạo nhà cung cấp mới</strong>.</li>
                            <li>Nếu Quỹ cùng Hãng, tên, Quý, Năm đã tồn tại, hệ thống sẽ <strong>cập nhật hạn mức</strong> và lưu lịch sử điều chỉnh.</li>
                        </ul>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button type="button" @click="showImportFundModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-xs font-bold">Huỷ</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-xs font-bold flex items-center gap-1.5 shadow-sm">
                            <i class="fas fa-upload"></i> Tiến hành Import
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Edit Fund Modal (Cập nhật / Bổ sung quỹ & Lưu lịch sử giao dịch) --}}
        <div x-show="showEditFundModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 overflow-y-auto p-4" x-transition>
            <div class="bg-white rounded-xl shadow-xl max-w-lg w-full overflow-hidden border border-gray-100" @click.away="showEditFundModal = false">
                <div class="bg-emerald-700 px-5 py-3.5 flex justify-between items-center">
                    <div>
                        <h4 class="text-white font-bold text-sm flex items-center gap-2">
                            <i class="fas fa-edit"></i> Cập nhật Quỹ Hãng & Bổ sung Ngân sách
                        </h4>
                        <p class="text-emerald-100 text-[11px] mt-0.5">Biến động số tiền sẽ tự động lưu vào Sổ lịch sử giao dịch của quỹ.</p>
                    </div>
                    <button @click="showEditFundModal = false" class="text-white hover:text-emerald-200 focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form :action="editFundData.actionUrl" method="POST" class="p-5 space-y-4">
                    @csrf
                    @method('PUT')
                    
                    {{-- Current fund status box --}}
                    <div class="p-3 rounded-lg border text-xs" :class="editFundData.remaining_amount < 0 ? 'bg-rose-50/70 border-rose-200' : 'bg-gray-50 border-gray-200'">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 font-medium">Hạn mức hiện tại:</span>
                            <span class="font-bold text-gray-800" x-text="formatMoneyNumber(editFundData.amount) + ' đ'"></span>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-gray-500 font-medium">Đã dùng thực tế:</span>
                            <span class="font-bold text-red-600" x-text="formatMoneyNumber(editFundData.used_amount) + ' đ'"></span>
                        </div>
                        <div class="flex items-center justify-between mt-1 pt-1.5 border-t border-dashed" :class="editFundData.remaining_amount < 0 ? 'border-rose-300' : 'border-gray-200'">
                            <span class="font-bold" :class="editFundData.remaining_amount < 0 ? 'text-rose-700' : 'text-gray-700'">Số dư khả dụng hiện tại:</span>
                            <span class="font-black text-sm" :class="editFundData.remaining_amount < 0 ? 'text-rose-600' : 'text-blue-700'"
                                x-text="formatMoneyNumber(editFundData.remaining_amount) + ' đ' + (editFundData.remaining_amount < 0 ? ' (Đang âm / Hãng nợ)' : '')"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Chọn Hãng cấp quỹ <span class="text-red-500">*</span></label>
                            <select name="supplier_id" x-model="editFundData.supplier_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400 bg-white">
                                <option value="">-- Chọn nhà cung cấp / Hãng --</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Tên chương trình quỹ / Tên quỹ <span class="text-red-500">*</span></label>
                            <input type="text" name="name" x-model="editFundData.name" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Quý <span class="text-red-500">*</span></label>
                            <select name="quarter" x-model="editFundData.quarter" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400 bg-white">
                                <option value="Q1">Quý 1 (Q1)</option>
                                <option value="Q2">Quý 2 (Q2)</option>
                                <option value="Q3">Quý 3 (Q3)</option>
                                <option value="Q4">Quý 4 (Q4)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Năm <span class="text-red-500">*</span></label>
                            <input type="number" name="year" x-model="editFundData.year" required min="2020" max="2100"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400">
                        </div>
                    </div>

                    {{-- Mode selection: Top-up vs Set total --}}
                    <div class="pt-2 border-t border-gray-100">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Hình thức cập nhật số tiền quỹ</label>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer transition-colors"
                                :class="editFundData.update_mode === 'top_up' ? 'border-emerald-500 bg-emerald-50/50 text-emerald-900 font-semibold' : 'border-gray-200 text-gray-600'">
                                <input type="radio" name="update_mode" value="top_up" x-model="editFundData.update_mode" class="text-emerald-600">
                                <span>+ Bổ sung thêm tiền vào quỹ</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer transition-colors"
                                :class="editFundData.update_mode === 'set_total' ? 'border-emerald-500 bg-emerald-50/50 text-emerald-900 font-semibold' : 'border-gray-200 text-gray-600'">
                                <input type="radio" name="update_mode" value="set_total" x-model="editFundData.update_mode" class="text-emerald-600">
                                <span>Đặt lại Tổng Hạn Mức</span>
                            </label>
                        </div>
                    </div>

                    <div x-show="editFundData.update_mode === 'top_up'" class="space-y-1">
                        <label class="block text-xs font-bold text-emerald-800 uppercase">Số tiền nạp thêm / Hãng bổ sung (VND) <span class="text-red-500">*</span></label>
                        <input type="text" name="top_up_amount"
                            :value="formatMoneyNumber(editFundData.top_up_amount)"
                            @input="editFundData.top_up_amount = $event.target.value.replace(/[^\d]/g, '')"
                            placeholder="Nhập số tiền bổ sung, VD: 50000000"
                            class="w-full border border-emerald-300 rounded-lg px-3 py-2 text-sm font-bold text-emerald-900 focus:ring-2 focus:ring-emerald-400 bg-emerald-50/20">
                        <p class="text-[11px] text-gray-500">
                            Số dư dự kiến sau bổ sung: 
                            <strong class="text-emerald-700" x-text="formatMoneyNumber(editFundData.remaining_amount + (parseFloat(editFundData.top_up_amount) || 0)) + ' đ'"></strong>
                        </p>
                    </div>

                    <div x-show="editFundData.update_mode === 'set_total'" class="space-y-1">
                        <label class="block text-xs font-bold text-gray-700 uppercase">Tổng số tiền quỹ mới (VND) <span class="text-red-500">*</span></label>
                        <input type="text" name="amount"
                            :value="formatMoneyNumber(editFundData.amount)"
                            @input="editFundData.amount = $event.target.value.replace(/[^\d]/g, '')"
                            placeholder="Nhập tổng hạn mức mới"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-emerald-400">
                        <p class="text-[11px] text-gray-500">
                            Chênh lệch điều chỉnh: 
                            <strong x-text="((parseFloat(editFundData.amount) || 0) >= (parseFloat(editFundData.amount) || 0) ? '+' : '-') + formatMoneyNumber(Math.abs((parseFloat(editFundData.amount) || 0) - (parseFloat(editFundData.amount) || 0))) + ' đ'"></strong>
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Lý do điều chỉnh / Ghi chú giao dịch</label>
                        <input type="text" name="adjustment_reason" x-model="editFundData.adjustment_reason"
                            placeholder="VD: Hãng thanh toán trả nợ bù quỹ, bổ sung gói MDF Q3..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400">
                        <p class="text-[10px] text-gray-400 mt-0.5">Lý do này sẽ được ghi vào Sổ lịch sử giao dịch của quỹ.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Ghi chú chung của quỹ</label>
                        <textarea name="note" x-model="editFundData.note" rows="2" placeholder="Ghi chú điều kiện sử dụng quỹ..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-400"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="showEditFundModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-xs font-bold">Huỷ</button>
                        <button type="submit" class="px-5 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-xs font-bold shadow-sm flex items-center gap-1.5">
                            <i class="fas fa-check"></i> Lưu & Cập nhật Quỹ
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Fund Transaction History Modal (Sổ lịch sử giao dịch của Quỹ) --}}
        <div x-show="showFundHistoryModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 overflow-y-auto" x-transition>
            <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full overflow-hidden border border-gray-200" @click.away="showFundHistoryModal = false">
                <div class="bg-gradient-to-r from-purple-800 to-indigo-900 px-6 py-4 flex justify-between items-center text-white">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-white/20 text-purple-100"
                                x-text="(selectedFund?.quarter || '') + ' - ' + (selectedFund?.year || '')"></span>
                            <h4 class="font-bold text-base" x-text="selectedFund?.name || 'Lịch sử Giao dịch Quỹ'"></h4>
                        </div>
                        <p class="text-xs text-purple-200 mt-1 flex items-center gap-1.5">
                            <i class="fas fa-building text-[10px]"></i> Hãng tài trợ: <strong class="text-white" x-text="selectedFund?.supplier_name || selectedFund?.supplier?.name || '—'"></strong>
                        </p>
                    </div>
                    <button @click="showFundHistoryModal = false" class="text-white/80 hover:text-white p-1 rounded-lg focus:outline-none">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>

                {{-- Fund Metrics Bar --}}
                <div class="grid grid-cols-3 bg-gray-50 border-b border-gray-200 p-4 gap-4 text-center">
                    <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-2xs">
                        <span class="text-[11px] font-bold text-gray-500 uppercase block">Tổng quỹ đã cấp</span>
                        <span class="text-base font-black text-gray-900 mt-0.5 block" x-text="formatMoneyNumber(selectedFund?.amount) + ' đ'"></span>
                    </div>
                    <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-2xs">
                        <span class="text-[11px] font-bold text-gray-500 uppercase block">Đã sử dụng thực tế</span>
                        <span class="text-base font-black text-red-600 mt-0.5 block" x-text="formatMoneyNumber(selectedFund?.used_amount) + ' đ'"></span>
                    </div>
                    <div class="bg-white p-3 rounded-xl border shadow-2xs" :class="selectedFund?.remaining_amount < 0 ? 'border-rose-300 bg-rose-50/50' : 'border-gray-200'">
                        <span class="text-[11px] font-bold uppercase block" :class="selectedFund?.remaining_amount < 0 ? 'text-rose-700' : 'text-gray-500'">Số dư khả dụng</span>
                        <span class="text-base font-black mt-0.5 block" :class="selectedFund?.remaining_amount < 0 ? 'text-rose-600' : 'text-blue-700'"
                            x-text="formatMoneyNumber(selectedFund?.remaining_amount) + ' đ'"></span>
                        <template x-if="selectedFund?.remaining_amount < 0">
                            <span class="text-[10px] font-bold text-rose-600 bg-rose-100 px-1.5 py-0.5 rounded mt-0.5 inline-block">Đang âm quỹ (Hãng nợ)</span>
                        </template>
                    </div>
                </div>

                {{-- Action / Info row --}}
                <div class="px-6 py-3 bg-white flex justify-between items-center border-b border-gray-100">
                    <div class="text-xs text-gray-600 flex items-center gap-1.5">
                        <i class="fas fa-receipt text-purple-600"></i>
                        <span>Chi tiết lịch sử biến động số dư:</span>
                    </div>
                    @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('marketing') || auth()->user()->hasRole('accountant') || auth()->user()->hasRole('director') || auth()->user()->hasRole('admin'))
                    <button type="button" @click="showFundHistoryModal = false; openEditFundModal(selectedFund)"
                        class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-all shadow-2xs flex items-center gap-1.5">
                        <i class="fas fa-plus-circle"></i> Bổ sung / Cập nhật quỹ
                    </button>
                    @endif
                </div>

                {{-- Transactions Table --}}
                <div class="max-h-96 overflow-y-auto p-4">
                    <div x-show="fundHistoryLoading" class="py-8 text-center text-gray-400">
                        <i class="fas fa-spinner fa-spin text-2xl text-purple-600"></i>
                        <p class="text-xs mt-2 font-medium">Đang tải lịch sử giao dịch...</p>
                    </div>

                    <table x-show="!fundHistoryLoading" class="w-full text-xs text-left">
                        <thead class="bg-gray-100 text-gray-600 font-bold uppercase sticky top-0 text-[10px]">
                            <tr>
                                <th class="p-2.5">Thời gian</th>
                                <th class="p-2.5 text-center">Loại giao dịch</th>
                                <th class="p-2.5 text-right">Biến động</th>
                                <th class="p-2.5">Sự kiện / Diễn giải</th>
                                <th class="p-2.5">Người thực hiện</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(tx, tIdx) in fundHistoryList" :key="tx.id || tIdx">
                                <tr class="hover:bg-gray-50/70">
                                    <td class="p-2.5 whitespace-nowrap text-gray-500" x-text="tx.created_at"></td>
                                    <td class="p-2.5 text-center whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                            :class="{
                                                'bg-emerald-50 text-emerald-700 border border-emerald-200': tx.type === 'incoming' || tx.type === 'top_up',
                                                'bg-rose-50 text-rose-700 border border-rose-200': tx.type === 'expense',
                                                'bg-amber-50 text-amber-700 border border-amber-200': tx.type === 'receivable',
                                                'bg-blue-50 text-blue-700 border border-blue-200': tx.type === 'collected',
                                                'bg-purple-50 text-purple-700 border border-purple-200': tx.type === 'adjustment'
                                            }"
                                            x-text="tx.type_label || tx.type">
                                        </span>
                                    </td>
                                    <td class="p-2.5 text-right font-bold whitespace-nowrap"
                                        :class="{
                                            'text-emerald-700': tx.type === 'incoming' || tx.type === 'top_up' || tx.type === 'collected',
                                            'text-rose-600': tx.type === 'expense',
                                            'text-amber-700': tx.type === 'receivable',
                                            'text-purple-700': tx.type === 'adjustment'
                                        }">
                                        <span x-text="(tx.type === 'incoming' || tx.type === 'top_up' ? '+' : (tx.type === 'expense' ? '-' : '')) + formatMoneyNumber(tx.amount) + ' đ'"></span>
                                    </td>
                                    <td class="p-2.5">
                                        <div class="font-medium text-gray-800" x-text="tx.note || '—'"></div>
                                        <template x-if="tx.event_title || tx.event?.title">
                                            <div class="text-[10px] text-purple-600 mt-0.5 flex items-center gap-1">
                                                <i class="fas fa-calendar-alt text-[9px]"></i>
                                                <span x-text="'Sự kiện: ' + (tx.event_title || tx.event?.title)"></span>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="p-2.5 whitespace-nowrap text-gray-500" x-text="tx.creator_name || tx.creator?.name || 'Hệ thống'"></td>
                                </tr>
                            </template>
                            <tr x-show="!fundHistoryLoading && fundHistoryList.length === 0">
                                <td colspan="5" class="p-6 text-center text-gray-400">Chưa có giao dịch phát sinh nào trong quỹ này.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button type="button" @click="showFundHistoryModal = false" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-xs font-bold">
                        Đóng
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL HOÀN THÀNH SỰ KIỆN & QUYẾT TOÁN CHI PHÍ THỰC TẾ --}}
    <div x-show="showCompleteModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
         x-transition>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden border border-purple-100"
             @click.outside="showCompleteModal = false">
            <div class="px-6 py-4 bg-gradient-to-r from-emerald-600 to-teal-700 text-white flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center text-lg">
                        <i class="fas fa-flag-checkered"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold">Nghiệm thu Hoàn thành Sự kiện & Quyết toán NS</h3>
                        <p class="text-xs text-emerald-100 mt-0.5">
                            Sự kiện: <strong x-text="completeData.eventCode"></strong> - <span x-text="completeData.eventTitle"></span>
                        </p>
                    </div>
                </div>
                <button type="button" @click="showCompleteModal = false" class="text-white/80 hover:text-white text-lg">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form :action="completeData.actionUrl" method="POST" class="p-6 space-y-4 max-h-[85vh] overflow-y-auto">
                @csrf

                {{-- Tổng chi phí thực tế --}}
                <div class="bg-gradient-to-r from-emerald-50 to-teal-50 p-4 rounded-xl border border-emerald-200">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <span class="text-xs font-bold text-gray-500 uppercase block">Ngân sách dự toán ban đầu:</span>
                            <span class="text-lg font-bold text-gray-800" x-text="formatMoneyNumber(completeData.budget) + ' đ'"></span>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-emerald-900 uppercase mb-1">
                                Tổng Chi phí Thực tế Phát sinh (VND) <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   :value="formatMoneyNumber(completeData.actualCost)"
                                   @input="completeData.actualCost = $event.target.value.replace(/[^\d]/g, '')"
                                   name="actual_cost"
                                   required
                                   class="w-full border border-emerald-300 rounded-lg px-3 py-2 text-base font-black text-emerald-800 bg-white focus:ring-2 focus:ring-emerald-400 text-right">
                        </div>
                    </div>
                </div>

                {{-- Bảng chi tiết thực tế tài trợ của từng Hãng / Nguồn tiền --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">
                            <i class="fas fa-hand-holding-usd text-emerald-600 mr-1"></i> Số tiền thực tế tài trợ từng Hãng & Nguồn tiền
                        </label>
                        <span class="text-xs text-gray-500">
                            Tổng tài trợ thực tế: <strong class="text-emerald-700" x-text="formatMoneyNumber(getCompletionTotalFunding()) + ' đ'"></strong>
                        </span>
                    </div>

                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50 font-bold uppercase text-gray-500">
                                <tr>
                                    <th class="p-2.5 text-left">Nguồn tài trợ / Hãng</th>
                                    <th class="p-2.5 text-right w-36">Dự toán cam kết</th>
                                    <th class="p-2.5 text-right w-44">Thực tế chi trả (VND) <span class="text-red-500">*</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(src, idx) in completeData.sources" :key="idx">
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="p-2.5 font-semibold text-gray-800" x-text="src.name || ('Nguồn ' + (idx + 1))"></td>
                                        <td class="p-2.5 text-right text-gray-500" x-text="formatMoneyNumber(src.planned_amount) + ' đ'"></td>
                                        <td class="p-2.5 text-right">
                                            <input type="text"
                                                   :name="'actual_funding_sources[' + idx + ']'"
                                                   :value="formatMoneyNumber(src.actual_amount)"
                                                   @input="src.actual_amount = $event.target.value.replace(/[^\d]/g, '')"
                                                   class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs text-right font-bold text-gray-800 focus:ring-1 focus:ring-emerald-400">
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Con số chênh lệch & Nguồn xử lý chênh lệch --}}
                <div class="p-4 rounded-xl border"
                     :class="getCompletionVariance() > 0 ? 'bg-rose-50/70 border-rose-200' : (getCompletionVariance() < 0 ? 'bg-emerald-50/70 border-emerald-200' : 'bg-gray-50 border-gray-200')">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase"
                              :class="getCompletionVariance() > 0 ? 'text-rose-800' : (getCompletionVariance() < 0 ? 'text-emerald-800' : 'text-gray-700')">
                            <i class="fas fa-balance-scale mr-1"></i> Chênh lệch (Tổng chi thực tế - Tổng tài trợ):
                        </span>
                        <span class="text-base font-black"
                              :class="getCompletionVariance() > 0 ? 'text-rose-700' : (getCompletionVariance() < 0 ? 'text-emerald-700' : 'text-gray-700')"
                              x-text="(getCompletionVariance() > 0 ? '▲ Thiếu hụt (Vượt chi): ' : (getCompletionVariance() < 0 ? '▼ Dư tiền tài trợ: ' : 'Cân bằng: ')) + formatMoneyNumber(Math.abs(getCompletionVariance())) + ' đ'">
                        </span>
                    </div>

                    {{-- Nguồn tiền bù đắp / xử lý phần dư --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            <span x-text="getCompletionVariance() > 0 ? 'Con số thiếu hụt / phát sinh vượt dự toán lấy nguồn bù từ đâu? *' : 'Con số dư tài trợ xử lý như thế nào? *'"></span>
                        </label>
                        <select name="variance_funding_source" x-model="completeData.varianceSource" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-purple-400 bg-white">
                            <option value="Ngân sách công ty bù thêm">Trích bổ sung từ Ngân sách Công ty</option>
                            <option value="Đàm phán Hãng hỗ trợ thêm">Đàm phán Hãng hỗ trợ thanh toán thêm</option>
                            <option value="Quỹ Marketing nội bộ dự phòng">Trích từ Quỹ Marketing dự phòng của năm</option>
                            <option value="Quỹ Công đoàn hỗ trợ">Trích từ Quỹ Công đoàn hỗ trợ</option>
                            <option value="Hoàn trả quỹ hãng / chuyển kỳ sau">Hoàn trả nguồn tài trợ / Chuyển sang sự kiện sau</option>
                            <option value="Khác">Khác (Ghi chú chi tiết bên dưới)</option>
                        </select>
                    </div>
                </div>

                {{-- Ghi chú nghiệm thu --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ghi chú tổng kết / Nghiệm thu tài chính</label>
                    <textarea name="completion_note" x-model="completeData.note" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-purple-400"
                              placeholder="Ghi chú kết quả sự kiện, đối soát hóa đơn với các hãng..."></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t">
                    <button type="button" @click="showCompleteModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-xs font-bold transition-colors">
                        Đóng
                    </button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-sm transition-all flex items-center gap-1.5">
                        <i class="fas fa-check-circle"></i> Xác nhận Hoàn thành & Lưu quyết toán
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

