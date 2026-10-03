<?php

namespace App\Http\Controllers;

use App\Models\MeetingRoomBooking;
use App\Models\MeetingRoomAttendee;
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

        $rooms = MeetingRoomBooking::ROOMS;

        return view('meeting-rooms.index', compact('bookings', 'date', 'room', 'rooms', 'myInvitations'));
    }

    public function create()
    {
        $rooms = MeetingRoomBooking::ROOMS;
        $users = User::where('status', 'active')->orderBy('name')->get();

        return view('meeting-rooms.create', compact('rooms', 'users'));
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
            $booking = MeetingRoomBooking::create([
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
}
