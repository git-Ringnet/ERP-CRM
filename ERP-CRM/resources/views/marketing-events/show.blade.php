@extends('layouts.app')
@section('title', $marketingEvent->title)

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
@endphp

<div class="space-y-5" x-data="{ showReject: false }">

    {{-- Flash messages --}}
    @foreach(['success' => ['green','check-circle'], 'error' => ['red','exclamation-circle'], 'warning' => ['yellow','exclamation-triangle']] as $type => [$color, $icon])
    @if(session($type))
    <div class="flex items-center gap-3 px-4 py-3 bg-{{ $color }}-50 border border-{{ $color }}-200 text-{{ $color }}-700 rounded-xl text-sm">
        <i class="fas fa-{{ $icon }}"></i> {{ session($type) }}
    </div>
    @endif
    @endforeach

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

        <div class="p-5 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-bold text-gray-800">{{ $marketingEvent->title }}</h1>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $marketingEvent->status_color }}">
                        @if($marketingEvent->status === 'approved') <i class="fas fa-check-circle"></i>
                        @elseif($marketingEvent->status === 'pending') <i class="fas fa-clock"></i>
                        @elseif($marketingEvent->status === 'rejected') <i class="fas fa-times-circle"></i>
                        @else <i class="fas fa-file-alt"></i>
                        @endif
                        {{ $marketingEvent->status_label }}
                    </span>
                </div>
                <p class="text-sm text-gray-400 mt-1.5 flex items-center flex-wrap gap-x-3 gap-y-1">
                    <span><i class="fas fa-user-circle mr-1"></i>{{ $marketingEvent->creator->name }}</span>
                    <span><i class="fas fa-calendar mr-1"></i>{{ $marketingEvent->event_date->format('d/m/Y') }}</span>
                    @if($marketingEvent->location)
                    <span><i class="fas fa-map-marker-alt mr-1"></i>{{ $marketingEvent->location }}</span>
                    @endif
                </p>
            </div>

            {{-- Action buttons --}}
            <div class="flex items-center gap-2 flex-wrap">
                @if($marketingEvent->isEditable())
                    <a href="{{ route('marketing-events.edit', $marketingEvent) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-sm font-medium hover:bg-amber-100 transition-colors">
                        <i class="fas fa-pen text-xs"></i> Chỉnh sửa
                    </a>
                    <form action="{{ route('marketing-events.submit-approval', $marketingEvent) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-violet-600 text-white text-sm font-medium hover:bg-violet-700 shadow-sm transition-colors">
                            <i class="fas fa-paper-plane text-xs"></i> Gửi duyệt ngân sách
                        </button>
                    </form>
                @endif

                @if($marketingEvent->status === 'pending' && $canApprove)
                    <form action="{{ route('marketing-events.approve', $marketingEvent) }}" method="POST">
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

                <a href="{{ route('marketing-events.index') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-gray-100 text-gray-600 text-sm font-medium hover:bg-gray-200 transition-colors">
                    <i class="fas fa-arrow-left text-xs"></i> Quay lại
                </a>
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

    {{-- ── Content grid ─────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Left: Budget + Approval history --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Budget card --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4 flex items-center gap-2">
                    <i class="fas fa-coins text-yellow-500"></i> Ngân sách
                </h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="relative overflow-hidden bg-gradient-to-br from-violet-50 to-purple-100 rounded-xl p-4 text-center border border-violet-100">
                        <div class="text-xs text-violet-500 font-medium mb-1">Dự toán</div>
                        <div class="text-2xl font-bold text-violet-700">{{ number_format($marketingEvent->budget) }}</div>
                        <div class="text-xs text-violet-400 mt-0.5">VNĐ</div>
                    </div>
                    <div class="relative overflow-hidden bg-gradient-to-br from-blue-50 to-indigo-100 rounded-xl p-4 text-center border border-blue-100">
                        <div class="text-xs text-blue-500 font-medium mb-1">Thực tế</div>
                        <div class="text-2xl font-bold text-blue-700">{{ number_format($marketingEvent->actual_cost ?? 0) }}</div>
                        <div class="text-xs text-blue-400 mt-0.5">VNĐ</div>
                    </div>
                </div>
                @if($marketingEvent->description)
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <div class="text-xs text-gray-400 font-medium mb-1 uppercase tracking-wide">Mô tả / Mục tiêu</div>
                    <p class="text-sm text-gray-700 leading-relaxed">{{ $marketingEvent->description }}</p>
                </div>
                @endif
            </div>

            {{-- Approval history --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4 flex items-center gap-2">
                    <i class="fas fa-history text-blue-500"></i> Lịch sử duyệt ngân sách
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
                                <span class="text-sm font-medium text-gray-800">Cấp {{ $h->level }}: {{ $h->level_name }}</span>
                                @if($h->action_at)
                                <span class="text-xs text-gray-400">{{ $h->action_at->format('d/m/Y H:i') }}</span>
                                @else
                                <span class="text-xs text-amber-500">Đang chờ...</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">{{ $h->approver_name }}</div>
                            @if($h->comment)
                            <div class="text-xs text-gray-600 mt-1.5 italic bg-white/70 rounded px-2 py-1">"{{ $h->comment }}"</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- Right: Customer list --}}
        <div class="space-y-5">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4 flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <i class="fas fa-users text-purple-500"></i> Danh sách khách mời
                    </span>
                    <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-semibold">
                        {{ $marketingEvent->customers->count() }}
                    </span>
                </h3>

                {{-- Add customer --}}
                <form action="{{ route('marketing-events.customers.add', $marketingEvent) }}" method="POST" class="mb-4">
                    @csrf
                    <select name="customer_ids[]" multiple
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm h-28 focus:outline-none focus:ring-2 focus:ring-purple-400 mb-2 bg-gray-50">
                        @foreach($allCustomers as $customer)
                            @unless($marketingEvent->customers->contains($customer->id))
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endunless
                        @endforeach
                    </select>
                    <button type="submit"
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 text-sm font-medium transition-colors">
                        <i class="fas fa-plus text-xs"></i> Thêm vào danh sách
                    </button>
                </form>

                {{-- Customer list --}}
                <div class="space-y-2 max-h-96 overflow-y-auto pr-1">
                    @forelse($marketingEvent->customers as $customer)
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors">
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-gray-800 truncate">{{ $customer->name }}</div>
                            @php $st = $customer->pivot->status; @endphp
                            <span class="inline-block text-xs px-2 py-0.5 rounded-full mt-0.5
                                {{ $st === 'attended' ? 'bg-emerald-100 text-emerald-700' : ($st === 'cancelled' ? 'bg-gray-200 text-gray-500' : 'bg-amber-100 text-amber-700') }}">
                                {{ $st === 'attended' ? 'Đã tham dự' : ($st === 'cancelled' ? 'Đã hủy' : 'Đã mời') }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1 ml-2 flex-shrink-0">
                            <form action="{{ route('marketing-events.customers.status', [$marketingEvent, $customer]) }}" method="POST">
                                @csrf @method('PATCH')
                                <select name="status" onchange="this.form.submit()"
                                    class="text-xs border border-gray-200 rounded-lg px-1.5 py-1 bg-white focus:outline-none focus:ring-1 focus:ring-purple-400 cursor-pointer">
                                    <option value="invited"   {{ $customer->pivot->status === 'invited'   ? 'selected' : '' }}>Mời</option>
                                    <option value="attended"  {{ $customer->pivot->status === 'attended'  ? 'selected' : '' }}>Tham dự</option>
                                    <option value="cancelled" {{ $customer->pivot->status === 'cancelled' ? 'selected' : '' }}>Hủy</option>
                                </select>
                            </form>
                            <form action="{{ route('marketing-events.customers.remove', [$marketingEvent, $customer]) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    onclick="return confirm('Xóa khách hàng này?')"
                                    class="w-7 h-7 flex items-center justify-center text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-6 text-gray-300">
                        <i class="fas fa-user-plus text-2xl mb-2 block"></i>
                        <p class="text-sm">Chưa có khách mời.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
