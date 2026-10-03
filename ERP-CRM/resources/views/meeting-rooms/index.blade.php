@extends('layouts.app')

@section('title', 'Đặt phòng họp')
@section('page-title', 'Quản lý lịch phòng họp')

@section('content')
<div class="space-y-6">
    <!-- Top toolbar & actions -->
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('meeting-rooms.index') }}" class="flex flex-wrap items-center gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Ngày xem lịch</label>
                <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Lọc phòng họp</label>
                <select name="room" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    <option value="">-- Tất cả phòng --</option>
                    @foreach($rooms as $r)
                        <option value="{{ $r }}" {{ $room === $r ? 'selected' : '' }}>{{ $r }}</option>
                    @endforeach
                </select>
            </div>
            <div class="pt-5">
                <a href="{{ route('meeting-rooms.index', ['date' => date('Y-m-d')]) }}" class="text-xs text-blue-600 hover:underline font-medium">Hôm nay</a>
            </div>
        </form>

        <div>
            <a href="{{ route('meeting-rooms.create') }}"
                class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-opacity-90 transition-all shadow-sm">
                <i class="fas fa-plus mr-2"></i> Đặt phòng họp mới
            </a>
        </div>
    </div>

    <!-- My upcoming invitations banner -->
    @if(isset($myInvitations) && $myInvitations->count() > 0)
        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-xl p-4">
            <h3 class="text-sm font-bold text-purple-900 mb-2 flex items-center gap-2">
                <i class="fas fa-bell text-purple-600"></i> Lịch họp sắp tới của bạn (Lời mời tham gia)
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($myInvitations as $inv)
                    @php
                        $myAtt = $inv->attendees->where('user_id', auth()->id())->first();
                        $statusBadge = match($myAtt?->status) {
                            'accepted' => '<span class="px-2 py-0.5 rounded text-[11px] font-bold bg-green-100 text-green-700">Đã nhận lời</span>',
                            'declined' => '<span class="px-2 py-0.5 rounded text-[11px] font-bold bg-red-100 text-red-700">Từ chối</span>',
                            default => '<span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-700">Chờ phản hồi</span>',
                        };
                    @endphp
                    <div class="bg-white p-3 rounded-lg border border-purple-100 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                <span class="font-medium text-purple-700">{{ $inv->room_name }}</span>
                                {!! $statusBadge !!}
                            </div>
                            <h4 class="font-bold text-sm text-gray-800 line-clamp-1">{{ $inv->title }}</h4>
                            <p class="text-xs text-gray-600 mt-1">
                                <i class="far fa-clock mr-1 text-gray-400"></i> {{ $inv->start_time->format('H:i') }} - {{ $inv->end_time->format('H:i') }} ({{ $inv->start_time->format('d/m/Y') }})
                            </p>
                            <p class="text-xs text-gray-500">Người đặt: {{ $inv->creator->name ?? 'N/A' }}</p>
                        </div>
                        <div class="mt-2 pt-2 border-t border-gray-100 flex justify-end">
                            <a href="{{ route('meeting-rooms.show', $inv->id) }}" class="text-xs font-semibold text-purple-600 hover:text-purple-800">
                                Xem & Phản hồi <i class="fas fa-chevron-right ml-0.5"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Daily Room Schedule Cards -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="p-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                <i class="fas fa-calendar-day text-blue-600"></i> Lịch phòng họp ngày {{ date('d/m/Y', strtotime($date)) }}
            </h3>
            <span class="text-xs text-gray-500">{{ $bookings->count() }} lượt đặt phòng</span>
        </div>

        @if($bookings->isEmpty())
            <div class="p-12 text-center text-gray-400">
                <i class="fas fa-door-open text-5xl mb-3 text-gray-300"></i>
                <p class="text-sm font-medium">Chưa có lịch đặt phòng họp nào trong ngày này.</p>
                <p class="text-xs text-gray-400 mt-1">Tất cả các phòng họp đều đang trống.</p>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($bookings as $booking)
                    @php
                        $canSee = $booking->canViewDetails(auth()->user());
                    @endphp
                    <div class="p-4 hover:bg-gray-50 transition-colors flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <!-- Time column -->
                            <div class="flex flex-col items-center justify-center bg-blue-50 text-blue-800 px-3 py-2 rounded-lg border border-blue-100 min-w-[110px]">
                                <span class="font-bold text-sm">{{ $booking->start_time->format('H:i') }} - {{ $booking->end_time->format('H:i') }}</span>
                                <span class="text-[11px] text-blue-600">{{ $booking->start_time->diffInMinutes($booking->end_time) }} phút</span>
                            </div>

                            <!-- Room & Meeting details -->
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800">
                                        {{ $booking->room_name }}
                                    </span>
                                    @if($canSee)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-100 text-emerald-700">
                                            <i class="fas fa-user-check mr-1"></i> Thành viên
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-600">
                                            <i class="fas fa-lock mr-1"></i> Riêng tư
                                        </span>
                                    @endif
                                </div>

                                @if($canSee)
                                    <h4 class="font-bold text-gray-900 text-base mt-1">
                                        <a href="{{ route('meeting-rooms.show', $booking->id) }}" class="hover:text-primary transition-colors">
                                            {{ $booking->title }}
                                        </a>
                                    </h4>
                                    @if($booking->description)
                                        <p class="text-xs text-gray-600 line-clamp-1 mt-0.5">{{ $booking->description }}</p>
                                    @endif
                                    <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500 mt-1.5">
                                        <span><i class="fas fa-user-tie text-gray-400 mr-1"></i> Người đặt: <strong class="text-gray-700">{{ $booking->creator->name ?? 'N/A' }}</strong></span>
                                        <span><i class="fas fa-users text-gray-400 mr-1"></i> Tham dự: <strong class="text-gray-700">{{ $booking->attendees->count() + 1 }} người</strong></span>
                                    </div>
                                @else
                                    <h4 class="font-bold text-gray-600 text-base mt-1 flex items-center gap-1.5">
                                        <i class="fas fa-lock text-gray-400 text-sm"></i> [BẬN] Phòng họp đã được đặt
                                    </h4>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Người đặt phòng: <strong class="text-gray-700">{{ $booking->creator->name ?? 'N/A' }}</strong>
                                        <span class="text-gray-400 italic ml-2">(Nội dung và người tham dự được ẩn để bảo mật)</span>
                                    </p>
                                @endif
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 self-end md:self-center">
                            <a href="{{ route('meeting-rooms.show', $booking->id) }}"
                               class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-xs font-semibold">
                                <i class="fas fa-eye mr-1"></i> Xem chi tiết
                            </a>
                            @if($booking->created_by === auth()->id() || auth()->user()->hasAnyRole(['super_admin', 'admin', 'director']))
                                <form action="{{ route('meeting-rooms.destroy', $booking->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn hủy lịch đặt phòng họp này?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors text-xs" title="Hủy lịch">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
