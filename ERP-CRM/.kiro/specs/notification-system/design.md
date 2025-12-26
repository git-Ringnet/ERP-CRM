# Design Document - Notification System

## Architecture Overview

Hệ thống thông báo sẽ được xây dựng với kiến trúc đơn giản, sử dụng:
- **Database**: Bảng `notifications` để lưu trữ thông báo
- **Backend**: Laravel Controller + Service để xử lý logic
- **Frontend**: Blade template + JavaScript (Alpine.js hoặc vanilla JS) + Polling
- **Realtime**: HTTP Polling mỗi 30 giây (không dùng WebSocket để đơn giản)

## Database Design

### Table: notifications

```sql
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL, -- 'import_created', 'export_created', 'transfer_created', 'approved', 'rejected'
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON NULL, -- Lưu thêm thông tin như document_id, document_type, etc.
    link VARCHAR(255) NULL, -- URL để chuyển đến khi click
    icon VARCHAR(50) NULL, -- 'arrow-down', 'arrow-up', 'exchange', 'check', 'times'
    color VARCHAR(50) NULL, -- 'blue', 'orange', 'purple', 'green', 'red'
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Component Design

### 1. Model: Notification

**File**: `app/Models/Notification.php`

```php
class Notification extends Model
{
    protected $fillable = [
        'user_id', 'type', 'title', 'message', 'data', 
        'link', 'icon', 'color', 'is_read', 'read_at'
    ];
    
    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];
    
    // Relationships
    public function user() { return $this->belongsTo(User::class); }
    
    // Scopes
    public function scopeUnread($query) { return $query->where('is_read', false); }
    public function scopeRecent($query) { return $query->orderBy('created_at', 'desc'); }
    
    // Methods
    public function markAsRead() { ... }
    public static function markAllAsRead($userId) { ... }
}
```

### 2. Service: NotificationService

**File**: `app/Services/NotificationService.php`

Chịu trách nhiệm tạo thông báo cho các sự kiện khác nhau.

```php
class NotificationService
{
    // Tạo thông báo khi phiếu nhập kho được tạo
    public function notifyImportCreated($import, $recipientUserIds) { ... }
    
    // Tạo thông báo khi phiếu xuất kho được tạo
    public function notifyExportCreated($export, $recipientUserIds) { ... }
    
    // Tạo thông báo khi phiếu chuyển kho được tạo
    public function notifyTransferCreated($transfer, $recipientUserIds) { ... }
    
    // Tạo thông báo khi phiếu được duyệt
    public function notifyDocumentApproved($document, $documentType, $creatorUserId) { ... }
    
    // Tạo thông báo khi phiếu bị từ chối
    public function notifyDocumentRejected($document, $documentType, $creatorUserId, $reason) { ... }
    
    // Helper: Tạo thông báo chung
    private function createNotification($userId, $type, $title, $message, $link, $icon, $color, $data = []) { ... }
}
```

### 3. Controller: NotificationController

**File**: `app/Http/Controllers/NotificationController.php`

```php
class NotificationController extends Controller
{
    // GET /notifications - Trang danh sách thông báo
    public function index(Request $request) { ... }
    
    // GET /notifications/unread-count - API lấy số thông báo chưa đọc
    public function unreadCount() { ... }
    
    // GET /notifications/recent - API lấy 10 thông báo gần nhất
    public function recent() { ... }
    
    // POST /notifications/{id}/mark-as-read - Đánh dấu 1 thông báo đã đọc
    public function markAsRead($id) { ... }
    
