@extends('layouts.app')
@section('title', $marketingEvent->title ?? 'Chương trình Marketing')

@section('content')
@php
    // Permission check for approval actions
    $mktWorkflow  = \App\Models\ApprovalWorkflow::getForDocumentType('marketing_budget');
    $mktNextLevel = null;
    $canApprove   = false;
    if ($mktWorkflow && $marketingEvent->status === 'pending') {
        $pendingHist = \App\Models\ApprovalHistory::where('document_type', 'marketing_budget')
            ->where('document_id', $marketingEvent->id)
            ->where('action', 'pending')
            ->orderBy('level')->first();
        if ($pendingHist) {
            $mktNextLevel = $mktWorkflow->levels()->where('level', $pendingHist->level)->first();
            $canApprove   = $mktNextLevel?->canApprove(auth()->user(), $marketingEvent->budget ?? 0) ?? false;
        }
    }

    $isMarketingOrBOD = auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('director') || auth()->user()->hasRole('marketing') || auth()->user()->hasRole('order_management');
    
    // Tính tổng tiền thanh toán đã hoàn thành chi
    $actualPaymentsCost = $marketingEvent->requests()
        ->whereHas('ticket', fn($q) => $q->where('type', 'payment'))
        ->where('status', 'completed')
        ->sum('amount');
    $effectiveActualCost = $marketingEvent->actual_cost ?? $actualPaymentsCost;
@endphp

