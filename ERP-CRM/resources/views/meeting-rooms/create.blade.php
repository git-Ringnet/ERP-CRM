@extends('layouts.app')

@section('title', 'Đặt phòng họp')
@section('page-title', 'Tạo lịch đặt phòng họp')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('meeting-rooms.index') }}" class="text-sm text-gray-600 hover:text-gray-900 font-medium">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại danh sách
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center gap-3 pb-4 mb-6 border-b border-gray-100">
            <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-xl">
                <i class="fas fa-door-open"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-900">Đặt lịch phòng họp mới</h2>
                <p class="text-xs text-gray-500">Mọi nhân sự trong công ty đều có thể tạo lịch đặt phòng và mời thành viên tham gia.</p>
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

        <form action="{{ route('meeting-rooms.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Chọn phòng họp -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Chọn phòng họp <span class="text-red-500">*</span>
                    </label>
                    <select name="room_name" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none @error('room_name') border-red-500 @enderror">
                        <option value="">-- Chọn phòng họp --</option>
                        @foreach($rooms as $r)
                            <option value="{{ $r }}" {{ old('room_name') === $r ? 'selected' : '' }}>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Ngày họp -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Ngày họp <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="booking_date" value="{{ old('booking_date', date('Y-m-d')) }}" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                </div>

                <!-- Khung giờ -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            Bắt đầu <span class="text-red-500">*</span>
                        </label>
                        <input type="time" name="start_time" value="{{ old('start_time', '09:00') }}" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            Kết thúc <span class="text-red-500">*</span>
                        </label>
                        <input type="time" name="end_time" value="{{ old('end_time', '10:00') }}" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                </div>

                <!-- Tiêu đề cuộc họp -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Tiêu đề / Chủ đề cuộc họp <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title') }}" required placeholder="Ví dụ: Họp nội bộ Team Sales định kỳ tuần 40"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                </div>

                <!-- Nội dung cuộc họp -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Nội dung chi tiết / Agenda cuộc họp
                    </label>
                    <textarea name="description" rows="3" placeholder="Nhập mục tiêu, nội dung cần trao đổi trong cuộc họp..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">{{ old('description') }}</textarea>
                </div>

                <!-- Mời người tham gia -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Mời người tham dự (Thành viên cuộc họp)
                    </label>
                    <p class="text-xs text-gray-500 mb-2">
                        * Những người được chọn sẽ nhận được thông báo, xem được toàn bộ nội dung cuộc họp và phản hồi Đồng ý / Từ chối tham gia. Người ngoài danh sách chỉ thấy phòng đang bận.
                    </p>
                    <div class="border border-gray-200 rounded-lg p-3 max-h-56 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 bg-gray-50">
                        @foreach($users as $u)
                            @if($u->id !== auth()->id())
                                <label class="inline-flex items-center gap-2 text-xs bg-white p-2 rounded border border-gray-200 hover:border-purple-300 cursor-pointer transition-colors">
                                    <input type="checkbox" name="attendee_ids[]" value="{{ $u->id }}"
                                        {{ in_array($u->id, old('attendee_ids', [])) ? 'checked' : '' }}
                                        class="rounded text-purple-600 focus:ring-purple-500">
                                    <span class="truncate">{{ $u->name }} <span class="text-gray-400">({{ $u->department ?: 'N/A' }})</span></span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                <a href="{{ route('meeting-rooms.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    Hủy
                </a>
                <button type="submit" class="px-6 py-2 bg-primary text-white text-sm font-bold rounded-lg hover:bg-opacity-90 transition-all shadow-sm">
                    <i class="fas fa-check mr-2"></i> Xác nhận đặt phòng
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