    // POST /notifications/mark-all-as-read - Đánh dấu tất cả đã đọc
    public function markAllAsRead() { ... }
}
```

### 4. Frontend: Notification Bell Component

**File**: `resources/views/layouts/app.blade.php` (thêm vào header)

```html
<!-- Notification Bell -->
<div class="relative" x-data="notificationBell()">
    <!-- Bell Icon with Badge -->
    <button @click="toggleDropdown()" class="relative">
        <i class="fas fa-bell text-xl"></i>
        <span x-show="unreadCount > 0" 
              x-text="unreadCount > 99 ? '99+' : unreadCount"
              class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full px-1.5">
        </span>
    </button>
    
    <!-- Dropdown -->
    <div x-show="isOpen" 
         x-transition
         class="absolute right-0 mt-2 w-96 bg-white shadow-lg rounded-lg">
        <!-- Header -->
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="font-semibold">Thông báo</h3>
            <button @click="markAllAsRead()" 
                    :disabled="unreadCount === 0"
                    class="text-sm text-blue-600">
                Đánh dấu tất cả đã đọc
            </button>
        </div>
        
        <!-- Notification List -->
        <div class="max-h-96 overflow-y-auto">
            <template x-for="notification in notifications" :key="notification.id">
                <a :href="notification.link" 
                   @click="markAsRead(notification.id)"
                   :class="!notification.is_read ? 'bg-blue-50' : ''"
                   class="block p-4 border-b hover:bg-gray-50">
                    <div class="flex items-start">
                        <i :class="getIconClass(notification)" class="mt-1 mr-3"></i>
                        <div class="flex-1">
                            <p class="font-semibold text-sm" x-text="notification.title"></p>
                            <p class="text-sm text-gray-600" x-text="notification.message"></p>
                            <p class="text-xs text-gray-400 mt-1" x-text="formatTime(notification.created_at)"></p>
                        </div>
                    </div>
                </a>
            </template>
        </div>
        
        <!-- Footer -->
        <div class="p-3 text-center border-t">
            <a href="/notifications" class="text-sm text-blue-600">Xem tất cả</a>
        </div>
    </div>