<div class="space-y-5" x-data="{ 
    showReject: new URLSearchParams(window.location.search).get('reject') === '1',
    showCompleteModal: false,
    completeData: {
        eventId: {{ $marketingEvent->id }},
        eventTitle: {{ Js::from($marketingEvent->title) }},
        eventCode: {{ Js::from($marketingEvent->code) }},
        budget: {{ (float)($marketingEvent->budget ?? 0) }},
        actualCost: '{{ (int)($marketingEvent->actual_cost ?? $effectiveActualCost ?? 0) }}',
        sources: {{ Js::from(!empty($marketingEvent->funding_sources) ? $marketingEvent->funding_sources : [['name' => $marketingEvent->vendor?->name ?? 'Hãng tài trợ', 'type' => 'brand', 'planned_amount' => $marketingEvent->budget ?? 0, 'actual_amount' => $marketingEvent->budget ?? 0]]) }},
        varianceSource: '{{ $marketingEvent->variance_funding_source ?? 'Ngân sách công ty bù thêm' }}',
        note: '{{ addslashes($marketingEvent->completion_note ?? '') }}'
    },
    formatMoneyNumber(val) {
        if (!val && val !== 0) return '0';
        return Number(val).toLocaleString('vi-VN');
    },
    getCompletionTotalFunding() {
        return (this.completeData.sources || []).reduce((sum, s) => sum + (parseFloat(s.actual_amount) || 0), 0);
    },
    getCompletionVariance() {
        const actual = parseFloat(this.completeData.actualCost) || 0;
        const funding = this.getCompletionTotalFunding();
        return actual - funding;
    }
}">
    {{-- ── Workflow Progress Guide ── --}}
    @php
        $steps = [
            ['id' => 1, 'name' => 'Kế hoạch', 'icon' => 'fa-file-alt'],
            ['id' => 2, 'name' => 'Duyệt ngân sách', 'icon' => 'fa-check-double'],
            ['id' => 3, 'name' => 'Mở Ticket hỗ trợ', 'icon' => 'fa-ticket-alt'],
            ['id' => 4, 'name' => 'Nghiệm thu quyết toán', 'icon' => 'fa-flag-checkered'],
        ];

        // Determine current step logic
        $currentStep = 1;
        if ($marketingEvent->status === 'completed') {
            $currentStep = 5; // Hoàn tất toàn bộ
        } elseif ($marketingEvent->status === 'approved') {
            $currentStep = 3;
            if ($marketingEvent->tickets->count() > 0) {
                $allRequests = \App\Models\MarketingRequest::where('marketing_event_id', $marketingEvent->id)->get();
                $allCompleted = $allRequests->isNotEmpty() && $allRequests->every(fn($r) => $r->status === 'completed');
                if ($allCompleted) {
                    $currentStep = 4; // Sẵn sàng quyết toán
                }
            }
        } elseif ($marketingEvent->status === 'pending') {
            $currentStep = 2;
        }
    @endphp

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 overflow-x-auto">
        <div class="flex items-center justify-between min-w-[800px] relative">
            {{-- Background line --}}
            <div class="absolute top-5 left-0 w-full h-0.5 bg-gray-100 -z-0"></div>
            
            @foreach($steps as $step)
                @php
                    $isCompleted = $step['id'] < $currentStep;
                    $isActive = $step['id'] === $currentStep;
                    $isUpcoming = $step['id'] > $currentStep;
                @endphp
                <div class="relative z-10 flex flex-col items-center flex-1">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm shadow-sm transition-all duration-300
                        {{ $isCompleted ? 'bg-emerald-500 text-white' : ($isActive ? 'bg-violet-600 text-white ring-4 ring-violet-100 scale-110' : 'bg-gray-50 text-gray-400 border border-gray-200') }}">
                        @if($isCompleted)
                            <i class="fas fa-check"></i>
                        @else
                            <i class="fas {{ $step['icon'] }}"></i>
                        @endif
                    </div>
                    <span class="mt-3 text-[10px] font-bold uppercase tracking-wider text-center px-1
                        {{ $isActive ? 'text-violet-600' : ($isCompleted ? 'text-emerald-600' : 'text-gray-400') }}">
                        {{ $step['name'] }}
                    </span>
                    @if($isActive)
                        <div class="mt-1 w-1 h-1 rounded-full bg-violet-600 animate-bounce"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Rejection Warning --}}
    @if($marketingEvent->status === 'rejected' && $marketingEvent->rejection_reason)
    <div class="flex items-start gap-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl">
        <div class="flex-shrink-0 w-10 h-10 bg-red-100 rounded-full flex items-center justify-center text-lg shadow-sm">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <div class="flex-1 min-w-0">
            <h4 class="font-bold text-red-800">Ngân sách bị từ chối</h4>
            <p class="text-sm mt-1 leading-relaxed"><strong>Lý do:</strong> {{ $marketingEvent->rejection_reason }}</p>
            <p class="text-xs mt-2 text-red-500 italic">Vui lòng kiểm tra lại thông tin, điều chỉnh và nhấn <strong>"Gửi duyệt lại"</strong> bên dưới.</p>
        </div>
    </div>
    @endif

    {{-- ── Header Card ────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {{-- Color accent bar --}}
        <div class="h-1 w-full
            @if($marketingEvent->status === 'approved') bg-gradient-to-r from-emerald-400 to-teal-500
            @elseif($marketingEvent->status === 'pending') bg-gradient-to-r from-amber-400 to-orange-500
            @elseif($marketingEvent->status === 'rejected') bg-gradient-to-r from-red-400 to-rose-500
            @else bg-gradient-to-r from-violet-400 to-purple-600
            @endif">
        </div>

        <div class="p-5 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-bold text-gray-800">
                        {{ $marketingEvent->title ?: 'Chương trình ' . $marketingEvent->code }}
                    </h1>
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold {{ $marketingEvent->status_color }}">
                        @if($marketingEvent->status === 'approved') <i class="fas fa-check-circle shrink-0"></i>
                        @elseif($marketingEvent->status === 'pending') <i class="fas fa-clock shrink-0"></i>
                        @elseif($marketingEvent->status === 'rejected') <i class="fas fa-times-circle shrink-0"></i>
                        @else <i class="fas fa-file-alt shrink-0"></i>
                        @endif
                        <span>{{ $marketingEvent->status_label }}</span>
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                        {{ $marketingEvent->code }}
                    </span>
                    @if($marketingEvent->is_public_to_sales)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <i class="fas fa-globe text-emerald-500"></i>
                            <span>Hãng lớn / Toàn công ty</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-50 text-slate-600 border border-slate-200">
                            <i class="fas fa-lock text-slate-400"></i>
                            <span>Chỉ định / Riêng tư</span>
                        </span>
                    @endif
                </div>
                <ul class="text-sm text-gray-400 mt-1.5 flex flex-wrap items-center">
                    <li class="inline-flex items-center gap-1.5 mr-4 mb-1">
                        <i class="fas fa-user-circle shrink-0 mr-1"></i>
                        <span>{{ $marketingEvent->creator->name }}</span>
                    </li>
                    <li class="inline-flex items-center gap-1.5 mr-4 mb-1">
                        <i class="fas fa-calendar shrink-0 mr-1"></i>
                        <span>{{ $marketingEvent->event_date->format('d/m/Y') }}</span>
                    </li>
                    @if($marketingEvent->location)
                    <li class="inline-flex items-center gap-1.5 mb-1">
                        <i class="fas fa-map-marker-alt shrink-0 mr-1"></i>
                        <span>{{ $marketingEvent->location }}</span>
                    </li>
                    @endif
                </ul>
            </div>

            {{-- Action buttons --}}
            <div class="w-full sm:w-auto sm:ml-auto flex items-center justify-end gap-2 flex-wrap sm:flex-nowrap sm:shrink-0">
                <a href="{{ route('marketing-events.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-100 text-gray-600 text-sm font-medium hover:bg-gray-200 transition-colors">
                    <i class="fas fa-arrow-left text-xs"></i> Quay lại
                </a>

                @if($marketingEvent->isEditable() || $marketingEvent->status === 'cancelled')
                    <a href="{{ route('marketing-events.edit', $marketingEvent) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-sm font-medium hover:bg-amber-100 transition-colors">
                        <i class="fas fa-pen text-xs"></i><span>Chỉnh sửa</span>
                    </a>

                    <form action="{{ route('marketing-events.destroy', $marketingEvent) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            onclick="return confirm('Bạn có chắc chắn muốn xóa sự kiện này? Hành động này không thể hoàn tác.')"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm font-medium hover:bg-red-100 transition-colors">
                            <i class="fas fa-trash text-xs"></i><span>Xóa</span>
                        </button>
                    </form>

                    <form action="{{ route('marketing-events.submit-approval', $marketingEvent) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-purple-600 text-white text-sm font-medium hover:bg-purple-700 shadow-sm transition-colors">
                            <i class="fas fa-paper-plane text-xs"></i>
                            {{ $marketingEvent->status === 'rejected' ? 'Gửi duyệt lại' : 'Gửi duyệt ngân sách' }}
                        </button>
                    </form>
                @endif

                @if($marketingEvent->status === 'approved')
                    @if($isMarketingOrBOD)
                    <button type="button" @click="showCompleteModal = true"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-teal-600 text-white text-sm font-semibold hover:bg-teal-700 shadow-sm transition-colors">
                        <i class="fas fa-check-double text-xs"></i><span>Hoàn thành & Nghiệm thu</span>
                    </button>
                    @endif
                @elseif($marketingEvent->status === 'completed')
                    <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-teal-50 border border-teal-200 text-teal-700 text-xs font-bold">
                        <i class="fas fa-flag-checkered text-teal-600"></i> Đã hoàn tất & Nghiệm thu
                    </span>
                    @if($isMarketingOrBOD)
                    <button type="button" @click="showCompleteModal = true"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold transition-colors">
                        <i class="fas fa-edit text-xs"></i><span>Điều chỉnh quyết toán</span>
                    </button>
                    @endif
                @endif

                @if($marketingEvent->status === 'pending' && $canApprove)
                    <form action="{{ route('marketing-events.approve', $marketingEvent) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            onclick="return confirm('Duyệt ngân sách sự kiện này?')"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 shadow-sm transition-colors">
                            <i class="fas fa-check text-xs"></i> Duyệt
                        </button>
                    </form>
                    <button @click="showReject = !showReject"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm font-medium hover:bg-red-100 transition-colors">
                        <i class="fas fa-times text-xs"></i> Từ chối
                    </button>
                @elseif($marketingEvent->status === 'pending' && !$canApprove)
                    <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-600 text-xs">
                        <i class="fas fa-hourglass-half"></i>
                        Chờ duyệt bởi: <strong class="ml-1">{{ $mktNextLevel?->approver_label ?? '—' }}</strong>
                    </span>
                @endif
            </div>
        </div>

        {{-- Reject form (slide down) --}}
        <div x-show="showReject" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="border-t border-red-100 bg-red-50 px-5 py-4">
            <form action="{{ route('marketing-events.reject', $marketingEvent) }}" method="POST" class="flex gap-3 items-end">
                @csrf
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-red-700 mb-1"><i class="fas fa-exclamation-triangle mr-1"></i>Lý do từ chối (bắt buộc)</label>
                    <textarea name="comment" rows="2" required placeholder="Nhập lý do từ chối..."
                        class="w-full border border-red-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 bg-white"></textarea>
                </div>
                <div class="flex gap-2 pb-0.5">
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                        Xác nhận từ chối
                    </button>
                    <button type="button" @click="showReject = false" class="px-4 py-2 bg-white border border-gray-300 text-gray-600 text-sm rounded-lg hover:bg-gray-50">
                        Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── 360-degree Dashboard Tabs ────────────────────── --}}
    <div x-data="{ 
        activeTab: 'plan', 
        currentRequest: null, 
        showRequestDetailModal: false,
        allUsers: {{ json_encode($users) }},
        getAvailableAssignees(team) {
            if (!team) return this.allUsers;
            const t = team.toLowerCase();
            if (t === 'technical' || t === 'tech') {
                return this.allUsers.filter(u => {
                    const dept = (u.department || '').toLowerCase();
                    const pos = (u.position || '').toLowerCase();
                    return dept.includes('tech') || dept.includes('it') || pos.includes('tech') || pos.includes('engineer');
                });
            }
            if (t === 'sales') {
                return this.allUsers.filter(u => {
                    const dept = (u.department || '').toLowerCase();
                    const pos = (u.position || '').toLowerCase();
                    return dept.includes('sale') || pos.includes('sale') || pos.includes('am');
                });
            }
            if (t === 'accounting' || t === 'finance' || t === 'accountant') {
                return this.allUsers.filter(u => {
                    const dept = (u.department || '').toLowerCase();
                    return dept.includes('finance') || dept.includes('account') || dept.includes('kế toán') || dept.includes('accounting');
                });
            }
            return this.allUsers;
        }
    }" class="space-y-4">
        
        {{-- Navigation Tabs --}}
        <div class="flex border-b border-gray-200 bg-white rounded-2xl p-4 gap-2 overflow-x-auto shadow-sm">
            <button @click="activeTab = 'plan'" :class="activeTab === 'plan' ? 'bg-violet-50 text-violet-700 border border-violet-100 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'" class="px-4 py-2 rounded-xl text-sm font-bold transition-all whitespace-nowrap">
                <i class="fas fa-file-alt mr-2 text-violet-500"></i>Kế hoạch & Chủ trương
            </button>
            @if($isMarketingOrBOD)
            <button @click="activeTab = 'budget'" :class="activeTab === 'budget' ? 'bg-violet-50 text-violet-700 border border-violet-100 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'" class="px-4 py-2 rounded-xl text-sm font-bold transition-all whitespace-nowrap">
                <i class="fas fa-coins mr-2 text-emerald-500"></i>Ngân sách & Thanh toán
            </button>
            @endif
            <button @click="activeTab = 'requests'" :class="activeTab === 'requests' ? 'bg-violet-50 text-violet-700 border border-violet-100 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'" class="px-4 py-2 rounded-xl text-sm font-bold transition-all whitespace-nowrap">
                <i class="fas fa-tasks mr-2 text-blue-500"></i>Yêu cầu & Đầu việc
            </button>

        </div>

        {{-- ── TAB 1: KẾ HOẠCH & CHỦ TRƯƠNG ── --}}
        <div x-show="activeTab === 'plan'" class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            {{-- Thông tin hoạt động --}}
            <div class="lg:col-span-2 space-y-5">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100 pb-3"><i class="fas fa-info-circle mr-2 text-violet-500"></i>Chi tiết hoạt động</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-400">Phạm vi hoạt động:</span>
                            <span class="font-bold text-gray-700 uppercase block">
                                {{ $marketingEvent->scope === 'internal' ? 'Internal (Nội bộ)' : 'External (Đối ngoại)' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-400">Loại hình tổ chức:</span>
                            <span class="font-bold text-gray-700 block notranslate" translate="no">
                                {{ $marketingEvent->organize_type_label }}
                            </span>
                        </div>

                        @if($marketingEvent->scope === 'internal')
                            {{-- Thông tin đặc thù sự kiện nội bộ --}}
                            <div>
                                <span class="text-gray-400">Phòng ban phụ trách:</span>
                                <span class="font-bold text-violet-700 block">{{ $marketingEvent->internal_department ?: 'Chưa chỉ định' }}</span>
                            </div>
                            <div class="md:col-span-2">
                                <span class="text-gray-400">Mục đích tổ chức nội bộ:</span>
                                <span class="font-bold text-gray-800 block mt-0.5">{{ $marketingEvent->internal_purpose ?: 'Không có' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Thời gian tổ chức:</span>
                                <span class="font-bold text-gray-700 block">
                                    {{ $marketingEvent->event_date->format('d/m/Y') }}
                                    @if($marketingEvent->start_time)
                                        ({{ date('H:i', strtotime($marketingEvent->start_time)) }} - {{ date('H:i', strtotime($marketingEvent->end_time)) }})
                                    @endif
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-400">Địa điểm:</span>
                                <span class="font-bold text-gray-700 block">{{ $marketingEvent->location ?? 'N/A' }}</span>
                            </div>
                            <div class="md:col-span-2">
                                <span class="text-gray-400">Quy mô CBNV tham gia:</span>
                                <span class="font-bold text-gray-700 block">
                                    {{ $marketingEvent->target_audience_count }} nhân sự / CBNV
                                    @if($marketingEvent->target_audience_note)
                                        ({{ $marketingEvent->target_audience_note }})
                                    @endif
                                </span>
                            </div>
                        @else
                            {{-- Sự kiện Đối ngoại --}}
                            <div class="md:col-span-2">
                                <span class="text-gray-400">Vendor / Các Hãng phối hợp:</span>
                                <div class="mt-1 flex flex-wrap gap-1.5">
                                    @if($marketingEvent->suppliers && $marketingEvent->suppliers->count() > 0)
                                        @foreach($marketingEvent->suppliers as $sup)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-violet-50 text-violet-700 border border-violet-200">
                                                <i class="fas fa-building text-[10px]"></i> {{ $sup->name }}
                                            </span>
                                        @endforeach
                                    @elseif($marketingEvent->vendor)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-violet-50 text-violet-700 border border-violet-200">
                                            <i class="fas fa-building text-[10px]"></i> {{ $marketingEvent->vendor->name }}
                                        </span>
                                    @else
                                        <span class="font-bold text-gray-400">Chưa chỉ định</span>
                                    @endif
                                </div>
                            </div>
                            <div>
                                <span class="text-gray-400">Ghi chú hãng khác:</span>
                                <span class="font-bold text-gray-700 block">{{ $marketingEvent->vendor_other_note ?? 'Không' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Có phối hợp Partner?</span>
                                <span class="font-bold text-gray-700 block">
                                    @if($marketingEvent->partner_cooperation === 'yes') Có
                                    @elseif($marketingEvent->partner_cooperation === 'other') Khác / Chưa chốt
                                    @else Không @endif
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-400">Chi tiết Partner & PIC:</span>
                                <span class="font-bold text-gray-700 block">{{ $marketingEvent->partner_info ?? '—' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Thời gian tổ chức:</span>
                                <span class="font-bold text-gray-700 block">
                                    {{ $marketingEvent->event_date->format('d/m/Y') }}
                                    @if($marketingEvent->start_time)
                                        ({{ date('H:i', strtotime($marketingEvent->start_time)) }} - {{ date('H:i', strtotime($marketingEvent->end_time)) }})
                                    @endif
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-400">Địa điểm:</span>
                                <span class="font-bold text-gray-700 block">{{ $marketingEvent->location ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Đối tượng & Số lượng khách:</span>
                                <span class="font-bold text-gray-700 block">{{ $marketingEvent->target_audience_count }} khách ({{ $marketingEvent->target_audience_note ?? 'Chưa note' }})</span>
                            </div>
                        @endif
                    </div>
                </div>

                @if($marketingEvent->description || $marketingEvent->special_notes)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100 pb-3"><i class="fas fa-clipboard-list mr-2 text-violet-500"></i>Mô tả & Điều kiện rủi ro</h3>
                    @if($marketingEvent->description)
                    <div>
                        <span class="text-xs text-gray-400 uppercase font-semibold">Mục tiêu & Kế hoạch sơ bộ</span>
                        <p class="text-sm text-gray-700 leading-relaxed mt-1">{{ $marketingEvent->description }}</p>
                    </div>
                    @endif
                    @if($marketingEvent->special_notes)
                    <div class="pt-3 border-t border-gray-100">
                        <span class="text-xs text-gray-400 uppercase font-semibold">Các điều kiện đặc biệt / Rủi ro xin ý kiến BOD</span>
                        <p class="text-sm text-gray-700 leading-relaxed mt-1">{{ $marketingEvent->special_notes }}</p>
                    </div>
                    @endif
                </div>
                @endif
            </div>

            {{-- Phê duyệt & Hồ sơ đính kèm --}}
            <div class="space-y-5">
                {{-- Lịch sử duyệt ngân sách --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-4 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <i class="fas fa-history text-blue-500"></i> Tiến trình phê duyệt
                    </h3>
                    @if($approvalHistory->isEmpty())
                        <div class="text-center py-6 text-gray-300">
                            <i class="fas fa-clipboard-list text-3xl mb-2 block"></i>
                            <p class="text-sm">Chưa có lịch sử duyệt.</p>
                        </div>
                    @else
                    <div class="space-y-3">
                        @foreach($approvalHistory as $h)
                        <div class="flex items-start gap-3 p-3 rounded-xl
                            @if($h->action === 'approved') bg-emerald-50 border border-emerald-100
                            @elseif($h->action === 'rejected') bg-red-50 border border-red-100
                            @elseif($h->action === 'pending') bg-amber-50 border border-amber-100
                            @else bg-gray-50 border border-gray-100 @endif">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm
                                @if($h->action === 'approved') bg-emerald-500 text-white
                                @elseif($h->action === 'rejected') bg-red-500 text-white
                                @elseif($h->action === 'pending') bg-amber-400 text-white
                                @else bg-gray-300 text-gray-600 @endif">
                                @if($h->action === 'approved') <i class="fas fa-check"></i>
                                @elseif($h->action === 'rejected') <i class="fas fa-times"></i>
                                @elseif($h->action === 'pending') <i class="fas fa-clock"></i>
                                @else <i class="fas fa-forward"></i>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between flex-wrap gap-1">
                                    <span class="text-xs font-bold text-gray-800">Cấp {{ $h->level }}: {{ $h->level_name }}</span>
                                    @if($h->action_at)
                                    <span class="text-[10px] text-gray-400">{{ $h->action_at->format('d/m/Y H:i') }}</span>
                                    @else
                                    <span class="text-[10px] text-amber-500 font-semibold animate-pulse">Chờ duyệt...</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-gray-500 mt-0.5">{{ $h->approver_name }}</div>
                                @if($h->comment)
                                <div class="text-[11px] text-gray-600 mt-1.5 italic bg-white/75 rounded px-2 py-1 border border-gray-100">"{{ $h->comment }}"</div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- Hồ sơ tài liệu --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-4 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <i class="fas fa-folder-open text-amber-500"></i> Hồ sơ tài liệu đính kèm
                    </h3>
                    @if(empty($marketingEvent->attachments))
                        <p class="text-xs text-gray-400 italic text-center py-4">Chưa có tài liệu đính kèm nào được tải lên.</p>
                    @else
                        <div class="space-y-2">
                            @foreach([
                                'cost_estimation_file' => ['Bảng dự toán chi phí', 'fa-file-excel', 'text-emerald-500'],
                                'event_plan_file'      => ['Kế hoạch tổ chức (Proposal)', 'fa-file-word', 'text-blue-500'],
                                'quotation_file'       => ['Báo giá nhà cung cấp', 'fa-file-pdf', 'text-red-500'],
                                'agenda_file'          => ['Agenda chương trình', 'fa-file-alt', 'text-gray-500'],
                                'guest_list_file'      => ['Danh sách khách mời dự kiến', 'fa-users', 'text-purple-500'],
                            ] as $key => [$label, $icon, $color])
                                @if(isset($marketingEvent->attachments[$key]))
                                <a href="{{ $marketingEvent->attachments[$key]['url'] }}" target="_blank"
                                    class="flex items-center justify-between p-2.5 rounded-xl border border-gray-100 bg-gray-50 hover:bg-violet-50/50 hover:border-violet-100 transition-colors group">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <i class="fas {{ $icon }} {{ $color }} text-lg"></i>
                                        <div class="min-w-0">
                                            <p class="text-xs font-bold text-gray-700 truncate">{{ $label }}</p>
                                            <p class="text-[10px] text-gray-400 truncate">{{ $marketingEvent->attachments[$key]['name'] }}</p>
                                        </div>
                                    </div>
                                    <i class="fas fa-download text-xs text-gray-300 group-hover:text-violet-600 mr-1"></i>
                                </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── TAB 2: NGÂN SÁCH & THANH TOÁN (BẢO MẬT) ── --}}
        @if($isMarketingOrBOD)
        <div x-show="activeTab === 'budget'" class="space-y-5">
            {{-- Ngân sách Card --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 flex-wrap gap-2">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide flex items-center gap-2">
                        <i class="fas fa-coins text-yellow-500"></i> Quản lý Ngân sách & Quyết toán chương trình
                    </h3>
                    @if($isMarketingOrBOD)
                    <button type="button" @click="showCompleteModal = true"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-1.5
                            {{ $marketingEvent->isCompleted() ? 'bg-gray-100 hover:bg-gray-200 text-gray-700' : 'bg-emerald-600 hover:bg-emerald-700 text-white' }}">
                        <i class="fas fa-check-circle"></i>
                        <span>{{ $marketingEvent->isCompleted() ? 'Cập nhật lại Quyết toán' : 'Nghiệm thu Hoàn thành Sự kiện' }}</span>
                    </button>
                    @endif
                </div>

                {{-- 3 Thẻ chỉ số --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="relative overflow-hidden bg-gradient-to-br from-violet-50 to-purple-100 rounded-2xl p-5 text-center border border-violet-100">
                        <div class="text-xs text-violet-500 font-bold uppercase tracking-wider mb-1">Dự toán kế hoạch đã duyệt</div>
                        <div class="text-3xl font-black text-violet-700">{{ number_format($marketingEvent->budget) }}</div>
                        <div class="text-xs text-violet-400 mt-1">VND</div>
                    </div>
                    
                    <div class="relative overflow-hidden bg-gradient-to-br from-blue-50 to-indigo-100 rounded-2xl p-5 text-center border border-blue-100">
                        <div class="text-xs text-blue-500 font-bold uppercase tracking-wider mb-1">
                            {{ $marketingEvent->isCompleted() ? 'Chi phí thực tế (Đã nghiệm thu)' : 'Chi phí thực tế (Đã chi qua ticket)' }}
                        </div>
                        <div class="text-3xl font-black text-blue-700">
                            {{ number_format($marketingEvent->isCompleted() ? ($marketingEvent->actual_cost ?? 0) : $actualPaymentsCost) }}
                        </div>
                        <div class="text-xs text-blue-400 mt-1">VND</div>
                    </div>

                    @if($marketingEvent->isCompleted())
                        @php $var = $marketingEvent->variance_amount ?? (($marketingEvent->actual_cost ?? 0) - ($marketingEvent->budget ?? 0)); @endphp
                        <div class="relative overflow-hidden rounded-2xl p-5 text-center border {{ $var > 0 ? 'bg-gradient-to-br from-rose-50 to-red-100 border-rose-200' : ($var < 0 ? 'bg-gradient-to-br from-emerald-50 to-teal-100 border-emerald-200' : 'bg-gradient-to-br from-gray-50 to-slate-100 border-gray-200') }}">
                            <div class="text-xs font-bold uppercase tracking-wider mb-1 {{ $var > 0 ? 'text-rose-600' : ($var < 0 ? 'text-emerald-600' : 'text-gray-600') }}">
                                {{ $var > 0 ? 'Chênh lệch Vượt chi (Thiếu hụt)' : ($var < 0 ? 'Chênh lệch Tiết kiệm (Dư ngân sách)' : 'Chênh lệch Quyết toán') }}
                            </div>
                            <div class="text-3xl font-black {{ $var > 0 ? 'text-rose-700' : ($var < 0 ? 'text-emerald-700' : 'text-gray-700') }}">
                                {{ ($var > 0 ? '+' : '') . number_format($var) }}
                            </div>
                            <div class="text-xs mt-1 {{ $var > 0 ? 'text-rose-400' : ($var < 0 ? 'text-emerald-400' : 'text-gray-400') }}">VND</div>
                        </div>
                    @else
                        @php $remainingBudget = $marketingEvent->budget - $actualPaymentsCost; @endphp
                        <div class="relative overflow-hidden bg-gradient-to-br from-emerald-50 to-teal-100 rounded-2xl p-5 text-center border border-emerald-100">
                            <div class="text-xs text-emerald-500 font-bold uppercase tracking-wider mb-1">Ngân sách còn lại (Ước tính)</div>
                            <div class="text-3xl font-black {{ $remainingBudget >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format($remainingBudget) }}</div>
                            <div class="text-xs text-emerald-400 mt-1">VND</div>
                        </div>
                    @endif
                </div>

                {{-- BẢNG PHÂN BỔ KẾ HOẠCH NGUỒN TIỀN (HÃNG, CÔNG ĐOÀN, CÔNG TY) --}}
                <div class="pt-4 border-t border-gray-100">
                    <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <i class="fas fa-hand-holding-usd text-violet-600"></i> Cơ cấu Nguồn vốn tài trợ (Dự kiến)
                    </h4>
                    @if(!empty($marketingEvent->funding_sources) && is_array($marketingEvent->funding_sources))
                        <div class="border border-gray-200 rounded-xl overflow-hidden">
                            <table class="w-full text-xs">
                                <thead class="bg-gray-50 text-gray-500 font-bold uppercase">
                                    <tr>
                                        <th class="p-2.5 text-left">Nguồn kinh phí</th>
                                        <th class="p-2.5 text-center">Phân loại</th>
                                        <th class="p-2.5 text-right">Dự toán cam kết</th>
                                        <th class="p-2.5 text-right">Tỷ trọng (%)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @php $totPlan = max(1, $marketingEvent->budget ?? 0); @endphp
                                    @foreach($marketingEvent->funding_sources as $f)
                                        @php
                                            $pAmt = (float)($f['planned_amount'] ?? 0);
                                            $pct = round(($pAmt / $totPlan) * 100, 1);
                                        @endphp
                                        <tr class="hover:bg-gray-50/50">
                                            <td class="p-2.5 font-bold text-gray-800">
                                                <i class="fas {{ ($f['type'] ?? '') === 'brand' ? 'fa-building text-violet-500' : (($f['type'] ?? '') === 'union' ? 'fa-users-cog text-amber-500' : 'fa-landmark text-blue-500') }} mr-1.5"></i>
                                                {{ $f['name'] ?? 'Nguồn kinh phí' }}
                                            </td>
                                            <td class="p-2.5 text-center">
                                                <span class="px-2 py-0.5 rounded-full font-semibold {{ ($f['type'] ?? '') === 'brand' ? 'bg-violet-50 text-violet-700' : (($f['type'] ?? '') === 'union' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700') }}">
                                                    {{ ($f['type'] ?? '') === 'brand' ? 'Hãng tài trợ' : (($f['type'] ?? '') === 'union' ? 'Quỹ Công đoàn' : 'Ngân sách Công ty') }}
                                                </span>
                                            </td>
                                            <td class="p-2.5 text-right font-black text-gray-800">{{ number_format($pAmt) }} đ</td>
                                            <td class="p-2.5 text-right font-bold text-gray-500">{{ $pct }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-3 bg-gray-50 rounded-xl text-xs text-gray-600 flex items-center justify-between">
                            <span>Nguồn tiền tài trợ chính: <strong>{{ $marketingEvent->funding_source ?: ($marketingEvent->vendor?->name ?? 'Công ty / Hãng') }}</strong></span>
                            <span>Dự toán: <strong class="text-violet-700">{{ number_format($marketingEvent->budget) }} đ</strong></span>
                        </div>
                    @endif
                </div>

                {{-- BẢNG NGHIỆM THU QUYẾT TOÁN THỰC TẾ (NẾU ĐÃ HOÀN TẤT) --}}
                @if($marketingEvent->isCompleted())
                <div class="pt-4 border-t border-emerald-100 bg-emerald-50/30 rounded-xl p-4 border border-emerald-100">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-xs font-bold text-emerald-900 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fas fa-balance-scale text-emerald-600"></i> Bảng Đối chiếu Quyết toán Thực tế từng Hãng & Nguồn tiền
                        </h4>
                        <span class="text-2xs font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                            Đã nghiệm thu lúc {{ $marketingEvent->completed_at ? $marketingEvent->completed_at->format('d/m/Y H:i') : '' }}
                        </span>
                    </div>

                    @if(!empty($marketingEvent->actual_funding_sources) && is_array($marketingEvent->actual_funding_sources))
                    <div class="border border-emerald-200 rounded-xl overflow-hidden bg-white mb-3">
                        <table class="w-full text-xs">
                            <thead class="bg-emerald-50 text-emerald-800 font-bold uppercase">
                                <tr>
                                    <th class="p-2.5 text-left">Nguồn tài trợ / Hãng</th>
                                    <th class="p-2.5 text-right">Dự toán cam kết</th>
                                    <th class="p-2.5 text-right">Thực tế chi trả / tài trợ</th>
                                    <th class="p-2.5 text-right">Chênh lệch</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-emerald-50">
                                @foreach($marketingEvent->actual_funding_sources as $af)
                                    @php
                                        $plan = (float)($af['planned_amount'] ?? 0);
                                        $act  = (float)($af['actual_amount'] ?? 0);
                                        $diff = $act - $plan;
                                    @endphp
                                    <tr class="hover:bg-emerald-50/30">
                                        <td class="p-2.5 font-bold text-gray-800">{{ $af['name'] ?? 'Nguồn' }}</td>
                                        <td class="p-2.5 text-right text-gray-500">{{ number_format($plan) }} đ</td>
                                        <td class="p-2.5 text-right font-black text-emerald-700">{{ number_format($act) }} đ</td>
                                        <td class="p-2.5 text-right font-bold {{ $diff > 0 ? 'text-emerald-600' : ($diff < 0 ? 'text-red-600' : 'text-gray-500') }}">
                                            {{ ($diff > 0 ? '+' : '') . number_format($diff) }} đ
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs bg-white p-3 rounded-xl border border-emerald-100">
                        <div>
                            <span class="text-gray-500 block">Nguồn xử lý con số chênh lệch / vượt dự toán:</span>
                            <span class="font-bold text-emerald-900 text-sm mt-0.5 block">
                                <i class="fas fa-check-circle text-emerald-500 mr-1"></i>
                                {{ $marketingEvent->variance_funding_source ?: 'Ngân sách công ty bù thêm' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500 block">Người xác nhận nghiệm thu:</span>
                            <span class="font-bold text-gray-800 text-sm mt-0.5 block">
                                {{ $marketingEvent->completer?->name ?? 'Hệ thống' }}
                            </span>
                        </div>
                        @if($marketingEvent->completion_note)
                        <div class="md:col-span-2 pt-2 border-t border-gray-100">
                            <span class="text-gray-500 block">Ghi chú tổng kết / Nghiệm thu:</span>
                            <p class="text-gray-700 italic mt-0.5">{{ $marketingEvent->completion_note }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            {{-- Lịch sử Yêu cầu thanh toán con --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-4 flex items-center gap-2 border-b border-gray-100 pb-3">
                    <i class="fas fa-file-invoice-dollar text-emerald-500"></i> Các khoản thanh toán / tạm ứng liên kết
                </h3>
                @php
                    $paymentRequests = $marketingEvent->requests()
                        ->whereHas('ticket', fn($q) => $q->where('type', 'payment'))
                        ->latest()
                        ->get();
                @endphp
                @if($paymentRequests->isEmpty())
                    <p class="text-xs text-gray-400 italic text-center py-6">Chưa có yêu cầu thanh toán nào được tạo.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-400 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3">Mã Request</th>
                                    <th class="px-4 py-3">Nội dung</th>
                                    <th class="px-4 py-3">Số tiền (VND)</th>
                                    <th class="px-4 py-3">Nguồn tiền</th>
                                    <th class="px-4 py-3 text-center">Trạng thái</th>
                                    <th class="px-4 py-3 text-center">Ngày tạo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($paymentRequests as $req)
                                <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                                    <td class="px-4 py-3 font-bold text-gray-700">{{ $req->code }}</td>
                                    <td class="px-4 py-3 font-semibold text-gray-800">{{ $req->description }}</td>
                                    <td class="px-4 py-3 font-bold text-emerald-600">{{ number_format($req->amount) }}</td>
                                    <td class="px-4 py-3">{{ $req->funding_source ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-block px-2.5 py-0.5 text-xs font-bold rounded-full {{ $req->status_color }}">
                                            {{ $req->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">{{ $req->created_at->format('d/m/Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
        @endif

        {{-- ── TAB 3: YÊU CẦU & ĐẦU VIỆC (STEP 2 & STEP 3) ── --}}
        <div x-show="activeTab === 'requests'" class="space-y-5">
            {{-- Form tạo Ticket (nếu được phép) --}}
            <div x-data="{ showCreateTicket: {{ ($errors->any() || session('error')) ? 'true' : 'false' }}, ticketType: '{{ old('type', 'internal_collaboration') }}' }" class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                <div class="flex justify-between items-center">
                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wide"><i class="fas fa-ticket-alt mr-2 text-violet-500"></i>Đầu việc & Yêu cầu hỗ trợ liên phòng ban</h4>
                    @if($marketingEvent->status === 'approved')
                        @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('marketing'))
                        <button @click="showCreateTicket = !showCreateTicket" class="px-4 py-1.5 bg-purple-600 text-white rounded-xl text-xs font-bold hover:bg-purple-700 shadow-sm transition-colors">
                            <i class="fas fa-plus mr-1"></i> Tạo Ticket hỗ trợ
                        </button>
                        @endif
                    @else
                    <span class="text-xs text-amber-600 font-bold bg-amber-50 border border-amber-100 px-3 py-1 rounded-xl"><i class="fas fa-exclamation-triangle mr-1"></i>Chờ BOD phê duyệt ngân sách để mở các ticket</span>
                    @endif
                </div>

                {{-- FORM TOGGLE SLIDE DOWN --}}
                <div x-show="showCreateTicket" x-transition class="bg-gray-50 rounded-xl p-4 mt-4 border border-gray-200">
                    <form action="{{ route('marketing-events.tickets.store', $marketingEvent) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Loại Ticket cần tạo</label>
                            <select name="type" x-model="ticketType" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm focus:ring-2 focus:ring-purple-400">
                                <option value="internal_collaboration">1. Ticket Internal Collaboration (Phối hợp nội bộ)</option>
                                <option value="business_trip">2. Ticket Business Trip (Đi công tác tỉnh ngoài)</option>
                                <option value="payment">3. Ticket Payment (Thanh toán / Tạm ứng)</option>
                                <option value="others">4. Ticket Others (Khác / Giao việc trực tiếp)</option>
                            </select>
                        </div>

                        {{-- TYPE 1: Collaboration --}}
                        <div x-show="ticketType === 'internal_collaboration'" class="space-y-3" x-data="{ 
                            rows: [{support_team: 'technical', support_team_other: '', pic_type: 'lead', support_content: 'speaker', support_content_other: '', description: '', deadline: ''}],
                            getPics(team) {
                                if (team === 'technical') {
                                    return [{val: 'lead', label: 'Lead Team'}, {val: 'all', label: 'All Members'}];
                                } else if (team === 'sales') {
                                    return [{val: 'assistant', label: 'Sales Director Assistant'}, {val: 'all', label: 'All Members'}];
                                } else {
                                    return [{val: 'lead', label: 'Lead Team / Trưởng bộ phận'}, {val: 'all', label: 'All Members / Thành viên'}];
                                }
                            },
                            getContents(team) {
                                if (team === 'technical') {
                                    return [{val: 'speaker', label: 'Speaker'}, {val: 'technical_support', label: 'Hỗ trợ kỹ thuật'}, {val: 'others', label: 'Others: Điền nội dung'}];
                                } else if (team === 'sales') {
                                    return [{val: 'customer_list', label: 'Danh sách khách hàng'}, {val: 'invite_customers', label: 'Mời khách hàng'}, {val: 'others', label: 'Others: Điền nội dung'}];
                                } else {
                                    return [{val: 'others', label: 'Others: Điền nội dung'}];
                                }
                            }
                        }">
                            <template x-for="(row, idx) in rows" :key="idx">
                                <div class="bg-white rounded-xl p-4 border border-gray-200 space-y-3 relative">
                                    <button type="button" @click="if(rows.length > 1) rows.splice(idx, 1)" class="absolute top-2 right-2 text-gray-400 hover:text-red-500">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <h5 class="text-xs font-bold text-purple-600">Yêu cầu phối hợp con <span x-text="idx + 1"></span></h5>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-500 uppercase">Team hỗ trợ</label>
                                            <select :name="'requests['+idx+'][support_team]'" x-model="row.support_team" 
                                                @change="
                                                    if(row.support_team === 'technical') {
                                                        row.pic_type = 'lead';
                                                        row.support_content = 'speaker';
                                                    } else if(row.support_team === 'sales') {
                                                        row.pic_type = 'assistant';
                                                        row.support_content = 'customer_list';
                                                    } else {
                                                        row.pic_type = 'lead';
                                                        row.support_content = 'others';
                                                    }
                                                "
                                                class="w-full border border-gray-300 rounded-lg px-2 py-1 bg-white text-xs">
                                                <option value="technical">Technical Team</option>
                                                <option value="sales">Sales Team</option>
                                                <option value="other">Team khác (Nhập tay)</option>
                                            </select>
                                        </div>
                                        
                                        <div x-show="row.support_team === 'other'">
                                            <label class="block text-[10px] font-bold text-gray-500 uppercase">Tên Team khác (*)</label>
                                            <input type="text" :name="'requests['+idx+'][support_team_other]'" x-model="row.support_team_other" 
                                                class="w-full border border-gray-300 rounded-lg px-2 py-1 text-xs" placeholder="Nhập tên bộ phận hỗ trợ...">
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-500 uppercase">Người tiếp nhận (P.I.C Group)</label>
                                            <select :name="'requests['+idx+'][pic_type]'" x-model="row.pic_type" class="w-full border border-gray-300 rounded-lg px-2 py-1 bg-white text-xs">
                                                <template x-for="p in getPics(row.support_team)">
                                                    <option :value="p.val" x-text="p.label" :selected="row.pic_type === p.val"></option>
                                                </template>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-500 uppercase">Nội dung hỗ trợ</label>
                                            <select :name="'requests['+idx+'][support_content]'" x-model="row.support_content" class="w-full border border-gray-300 rounded-lg px-2 py-1 bg-white text-xs">
                                                <template x-for="c in getContents(row.support_team)">
                                                    <option :value="c.val" x-text="c.label" :selected="row.support_content === c.val"></option>
                                                </template>
                                            </select>
                                        </div>
                                        
                                        <div x-show="row.support_content === 'others'">
                                            <label class="block text-[10px] font-bold text-gray-500 uppercase">Điền nội dung khác</label>
                                            <input type="text" :name="'requests['+idx+'][support_content_other]'" x-model="row.support_content_other" class="w-full border border-gray-300 rounded-lg px-2 py-1 text-xs" placeholder="VD: Gửi thư mời, chụp hình...">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-500 uppercase">Hạn hoàn thành (Deadline)</label>
                                            <input type="datetime-local" :name="'requests['+idx+'][deadline]'" x-model="row.deadline" class="w-full border border-gray-300 rounded-lg px-2 py-1 text-xs">
                                        </div>
                                        
                                        <div class="md:col-span-2">
                                            <label class="block text-[10px] font-bold text-gray-500 uppercase">Ghi chi tiết yêu cầu / Kịch bản / Tiêu chí</label>
                                            <textarea :name="'requests['+idx+'][description]'" x-model="row.description" rows="2" class="w-full border border-gray-300 rounded-lg px-2 py-1 text-xs" placeholder="Mô tả công việc chi tiết, kịch bản mời khách, tiêu chí chọn lọc danh sách..."></textarea>
                                        </div>
                                        
                                        <div class="md:col-span-2">
                                            <label class="block text-[10px] font-bold text-gray-500 uppercase">Đính kèm file liên quan (nếu có)</label>
                                            <input type="file" :name="'request_files_'+idx+'[]'" multiple class="w-full text-xs text-gray-500 file:mr-3 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-violet-50 file:text-violet-700">
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <button type="button" @click="rows.push({support_team: 'technical', support_team_other: '', pic_type: 'lead', support_content: 'speaker', support_content_other: '', description: '', deadline: ''})" class="w-full py-1.5 border border-dashed border-purple-300 text-purple-600 rounded-lg text-xs font-semibold hover:bg-purple-50">
                                <i class="fas fa-plus mr-1"></i> Thêm yêu cầu con
                            </button>
                        </div>

                        {{-- TYPE 2: Business Trip --}}
                        <div x-show="ticketType === 'business_trip'" class="bg-white rounded-xl p-4 border border-gray-200 space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Ngày xuất phát <span class="text-red-500">*</span></label>
                                    <input type="date" name="departure_date" value="{{ old('departure_date') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Số lượng nhân sự tham gia <span class="text-red-500">*</span></label>
                                    <input type="number" name="personnel_count" min="1" value="{{ old('personnel_count') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400" placeholder="VD: 3">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Ghi chú ngày đi (nếu nhiều đoàn khác nhau)</label>
                                    <textarea name="departure_date_note" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400" placeholder="VD: 2 nhân sự bay ngày 26/04, 1 nhân sự đi xe ngày 27/04...">{{ old('departure_date_note') }}</textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Chi phí dự toán công tác (VND) <span class="text-red-500">*</span></label>
                                    <input type="text" name="amount" value="{{ old('amount') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400" placeholder="Nhập số tiền">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Đính kèm vé máy bay / Booking <span class="text-gray-400 text-[11px] font-normal">(Tùy chọn)</span></label>
                                    <input type="file" name="trip_files[]" multiple class="w-full text-xs text-gray-500 mt-1 file:mr-3 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[10px] file:bg-violet-50 file:text-violet-700">
                                </div>
                            </div>
                        </div>

                        @php
                            $eventSources = is_array($marketingEvent->funding_sources) ? $marketingEvent->funding_sources : [];
                            $declaredFundsForPayment = ($supplierFunds ?? collect())->map(function($f) {
                                return [
                                    'id' => $f->id,
                                    'supplier_id' => $f->supplier_id,
                                    'supplier_name' => $f->supplier->name ?? 'Hãng',
                                    'name' => ($f->supplier->name ?? 'Hãng') . ' - ' . $f->name,
                                    'remaining_amount' => (float)$f->remaining_amount,
                                ];
                            })->values();

                            $initialPaymentAllocations = [];
                            if (!empty($eventSources)) {
                                foreach ($eventSources as $es) {
                                    $fId = $es['fund_id'] ?? '';
                                    $rem = 0;
                                    if ($fId) {
                                        $fObj = $declaredFundsForPayment->firstWhere('id', (int)$fId);
                                        $rem = $fObj ? $fObj['remaining_amount'] : 0;
                                    }
                                    $initialPaymentAllocations[] = [
                                        'fund_id'          => $fId ? (string)$fId : '',
                                        'source_type'      => $es['source_type'] ?? 'fund',
                                        'name'             => $es['name'] ?? '',
                                        'amount'           => (int)($es['planned_amount'] ?? 0),
                                        'remaining_amount' => $rem,
                                        'debt_checked'     => false,
                                    ];
                                }
                            }
                            if (empty($initialPaymentAllocations)) {
                                $initialPaymentAllocations[] = [
                                    'fund_id'          => '',
                                    'source_type'      => 'fund',
                                    'name'             => '',
                                    'amount'           => 0,
                                    'remaining_amount' => 0,
                                    'debt_checked'     => false,
                                ];
                            }
                        @endphp

                        {{-- TYPE 3: Payment --}}
                        <div x-show="ticketType === 'payment'" class="bg-white rounded-xl p-4 border border-gray-200 space-y-4" x-data="{
                            availableFunds: {{ json_encode($declaredFundsForPayment) }},
                            allocations: {{ json_encode($initialPaymentAllocations) }},
                            paymentAmount: '{{ old('amount', '') }}',
                            addAllocation() {
                                this.allocations.push({
                                    fund_id: '',
                                    source_type: 'fund',
                                    name: '',
                                    amount: 0,
                                    remaining_amount: 0,
                                    debt_checked: false
                                });
                                this.syncPaymentAmount();
                            },
                            removeAllocation(idx) {
                                if (this.allocations.length > 1) {
                                    this.allocations.splice(idx, 1);
                                    this.syncPaymentAmount();
                                }
                            },
                            onFundSelect(item, fundId) {
                                if (!fundId) {
                                    item.fund_id = '';
                                    item.remaining_amount = 0;
                                    return;
                                }
                                const fund = this.availableFunds.find(f => f.id == fundId);
                                if (fund) {
                                    item.fund_id = fund.id;
                                    item.name = fund.name;
                                    item.remaining_amount = fund.remaining_amount;
                                }
                            },
                            formatMoney(val) {
                                const num = parseFloat((val + '').replace(/[^\d.]/g, '')) || 0;
                                return new Intl.NumberFormat('en-US').format(Math.round(num));
                            },
                            syncPaymentAmount() {
                                let total = 0;
                                this.allocations.forEach(a => {
                                    total += parseFloat((a.amount + '').replace(/[^\d.]/g, '')) || 0;
                                });
                                if (total > 0) {
                                    this.paymentAmount = this.formatMoney(total);
                                }
                            },
                            getTotalAllocated() {
                                let total = 0;
                                this.allocations.forEach(a => {
                                    total += parseFloat((a.amount + '').replace(/[^\d.]/g, '')) || 0;
                                });
                                return total;
                            }
                        }">
                            <input type="hidden" name="funding_allocations" :value="JSON.stringify(allocations)">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nội dung thanh toán / Tạm ứng <span class="text-red-500">*</span></label>
                                    <textarea name="payment_content" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400" placeholder="Tóm tắt: Tạm ứng cọc tiệc khách sạn Rex, thanh toán hóa đơn in backdrop...">{{ old('payment_content') }}</textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Số tiền cần chi (VND) <span class="text-red-500">*</span></label>
                                    <input type="text" name="amount"
                                        x-model="paymentAmount"
                                        @input="paymentAmount = formatMoney($event.target.value)"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-purple-400"
                                        placeholder="Nhập hoặc để tự động tính từ bảng phân bổ quỹ">
                                    <p class="text-[11px] text-gray-400 mt-0.5">Tự động đồng bộ từ tổng số tiền phân bổ các nguồn bên dưới.</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Số tiền bằng chữ</label>
                                    <input type="text" name="amount_in_words" value="{{ old('amount_in_words') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400" placeholder="Bằng chữ (VD: Bốn trăm nghìn đồng)">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Mã request phối hợp liên kết (nếu có)</label>
                                    <input type="text" name="reference_request_code" value="{{ old('reference_request_code') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400" placeholder="VD: REQ-2026-0001">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Hóa đơn / Bảng tính đính kèm <span class="text-gray-400 text-[11px] font-normal">(Tùy chọn)</span></label>
                                    <input type="file" name="payment_files[]" multiple class="w-full text-xs text-gray-500 mt-1 file:mr-3 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[10px] file:bg-violet-50 file:text-violet-700">
                                </div>
                            </div>

                            {{-- PHÂN BỔ NGUỒN TIỀN / QUỸ HÃNG ĐÃ KHAI BÁO --}}
                            <div class="pt-3 border-t border-gray-100 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h5 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                            <i class="fas fa-coins text-emerald-600"></i> Phân bổ nguồn tiền chi trả (Đồng bộ nhiều quỹ & Cảnh báo số dư)
                                        </h5>
                                        <p class="text-[11px] text-gray-400">Chọn quỹ hãng đã khai báo, nhập số tiền tương ứng và nhận cảnh báo nếu vượt quá số dư trong quỹ.</p>
                                    </div>
                                    <button type="button" @click="addAllocation()" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-purple-50 text-purple-700 hover:bg-purple-100 transition-colors">
                                        <i class="fas fa-plus mr-1"></i> Thêm nguồn chi
                                    </button>
                                </div>

                                <div class="border border-gray-200 rounded-xl overflow-hidden">
                                    <table class="w-full text-xs text-left">
                                        <thead class="bg-gray-50 text-gray-500 font-bold uppercase text-[11px]">
                                            <tr>
                                                <th class="p-2.5 min-w-[220px]">Nguồn tiền / Quỹ Hãng</th>
                                                <th class="p-2.5 w-44 text-right">Số tiền chi (VND)</th>
                                                <th class="p-2.5 w-44">Ghi nhận công nợ hãng</th>
                                                <th class="p-2.5 w-10 text-center">Xóa</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <template x-for="(alloc, aIdx) in allocations" :key="aIdx">
                                                <tr class="hover:bg-gray-50/50" :class="alloc.fund_id && parseFloat((alloc.amount + '').replace(/[^\d.]/g, '')) > parseFloat(alloc.remaining_amount || 0) ? 'bg-red-50/30' : ''">
                                                    <td class="p-2.5 align-top space-y-1">
                                                        <select x-model="alloc.fund_id" @change="onFundSelect(alloc, $event.target.value)"
                                                            class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs bg-white focus:ring-1 focus:ring-purple-400">
                                                            <option value="">-- Chọn Quỹ Hãng đã khai báo (hoặc nguồn khác) --</option>
                                                            <template x-for="f in availableFunds" :key="f.id">
                                                                <option :value="f.id" :selected="alloc.fund_id == f.id" x-text="f.name + ' (Số dư: ' + formatMoney(f.remaining_amount) + ' đ)'"></option>
                                                            </template>
                                                        </select>
                                                        <div x-show="!alloc.fund_id">
                                                            <input type="text" x-model="alloc.name" placeholder="Gõ tên nguồn chi (VD: Quỹ Công đoàn, Ngân sách CT...)"
                                                                class="w-full border border-gray-300 rounded-lg px-2 py-1 text-xs focus:ring-1 focus:ring-purple-400">
                                                        </div>
                                                        <template x-if="alloc.fund_id">
                                                            <div class="flex items-center gap-1.5 text-[11px] text-gray-500">
                                                                <i class="fas fa-wallet text-emerald-600"></i>
                                                                <span>Số dư khả dụng:</span>
                                                                <span class="font-bold text-emerald-700" x-text="formatMoney(alloc.remaining_amount) + ' đ'"></span>
                                                            </div>
                                                        </template>
                                                    </td>
                                                    <td class="p-2.5 align-top text-right">
                                                        <input type="text"
                                                            :value="formatMoney(alloc.amount)"
                                                            @input="alloc.amount = $event.target.value.replace(/[^\d]/g, ''); syncPaymentAmount()"
                                                            class="w-full border rounded-lg px-2.5 py-1.5 text-xs text-right font-bold text-gray-800 focus:ring-1 focus:ring-purple-400"
                                                            :class="alloc.fund_id && parseFloat((alloc.amount + '').replace(/[^\d.]/g, '')) > parseFloat(alloc.remaining_amount || 0) ? 'border-red-400 bg-red-50 text-red-700' : 'border-gray-300'"
                                                            placeholder="0">
                                                        <template x-if="alloc.fund_id && parseFloat((alloc.amount + '').replace(/[^\d.]/g, '')) > parseFloat(alloc.remaining_amount || 0)">
                                                            <div class="mt-1 text-[11px] font-bold text-red-600 bg-red-50 border border-red-200 rounded px-2 py-0.5 text-left flex items-start gap-1">
                                                                <i class="fas fa-exclamation-triangle text-red-500 mt-0.5 shrink-0"></i>
                                                                <span>Vượt quá số dư quỹ (<span x-text="formatMoney(alloc.remaining_amount)"></span> đ)!</span>
                                                            </div>
                                                        </template>
                                                    </td>
                                                    <td class="p-2.5 align-top">
                                                        <label class="inline-flex items-center gap-1.5 cursor-pointer mt-1.5">
                                                            <input type="checkbox" x-model="alloc.debt_checked" class="rounded border-gray-300 text-purple-600 focus:ring-purple-400 h-4 w-4">
                                                            <span class="text-[11px] text-gray-600 select-none">Hãng hoàn trả sau</span>
                                                        </label>
                                                    </td>
                                                    <td class="p-2.5 align-top text-center">
                                                        <button type="button" @click="removeAllocation(aIdx)" :disabled="allocations.length <= 1"
                                                            class="text-gray-400 hover:text-red-600 disabled:opacity-30 transition-colors p-1 mt-1">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                        <tfoot class="bg-gray-50 border-t border-gray-200">
                                            <tr>
                                                <td class="p-2.5 font-bold text-gray-700 text-right">Tổng phân bổ:</td>
                                                <td class="p-2.5 text-right font-black text-purple-700 text-sm">
                                                    <span x-text="formatMoney(getTotalAllocated()) + ' đ'"></span>
                                                </td>
                                                <td colspan="2" class="p-2.5 text-gray-400 text-[11px]">
                                                    <span x-show="getTotalAllocated() > 0" class="text-emerald-600 font-semibold">✓ Đã khớp với phiếu chi</span>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- TYPE 4: Others --}}
                        <div x-show="ticketType === 'others'" class="bg-white rounded-xl p-4 border border-gray-200 space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Chọn người hỗ trợ phối hợp <span class="text-red-500">*</span></label>
                                    <select name="assigned_to" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm focus:ring-2 focus:ring-purple-400">
                                        <option value="">-- Chọn nhân sự --</option>
                                        @foreach($users as $u)
                                            <option value="{{ $u->id }}" {{ old('assigned_to') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->department }} - {{ $u->position }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Tài liệu đính kèm (nếu có)</label>
                                    <input type="file" name="other_files[]" multiple class="w-full text-xs text-gray-500 mt-1 file:mr-3 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[10px] file:bg-violet-50 file:text-violet-700">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nội dung yêu cầu phối hợp chi tiết <span class="text-red-500">*</span></label>
                                    <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400" placeholder="Chi tiết các nội dung phối hợp khác...">{{ old('description') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2 justify-end pt-2 border-t border-gray-200">
                            <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-sm transition-colors">Gửi Ticket yêu cầu</button>
                            <button type="button" @click="showCreateTicket = false" class="px-5 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-600 text-xs rounded-xl">Hủy</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- BẢNG THEO DÕI CÁC YÊU CẦU (STEP 3) --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-4 flex items-center gap-2 border-b border-gray-100 pb-3">
                    <i class="fas fa-list-ol text-blue-500"></i> Bảng tiến độ theo dõi yêu cầu (SLA)
                </h3>
                @php
                    // Lọc yêu cầu theo quyền hiển thị
                    $allRequestsQuery = \App\Models\MarketingRequest::where('marketing_event_id', $marketingEvent->id)
                        ->with(['assignee', 'ticket', 'comments.user']);
                    $requestsList = $allRequestsQuery->forUser(auth()->user())->latest()->get();
                @endphp
                @if($requestsList->isEmpty())
                    <p class="text-xs text-gray-400 italic text-center py-6">Chưa có yêu cầu hỗ trợ nào cho hoạt động này.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-400 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3">Mã Request</th>
                                    <th class="px-4 py-3">Loại Request</th>
                                    <th class="px-4 py-3">Nội dung</th>
                                    <th class="px-4 py-3">Người/Bộ phận xử lý</th>
                                    @if($isMarketingOrBOD)
                                    <th class="px-4 py-3">Giá trị phát sinh</th>
                                    @endif
                                    <th class="px-4 py-3 text-center">Trạng thái</th>
                                    <th class="px-4 py-3 text-center">Thời hạn (SLA)</th>
                                    <th class="px-4 py-3 text-center">Thảo luận & Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requestsList as $req)
                                    @php
                                        // Kiểm tra quá hạn
                                        $isOverdue = false;
                                        $slaLabel = '';
                                        $slaColor = '';
                                        
                                        if ($req->status !== 'completed' && $req->deadline) {
                                            $deadline = \Carbon\Carbon::parse($req->deadline);
                                            if (now()->greaterThan($deadline)) {
                                                $isOverdue = true;
                                                $req->update(['status' => 'overdue']); // Auto update status to overdue in database
                                            }
                                        }

                                        if ($req->status === 'completed') {
                                            $slaLabel = '✅ Hoàn thành';
                                            $slaColor = 'text-green-600 bg-green-50 border border-green-100';
                                        } elseif ($req->status === 'overdue' || $isOverdue) {
                                            $slaLabel = '⚠️ Quá hạn';
                                            $slaColor = 'text-red-700 bg-red-50 border border-red-200 font-bold';
                                        } elseif ($req->deadline) {
                                            $deadline = \Carbon\Carbon::parse($req->deadline);
                                            $remaining = ceil(now()->diffInMinutes($deadline, false) / 60);
                                            if ($remaining > 24) {
                                                $days = ceil($remaining / 24);
                                                $slaLabel = "⏳ Còn {$days} ngày";
                                                $slaColor = 'text-blue-600 bg-blue-50 border border-blue-100';
                                            } else {
                                                $slaLabel = "⏳ Còn {$remaining}h";
                                                $slaColor = 'text-amber-600 bg-amber-50 border border-amber-100';
                                            }
                                        } else {
                                            $slaLabel = '—';
                                            $slaColor = 'text-gray-400 bg-gray-50 border border-gray-100';
                                        }
                                    @endphp
                                    <tr class="border-b border-gray-100 hover:bg-gray-50/50 {{ $req->status === 'overdue' ? 'bg-red-50/30' : '' }}">
                                        <td class="px-4 py-3 font-bold text-gray-700">{{ $req->code }}</td>
                                        <td class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase">{{ $req->ticket->type_label }}</td>
                                        <td class="px-4 py-3 text-gray-800">
                                            <p class="font-bold">{{ $req->support_content_label }}</p>
                                            <p class="text-xs text-gray-400 truncate max-w-[200px]">{{ $req->description }}</p>
                                            @if($req->support_content === 'invite_customers')
                                                <div class="mt-2 p-2 bg-purple-50/50 rounded-lg border border-purple-100/50 space-y-1">
                                                    <span class="text-[9px] uppercase font-black text-purple-700 tracking-wider"><i class="fas fa-tasks mr-1"></i>Tiến độ Sales mời khách:</span>
                                                    @php
                                                        $amGrouped = $marketingEvent->customers->groupBy('am');
                                                    @endphp
                                                    @if($amGrouped->isEmpty())
                                                        <p class="text-[10px] text-gray-400 italic">Chưa gán khách mời nào.</p>
                                                    @else
                                                        <div class="space-y-1">
                                                            @foreach($amGrouped as $amId => $amGuests)
                                                                @php
                                                                    $amUser = \App\Models\User::find($amId);
                                                                    $amTotal = $amGuests->count();
                                                                    $amUpdated = $amGuests->filter(fn($g) => $g->pivot->status !== 'not_contacted')->count();
                                                                    $isAmCompleted = $amTotal > 0 && $amTotal === $amUpdated;
                                                                @endphp
                                                                @if($amUser)
                                                                    <div class="flex items-center justify-between text-[10px] gap-3">
                                                                        <span class="font-semibold text-gray-600 truncate max-w-[120px]">{{ $amUser->name }}</span>
                                                                        <span class="font-bold {{ $isAmCompleted ? 'text-green-600' : 'text-amber-600' }} shrink-0">
                                                                            {{ $amUpdated }}/{{ $amTotal }} {{ $isAmCompleted ? '✓' : '' }}
                                                                        </span>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($req->assigned_to)
                                                <span class="font-semibold text-gray-700"><i class="fas fa-user-circle mr-1 text-gray-400"></i>{{ $req->assignee->name }}</span>
                                                <p class="text-[10px] text-gray-400">{{ $req->assignee->department }} - {{ $req->assignee->position }}</p>
                                            @else
                                                <span class="text-xs text-amber-600 font-bold bg-amber-50 border border-amber-100 px-2 py-0.5 rounded-full"><i class="fas fa-users-cog mr-1"></i>Chờ gán PIC</span>
                                                <p class="text-[10px] text-gray-400">Bộ phận: {{ strtoupper($req->support_team) }}</p>
                                            @endif
                                        </td>
                                        @if($isMarketingOrBOD)
                                        <td class="px-4 py-3 font-bold text-gray-700">
                                            {{ $req->amount ? number_format($req->amount) . ' VND' : '—' }}
                                        </td>
                                        @endif
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-block px-2.5 py-0.5 text-xs font-bold rounded-full {{ $req->status_color }}">
                                                {{ $req->status_label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-block px-2.5 py-0.5 text-xs font-bold rounded-full {{ $slaColor }}">
                                                {{ $slaLabel }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button data-request="{{ json_encode($req) }}" @click="currentRequest = JSON.parse($el.dataset.request); showRequestDetailModal = true"
                                                class="px-3 py-1 bg-violet-600 text-white text-xs font-bold rounded-lg hover:bg-violet-700 transition-colors">
                                                <i class="fas fa-comments mr-1"></i> Trao đổi
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>






        {{-- ── SLIDE PANEL / MODAL CHI TIẾT TICKET & COMM (STEP 3 DETAIL) ── --}}
        <div x-show="showRequestDetailModal" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity" @click="showRequestDetailModal = false" x-show="showRequestDetailModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

            <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    
                    <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg" x-show="showRequestDetailModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        
                        <template x-if="currentRequest">
                            <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center bg-violet-50/50">
                                <h3 class="text-sm font-bold text-violet-800">
                                    <i class="fas fa-ticket-alt mr-2"></i> Chi tiết đầu việc: <span x-text="currentRequest.code"></span>
                                </h3>
                                <button type="button" @click="showRequestDetailModal = false" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </template>

                        <div class="px-6 py-4 space-y-4 max-h-[480px] overflow-y-auto">
                            <template x-if="currentRequest">
                                <div class="space-y-3">
                                    {{-- Status and content --}}
                                    <div class="flex items-center justify-between gap-2 border-b border-gray-100 pb-2">
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-gray-400 block">Nội dung hỗ trợ</span>
                                            <span class="text-sm font-bold text-gray-800" x-text="currentRequest.support_content === 'others' ? currentRequest.support_content_other : currentRequest.support_content"></span>
                                        </div>
                                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full" :class="currentRequest.status === 'completed' ? 'bg-green-100 text-green-800' : (currentRequest.status === 'overdue' ? 'bg-red-100 text-red-800 animate-pulse' : 'bg-amber-100 text-amber-800')" x-text="currentRequest.status"></span>
                                    </div>

                                    {{-- Description --}}
                                    <div>
                                        <span class="text-[10px] uppercase font-bold text-gray-400 block">Mô tả công việc</span>
                                        <p class="text-xs text-gray-700 bg-gray-50 p-2.5 rounded-lg border border-gray-100 whitespace-pre-line" x-text="currentRequest.description || 'Không có mô tả chi tiết'"></p>
                                    </div>

                                    {{-- Deadline / Departure details --}}
                                    <div class="grid grid-cols-2 gap-3 text-xs border-b border-gray-100 pb-3">
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-gray-400 block">Hạn hoàn thành (SLA)</span>
                                            <span class="font-semibold text-gray-700" x-text="currentRequest.deadline ? new Date(currentRequest.deadline).toLocaleString('vi-VN') : '—'"></span>
                                        </div>
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-gray-400 block">Bộ phận phụ trách</span>
                                            <span class="font-semibold text-gray-700 uppercase" x-text="currentRequest.support_team"></span>
                                        </div>

                                        {{-- If Business Trip fields exist --}}
                                        <template x-if="currentRequest.departure_date">
                                            <div class="col-span-2 grid grid-cols-2 gap-2 pt-2 border-t border-gray-50">
                                                <div>
                                                    <span class="text-[10px] uppercase font-bold text-gray-400 block">Ngày đi</span>
                                                    <span class="font-semibold text-gray-700" x-text="currentRequest.departure_date"></span>
                                                </div>
                                                <div>
                                                    <span class="text-[10px] uppercase font-bold text-gray-400 block">Số nhân sự đi</span>
                                                    <span class="font-semibold text-gray-700" x-text="currentRequest.personnel_count + ' người'"></span>
                                                </div>
                                                <div class="col-span-2" x-show="currentRequest.departure_date_note">
                                                    <span class="text-[10px] uppercase font-bold text-gray-400 block">Note ngày đi</span>
                                                    <span class="text-xs text-gray-600 block" x-text="currentRequest.departure_date_note"></span>
                                                </div>
                                            </div>
                                        </template>

                                        {{-- If Payment fields exist --}}
                                        @if($isMarketingOrBOD)
                                        <template x-if="currentRequest.amount">
                                             <div class="col-span-2 pt-2 border-t border-gray-50 space-y-1">
                                                 <div class="flex justify-between">
                                                     <span class="text-[10px] uppercase font-bold text-gray-400">Số tiền yêu cầu</span>
                                                     <span class="font-bold text-emerald-600" x-text="new Intl.NumberFormat('vi-VN').format(currentRequest.amount) + ' VND'"></span>
                                                 </div>
                                                 <div x-show="currentRequest.amount_in_words">
                                                     <span class="text-[10px] uppercase font-bold text-gray-400 block">Bằng chữ</span>
                                                     <span class="text-xs text-gray-600 italic block" x-text="currentRequest.amount_in_words"></span>
                                                 </div>
                                                 <div class="grid grid-cols-2 gap-2 text-xs">
                                                     <div x-show="currentRequest.reference_request_code">
                                                         <span class="text-[10px] uppercase font-bold text-gray-400 block">Mã request tham chiếu</span>
                                                         <span class="font-semibold text-gray-700" x-text="currentRequest.reference_request_code"></span>
                                                     </div>
                                                     <div x-show="currentRequest.funding_source">
                                                         <span class="text-[10px] uppercase font-bold text-gray-400 block">Nguồn tiền chi trả</span>
                                                         <span class="font-semibold text-gray-700" x-text="currentRequest.funding_source"></span>
                                                     </div>
                                                 </div>
                                                 <template x-if="currentRequest.funding_allocations && currentRequest.funding_allocations.length > 0">
                                                     <div class="mt-2 pt-2 border-t border-gray-100">
                                                         <span class="text-[10px] uppercase font-bold text-gray-500 block mb-1">
                                                             <i class="fas fa-coins text-emerald-600 mr-1"></i> Phân bổ theo Quỹ / Nguồn tiền:
                                                         </span>
                                                         <div class="space-y-1">
                                                             <template x-for="(alloc, aIdx) in currentRequest.funding_allocations" :key="aIdx">
                                                                 <div class="flex items-center justify-between text-xs bg-gray-50 px-2.5 py-1.5 rounded-lg border border-gray-200">
                                                                     <span class="font-medium text-gray-800" x-text="alloc.name || 'Quỹ Hãng'"></span>
                                                                     <span class="font-black text-purple-700" x-text="new Intl.NumberFormat('vi-VN').format(alloc.amount || 0) + ' đ'"></span>
                                                                 </div>
                                                             </template>
                                                         </div>
                                                     </div>
                                                 </template>
                                             </div>
                                        </template>
                                        @endif
                                    </div>

                                    {{-- File đính kèm của request --}}
                                    <div x-show="currentRequest.attachment_path && currentRequest.attachment_path.length > 0">
                                        <span class="text-[10px] uppercase font-bold text-gray-400 block mb-1">Tài liệu đính kèm</span>
                                        <div class="space-y-1">
                                            <template x-for="(file, fIdx) in currentRequest.attachment_path" :key="fIdx">
                                                <a :href="file.url" target="_blank" class="flex items-center justify-between p-2 rounded-lg bg-gray-50 border border-gray-100 hover:bg-violet-50 transition-colors">
                                                    <span class="text-xs text-gray-600 font-bold truncate max-w-[320px]" x-text="file.name"></span>
                                                    <i class="fas fa-download text-[10px] text-gray-400"></i>
                                                </a>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- ACTIONS FORM --}}
                                    <div class="bg-gray-50 border border-gray-100 p-3.5 rounded-2xl space-y-3">
                                        <h5 class="text-xs font-bold text-gray-700 uppercase tracking-wide border-b border-gray-200 pb-1.5"><i class="fas fa-cogs mr-1"></i>Hành động xử lý</h5>
                                        
                                        {{-- 1. Phân công PIC (Lead Technical / Sales Manager) --}}
                                        @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('director') || auth()->user()->hasRole('sales_manager') || str_contains(strtolower(auth()->user()->position), 'manager') || str_contains(strtolower(auth()->user()->position), 'lead'))
                                            <form :action="'/marketing-requests/' + currentRequest.id + '/assign'" method="POST" class="space-y-2">
                                                @csrf
                                                <div>
                                                    <label class="block text-[10px] font-bold text-gray-500 uppercase">Giao/Phân công nhân sự phụ trách (P.I.C)</label>
                                                    <div class="flex gap-2">
                                                        <select name="assigned_to" required class="flex-1 text-xs border border-gray-300 rounded-lg px-2 py-1 bg-white focus:outline-none">
                                                            <option value="">-- Chọn người thực hiện --</option>
                                                            <template x-for="u in getAvailableAssignees(currentRequest.support_team)" :key="u.id">
                                                                <option :value="u.id" x-text="u.name + ' (' + (u.department || 'Không phòng ban') + ' - ' + (u.position || 'Nhân viên') + ')'"></option>
                                                            </template>
                                                        </select>
                                                        <button type="submit" class="px-4 py-1 bg-purple-600 text-white text-xs font-bold rounded-lg hover:bg-purple-700">Phân công</button>
                                                    </div>
                                                </div>
                                            </form>
                                        @endif

                                        {{-- 2. Xác nhận hoàn thành công việc (Bao gồm slide check cho Speaker) --}}
                                        <div class="pt-2 border-t border-gray-200/60">
                                            <form :action="'/marketing-requests/' + currentRequest.id + '/status'" method="POST" enctype="multipart/form-data" class="space-y-2">
                                                @csrf

                                                {{-- Nếu chưa được gán PIC, PIC được gán bấm accept --}}
                                                <template x-if="currentRequest.assigned_to == {{ auth()->id() }} && currentRequest.status === 'received'">
                                                    <a :href="'/marketing-requests/' + currentRequest.id + '/accept'" class="w-full flex justify-center py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition-colors mb-2">
                                                        <i class="fas fa-play mr-1"></i> Bấm để xác nhận tiếp nhận công việc
                                                    </a>
                                                </template>

                                                {{-- Form nộp kết quả của PIC (khi trạng thái chưa phải pending_approval) --}}
                                                <template x-if="currentRequest.status !== 'completed' && currentRequest.status !== 'pending_approval' && currentRequest.ticket.type !== 'payment' && currentRequest.ticket.type !== 'business_trip'">
                                                    <div class="space-y-2">
                                                        <span class="block text-[10px] font-bold text-gray-500 uppercase">Nộp báo cáo kết quả / sản phẩm</span>
                                                        
                                                        {{-- Slide upload check for speaker --}}
                                                        <template x-if="currentRequest.support_content === 'speaker'">
                                                            <div>
                                                                <label class="block text-[10px] font-bold text-red-600 uppercase mb-1">Đính kèm Slide thuyết trình/Tài liệu trình bày (* bắt buộc)</label>
                                                                <input type="file" name="presentation_file" required class="w-full text-xs text-gray-500">
                                                            </div>
                                                        </template>

                                                        {{-- Excel check for customer list --}}
                                                        <template x-if="currentRequest.support_team === 'sales' && currentRequest.support_content === 'customer_list'">
                                                            <div>
                                                                <label class="block text-[10px] font-bold text-red-600 uppercase mb-1">Đính kèm File Excel danh sách khách hàng (* bắt buộc)</label>
                                                                <input type="file" name="presentation_file" required class="w-full text-xs text-gray-500">
                                                            </div>
                                                        </template>
                                                        
                                                        <template x-if="currentRequest.support_content !== 'speaker' && !(currentRequest.support_team === 'sales' && currentRequest.support_content === 'customer_list')">
                                                            <div>
                                                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Đính kèm tài liệu kết quả/Báo cáo (nếu có)</label>
                                                                <input type="file" name="presentation_file" class="w-full text-xs text-gray-500">
                                                            </div>
                                                        </template>
                                                        
                                                        <div class="flex gap-2">
                                                            <input type="hidden" name="status" value="pending_approval">
                                                            <input type="text" name="comment" placeholder="Mô tả kết quả thực hiện gửi Marketing..." class="flex-1 text-xs border border-gray-300 rounded-lg px-2.5 py-1 focus:outline-none">
                                                            <button type="submit" class="px-4 py-1 bg-blue-600 text-white text-xs font-bold rounded-lg hover:bg-blue-700">Gửi kết quả (Chờ duyệt)</button>
                                                        </div>
                                                    </div>
                                                </template>

                                                {{-- Form duyệt của Marketing / Admin (khi trạng thái là pending_approval) --}}
                                                <template x-if="currentRequest.status === 'pending_approval' && currentRequest.ticket.type !== 'payment' && currentRequest.ticket.type !== 'business_trip'">
                                                    <div class="space-y-2">
                                                        <span class="block text-[10px] font-bold text-purple-700 uppercase">Đánh giá kết quả của Marketing</span>
                                                        @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('marketing'))
                                                        <div class="flex gap-2">
                                                            <input type="text" name="comment" id="mktApproveComment" placeholder="Ghi chú đánh giá, nhận xét..." class="flex-1 text-xs border border-gray-300 rounded-lg px-2 py-1">
                                                            
                                                            <button type="submit" name="status" value="completed" 
                                                                onclick="document.getElementById('mktApproveComment').form.action = '/marketing-requests/' + currentRequest.id + '/status'"
                                                                class="px-3 py-1 bg-green-600 text-white text-xs font-bold rounded-lg hover:bg-green-700">
                                                                Đồng ý & Hoàn thành (OK)
                                                            </button>
                                                            <button type="submit" name="status" value="rejected" 
                                                                onclick="document.getElementById('mktApproveComment').form.action = '/marketing-requests/' + currentRequest.id + '/status'"
                                                                class="px-3 py-1 bg-red-600 text-white text-xs font-bold rounded-lg hover:bg-red-700">
                                                                Yêu cầu làm lại
                                                            </button>
                                                        </div>
                                                        @else
                                                        <div class="text-xs text-gray-500 italic bg-gray-50 p-2 rounded-lg border border-gray-200/50">
                                                            <i class="fas fa-info-circle mr-1"></i> Chờ bộ phận Marketing kiểm duyệt kết quả đã nộp.
                                                        </div>
                                                        @endif
                                                    </div>
                                                </template>
                                            </form>
                                        </div>

                                        {{-- 3. BOD duyệt công tác / thanh toán --}}
                                        @if(auth()->user()->hasRole('director') || auth()->user()->hasRole('super_admin'))
                                            <template x-if="currentRequest.status === 'pending_approval'">
                                                <div class="pt-2 border-t border-gray-200/60 space-y-2">
                                                    <span class="block text-[10px] font-bold text-gray-500 uppercase">Phê duyệt chi / Duyệt công tác (BOD)</span>
                                                    <form :action="'/marketing-requests/' + currentRequest.id + '/status'" method="POST" class="flex gap-2">
                                                        @csrf
                                                        <input type="text" name="comment" placeholder="Ý kiến chỉ đạo..." class="flex-1 text-xs border border-gray-300 rounded-lg px-2 py-1">
                                                        
                                                        {{-- Nếu là thanh toán thì BOD duyệt chi chuyển sang pending_payment (chờ kế toán) --}}
                                                        <template x-if="currentRequest.ticket.type === 'payment'">
                                                            <button type="submit" name="status" value="pending_payment" class="px-3 py-1 bg-emerald-600 text-white text-xs font-bold rounded-lg hover:bg-emerald-700">Duyệt chi</button>
                                                        </template>
                                                        {{-- Nếu là công tác thì BOD duyệt thẳng sang completed --}}
                                                        <template x-if="currentRequest.ticket.type === 'business_trip'">
                                                            <button type="submit" name="status" value="completed" class="px-3 py-1 bg-emerald-600 text-white text-xs font-bold rounded-lg hover:bg-emerald-700">Duyệt đi</button>
                                                        </template>
                                                        
                                                        <button type="submit" name="status" value="rejected" class="px-3 py-1 bg-red-600 text-white text-xs font-bold rounded-lg hover:bg-red-700">Từ chối</button>
                                                    </form>
                                                </div>
                                            </template>
                                        @endif

                                        {{-- 4. Kế toán xác nhận đã chi tiền --}}
                                        @if(auth()->user()->hasRole('accountant') || auth()->user()->hasRole('super_admin'))
                                            <template x-if="currentRequest.status === 'pending_payment'">
                                                <div class="pt-2 border-t border-gray-200/60">
                                                    <span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Xác nhận chi tiền (Kế toán)</span>
                                                    <form :action="'/marketing-requests/' + currentRequest.id + '/status'" method="POST" class="flex gap-2">
                                                        @csrf
                                                        <input type="hidden" name="status" value="completed">
                                                        <input type="text" name="comment" placeholder="Mã phiếu chi / Chứng từ giao dịch..." class="flex-1 text-xs border border-gray-300 rounded-lg px-2 py-1">
                                                        <button type="submit" class="px-4 py-1 bg-green-600 text-white text-xs font-bold rounded-lg hover:bg-green-700">Đã chi tiền</button>
                                                    </form>
                                                </div>
                                            </template>
                                        @endif
                                    </div>

                                    {{-- KHUNG THẢO LUẬN / CHAT LOG --}}
                                    <div class="border-t border-gray-200 pt-3 space-y-3">
                                        <h5 class="text-xs font-bold text-gray-600 uppercase tracking-wide"><i class="fas fa-comments text-violet-500 mr-1"></i>Thảo luận & Lịch sử log</h5>
                                        
                                        {{-- Lịch sử comment --}}
                                        <div class="space-y-2 max-h-[220px] overflow-y-auto pr-1">
                                            <template x-for="(cmt, cIdx) in currentRequest.comments" :key="cIdx">
                                                <div class="bg-gray-50 rounded-xl p-2.5 border border-gray-100">
                                                    <div class="flex justify-between items-center text-[10px] text-gray-400">
                                                        <span class="font-bold text-gray-700" x-text="cmt.user.name"></span>
                                                        <span x-text="new Date(cmt.created_at).toLocaleString('vi-VN')"></span>
                                                    </div>
                                                    <p class="text-xs text-gray-600 mt-1 whitespace-pre-line" x-text="cmt.comment"></p>
                                                    <template x-if="cmt.attachment_path">
                                                        <a :href="'/storage/' + cmt.attachment_path" target="_blank" class="inline-flex items-center gap-1 text-[10px] text-purple-600 hover:underline mt-1">
                                                            <i class="fas fa-paperclip"></i> File đính kèm: <span x-text="cmt.attachment_path.split('/').pop()"></span>
                                                        </a>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>

                                        {{-- Add comment form --}}
                                        <form :action="'/marketing-requests/' + currentRequest.id + '/comments'" method="POST" enctype="multipart/form-data" class="space-y-2">
                                            @csrf
                                            <div class="flex gap-2 items-end">
                                                <textarea name="comment" rows="1" required placeholder="Gửi phản hồi nhanh..." class="flex-1 text-xs border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-purple-400 bg-white"></textarea>
                                                <input type="file" name="attachment" class="hidden" id="cmtFileUpload">
                                                <button type="button" onclick="document.getElementById('cmtFileUpload').click()" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center border border-gray-200" title="Đính kèm tài liệu">
                                                    <i class="fas fa-paperclip text-xs"></i>
                                                </button>
                                                <button type="submit" class="px-3.5 py-1.5 bg-violet-600 text-white text-xs font-bold rounded-lg hover:bg-violet-700 shadow-sm transition-colors">Gửi</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- MODAL HOÀN THÀNH SỰ KIỆN & QUYẾT TOÁN THỰC TẾ --}}
    <div x-show="showCompleteModal"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 overflow-y-auto"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">
        <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-4 my-8 max-h-[90vh] overflow-y-auto"
             @click.away="showCompleteModal = false">
            
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div>
                    <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-flag-checkered text-emerald-600"></i> Hoàn thành Sự kiện & Nghiệm thu Chi phí Thực tế
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Chương trình: <strong class="text-gray-700" x-text="completeData.eventCode + ' - ' + completeData.eventTitle"></strong>
                    </p>
                </div>
                <button type="button" @click="showCompleteModal = false" class="text-gray-400 hover:text-gray-600 p-1">
                    <i class="fas fa-times text-base"></i>
                </button>
            </div>

            <form :action="'/marketing-events/' + completeData.eventId + '/complete'" method="POST" class="space-y-4">
                @csrf
                
                {{-- Tổng ngân sách & Chi phí thực tế --}}
                <div class="p-4 bg-emerald-50/60 rounded-xl border border-emerald-100">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-xs font-semibold text-emerald-800 uppercase block">Dự toán đã duyệt</span>
                            <span class="text-lg font-black text-emerald-950 block mt-0.5" x-text="formatMoneyNumber(completeData.budget) + ' đ'"></span>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const eventId = @json($marketingEvent->id);
  const searchEl = document.getElementById(`customerSearch-${eventId}`);
  const listEl = document.getElementById(`customerList-${eventId}`);
  const selectAllEl = document.getElementById(`customerSelectAll-${eventId}`);
  const countEl = document.getElementById(`customerCount-${eventId}`);
  const selectedWrapEl = document.getElementById(`selectedCustomersWrap-${eventId}`);
  const selectedListEl = document.getElementById(`selectedCustomersList-${eventId}`);
  const selectedInputsEl = document.getElementById(`selectedCustomerInputs-${eventId}`);

  if (!searchEl || !listEl || !selectAllEl || !selectedWrapEl || !selectedListEl || !selectedInputsEl) return;

  const initialCustomers = (() => {
    try { return JSON.parse(listEl.getAttribute('data-initial') || '[]'); } catch { return []; }
  })();
  const selectedCustomers = new Map(); // id -> name

  function renderCustomers(customers) {
    listEl.innerHTML = (customers || []).map(c => `
      <label class="customer-item flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-white transition-colors cursor-pointer">
        <input type="checkbox" name="customer_ids[]" value="${c.id}"
          class="customer-checkbox rounded border-gray-300 text-purple-600 focus:ring-purple-400">
        <span class="text-sm text-gray-700 truncate">${escapeHtml(c.name || '')}</span>
      </label>
    `).join('') || `<div class="px-2 py-3 text-xs text-gray-400 italic">Không có kết quả.</div>`;
    syncRenderedChecks();
  }

  function escapeHtml(str) {
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function getVisibleItems() {
    return Array.from(listEl.querySelectorAll('.customer-item'))
      .filter(el => el.style.display !== 'none');
  }

  function updateCount() {
    const visible = getVisibleItems().length;
    const total = listEl.querySelectorAll('.customer-item').length;
    if (countEl) countEl.textContent = `${visible}/${total} khách | Đã chọn: ${selectedCustomers.size}`;
  }

  function updateSelectAllState() {
    const visibleItems = getVisibleItems();
    const visibleChecks = visibleItems
      .map(el => el.querySelector('.customer-checkbox'))
      .filter(Boolean);

    if (visibleChecks.length === 0) {
      selectAllEl.checked = false;
      selectAllEl.indeterminate = false;
      return;
    }

    const checkedCount = visibleChecks.filter(cb => cb.checked).length;
    selectAllEl.checked = checkedCount === visibleChecks.length;
    selectAllEl.indeterminate = checkedCount > 0 && checkedCount < visibleChecks.length;
  }

  function syncRenderedChecks() {
    listEl.querySelectorAll('.customer-checkbox').forEach(cb => {
      cb.checked = selectedCustomers.has(String(cb.value));
    });
    updateSelectAllState();
  }

  function renderSelectedSummary() {
    if (selectedCustomers.size === 0) {
      selectedWrapEl.classList.add('hidden');
      selectedListEl.innerHTML = '';
      selectedInputsEl.innerHTML = '';
      updateCount();
      return;
    }

    selectedWrapEl.classList.remove('hidden');
    selectedListEl.innerHTML = Array.from(selectedCustomers.entries()).map(([id, name]) => `
      <span class="inline-flex items-center gap-1 bg-purple-100 text-purple-700 text-xs px-2 py-1 rounded-full">
        <span class="max-w-[180px] truncate">${escapeHtml(name)}</span>
        <button type="button" class="remove-selected text-purple-500 hover:text-purple-700" data-id="${id}" title="Bỏ chọn">
          <i class="fas fa-times text-[10px]"></i>
        </button>
      </span>
    `).join('');

    selectedInputsEl.innerHTML = Array.from(selectedCustomers.keys()).map(id =>
      `<input type="hidden" name="customer_ids[]" value="${id}">`
    ).join('');

    updateCount();
    syncRenderedChecks();
  }

  let debounceTimer = null;
  searchEl.addEventListener('input', function () {
    const q = (searchEl.value || '').trim();
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(async () => {
      if (!q) {
        renderCustomers(initialCustomers);
        updateCount();
        updateSelectAllState();
        return;
      }

      listEl.innerHTML = `<div class="px-2 py-3 text-xs text-gray-400 italic">Đang tìm...</div>`;
      selectAllEl.checked = false;
      selectAllEl.indeterminate = false;
      if (countEl) countEl.textContent = '';

      try {
        const res = await fetch(@json(route('customers.ajax-search')) + `?q=${encodeURIComponent(q)}&marketing_event_id=${encodeURIComponent(eventId)}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        renderCustomers(data);
      } catch (e) {
        listEl.innerHTML = `<div class="px-2 py-3 text-xs text-red-500 italic">Lỗi tìm kiếm.</div>`;
      }

      updateCount();
      updateSelectAllState();
    }, 250);
  });

  selectAllEl.addEventListener('change', function () {
    const targetChecked = !!selectAllEl.checked;
    getVisibleItems().forEach(el => {
      const cb = el.querySelector('.customer-checkbox');
      if (!cb) return;
      cb.checked = targetChecked;
      const label = cb.closest('.customer-item');
      const name = label ? (label.querySelector('span')?.textContent || '') : '';
      if (targetChecked) {
        selectedCustomers.set(String(cb.value), name.trim());
      } else {
        selectedCustomers.delete(String(cb.value));
      }
    });
    renderSelectedSummary();
    updateSelectAllState();
  });

  listEl.addEventListener('change', function (e) {
    if (e.target && e.target.classList && e.target.classList.contains('customer-checkbox')) {
      const cb = e.target;
      const label = cb.closest('.customer-item');
      const name = label ? (label.querySelector('span')?.textContent || '') : '';
      if (cb.checked) {
        selectedCustomers.set(String(cb.value), name.trim());
      } else {
        selectedCustomers.delete(String(cb.value));
      }
      renderSelectedSummary();
      updateSelectAllState();
    }
  });

  selectedListEl.addEventListener('click', function (e) {
    const btn = e.target.closest('.remove-selected');
    if (!btn) return;
    selectedCustomers.delete(String(btn.dataset.id));
    renderSelectedSummary();
  });

  renderSelectedSummary();
  updateCount();
  updateSelectAllState();
});

document.addEventListener('DOMContentLoaded', function () {
  const eventId = @json($marketingEvent->id);
  const bulkSelectAll = document.getElementById(`bulkSelectAllCustomers-${eventId}`);
  const bulkInputs = document.getElementById(`bulkCustomerStatusInputs-${eventId}`);
  const bulkSubmit = document.getElementById(`bulkStatusSubmitBtn-${eventId}`);
  const bulkStatusSelect = document.getElementById(`bulkStatusSelect-${eventId}`);
  const bulkForm = document.getElementById(`bulkCustomerStatusForm-${eventId}`);
  const checkboxes = Array.from(document.querySelectorAll('.bulk-customer-checkbox'));

  if (!bulkSelectAll || !bulkInputs || !bulkSubmit || !bulkStatusSelect || !bulkForm || checkboxes.length === 0) {
    return;
  }

  function refreshBulkInputs() {
    const selected = checkboxes.filter(cb => cb.checked).map(cb => cb.dataset.customerId);
    bulkInputs.innerHTML = selected.map(id => `<input type="hidden" name="customer_ids[]" value="${id}">`).join('');
    bulkInputs.insertAdjacentHTML('beforeend', `<input type="hidden" name="status" value="${bulkStatusSelect.value}">`);
    bulkSubmit.disabled = selected.length === 0;
  }

  function refreshSelectAllState() {
    const selectedCount = checkboxes.filter(cb => cb.checked).length;
    bulkSelectAll.checked = selectedCount > 0 && selectedCount === checkboxes.length;
    bulkSelectAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
  }

  bulkSelectAll.addEventListener('change', function () {
    checkboxes.forEach(cb => {
      cb.checked = bulkSelectAll.checked;
    });
    refreshSelectAllState();
    refreshBulkInputs();
  });

  checkboxes.forEach(cb => cb.addEventListener('change', function () {
    refreshSelectAllState();
    refreshBulkInputs();
  }));

  bulkStatusSelect.addEventListener('change', refreshBulkInputs);

  bulkForm.addEventListener('submit', function (e) {
    const selectedCount = checkboxes.filter(cb => cb.checked).length;
    if (selectedCount === 0) {
      e.preventDefault();
      return;
    }
  });

  refreshSelectAllState();
  refreshBulkInputs();
});
</script>
@endpush
