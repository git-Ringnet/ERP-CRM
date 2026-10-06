@extends('layouts.app')

@section('title', 'Chi tiết đặt phòng họp')
@section('page-title', 'Chi tiết lịch phòng họp')

@section('content')
    <div class="">
        <div class="flex items-center justify-between">
            <a href="{{ route('meeting-rooms.index', ['date' => $booking->start_time->format('Y-m-d')]) }}"
                class="text-sm text-gray-600 hover:text-gray-900 font-medium">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại lịch ngày {{ $booking->start_time->format('d/m/Y') }}
            </a>
        </div>

        @if(!$canViewDetails)
            <!-- Privacy Masked View for Non-Attendees -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 text-center space-y-4">
                <div class="w-16 h-16 bg-gray-100 text-gray-500 rounded-full flex items-center justify-center text-2xl mx-auto">
                    <i class="fas fa-lock"></i>
                </div>
                <div>
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-800">
                        {{ $booking->room_name }}
                    </span>
                    <h3 class="text-lg font-bold text-gray-800 mt-2">Phòng họp đang bận (Đã được đặt)</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Khung giờ: <strong>{{ $booking->start_time->format('H:i') }} -
                            {{ $booking->end_time->format('H:i') }}</strong> ngày
                        <strong>{{ $booking->start_time->format('d/m/Y') }}</strong>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        Người đặt phòng: <strong>{{ $booking->creator->name ?? 'N/A' }}</strong>
                    </p>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 text-xs text-gray-600 max-w-lg mx-auto">
                    <i class="fas fa-shield-alt text-gray-400 mr-1"></i>
                    Chi tiết nội dung cuộc họp và danh sách người tham gia được bảo mật riêng cho những người được mời.
                </div>
            </div>
        @else
            <!-- Full Authorized View -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div
                    class="p-6 bg-gradient-to-r from-purple-50 to-indigo-50 border-b border-purple-100 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-xl bg-purple-600 text-white flex items-center justify-center text-2xl shadow-sm">
                            <i class="fas fa-door-open"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-200 text-purple-800">
                                    {{ $booking->room_name }}
                                </span>
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-100 text-emerald-700">
                                    <i class="fas fa-check mr-1"></i> Đã xác nhận
                                </span>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 mt-1">{{ $booking->title }}</h2>
                        </div>
                    </div>

                    @if($booking->created_by === auth()->id() || auth()->user()->hasAnyRole(['super_admin', 'admin', 'director']))
                        <div class="flex items-center gap-2">
                            <a href="{{ route('meeting-rooms.edit', $booking->id) }}"
                                class="px-3.5 py-1.5 bg-purple-100 hover:bg-purple-200 text-purple-700 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-xs">
                                <i class="fas fa-edit"></i> Chỉnh sửa
                            </a>
                            <form action="{{ route('meeting-rooms.destroy', $booking->id) }}" method="POST"
                                onsubmit="return confirm('Bạn có chắc muốn hủy lịch đặt phòng họp này?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="px-3.5 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1.5">
                                    <i class="fas fa-trash"></i> Hủy lịch đặt phòng
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                <!-- RSVP Section for invited attendee -->
                @if($myAttendance)
                    <div class="p-4 bg-amber-50 border-b border-amber-200 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-envelope-open-text text-amber-600 text-lg"></i>
                            <div>
                                <p class="text-xs font-bold text-amber-900">Trạng thái tham gia của bạn:</p>
                                <p class="text-xs text-amber-700">
                                    Hiện tại: <strong>
                                        {{ match ($myAttendance->status) {
                        'accepted' => 'Đồng ý tham gia',
                        'declined' => 'Từ chối tham gia',
                        default => 'Chờ bạn phản hồi',
                    } }}
                                    </strong>
                                </p>
                            </div>
                        </div>

                        <form action="{{ route('meeting-rooms.respond', $booking->id) }}" method="POST"
                            class="flex items-center gap-2">
                            @csrf
                            <button type="submit" name="response" value="accepted"
                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-xs transition-colors flex items-center gap-1">
                                <i class="fas fa-check"></i> Sẽ tham gia (Accept)
                            </button>
                            <button type="submit" name="response" value="declined"
                                class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold shadow-xs transition-colors flex items-center gap-1">
                                <i class="fas fa-times"></i> Từ chối (Decline)
                            </button>
                        </form>
                    </div>
                @endif

                <div class="p-6 space-y-6">
                    <!-- Info cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-150">
                            <span class="text-xs text-gray-500 uppercase font-semibold block mb-1">Thời gian họp</span>
                            <p class="text-sm font-bold text-gray-800">
                                {{ $booking->start_time->format('H:i') }} - {{ $booking->end_time->format('H:i') }}
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Ngày {{ $booking->start_time->format('d/m/Y') }}
                                ({{ $booking->start_time->diffInMinutes($booking->end_time) }} phút)
                            </p>
                        </div>

                        <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-150">
                            <span class="text-xs text-gray-500 uppercase font-semibold block mb-1">Người chủ trì / Đặt
                                phòng</span>
                            <p class="text-sm font-bold text-gray-800">{{ $booking->creator->name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $booking->creator->department ?? 'N/A' }} -
                                {{ $booking->creator->email ?? '' }}</p>
                        </div>

                        <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-150">
                            <span class="text-xs text-gray-500 uppercase font-semibold block mb-1">Tổng số người tham dự</span>
                            <p class="text-sm font-bold text-gray-800">{{ $booking->attendees->count() + 1 }} người</p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $booking->attendees->where('status', 'accepted')->count() }} đã đồng ý |
                                {{ $booking->attendees->where('status', 'declined')->count() }} từ chối
                            </p>
                        </div>
                    </div>

                    <!-- Description / Agenda -->
                    <div>
                        <h3 class="text-sm font-bold text-gray-800 mb-2 uppercase tracking-wide">Nội dung chi tiết / Agenda</h3>
                        <div
                            class="p-4 bg-gray-50 rounded-xl border border-gray-200 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">
                            {{ $booking->description ?: '(Không có ghi chú thêm)' }}
                        </div>
                    </div>

                    <!-- Attendees Table -->
                    <div>
                        <h3 class="text-sm font-bold text-gray-800 mb-2 uppercase tracking-wide">
                            Danh sách người tham dự ({{ $booking->attendees->count() + 1 }})
                        </h3>
                        <div class="border border-gray-200 rounded-xl overflow-hidden">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-gray-100 text-gray-600 font-semibold border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-2.5">Họ và tên</th>
                                        <th class="px-4 py-2.5">Phòng ban</th>
                                        <th class="px-4 py-2.5">Vai trò</th>
                                        <th class="px-4 py-2.5 text-center">Trạng thái tham gia</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <!-- Creator -->
                                    <tr class="bg-purple-50/40 font-semibold">
                                        <td class="px-4 py-2.5 text-gray-900">{{ $booking->creator->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-2.5 text-gray-600">{{ $booking->creator->department ?? 'N/A' }}</td>
                                        <td class="px-4 py-2.5 text-purple-700">Người chủ trì (Chủ phòng)</td>
                                        <td class="px-4 py-2.5 text-center">
                                            <span
                                                class="px-2 py-0.5 rounded text-[11px] font-bold bg-green-100 text-green-700">Chủ
                                                trì</span>
                                        </td>
                                    </tr>
                                    <!-- Attendees -->
                                    @foreach($booking->attendees as $att)
                                        <tr>
                                            <td class="px-4 py-2.5 text-gray-800">{{ $att->user->name ?? 'N/A' }}</td>
                                            <td class="px-4 py-2.5 text-gray-500">{{ $att->user->department ?? 'N/A' }}</td>
                                            <td class="px-4 py-2.5 text-gray-500">Thành viên tham dự</td>
                                            <td class="px-4 py-2.5 text-center">
                                                @if($att->status === 'accepted')
                                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-green-100 text-green-700">
                                                        <i class="fas fa-check mr-1"></i> Đồng ý
                                                    </span>
                                                @elseif($att->status === 'declined')
                                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-red-100 text-red-700">
                                                        <i class="fas fa-times mr-1"></i> Từ chối
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-700">
                                                        <i class="fas fa-clock mr-1"></i> Chờ phản hồi
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection