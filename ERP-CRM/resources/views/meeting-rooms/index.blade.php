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

        <div class="flex items-center gap-2">
            @if(!empty($canManageRooms))
                <button type="button" onclick="openRoomManagementModal()"
                    class="inline-flex items-center px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition-colors border border-gray-300">
                    <i class="fas fa-door-closed mr-1.5 text-purple-600"></i> Quản lý phòng họp
                </button>
            @endif
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
                                <a href="{{ route('meeting-rooms.edit', $booking->id) }}" class="p-1.5 bg-purple-50 text-purple-600 rounded-lg hover:bg-purple-100 transition-colors text-xs" title="Chỉnh sửa đặt phòng">
                                    <i class="fas fa-edit"></i>
                                </a>
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

    @push('modals')
    <!-- Room Management Modal (for managers/admins) -->
    @if(!empty($canManageRooms))
    <div id="roomManagementModal" class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-sm items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-3xl w-full shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-gradient-to-r from-purple-700 to-indigo-700 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center text-lg">
                        <i class="fas fa-door-closed"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-base">Quản lý danh sách phòng họp</h3>
                        <p class="text-xs text-purple-200">Khai báo phòng họp, sức chứa và vị trí để nhân viên đặt phòng</p>
                    </div>
                </div>
                <button type="button" onclick="closeRoomManagementModal()" class="text-white/80 hover:text-white text-lg p-1">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-6 overflow-y-auto space-y-6">
                <!-- Add / Edit Form Card -->
                <div class="bg-purple-50/60 border border-purple-200 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 id="roomFormTitle" class="text-sm font-bold text-purple-900 flex items-center gap-2">
                            <i class="fas fa-plus-circle text-purple-600"></i> Thêm phòng họp mới
                        </h4>
                        <button type="button" id="roomCancelEditBtn" onclick="resetRoomForm()" class="hidden text-xs text-gray-500 hover:text-gray-700 font-medium">
                            <i class="fas fa-undo mr-1"></i> Hủy chế độ sửa
                        </button>
                    </div>

                    <form id="roomForm" action="{{ route('meeting-rooms.rooms.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="_method" id="roomFormMethod" value="POST">

                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Tên phòng họp <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="roomInputName" required placeholder="VD: Phòng họp VIP, Phòng 201..."
                                    class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-purple-500 focus:outline-none bg-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Vị trí / Tầng</label>
                                <input type="text" name="location" id="roomInputLocation" placeholder="VD: Tầng 2, Tòa A..."
                                    class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-purple-500 focus:outline-none bg-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Sức chứa (người)</label>
                                <input type="number" name="capacity" id="roomInputCapacity" min="1" max="500" placeholder="VD: 10"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-purple-500 focus:outline-none bg-white">
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Mô tả / Trang thiết bị sẵn có</label>
                                <input type="text" name="description" id="roomInputDescription" placeholder="VD: Máy chiếu, bảng trắng, micro không dây, TV..."
                                    class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-purple-500 focus:outline-none bg-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Trạng thái</label>
                                <select name="status" id="roomInputStatus" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-purple-500 focus:outline-none bg-white">
                                    <option value="active">Hoạt động</option>
                                    <option value="inactive">Tạm dừng</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-3 flex justify-end gap-2">
                            <button type="submit" id="roomSubmitBtn"
                                class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-lg transition-colors shadow-xs">
                                <i class="fas fa-plus mr-1.5"></i> Thêm phòng họp
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Existing Rooms List -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3 flex items-center justify-between">
                        <span>Danh sách phòng họp hiện có ({{ $allRooms->count() }})</span>
                        <span class="text-[11px] font-normal text-gray-400">Các phòng có trạng thái "Hoạt động" sẽ hiển thị khi nhân viên đặt phòng</span>
                    </h4>

                    @if($allRooms->isEmpty())
                        <div class="text-center py-8 text-gray-400 border border-dashed border-gray-200 rounded-xl">
                            <i class="fas fa-door-open text-3xl mb-2 text-gray-300"></i>
                            <p class="text-xs">Chưa có phòng họp nào. Vui lòng thêm phòng họp ở biểu mẫu trên.</p>
                        </div>
                    @else
                        <div class="border border-gray-200 rounded-xl overflow-hidden shadow-xs">
                            <table class="min-w-full divide-y divide-gray-200 text-xs">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Tên phòng & Thiết bị</th>
                                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Vị trí</th>
                                        <th class="px-4 py-2.5 text-center font-semibold text-gray-600">Sức chứa</th>
                                        <th class="px-4 py-2.5 text-center font-semibold text-gray-600">Trạng thái</th>
                                        <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach($allRooms as $r)
                                        <tr class="hover:bg-gray-50/80 transition-colors">
                                            <td class="px-4 py-3">
                                                <div class="font-bold text-gray-900">{{ $r->name }}</div>
                                                @if($r->description)
                                                    <div class="text-[11px] text-gray-500 mt-0.5"><i class="fas fa-tv text-gray-400 mr-1"></i>{{ $r->description }}</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-gray-600">
                                                {{ $r->location ?: '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-center text-gray-700 font-medium">
                                                {{ $r->capacity ? $r->capacity . ' người' : '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @if($r->status === 'active')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-green-100 text-green-700">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1"></span> Hoạt động
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-gray-100 text-gray-600">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400 mr-1"></span> Tạm dừng
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="inline-flex items-center gap-1">
                                                    <button type="button" onclick="editRoom({{ json_encode($r) }})"
                                                        class="px-2 py-1 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded text-xs font-semibold transition-colors" title="Chỉnh sửa">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form action="{{ route('meeting-rooms.rooms.destroy', $r->id) }}" method="POST"
                                                        onsubmit="return confirm('Bạn có chắc chắn muốn xóa phòng họp &quot;{{ addslashes($r->name) }}&quot;?');" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="px-2 py-1 bg-red-50 hover:bg-red-100 text-red-600 rounded text-xs transition-colors" title="Xóa phòng">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 flex justify-end">
                <button type="button" onclick="closeRoomManagementModal()"
                    class="px-4 py-2 border border-gray-300 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-100 transition-colors">
                    Đóng
                </button>
            </div>
        </div>
    </div>
    @endif
    @endpush

    @push('scripts')
    @if(!empty($canManageRooms))
    <script>
        const roomStoreUrl = "{{ route('meeting-rooms.rooms.store') }}";

        function openRoomManagementModal() {
            const modal = document.getElementById('roomManagementModal');
            if (modal) {
                if (modal.parentElement !== document.body) {
                    document.body.appendChild(modal);
                }
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeRoomManagementModal() {
            const modal = document.getElementById('roomManagementModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            resetRoomForm();
        }

        function editRoom(room) {
            const form = document.getElementById('roomForm');
            const title = document.getElementById('roomFormTitle');
            const submitBtn = document.getElementById('roomSubmitBtn');
            const cancelBtn = document.getElementById('roomCancelEditBtn');
            const methodInput = document.getElementById('roomFormMethod');

            form.action = `/meeting-rooms/manage/rooms/${room.id}`;
            methodInput.value = 'PUT';

            document.getElementById('roomInputName').value = room.name || '';
            document.getElementById('roomInputLocation').value = room.location || '';
            document.getElementById('roomInputCapacity').value = room.capacity || '';
            document.getElementById('roomInputDescription').value = room.description || '';
            document.getElementById('roomInputStatus').value = room.status || 'active';

            title.innerHTML = '<i class="fas fa-edit text-purple-600"></i> Cập nhật phòng: ' + (room.name || '');
            submitBtn.innerHTML = '<i class="fas fa-save mr-1.5"></i> Lưu thay đổi';
            submitBtn.classList.remove('bg-purple-600', 'hover:bg-purple-700');
            submitBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
            cancelBtn.classList.remove('hidden');

            document.getElementById('roomInputName').focus();
        }

        function resetRoomForm() {
            const form = document.getElementById('roomForm');
            if (!form) return;
            form.action = roomStoreUrl;
            document.getElementById('roomFormMethod').value = 'POST';

            document.getElementById('roomInputName').value = '';
            document.getElementById('roomInputLocation').value = '';
            document.getElementById('roomInputCapacity').value = '';
            document.getElementById('roomInputDescription').value = '';
            document.getElementById('roomInputStatus').value = 'active';

            document.getElementById('roomFormTitle').innerHTML = '<i class="fas fa-plus-circle text-purple-600"></i> Thêm phòng họp mới';
            const submitBtn = document.getElementById('roomSubmitBtn');
            submitBtn.innerHTML = '<i class="fas fa-plus mr-1.5"></i> Thêm phòng họp';
            submitBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            submitBtn.classList.add('bg-purple-600', 'hover:bg-purple-700');
            document.getElementById('roomCancelEditBtn').classList.add('hidden');
        }
    </script>
    @endif
    @endpush
</div>
@endsection
