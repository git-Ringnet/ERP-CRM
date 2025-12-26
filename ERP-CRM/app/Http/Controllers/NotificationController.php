<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Hiển thị trang danh sách thông báo
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $userId = Auth::id();
        
        $query = Notification::where('user_id', $userId)->recent();
        
        if ($filter === 'unread') {
            $query->unread();
        } elseif ($filter === 'read') {
            $query->where('is_read', true);
        }
        
        $notifications = $query->paginate(20);
        
        return view('notifications.index', compact('notifications', 'filter'));
    }

    /**
     * API lấy số thông báo chưa đọc
     */
    public function unreadCount()
    {
        $count = Notification::where('user_id', Auth::id())
            ->unread()
            ->count();
        
        return response()->json(['count' => $count]);
    }

    /**
     * API lấy 10 thông báo gần nhất
     */
    public function recent()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->recent()
            ->limit(10)
            ->get();
        
        $unreadCount = Notification::where('user_id', Auth::id())
            ->unread()
            ->count();
        
        return response()->json([
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
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
