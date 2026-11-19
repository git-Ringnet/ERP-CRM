<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Polling Routes
|--------------------------------------------------------------------------
|
| Các endpoint được JavaScript gọi định kỳ ở nền. Chạy với middleware group
| 'web_polling' (session chỉ-đọc - xem App\Http\Middleware\StartReadOnlySession)
| để một request chậm không thể khôi phục session cũ sau khi người dùng đã
| đăng xuất / đổi tài khoản ở tab khác (gây lỗi 419 Page Expired).
|
| Chỉ đặt ở đây các route GET không cần ghi session.
|
*/

Route::get('/sidebar-badges', function () {
    return response()->json(app(\App\Services\SidebarBadgeService::class)->getBadges(auth()->user()));
})->name('sidebar-badges');

Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
