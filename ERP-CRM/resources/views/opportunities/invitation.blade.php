@extends('layouts.app')

@section('title', 'Lời mời tham gia hoạt động: ' . $opportunity->name)

@section('content')
<div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 space-y-6">
    <!-- Breadcrumb / Back button -->
    <div class="flex items-center justify-between">
        <a href="{{ route('opportunities.index') }}" 
           class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-gray-800 transition-colors">
            <i class="fas fa-arrow-left"></i> Quay lại danh sách Cơ hội
        </a>
        <span class="text-xs px-3 py-1 rounded-full font-bold {{ $opportunity->status_color }}">
            {{ $opportunity->status_label }}
        </span>
    </div>

    <!-- Main Invitation Card -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <!-- Card Header Banner -->
        <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-blue-600 p-6 sm:p-8 text-white relative">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-white/20 backdrop-blur-md text-white border border-white/30">
                        <i class="fas fa-envelope-open-text"></i> Lời mời tham gia hoạt động
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white leading-tight">
                        {{ $opportunity->name }}
                    </h1>
                    <p class="text-indigo-100 text-sm flex items-center gap-2 flex-wrap">
                        <span><i class="fas fa-building mr-1 text-indigo-200"></i>{{ $opportunity->customer_display_name }}</span>
                        <span>•</span>
                        <span><i class="fas fa-tag mr-1 text-indigo-200"></i>{{ $opportunity->activity_type_label }}</span>
                    </p>
                </div>

                @if($myAttendance)
                    <div class="sm:text-right shrink-0">
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold shadow-xs border bg-white {{ $myAttendance->status === 'accepted' ? 'text-emerald-700 border-emerald-300' : ($myAttendance->status === 'declined' ? 'text-rose-700 border-rose-300' : 'text-purple-700 border-purple-300') }}">
                            <i class="{{ $myAttendance->status_icon }}"></i>
                            {{ $myAttendance->status_label }}
                        </span>
                        @if($myAttendance->responded_at)
                            <div class="text-[11px] text-indigo-100 mt-1">
                                Phản hồi: {{ $myAttendance->responded_at->format('H:i d/m/Y') }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- RSVP Decision Section -->
        <div class="p-6 sm:p-8 border-b border-gray-100">
            @if($myAttendance)
                @if($myAttendance->status === 'accepted')
                    <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0 text-lg shadow-sm">
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-emerald-950 text-base">Bạn đã xác nhận đồng ý tham gia!</h3>
                                <p class="text-xs text-emerald-800 mt-0.5 leading-relaxed">
                                    Hoạt động này đã được đồng bộ vào <strong>Lịch làm việc (Calendar)</strong> của bạn. Bây giờ bạn có thể truy cập để xem toàn bộ tài liệu đính kèm và thông tin chi tiết.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 shrink-0 sm:self-center">
                            <a href="{{ route('opportunities.show', $opportunity->id) }}" 
                               class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs shadow-sm transition-all flex items-center gap-2">
                                <i class="fas fa-external-link-alt"></i> Vào xem chi tiết Cơ hội
                            </a>
                        </div>
                    </div>
                @elseif($myAttendance->status === 'declined')
                    <div class="rounded-xl bg-rose-50 border border-rose-200 p-5 space-y-4">
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-full bg-rose-600 text-white flex items-center justify-center shrink-0 text-lg shadow-sm">
                                <i class="fas fa-times"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-rose-950 text-base">Bạn đã từ chối tham gia hoạt động này</h3>
                                <p class="text-xs text-rose-800 mt-0.5 leading-relaxed">
                                    Nội dung và tài liệu của hoạt động hiện đang được tạm khóa. Nếu bạn thay đổi kế hoạch và muốn đồng hành cùng đồng nghiệp, bạn có thể xác nhận lại bên dưới.
                                </p>
                                @if($myAttendance->note)
                                    <div class="mt-2 text-xs text-rose-900 bg-white/70 p-2.5 rounded-lg border border-rose-100">
                                        <strong>Ghi chú của bạn:</strong> {{ $myAttendance->note }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <form action="{{ route('opportunities.attendee-respond', $opportunity->id) }}" method="POST" class="pt-3 border-t border-rose-200/60 flex flex-wrap items-center justify-end gap-3 m-0">
                            @csrf
                            <input type="text" name="note" placeholder="Lời nhắn bổ sung (tùy chọn)..."
                                class="text-xs px-3.5 py-2 border border-gray-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 w-full sm:w-72">
                            <input type="hidden" name="status" value="accepted">
                            <button type="submit"
                                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all flex items-center gap-2">
                                <i class="fas fa-check"></i> Đổi ý: Xác nhận tham gia
                            </button>
                        </form>
                    </div>
                @else
                    {{-- Status is Pending --}}
                    <div class="rounded-2xl bg-gradient-to-br from-purple-50 via-indigo-50 to-blue-50 border-2 border-indigo-200 p-6 shadow-xs">
                        <div class="text-center sm:text-left flex flex-col sm:flex-row items-center sm:items-start gap-4 mb-6">
                            <div class="w-14 h-14 rounded-2xl bg-indigo-600 text-white flex items-center justify-center text-2xl shadow-md shrink-0">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="space-y-1">
                                <h3 class="text-lg font-bold text-indigo-950">Xác nhận lời mời tham gia hoạt động</h3>
                                <p class="text-xs text-indigo-800 leading-relaxed max-w-2xl">
                                    Đồng nghiệp đã gửi lời mời bạn cùng tham gia hoạt động này. Sau khi bạn <strong>xác nhận đồng ý</strong>, hệ thống sẽ tự động đồng bộ sự kiện vào Lịch làm việc (Calendar) của bạn và mở khóa toàn bộ hồ sơ chi tiết cơ hội.
                                </p>
                            </div>
                        </div>

                        <form action="{{ route('opportunities.attendee-respond', $opportunity->id) }}" method="POST" class="space-y-4 m-0">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Ghi chú phản hồi (Tùy chọn)
                                </label>
                                <input type="text" name="note" value="{{ old('note', $myAttendance->note) }}" 
                                    placeholder="Ví dụ: Tôi sẽ tham gia đúng giờ; Có thể đến muộn 10 phút..."
                                    class="w-full text-xs px-3.5 py-2.5 border border-gray-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-2xs">
                            </div>

                            <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-3 border-t border-indigo-100">
                                <button type="submit" name="status" value="declined"
                                    onclick="return confirm('Bạn có chắc chắn muốn từ chối tham gia hoạt động này?')"
                                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-rose-300 text-rose-700 bg-white hover:bg-rose-50 text-xs font-bold transition-colors flex items-center justify-center gap-2">
                                    <i class="fas fa-times"></i> Từ chối tham gia
                                </button>
                                <button type="submit" name="status" value="accepted"
                                    class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2">
                                    <i class="fas fa-check"></i> Xác nhận đồng ý tham gia
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            @else
                <div class="rounded-xl bg-blue-50 border border-blue-200 p-4 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-info-circle text-blue-600 text-lg"></i>
                        <p class="text-xs text-blue-800">
                            Bạn là Người phụ trách / Quản lý của hoạt động này.
                        </p>
                    </div>
                    <a href="{{ route('opportunities.show', $opportunity->id) }}" 
                       class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold">
                        Vào chi tiết Cơ hội
                    </a>
                </div>
            @endif
        </div>

        <!-- Meeting & Activity Details Grid -->
        <div class="p-6 sm:p-8 space-y-6">
            <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-info-circle text-indigo-600"></i> Thông tin buổi làm việc
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-sm">
                <!-- Thời gian -->
                <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/70 space-y-2">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Thời gian diễn ra</span>
                    <div class="flex items-center gap-3 text-gray-800">
                        <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm shrink-0">
                            <i class="far fa-calendar-alt"></i>
                        </div>
                        <div>
                            <div class="font-bold text-sm text-gray-900">
                                {{ $opportunity->activity_date ? $opportunity->activity_date->format('d/m/Y') : 'Chưa xác định' }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ $opportunity->start_time ?: '09:00' }} - {{ $opportunity->end_time ?: '10:00' }} ({{ $opportunity->duration_minutes }} phút)
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Người tổ chức / Mời -->
                <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/70 space-y-2">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Người phụ trách / Tạo</span>
                    <div class="flex items-center gap-3 text-gray-800">
                        <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-sm shrink-0">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <div class="font-bold text-sm text-gray-900">
                                {{ $opportunity->assignedTo?->name ?: ($opportunity->createdBy?->name ?: 'N/A') }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ $opportunity->assignedTo?->email ?: $opportunity->createdBy?->email }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Khách hàng / Đối tác -->
                <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/70 space-y-2">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Khách hàng / Đối tác</span>
                    <div class="flex items-center gap-3 text-gray-800">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-sm shrink-0">
                            <i class="fas fa-building"></i>
                        </div>
                        <div>
                            <div class="font-bold text-sm text-gray-900">
                                {{ $opportunity->customer_display_name }}
                            </div>
                            @if($opportunity->contact)
                                <div class="text-xs text-gray-500">
                                    Liên hệ: {{ $opportunity->contact->name }} ({{ $opportunity->contact->phone ?: $opportunity->contact->email }})
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Phối hợp kỹ thuật -->
                <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/70 space-y-2">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Kỹ sư phối hợp (Nếu có)</span>
                    <div class="flex items-center gap-3 text-gray-800">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm shrink-0">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <div>
                            @if($opportunity->technicalUser)
                                <div class="font-bold text-sm text-gray-900">
                                    {{ $opportunity->technicalUser->name }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $opportunity->technicalUser->department ?? 'Phòng Kỹ thuật' }}
                                </div>
                            @else
                                <div class="text-xs text-gray-400 italic">
                                    Không yêu cầu kỹ thuật đi kèm
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mô tả & Mục tiêu cuộc gặp -->
            @if($opportunity->description || $opportunity->notes)
                <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 space-y-2">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Nội dung / Mục tiêu làm việc</span>
                    <p class="text-xs text-gray-700 leading-relaxed whitespace-pre-line">
                        {{ $opportunity->description ?: $opportunity->notes }}
                    </p>
                </div>
            @endif

            <!-- Danh sách người cùng tham dự -->
            <div class="space-y-3 pt-2">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-user-friends text-purple-600"></i> Đồng nghiệp cùng được mời tham gia ({{ $opportunity->attendees->count() }})
                    </h3>
                </div>

                @if($opportunity->attendees->isEmpty())
                    <p class="text-xs text-gray-400 italic">Chưa có người tham dự nào khác được mời.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($opportunity->attendees as $att)
                            <div class="p-3 rounded-xl border border-gray-100 bg-white flex items-center justify-between gap-3 shadow-2xs">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-xs font-bold shrink-0">
                                        {{ mb_substr($att->user?->name ?: 'U', 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-xs text-gray-800 truncate">
                                            {{ $att->user?->name ?: 'N/A' }}
                                            @if($att->user_id === auth()->id())
                                                <span class="text-[10px] text-purple-600 font-semibold">(Bạn)</span>
                                            @endif
                                        </p>
                                        <p class="text-[11px] text-gray-400 truncate">{{ $att->user?->email }}</p>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border shrink-0 {{ $att->status_color }}">
                                    <i class="{{ $att->status_icon }} mr-0.5"></i> {{ $att->status_label }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
