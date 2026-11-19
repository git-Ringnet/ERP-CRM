<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Số thông báo gần nhất tối đa được kiểm tra quyền cho dropdown chuông.
     */
    private const RECENT_SCAN_LIMIT = 20;
    private const RECENT_DISPLAY_LIMIT = 10;

    /**
     * Hiển thị trang danh sách thông báo
     *
     * Lưu ý hiệu năng: quyền xem đã được kiểm tra khi tạo thông báo
     * (Notification::booted creating hook / NotificationService), nên ở đây
     * phân trang trực tiếp bằng SQL thay vì tải toàn bộ rồi lọc từng bản ghi
     * (mỗi lần isAccessibleBy() tốn >= 1 query). Trang đích vẫn tự chặn quyền.
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $query = Notification::where('user_id', $user->id)->recent();

        if ($filter === 'unread') {
            $query->unread();
        } elseif ($filter === 'read') {
            $query->where('is_read', true);
        }

        $notifications = $query->paginate(20)->withQueryString();

        return view('notifications.index', compact('notifications', 'filter'));
    }

    /**
     * API lấy số thông báo chưa đọc
     */
    public function unreadCount()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['count' => 0]);
        }

        return response()->json(['count' => $this->countUnread($user->id)]);
    }

    /**
     * API lấy 10 thông báo gần nhất
     */
    public function recent()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'notifications' => [],
                'unreadCount' => 0,
            ]);
        }

        // Chỉ kiểm tra quyền trên một số ít bản ghi mới nhất để hiển thị
        $accessibleNotifications = Notification::where('user_id', $user->id)
            ->recent()
            ->limit(self::RECENT_SCAN_LIMIT)
            ->get()
            ->filter(fn($n) => $n->isAccessibleBy($user))
            ->take(self::RECENT_DISPLAY_LIMIT)
            ->values();

        return response()->json([
            'notifications' => $accessibleNotifications,
            'unreadCount' => $this->countUnread($user->id),
        ]);
    }

    /**
     * Đếm thông báo chưa đọc bằng 1 câu COUNT(*) (dùng index user_id, is_read).
     */
    private function countUnread(int $userId): int
    {
        return Notification::where('user_id', $userId)->unread()->count();
    }

    /**
     * Đánh dấu 1 thông báo đã đọc
     */
    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->findOrFail($id);
        
        $notification->markAsRead();
        
        return response()->json([
            'success' => true,
            'message' => 'Đã đánh dấu thông báo là đã đọc',
        ]);
    }

    /**
     * Đánh dấu tất cả thông báo đã đọc
     */
    public function markAllAsRead()
    {
        Notification::markAllAsRead(Auth::id());
        
        return response()->json([
            'success' => true,
            'message' => 'Đã đánh dấu tất cả thông báo là đã đọc',
        ]);
    }
}
