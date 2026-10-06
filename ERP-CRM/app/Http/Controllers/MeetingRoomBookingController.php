<?php

namespace App\Http\Controllers;

use App\Models\MeetingRoomBooking;
use App\Models\MeetingRoomAttendee;
use App\Models\MeetingRoom;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MeetingRoomBookingController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $date = $request->input('date', date('Y-m-d'));
        $room = $request->input('room');

        $query = MeetingRoomBooking::with(['creator', 'attendees.user'])
            ->where('status', 'confirmed')
            ->whereDate('start_time', $date)
            ->orderBy('start_time');

        if ($room) {
            $query->where('room_name', $room);
        }

        $bookings = $query->get();

        // Also fetch user's upcoming invitations (accepted / pending)
        $myInvitations = MeetingRoomBooking::whereHas('attendees', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->where('status', 'confirmed')
        ->where('start_time', '>=', now())
        ->orderBy('start_time')
        ->take(5)
        ->get();

        $rooms = MeetingRoom::where('status', 'active')->orderBy('name')->pluck('name')->toArray();
        if (empty($rooms)) {
            $rooms = MeetingRoomBooking::getRoomNames();
        }
        $allRooms = MeetingRoom::orderBy('name')->get();
        $canManageRooms = $user->can('manage_meeting_rooms') || $user->hasAnyRole(['super_admin', 'admin', 'director']);

        return view('meeting-rooms.index', compact('bookings', 'date', 'room', 'rooms', 'allRooms', 'canManageRooms', 'myInvitations'));
    }

    public function create()
    {
        $user = auth()->user();
        $rooms = MeetingRoom::where('status', 'active')->orderBy('name')->get();
        $canManageRooms = $user->can('manage_meeting_rooms') || $user->hasAnyRole(['super_admin', 'admin', 'director']);
        $users = User::where('status', 'active')->orderBy('name')->get();

        return view('meeting-rooms.create', compact('rooms', 'users', 'canManageRooms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'booking_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'attendee_ids' => 'nullable|array',
            'attendee_ids.*' => 'exists:users,id',
        ], [
            'room_name.required' => 'Vui lòng chọn phòng họp.',
            'title.required' => 'Vui lòng nhập tiêu đề/nội dung cuộc họp.',
            'booking_date.required' => 'Vui lòng chọn ngày họp.',
            'start_time.required' => 'Vui lòng chọn giờ bắt đầu.',
            'end_time.required' => 'Vui lòng chọn giờ kết thúc.',
        ]);

        $startDateTime = $validated['booking_date'] . ' ' . $validated['start_time'];
        $endDateTime = $validated['booking_date'] . ' ' . $validated['end_time'];

        if (strtotime($endDateTime) <= strtotime($startDateTime)) {
            return back()->withInput()->withErrors([
                'end_time' => 'Giờ kết thúc phải sau giờ bắt đầu cuộc họp.'
            ]);
        }

        // Check for room schedule collision
        $hasOverlap = MeetingRoomBooking::where('room_name', $validated['room_name'])
            ->where('status', 'confirmed')
            ->where(function ($q) use ($startDateTime, $endDateTime) {
                $q->where(function ($sub) use ($startDateTime, $endDateTime) {
                    $sub->where('start_time', '<', $endDateTime)
                        ->where('end_time', '>', $startDateTime);
                });
            })
            ->exists();

        if ($hasOverlap) {
            return back()->withInput()->withErrors([
                'room_name' => 'Phòng họp này đã có người đặt trong khoảng thời gian bạn chọn. Vui lòng chọn khung giờ khác hoặc phòng họp khác.'
            ]);
        }

        DB::beginTransaction();
        try {
            $roomModel = MeetingRoom::where('name', $validated['room_name'])->first();

            $booking = MeetingRoomBooking::create([
                'meeting_room_id' => $roomModel?->id,
                'room_name' => $validated['room_name'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'start_time' => $startDateTime,
                'end_time' => $endDateTime,
                'created_by' => auth()->id(),
                'status' => 'confirmed',
                'is_private' => true,
            ]);

            $attendeeIds = $request->input('attendee_ids', []);
            foreach ($attendeeIds as $userId) {
                if ($userId == auth()->id()) continue; // Creator is owner
                MeetingRoomAttendee::create([
                    'meeting_room_booking_id' => $booking->id,
                    'user_id' => $userId,
                    'status' => 'pending',
                ]);

                // Send notification to invited attendee
                Notification::create([
                    'user_id' => $userId,
                    'type' => 'meeting_invitation',
                    'title' => 'Lời mời họp phòng ' . $booking->room_name,
                    'message' => auth()->user()->name . ' đã mời bạn tham gia cuộc họp: ' . $booking->title . ' vào lúc ' . date('H:i d/m/Y', strtotime($startDateTime)),
                    'link' => route('meeting-rooms.show', $booking->id),
                    'icon' => 'fas fa-door-open',
                    'color' => 'purple',
                ]);
            }

            DB::commit();

            return redirect()->route('meeting-rooms.index', ['date' => $validated['booking_date']])
                ->with('success', 'Đã đặt phòng họp thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['general' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    public function show(MeetingRoomBooking $meeting_room)
    {
        $booking = $meeting_room->load(['creator', 'attendees.user']);
        $user = auth()->user();
        $canViewDetails = $booking->canViewDetails($user);

        $myAttendance = $booking->attendees()->where('user_id', $user->id)->first();

        return view('meeting-rooms.show', compact('booking', 'canViewDetails', 'myAttendance'));
    }

    public function edit(MeetingRoomBooking $meeting_room)
    {
        $booking = $meeting_room->load(['creator', 'attendees']);
        $user = auth()->user();

        if ($booking->created_by !== $user->id && !$user->hasAnyRole(['super_admin', 'admin', 'director'])) {
            abort(403, 'Bạn không có quyền chỉnh sửa lịch đặt phòng họp này.');
        }

        $rooms = MeetingRoom::where('status', 'active')->orderBy('name')->get();
        if ($rooms->isEmpty()) {
            $rooms = MeetingRoomBooking::getRoomNames();
        }
        $canManageRooms = $user->can('manage_meeting_rooms') || $user->hasAnyRole(['super_admin', 'admin', 'director']);
        $users = User::where('status', 'active')->orderBy('name')->get();
        $selectedAttendeeIds = $booking->attendees->pluck('user_id')->toArray();

        return view('meeting-rooms.edit', compact('booking', 'rooms', 'users', 'canManageRooms', 'selectedAttendeeIds'));
    }

    public function update(Request $request, MeetingRoomBooking $meeting_room)
    {
        $user = auth()->user();
        if ($meeting_room->created_by !== $user->id && !$user->hasAnyRole(['super_admin', 'admin', 'director'])) {
            abort(403, 'Bạn không có quyền chỉnh sửa lịch đặt phòng họp này.');
        }

        $validated = $request->validate([
            'room_name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'booking_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'attendee_ids' => 'nullable|array',
            'attendee_ids.*' => 'exists:users,id',
        ], [
            'room_name.required' => 'Vui lòng chọn phòng họp.',
            'title.required' => 'Vui lòng nhập tiêu đề/nội dung cuộc họp.',
            'booking_date.required' => 'Vui lòng chọn ngày họp.',
            'start_time.required' => 'Vui lòng chọn giờ bắt đầu.',
            'end_time.required' => 'Vui lòng chọn giờ kết thúc.',
        ]);

        $startDateTime = $validated['booking_date'] . ' ' . $validated['start_time'];
        $endDateTime = $validated['booking_date'] . ' ' . $validated['end_time'];

        if (strtotime($endDateTime) <= strtotime($startDateTime)) {
            return back()->withInput()->withErrors([
                'end_time' => 'Giờ kết thúc phải sau giờ bắt đầu cuộc họp.'
            ]);
        }

        // Check for room schedule collision (excluding current booking)
        $hasOverlap = MeetingRoomBooking::where('room_name', $validated['room_name'])
            ->where('id', '!=', $meeting_room->id)
            ->where('status', 'confirmed')
            ->where(function ($q) use ($startDateTime, $endDateTime) {
                $q->where(function ($sub) use ($startDateTime, $endDateTime) {
                    $sub->where('start_time', '<', $endDateTime)
                        ->where('end_time', '>', $startDateTime);
                });
            })
            ->exists();

        if ($hasOverlap) {
            return back()->withInput()->withErrors([
                'room_name' => 'Phòng họp này đã có người đặt trong khoảng thời gian bạn chọn. Vui lòng chọn khung giờ khác hoặc phòng họp khác.'
            ]);
        }

        DB::beginTransaction();
        try {
            $roomModel = MeetingRoom::where('name', $validated['room_name'])->first();

            $meeting_room->update([
                'meeting_room_id' => $roomModel?->id,
                'room_name' => $validated['room_name'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'start_time' => $startDateTime,
                'end_time' => $endDateTime,
            ]);

            $newAttendeeIds = array_map('intval', $request->input('attendee_ids', []));
            $newAttendeeIds = array_values(array_filter($newAttendeeIds, fn($id) => $id !== $meeting_room->created_by));

            $currentAttendees = $meeting_room->attendees()->get();
            $currentAttendeeUserIds = $currentAttendees->pluck('user_id')->toArray();

            // Delete attendees removed from list
            $toDelete = array_diff($currentAttendeeUserIds, $newAttendeeIds);
            if (!empty($toDelete)) {
                $meeting_room->attendees()->whereIn('user_id', $toDelete)->delete();
            }

            // Add new attendees and notify them
            $toAdd = array_diff($newAttendeeIds, $currentAttendeeUserIds);
            foreach ($toAdd as $userId) {
                MeetingRoomAttendee::create([
                    'meeting_room_booking_id' => $meeting_room->id,
                    'user_id' => $userId,
                    'status' => 'pending',
                ]);

                Notification::create([
                    'user_id' => $userId,
                    'type' => 'meeting_invitation',
                    'title' => 'Lời mời họp phòng ' . $meeting_room->room_name,
                    'message' => auth()->user()->name . ' đã mời bạn tham gia cuộc họp: ' . $meeting_room->title . ' vào lúc ' . date('H:i d/m/Y', strtotime($startDateTime)),
                    'link' => route('meeting-rooms.show', $meeting_room->id),
                    'icon' => 'fas fa-door-open',
                    'color' => 'purple',
                ]);
            }

            DB::commit();

            return redirect()->route('meeting-rooms.show', $meeting_room->id)
                ->with('success', 'Đã cập nhật thông tin phòng họp thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['general' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }


    public function respond(Request $request, MeetingRoomBooking $booking)
    {
        $user = auth()->user();
        $attendee = $booking->attendees()->where('user_id', $user->id)->firstOrFail();

        $action = $request->input('response'); // accepted or declined
        if (!in_array($action, ['accepted', 'declined'])) {
            return back()->with('error', 'Phản hồi không hợp lệ.');
        }

        $attendee->update([
            'status' => $action,
            'note' => $request->input('note'),
            'responded_at' => now(),
        ]);

        $statusText = ($action === 'accepted') ? 'đồng ý tham gia' : 'từ chối tham gia';

        // Notify creator
        Notification::create([
            'user_id' => $booking->created_by,
            'type' => 'meeting_response',
            'title' => 'Phản hồi lịch họp: ' . $booking->title,
            'message' => $user->name . ' đã ' . $statusText . ' cuộc họp lúc ' . $booking->start_time->format('H:i d/m/Y'),
            'link' => route('meeting-rooms.show', $booking->id),
            'icon' => $action === 'accepted' ? 'fas fa-check-circle' : 'fas fa-times-circle',
            'color' => $action === 'accepted' ? 'green' : 'red',
        ]);

        return back()->with('success', 'Đã gửi phản hồi lịch họp: ' . ($action === 'accepted' ? 'Sẽ tham gia' : 'Không tham gia'));
    }

    public function destroy(MeetingRoomBooking $meeting_room)
    {
        $user = auth()->user();
        if ($meeting_room->created_by !== $user->id && !$user->hasAnyRole(['super_admin', 'admin', 'director'])) {
            abort(403, 'Bạn không có quyền hủy lịch đặt phòng họp này.');
        }

        $meeting_room->update(['status' => 'cancelled']);

        return redirect()->route('meeting-rooms.index')
            ->with('success', 'Đã hủy lịch đặt phòng họp thành công.');
    }

    // =========================================================================
    // Room Management Actions (Thêm / Sửa / Xóa phòng họp)
    // =========================================================================

    public function storeRoom(Request $request)
    {
        $user = auth()->user();
        if (!$user->can('manage_meeting_rooms') && !$user->hasAnyRole(['super_admin', 'admin', 'director'])) {
            abort(403, 'Bạn không có quyền quản lý danh mục phòng họp.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:meeting_rooms,name',
            'location' => 'nullable|string|max:100',
            'capacity' => 'nullable|integer|min:1|max:500',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
        ], [
            'name.required' => 'Vui lòng nhập tên phòng họp.',
            'name.unique' => 'Tên phòng họp này đã tồn tại.',
            'capacity.integer' => 'Sức chứa phải là số nguyên.',
        ]);

        MeetingRoom::create($validated);

        return back()->with('success', 'Đã thêm phòng họp "' . $validated['name'] . '" thành công!');
    }

    public function updateRoom(Request $request, MeetingRoom $room)
    {
        $user = auth()->user();
        if (!$user->can('manage_meeting_rooms') && !$user->hasAnyRole(['super_admin', 'admin', 'director'])) {
            abort(403, 'Bạn không có quyền quản lý danh mục phòng họp.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:meeting_rooms,name,' . $room->id,
            'location' => 'nullable|string|max:100',
            'capacity' => 'nullable|integer|min:1|max:500',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
        ], [
            'name.required' => 'Vui lòng nhập tên phòng họp.',
            'name.unique' => 'Tên phòng họp này đã trùng với phòng khác.',
        ]);

        $oldName = $room->name;
        $room->update($validated);

        // Update room_name in future bookings if name changed
        if ($oldName !== $validated['name']) {
            MeetingRoomBooking::where('meeting_room_id', $room->id)
                ->where('start_time', '>=', now())
                ->update(['room_name' => $validated['name']]);
        }

        return back()->with('success', 'Cập nhật thông tin phòng họp "' . $validated['name'] . '" thành công!');
    }

    public function destroyRoom(MeetingRoom $room)
    {
        $user = auth()->user();
        if (!$user->can('manage_meeting_rooms') && !$user->hasAnyRole(['super_admin', 'admin', 'director'])) {
            abort(403, 'Bạn không có quyền quản lý danh mục phòng họp.');
        }

        // Check active upcoming bookings
        $hasActiveBookings = MeetingRoomBooking::where(function ($q) use ($room) {
                $q->where('meeting_room_id', $room->id)
                  ->orWhere('room_name', $room->name);
            })
            ->where('status', 'confirmed')
            ->where('start_time', '>=', now())
            ->exists();

        if ($hasActiveBookings) {
            return back()->withErrors(['general' => 'Không thể xóa phòng họp này vì đang có lịch đặt sắp diễn ra. Bạn có thể chỉnh trạng thái sang "Tạm dừng".']);
        }

        $roomName = $room->name;
        $room->delete();

        return back()->with('success', 'Đã xóa phòng họp "' . $roomName . '" thành công.');
    }
}
