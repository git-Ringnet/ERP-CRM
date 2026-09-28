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
        
        $allNotifications = $query->get();
        $accessibleNotifications = $allNotifications->filter(fn($n) => $n->isAccessibleBy($user))->values();
        
        $perPage = 20;
        $page = (int) $request->get('page', 1);
        $paginatedItems = $accessibleNotifications->slice(($page - 1) * $perPage, $perPage)->all();
        
        $notifications = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $accessibleNotifications->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
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

        $unreadNotifications = Notification::where('user_id', $user->id)
            ->unread()
            ->get();

        $count = $unreadNotifications->filter(fn($n) => $n->isAccessibleBy($user))->count();
        
        return response()->json(['count' => $count]);
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

        $notifications = Notification::where('user_id', $user->id)
            ->recent()
            ->limit(50)
            ->get();
        
        $accessibleNotifications = $notifications->filter(fn($n) => $n->isAccessibleBy($user))
            ->values()
            ->slice(0, 10);
        
        $unreadCount = Notification::where('user_id', $user->id)
            ->unread()
            ->get()
            ->filter(fn($n) => $n->isAccessibleBy($user))
            ->count();
        
        return response()->json([
            'notifications' => $accessibleNotifications,
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