</div>
```

**JavaScript**: `public/js/notification-bell.js`

```javascript
function notificationBell() {
    return {
        isOpen: false,
        unreadCount: 0,
        notifications: [],
        
        init() {
            this.fetchNotifications();
            // Polling mỗi 30 giây
            setInterval(() => this.fetchNotifications(), 30000);
        },
        
        toggleDropdown() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.fetchNotifications();
            }
        },
        
        async fetchNotifications() {
            const response = await fetch('/notifications/recent');
            const data = await response.json();
            this.notifications = data.notifications;
            this.unreadCount = data.unreadCount;
        },
        
        async markAsRead(notificationId) {
            await fetch(`/notifications/${notificationId}/mark-as-read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            this.unreadCount = Math.max(0, this.unreadCount - 1);
            const notification = this.notifications.find(n => n.id === notificationId);
            if (notification) notification.is_read = true;
        },
        
        async markAllAsRead() {
            await fetch('/notifications/mark-all-as-read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            this.unreadCount = 0;
            this.notifications.forEach(n => n.is_read = true);
        },
        
        getIconClass(notification) {
            const iconMap = {
                'arrow-down': 'fas fa-arrow-down text-blue-500',
                'arrow-up': 'fas fa-arrow-up text-orange-500',
                'exchange': 'fas fa-exchange-alt text-purple-500',
                'check': 'fas fa-check text-green-500',
                'times': 'fas fa-times text-red-500'
            };
            return iconMap[notification.icon] || 'fas fa-bell text-gray-500';
        },
        
        formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000); // seconds
            
            if (diff < 60) return 'Vừa xong';
            if (diff < 3600) return Math.floor(diff / 60) + ' phút trước';
            if (diff < 86400) return Math.floor(diff / 3600) + ' giờ trước';
            return Math.floor(diff / 86400) + ' ngày trước';
        }
    }
}
```

### 5. View: Notifications Index Page

**File**: `resources/views/notifications/index.blade.php`

Trang danh sách tất cả thông báo với filter và phân trang.

```blade
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6">Tất cả thông báo</h1>
    
    <!-- Filter -->
    <div class="mb-4 flex gap-2">
        <a href="?filter=all" class="px-4 py-2 rounded {{ $filter === 'all' ? 'bg-blue-500 text-white' : 'bg-gray-200' }}">
            Tất cả
        </a>
        <a href="?filter=unread" class="px-4 py-2 rounded {{ $filter === 'unread' ? 'bg-blue-500 text-white' : 'bg-gray-200' }}">
            Chưa đọc
        </a>
        <a href="?filter=read" class="px-4 py-2 rounded {{ $filter === 'read' ? 'bg-blue-500 text-white' : 'bg-gray-200' }}">
            Đã đọc
        </a>
    </div>
    
    <!-- Notification List -->
    <div class="bg-white rounded-lg shadow">
        @foreach($notifications as $notification)
        <a href="{{ $notification->link }}" 
           onclick="markAsRead({{ $notification->id }})"
           class="block p-4 border-b hover:bg-gray-50 {{ !$notification->is_read ? 'bg-blue-50' : '' }}">
            <div class="flex items-start">
                <i class="{{ getIconClass($notification) }} mt-1 mr-3"></i>
                <div class="flex-1">
                    <p class="font-semibold">{{ $notification->title }}</p>
                    <p class="text-sm text-gray-600">{{ $notification->message }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
            </div>
        </a>
        @endforeach
    </div>
    
    <!-- Pagination -->
    <div class="mt-4">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
```

## Integration Points

### 1. ImportController

Sau khi tạo phiếu nhập kho thành công:

```php
public function store(Request $request)
{
    // ... existing code ...
    
    // Tạo thông báo cho người quản lý kho
    $warehouseManagerIds = User::where('role', 'warehouse_manager')->pluck('id');
    app(NotificationService::class)->notifyImportCreated($import, $warehouseManagerIds);
    
    return redirect()->route('imports.show', $import);
}

public function approve($id)
{
    // ... existing code ...
    
    // Tạo thông báo cho người tạo phiếu
    app(NotificationService::class)->notifyDocumentApproved($import, 'import', $import->created_by);
    
    return response()->json(['success' => true, 'message' => 'Phiếu nhập đã được duyệt']);
}

public function reject(Request $request, $id)
{
    // ... existing code ...
    
    // Tạo thông báo cho người tạo phiếu
    app(NotificationService::class)->notifyDocumentRejected($import, 'import', $import->created_by, $request->reason);
    
    return response()->json(['success' => true, 'message' => 'Phiếu nhập đã bị từ chối']);
}
```

### 2. ExportController

Tương tự như ImportController, thêm notification sau khi:
- Tạo phiếu xuất kho mới
- Duyệt phiếu xuất kho
- Từ chối phiếu xuất kho

### 3. TransferController

Tương tự, thêm notification sau khi:
- Tạo phiếu chuyển kho mới (thông báo cho cả kho nguồn và kho đích)
- Duyệt phiếu chuyển kho
- Từ chối phiếu chuyển kho

## Routes

**File**: `routes/web.php`

```php
// Notification routes
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');
});
```

## UI/UX Considerations

### Icon và Màu sắc

| Loại thông báo | Icon | Màu sắc | Class |
|----------------|------|---------|-------|
| Phiếu nhập kho | arrow-down | Xanh dương | `fas fa-arrow-down text-blue-500` |
| Phiếu xuất kho | arrow-up | Cam | `fas fa-arrow-up text-orange-500` |
| Phiếu chuyển kho | exchange | Tím | `fas fa-exchange-alt text-purple-500` |
| Duyệt phiếu | check | Xanh lá | `fas fa-check text-green-500` |
| Từ chối phiếu | times | Đỏ | `fas fa-times text-red-500` |

### Animation

- Badge nhấp nháy khi có thông báo mới: `animate-pulse`
- Dropdown slide down: `x-transition`
- Highlight thông báo chưa đọc: `bg-blue-50`

### Responsive

- Desktop: Dropdown width 384px (w-96)
- Mobile: Dropdown full width với padding
- Max height dropdown: 384px với scroll

## Performance Considerations

1. **Polling Interval**: 30 giây để cân bằng giữa realtime và server load
2. **Pagination**: 20 thông báo/trang để tránh query quá lớn
3. **Index**: Đánh index trên `user_id`, `is_read`, `created_at` để tăng tốc query
4. **Caching**: Có thể cache unread count trong Redis nếu cần (optional)
5. **Soft Delete**: Không xóa thông báo cũ, chỉ filter theo thời gian nếu cần

## Security Considerations

1. **Authorization**: Chỉ user được phép xem thông báo của chính mình
2. **CSRF Protection**: Tất cả POST request phải có CSRF token
3. **XSS Prevention**: Escape tất cả user input trong notification message
4. **Rate Limiting**: Giới hạn số lần gọi API polling để tránh abuse

## Testing Strategy

1. **Unit Tests**: Test NotificationService methods
2. **Feature Tests**: Test NotificationController endpoints
3. **Browser Tests**: Test notification bell UI interaction
4. **Integration Tests**: Test notification được tạo khi approve/reject phiếu

## Future Enhancements

1. **WebSocket**: Thay thế polling bằng WebSocket để realtime thực sự
2. **Push Notifications**: Thông báo trên browser/mobile app
3. **Email Notifications**: Gửi email cho thông báo quan trọng
4. **Notification Preferences**: User tự chọn loại thông báo muốn nhận
5. **Notification Templates**: Template engine để customize message
