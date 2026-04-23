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

<div class="space-y-5" x-data="{ showReject: new URLSearchParams(window.location.search).get('reject') === '1' }">

    {{-- Flash messages --}}
    @foreach(['success' => ['green','check-circle'], 'error' => ['red','exclamation-circle'], 'warning' => ['yellow','exclamation-triangle']] as $type => [$color, $icon])
    @if(session($type))
    <div class="flex items-center gap-3 px-4 py-3 bg-{{ $color }}-50 border border-{{ $color }}-200 text-{{ $color }}-700 rounded-xl text-sm">
        <i class="fas fa-{{ $icon }}"></i> {{ session($type) }}
    </div>
    @endif
    @endforeach

    {{-- ── Workflow Progress Guide ── --}}
    @php
        $steps = [
            ['id' => 1, 'name' => 'Kế hoạch', 'icon' => 'fa-file-alt'],
            ['id' => 2, 'name' => 'Duyệt ngân sách', 'icon' => 'fa-check-double'],
            ['id' => 3, 'name' => 'Mời tham dự', 'icon' => 'fa-envelope-open-text'],
            ['id' => 4, 'name' => 'Tổ chức Event', 'icon' => 'fa-calendar-check'],
            ['id' => 5, 'name' => 'Sàng lọc thông tin', 'icon' => 'fa-filter'],
            ['id' => 6, 'name' => 'Tư vấn', 'icon' => 'fa-comments-dollar'],
            ['id' => 7, 'name' => 'Gửi thông báo ĐKDA', 'icon' => 'fa-bell'],
            ['id' => 8, 'name' => 'Đăng ký dự án', 'icon' => 'fa-project-diagram'],
            ['id' => 9, 'name' => 'Gửi báo giá', 'icon' => 'fa-file-invoice-dollar'],
        ];

        // Determine current step logic
        $currentStep = 1;
        if ($marketingEvent->status === 'approved') {
            $currentStep = 2; // Approved
            if ($marketingEvent->customers->count() > 0) {
                $currentStep = 3; // Invited
                $attendedCount = $marketingEvent->customers->where('pivot.status', 'attended')->count();
                if ($attendedCount > 0) {
                    $currentStep = 4; // Organised
                    
                    // Step 5: Sàng lọc (Checks if any attended customer has notes)
                    $hasNotes = $marketingEvent->customers()
                        ->wherePivot('status', 'attended')
                        ->wherePivotNotNull('notes')
                        ->wherePivot('notes', '!=', '')
                        ->exists();
                        
                    if ($hasNotes) {
                        $currentStep = 5;
                        // Step 6-7: Combine logic or use flags. For now move to 8 if project exists
                        if ($marketingEvent->projects()->count() > 0) {
                            $currentStep = 8;
                            
                            // Step 9: Gửi báo giá (Check if project has sales)
                            $hasSales = $marketingEvent->projects()->whereHas('sales')->exists();
                            if ($hasSales) {
                                $currentStep = 9;
                            }
                        }
                    }
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

    {{-- ── Guidance Box ── --}}
    @if($marketingEvent->status === 'approved')
        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-100 rounded-2xl p-5 shadow-sm">
            <div class="flex gap-4">
                <div class="w-12 h-12 bg-emerald-500 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-200 flex-shrink-0">
                    <i class="fas fa-rocket text-xl animate-pulse"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-emerald-800">
                        @if($currentStep < 5) Ngân sách đã được phê duyệt! @else Quy trình đang tiến triển tốt! @endif
                    </h3>
                    <p class="text-sm text-emerald-700 mt-1 opacity-90">
                        @if($currentStep == 4) Bạn đã tổ chức sự kiện. Bước tiếp theo là **Sàng lọc thông tin** khách hàng. @else Kế hoạch marketing đã sẵn sàng triển khai. Hãy thực hiện các công việc tiếp theo: @endif
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
                        @if($currentStep < 4)
                        <div class="bg-white/60 backdrop-blur p-3 rounded-xl border border-white/50">
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Bước 3</h4>
                            <p class="text-sm font-semibold text-gray-800">Mời khách hàng tham dự</p>
                            <p class="text-xs text-gray-500 mt-1">Sử dụng danh sách bên dưới để thêm và gửi lời mời đến khách hàng.</p>
                        </div>
                        <div class="bg-white/60 backdrop-blur p-3 rounded-xl border border-white/50">
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Bước 4</h4>
                            <p class="text-sm font-semibold text-gray-800">Tổ chức & Điểm danh</p>
                            <p class="text-xs text-gray-500 mt-1">Cập nhật trạng thái "Đã tham dự" ngay khi khách hàng có mặt.</p>
                        </div>
                        @elseif($currentStep >= 4 && $currentStep < 8)
                        <div class="bg-white/60 backdrop-blur p-3 rounded-xl border border-white/50">
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Bước 5-7</h4>
                            <p class="text-sm font-semibold text-gray-800">Sàng lọc & Tư vấn</p>
                            <p class="text-xs text-gray-500 mt-1">Nhập ghi chú phản hồi của khách hàng để hệ thống ghi nhận nhu cầu.</p>
                        </div>
                        <div class="bg-white/60 backdrop-blur p-3 rounded-xl border border-white/50">
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Bước 8</h4>
                            <p class="text-sm font-semibold text-gray-800">Đăng ký dự án (ĐKDA)</p>
                            <p class="text-xs text-gray-500 mt-1">Nhấn biểu tượng chữ ký bên cạnh tên khách hàng để chuyển sang bộ phận Sales.</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @elseif($marketingEvent->status === 'pending')
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 shadow-sm flex gap-4">
            <div class="w-12 h-12 bg-amber-400 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-amber-200 flex-shrink-0">
                <i class="fas fa-hourglass-half text-xl animate-spin-slow"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-amber-800">Đang chờ duyệt ngân sách</h3>
                <p class="text-sm text-amber-700 mt-1">Hệ thống đã gửi thông báo đến cấp trên. Bạn sẽ nhận được thông báo ngay khi có kết quả.</p>
            </div>
        </div>
    @endif

    {{-- Rejection Warning (Keep existing) --}}
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
                    <h1 class="text-2xl font-bold text-gray-800">{{ $marketingEvent->title }}</h1>
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold {{ $marketingEvent->status_color }}">
                        @if($marketingEvent->status === 'approved') <i class="fas fa-check-circle shrink-0"></i>
                        @elseif($marketingEvent->status === 'pending') <i class="fas fa-clock shrink-0"></i>
                        @elseif($marketingEvent->status === 'rejected') <i class="fas fa-times-circle shrink-0"></i>
                        @else <i class="fas fa-file-alt shrink-0"></i>
                        @endif
                        <span>{{ $marketingEvent->status_label }}</span>
                    </span>
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
                <form action="{{ route('marketing-events.customers.add', $marketingEvent) }}" method="POST" class="mb-4" id="addCustomersForm-{{ $marketingEvent->id }}">
                    @csrf
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-3">
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                <input type="text"
                                    id="customerSearch-{{ $marketingEvent->id }}"
                                    placeholder="Tìm khách hàng..."
                                    autocomplete="off"
                                    class="w-full border border-gray-200 bg-white rounded-lg pl-10 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-3">
                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-600 select-none">
                                <input type="checkbox"
                                    id="customerSelectAll-{{ $marketingEvent->id }}"
                                    class="rounded border-gray-300 text-purple-600 focus:ring-purple-400">
                                Chọn tất cả
                            </label>
                            <span class="text-[10px] text-gray-400" id="customerCount-{{ $marketingEvent->id }}"></span>
                        </div>

                        <div class="mt-2 hidden" id="selectedCustomersWrap-{{ $marketingEvent->id }}">
                            <div class="text-[10px] uppercase tracking-wide text-gray-400 mb-1">Đã chọn</div>
                            <div class="flex flex-wrap gap-1.5" id="selectedCustomersList-{{ $marketingEvent->id }}"></div>
                        </div>

                        <div class="mt-2 max-h-56 overflow-y-auto pr-1 space-y-1" id="customerList-{{ $marketingEvent->id }}"
                            data-initial='@json($suggestCustomers)'>
                            @foreach($suggestCustomers as $customer)
                                <label class="customer-item flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-white transition-colors cursor-pointer">
                                    <input type="checkbox" name="customer_ids[]" value="{{ $customer->id }}"
                                        class="customer-checkbox rounded border-gray-300 text-purple-600 focus:ring-purple-400">
                                    <span class="text-sm text-gray-700 truncate">{{ $customer->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div id="selectedCustomerInputs-{{ $marketingEvent->id }}"></div>
                    </div>
                    <button type="submit"
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 text-sm font-medium transition-colors">
                        <i class="fas fa-plus text-xs"></i> Thêm vào danh sách
                    </button>
                </form>

                {{-- Bulk status update --}}
                @if($marketingEvent->customers->isNotEmpty())
                <form action="{{ route('marketing-events.customers.status.bulk', $marketingEvent) }}" method="POST" id="bulkCustomerStatusForm-{{ $marketingEvent->id }}" class="mb-3">
                    @csrf
                    @method('PATCH')
                    <div id="bulkCustomerStatusInputs-{{ $marketingEvent->id }}"></div>
                    <div class="flex items-center gap-2">
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-600 whitespace-nowrap select-none">
                            <input type="checkbox" id="bulkSelectAllCustomers-{{ $marketingEvent->id }}"
                                class="rounded border-gray-300 text-purple-600 focus:ring-purple-400">
                            Chọn tất cả
                        </label>
                        <select id="bulkStatusSelect-{{ $marketingEvent->id }}"
                            class="flex-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5 bg-white focus:outline-none focus:ring-1 focus:ring-purple-400">
                            <option value="invited">Mời</option>
                            <option value="attended">Tham dự</option>
                            <option value="cancelled">Hủy</option>
                        </select>
                        <button type="submit" id="bulkStatusSubmitBtn-{{ $marketingEvent->id }}"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-600 text-white rounded-lg text-xs font-semibold hover:bg-purple-700 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed"
                            disabled>
                            <i class="fas fa-check-double text-[10px]"></i> Cập nhật
                        </button>
                    </div>
                </form>
                @endif

                {{-- Customer list --}}
                <div class="space-y-2 h-96 overflow-y-auto pr-1" style="max-height: 420px; overflow-y: auto;">
                    @forelse($marketingEvent->customers as $customer)
                    <div class="p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition-all border border-transparent hover:border-purple-100" x-data="{ showNotes: {{ $customer->pivot->notes ? 'true' : 'false' }} }">
                        <div class="flex items-center justify-between">
                            <div class="min-w-0 flex-1 flex items-start gap-2">
                                <input type="checkbox"
                                    class="bulk-customer-checkbox mt-1 rounded border-gray-300 text-purple-600 focus:ring-purple-400"
                                    data-customer-id="{{ $customer->id }}">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-800 truncate">{{ $customer->name }}</div>
                                    @php $st = $customer->pivot->status; @endphp
                                    <span class="inline-block text-[10px] px-2 py-0.5 rounded-full mt-0.5 font-bold uppercase tracking-wider
                                        {{ $st === 'attended' ? 'bg-emerald-100 text-emerald-700' : ($st === 'cancelled' ? 'bg-gray-200 text-gray-500' : 'bg-amber-100 text-amber-700') }}">
                                        {{ $st === 'attended' ? 'Đã tham dự' : ($st === 'cancelled' ? 'Đã hủy' : 'Đã mời') }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 ml-2 flex-shrink-0">
                                <form action="{{ route('marketing-events.customers.status', [$marketingEvent, $customer]) }}" method="POST" class="flex items-center gap-1">
                                    @csrf @method('PATCH')
                                    <select name="status" onchange="this.form.submit()"
                                        class="text-xs border border-gray-200 rounded-lg px-2 py-1 bg-white focus:outline-none focus:ring-1 focus:ring-purple-400 cursor-pointer">
                                        <option value="invited"   {{ $customer->pivot->status === 'invited'   ? 'selected' : '' }}>Mời</option>
                                        <option value="attended"  {{ $customer->pivot->status === 'attended'  ? 'selected' : '' }}>Tham dự</option>
                                        <option value="cancelled" {{ $customer->pivot->status === 'cancelled' ? 'selected' : '' }}>Hủy</option>
                                    </select>
                                    
                                    @if($customer->pivot->status === 'attended')
                                    <button type="button" @click="showNotes = !showNotes" 
                                        class="w-7 h-7 flex items-center justify-center rounded-lg transition-colors {{ $customer->pivot->notes ? 'text-violet-600 bg-violet-50' : 'text-gray-300 hover:text-violet-500 hover:bg-violet-50' }}"
                                        title="Ghi chú phản hồi">
                                        <i class="fas fa-comment-dots text-xs"></i>
                                    </button>
                                    @endif
                                </form>
                                
                                @if($customer->pivot->status === 'attended')
                                    @can('create_projects')
                                    <a href="{{ route('projects.create', ['marketing_event_id' => $marketingEvent->id, 'customer_id' => $customer->id]) }}"
                                        class="w-7 h-7 flex items-center justify-center text-gray-300 hover:text-emerald-500 hover:bg-emerald-50 rounded-lg transition-colors border border-transparent hover:border-emerald-100"
                                        title="Đăng ký dự án (ĐKDA)">
                                        <i class="fas fa-file-signature text-xs"></i>
                                    </a>
                                    @endcan
                                @endif
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
                        
                        {{-- Quick Notes form --}}
                        <div x-show="showNotes" x-transition class="mt-2 pt-2 border-t border-gray-100">
                            <form action="{{ route('marketing-events.customers.status', [$marketingEvent, $customer]) }}" method="POST">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="{{ $customer->pivot->status }}">
                                <div class="flex gap-2">
                                    <input type="text" name="notes" value="{{ $customer->pivot->notes }}" 
                                        class="flex-1 text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-purple-400"
                                        placeholder="Nhập ghi chú sàng lọc...">
                                    <button type="submit" class="bg-violet-600 text-white text-[10px] uppercase font-bold px-3 py-1 rounded-lg hover:bg-violet-700 transition-colors">Lưu</button>
                                </div>
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

      // show lightweight loading state
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

  // initial
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
