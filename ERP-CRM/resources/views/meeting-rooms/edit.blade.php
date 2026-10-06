@extends('layouts.app')

@section('title', 'Chỉnh sửa đặt phòng họp')
@section('page-title', 'Chỉnh sửa lịch đặt phòng họp')

@section('content')
<div class="">
    <div class="flex items-center justify-between">
        <a href="{{ route('meeting-rooms.show', $booking->id) }}" class="text-sm text-gray-600 hover:text-gray-900 font-medium">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại chi tiết cuộc họp
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center gap-3 pb-4 mb-6 border-b border-gray-100">
            <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-xl">
                <i class="fas fa-edit"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-900">Chỉnh sửa lịch đặt phòng họp</h2>
                <p class="text-xs text-gray-500">Cập nhật thông tin thời gian, phòng họp hoặc danh sách người được mời tham gia.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
                <p class="font-bold mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> Vui lòng kiểm tra lại thông tin:</p>
                <ul class="list-disc pl-5 space-y-1 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('meeting-rooms.update', $booking->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Chọn phòng họp -->
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-semibold text-gray-700">
                            Chọn phòng họp <span class="text-red-500">*</span>
                        </label>
                        @if(!empty($canManageRooms))
                            <a href="{{ route('meeting-rooms.index') }}" class="text-xs text-purple-600 hover:text-purple-800 font-medium">
                                <i class="fas fa-door-closed mr-1"></i> Quản lý danh mục phòng họp
                            </a>
                        @endif
                    </div>
                    <select name="room_name" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none @error('room_name') border-red-500 @enderror">
                        <option value="">-- Chọn phòng họp --</option>
                        @foreach($rooms as $r)
                            @php
                                $rName = is_string($r) ? $r : $r->name;
                                $rLoc = is_object($r) && $r->location ? ' (' . $r->location . ')' : '';
                                $rCap = is_object($r) && $r->capacity ? ' - Sức chứa: ' . $r->capacity . ' người' : '';
                                $isSelected = old('room_name', $booking->room_name) === $rName;
                            @endphp
                            <option value="{{ $rName }}" {{ $isSelected ? 'selected' : '' }}>
                                {{ $rName }}{{ $rLoc }}{{ $rCap }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Ngày họp -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Ngày họp <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="booking_date" value="{{ old('booking_date', $booking->start_time->format('Y-m-d')) }}" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                </div>

                <!-- Khung giờ -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            Bắt đầu <span class="text-red-500">*</span>
                        </label>
                        <input type="time" name="start_time" value="{{ old('start_time', $booking->start_time->format('H:i')) }}" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            Kết thúc <span class="text-red-500">*</span>
                        </label>
                        <input type="time" name="end_time" value="{{ old('end_time', $booking->end_time->format('H:i')) }}" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                </div>

                <!-- Tiêu đề cuộc họp -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Tiêu đề / Chủ đề cuộc họp <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title', $booking->title) }}" required placeholder="Ví dụ: Họp nội bộ Team Sales định kỳ tuần 40"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                </div>

                <!-- Nội dung cuộc họp -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Nội dung chi tiết / Agenda cuộc họp
                    </label>
                    <textarea name="description" rows="3" placeholder="Nhập mục tiêu, nội dung cần trao đổi trong cuộc họp..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">{{ old('description', $booking->description) }}</textarea>
                </div>

                <!-- Mời người tham gia -->
                <div class="md:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                        <div class="flex items-center gap-2">
                            <label class="block text-sm font-semibold text-gray-700">
                                Mời người tham dự (Thành viên cuộc họp)
                            </label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">
                                Đã chọn: <span id="attendeeCount" class="ml-1 font-bold">0</span>&nbsp;người
                            </span>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <button type="button" onclick="selectAllAttendees(true)" class="text-purple-600 hover:text-purple-800 font-medium hover:underline">
                                <i class="fas fa-check-double mr-1"></i>Chọn tất cả
                            </button>
                            <span class="text-gray-300">|</span>
                            <button type="button" onclick="selectAllAttendees(false)" class="text-gray-500 hover:text-gray-700 font-medium hover:underline">
                                <i class="fas fa-times mr-1"></i>Bỏ chọn tất cả
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mb-2.5">
                        * Những người được chọn sẽ nhận được thông báo, xem được toàn bộ nội dung cuộc họp và phản hồi Đồng ý / Từ chối tham gia. Người ngoài danh sách chỉ thấy phòng đang bận.
                    </p>

                    <!-- Search bar -->
                    <div class="relative mb-2.5">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                            <i class="fas fa-search text-xs"></i>
                        </span>
                        <input type="text" id="attendeeSearchInput" placeholder="Tìm kiếm người tham dự theo tên, phòng ban, email..."
                            oninput="filterAttendees(this.value)"
                            class="w-full pl-9 pr-8 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary focus:outline-none transition-all shadow-xs bg-white">
                        <button type="button" id="clearAttendeeSearchBtn" onclick="clearAttendeeSearch()" class="hidden absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>

                    <!-- Attendee Grid -->
                    <div id="attendeeListContainer" class="border border-gray-200 rounded-lg p-3 max-h-60 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 bg-gray-50">
                        @foreach($users as $u)
                            @if($u->id !== $booking->created_by)
                                <label class="attendee-card inline-flex items-center gap-2.5 text-xs bg-white p-2.5 rounded-lg border border-gray-200 hover:border-purple-300 hover:shadow-xs cursor-pointer transition-all select-none"
                                    data-search="{{ Str::slug($u->name . ' ' . ($u->department ?? '') . ' ' . ($u->email ?? ''), ' ') }} {{ mb_strtolower($u->name) }} {{ mb_strtolower($u->department ?? '') }} {{ mb_strtolower($u->email ?? '') }}">
                                    <input type="checkbox" name="attendee_ids[]" value="{{ $u->id }}"
                                        {{ in_array($u->id, old('attendee_ids', $selectedAttendeeIds)) ? 'checked' : '' }}
                                        onchange="updateAttendeeCount()"
                                        class="attendee-checkbox rounded text-purple-600 focus:ring-purple-500 w-4 h-4 cursor-pointer">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium text-gray-800 truncate">{{ $u->name }}</div>
                                        <div class="text-[11px] text-gray-400 truncate flex items-center gap-1">
                                            @if($u->department)
                                                <span class="text-purple-600 font-semibold">{{ $u->department }}</span>
                                            @endif
                                            @if($u->email)
                                                <span class="truncate">&bull; {{ $u->email }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endif
                        @endforeach
                    </div>
                    <div id="noAttendeeFound" class="hidden text-center py-6 text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-lg mt-1">
                        <i class="fas fa-user-slash text-gray-300 text-lg mb-1 block"></i>
                        Không tìm thấy người tham dự nào khớp với từ khóa tìm kiếm.
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                <a href="{{ route('meeting-rooms.show', $booking->id) }}" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    Hủy
                </a>
                <button type="submit" class="px-6 py-2 bg-primary text-white text-sm font-bold rounded-lg hover:bg-opacity-90 transition-all shadow-sm">
                    <i class="fas fa-check mr-2"></i> Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function removeVietnameseTones(str) {
        if (!str) return '';
        str = str.toLowerCase();
        str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a");
        str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e");
        str = str.replace(/ì|í|ị|ỉ|ĩ/g, "i");
        str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o");
        str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u");
        str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y");
        str = str.replace(/đ/g, "d");
        str = str.replace(/\u0300|\u0301|\u0303|\u0309|\u0323/g, "");
        str = str.replace(/\u02C6|\u0306|\u031B/g, "");
        return str.trim();
    }

    function filterAttendees(keyword) {
        const term = removeVietnameseTones(keyword);
        const clearBtn = document.getElementById('clearAttendeeSearchBtn');
        if (clearBtn) {
            clearBtn.classList.toggle('hidden', !keyword);
        }
        const cards = document.querySelectorAll('.attendee-card');
        let visibleCount = 0;
        cards.forEach(card => {
            const rawData = card.getAttribute('data-search') || '';
            const normalized = removeVietnameseTones(rawData);
            if (!term || normalized.includes(term) || rawData.toLowerCase().includes(keyword.toLowerCase().trim())) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        const noFound = document.getElementById('noAttendeeFound');
        if (noFound) {
            noFound.classList.toggle('hidden', visibleCount > 0);
        }
    }

    function clearAttendeeSearch() {
        const input = document.getElementById('attendeeSearchInput');
        if (input) {
            input.value = '';
            filterAttendees('');
            input.focus();
        }
    }

    function updateAttendeeCount() {
        const checked = document.querySelectorAll('.attendee-checkbox:checked').length;
        const badge = document.getElementById('attendeeCount');
        if (badge) badge.textContent = checked;
    }

    function selectAllAttendees(checked) {
        const cards = document.querySelectorAll('.attendee-card');
        cards.forEach(card => {
            if (card.style.display !== 'none') {
                const cb = card.querySelector('.attendee-checkbox');
                if (cb) cb.checked = checked;
            }
        });
        updateAttendeeCount();
    }

    document.addEventListener('DOMContentLoaded', updateAttendeeCount);
</script>
@endpush
@endsection
