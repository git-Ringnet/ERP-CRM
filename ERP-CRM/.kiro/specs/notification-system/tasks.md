# Tasks Document - Notification System

## Task 1: Tạo Migration và Model cho Notifications

**Requirements**: REQ-1, REQ-2, REQ-3, REQ-4, REQ-5, REQ-6, REQ-7, REQ-8, REQ-9

**Description**: Tạo bảng `notifications` trong database và Model `Notification` với các relationships, scopes, và methods cần thiết.

**Files to create/modify**:
- `database/migrations/YYYY_MM_DD_HHMMSS_create_notifications_table.php`
- `app/Models/Notification.php`

**Acceptance Criteria**:
- Migration tạo bảng `notifications` với đầy đủ columns: id, user_id, type, title, message, data, link, icon, color, is_read, read_at, timestamps
- Foreign key constraint với bảng `users` (ON DELETE CASCADE)
- Index trên `user_id`, `is_read`, `created_at`
- Model có fillable, casts, relationships, scopes (unread, recent)
- Model có methods: `markAsRead()`, `markAllAsRead($userId)`

---

## Task 2: Tạo NotificationService

**Requirements**: REQ-5, REQ-6, REQ-9

**Description**: Tạo service class để xử lý logic tạo thông báo cho các sự kiện khác nhau.

**Files to create/modify**:
- `app/Services/NotificationService.php`

**Acceptance Criteria**:
- Method `notifyImportCreated($import, $recipientUserIds)` - tạo thông báo phiếu nhập kho mới
- Method `notifyExportCreated($export, $recipientUserIds)` - tạo thông báo phiếu xuất kho mới
- Method `notifyTransferCreated($transfer, $recipientUserIds)` - tạo thông báo phiếu chuyển kho mới
- Method `notifyDocumentApproved($document, $documentType, $creatorUserId)` - tạo thông báo duyệt phiếu
- Method `notifyDocumentRejected($document, $documentType, $creatorUserId, $reason)` - tạo thông báo từ chối phiếu
- Helper method `createNotification()` để tạo notification với đầy đủ thông tin
- Icon và màu sắc đúng theo từng loại thông báo

---

## Task 3: Tạo NotificationController

**Requirements**: REQ-1, REQ-2, REQ-3, REQ-4, REQ-7, REQ-8

**Description**: Tạo controller để xử lý các API endpoints cho notification system.

**Files to create/modify**:
- `app/Http/Controllers/NotificationController.php`

**Acceptance Criteria**:
- Method `index()` - hiển thị trang danh sách thông báo với filter (all/unread/read) và pagination (20/page)
- Method `unreadCount()` - API trả về số thông báo chưa đọc
- Method `recent()` - API trả về 10 thông báo gần nhất
- Method `markAsRead($id)` - API đánh dấu 1 thông báo đã đọc
- Method `markAllAsRead()` - API đánh dấu tất cả thông báo đã đọc
- Authorization: chỉ user được phép xem/update thông báo của chính mình
- Return JSON response cho các API endpoints

---

## Task 4: Thêm Routes cho Notification

**Requirements**: REQ-1, REQ-2, REQ-3, REQ-4, REQ-7

**Description**: Thêm routes cho notification system vào `routes/web.php`.

**Files to create/modify**:
- `routes/web.php`

**Acceptance Criteria**:
- Route `GET /notifications` - trang danh sách thông báo
- Route `GET /notifications/unread-count` - API lấy số thông báo chưa đọc
- Route `GET /notifications/recent` - API lấy thông báo gần nhất
- Route `POST /notifications/{id}/mark-as-read` - API đánh dấu đã đọc
- Route `POST /notifications/mark-all-as-read` - API đánh dấu tất cả đã đọc
- Tất cả routes đều có middleware `auth`

---

## Task 5: Tạo Notification Bell Component trong Header

**Requirements**: REQ-1, REQ-2, REQ-3, REQ-4, REQ-8

**Description**: Thêm notification bell icon với badge và dropdown vào header của `app.blade.php`.

**Files to create/modify**:
- `resources/views/layouts/app.blade.php`

**Acceptance Criteria**:
- Icon chuông hiển thị ở header (bên cạnh user menu)
- Badge màu đỏ hiển thị số thông báo chưa đọc (ẩn nếu = 0)
- Badge hiển thị "99+" nếu > 99
- Click icon để toggle dropdown
- Dropdown hiển thị 10 thông báo gần nhất
- Mỗi thông báo có icon, title, message, time
- Thông báo chưa đọc có background xanh nhạt (bg-blue-50)
- Nút "Đánh dấu tất cả đã đọc" ở header dropdown (disable nếu không có unread)
- Link "Xem tất cả" ở footer dropdown

---

## Task 6: Tạo JavaScript cho Notification Bell

**Requirements**: REQ-1, REQ-2, REQ-3, REQ-4, REQ-8

**Description**: Tạo JavaScript logic cho notification bell với Alpine.js hoặc vanilla JS.

**Files to create/modify**:
- `public/js/notification-bell.js`
- `resources/views/layouts/app.blade.php` (include script)

**Acceptance Criteria**:
- Function `notificationBell()` với Alpine.js data component
- Property: `isOpen`, `unreadCount`, `notifications`
- Method `init()` - fetch notifications và setup polling 30s
- Method `toggleDropdown()` - mở/đóng dropdown
- Method `fetchNotifications()` - gọi API lấy thông báo
- Method `markAsRead(id)` - gọi API đánh dấu đã đọc và update UI
- Method `markAllAsRead()` - gọi API đánh dấu tất cả và update UI
- Method `getIconClass(notification)` - trả về icon class theo loại
- Method `formatTime(timestamp)` - format thời gian (vừa xong, X phút trước, X giờ trước, X ngày trước)
- Polling tự động mỗi 30 giây
- CSRF token trong POST requests

---

## Task 7: Tạo View cho Trang Danh Sách Thông Báo

**Requirements**: REQ-7

**Description**: Tạo view blade template cho trang "Tất cả thông báo" với filter và pagination.

**Files to create/modify**:
- `resources/views/notifications/index.blade.php`

**Acceptance Criteria**:
- Layout extends `layouts.app`
- Title "Tất cả thông báo"
- Filter buttons: Tất cả / Chưa đọc / Đã đọc
- Danh sách thông báo với icon, title, message, time
- Thông báo chưa đọc có background xanh nhạt
- Click vào thông báo để đánh dấu đã đọc và chuyển trang
- Pagination links (20 thông báo/trang)
- Responsive design

---

## Task 8: Integrate Notification vào ImportController

**Requirements**: REQ-5, REQ-6

**Description**: Thêm logic tạo thông báo vào ImportController khi tạo/duyệt/từ chối phiếu nhập kho.

**Files to create/modify**:
- `app/Http/Controllers/ImportController.php`

**Acceptance Criteria**:
- Method `store()` - sau khi tạo phiếu thành công, gọi `NotificationService::notifyImportCreated()`
- Method `approve()` - sau khi duyệt phiếu thành công, gọi `NotificationService::notifyDocumentApproved()`
- Method `reject()` - sau khi từ chối phiếu thành công, gọi `NotificationService::notifyDocumentRejected()`
- Thông báo gửi đến đúng người (người quản lý kho khi tạo, người tạo phiếu khi duyệt/từ chối)

---

## Task 9: Integrate Notification vào ExportController

**Requirements**: REQ-5, REQ-6

**Description**: Thêm logic tạo thông báo vào ExportController khi tạo/duyệt/từ chối phiếu xuất kho.

**Files to create/modify**:
- `app/Http/Controllers/ExportController.php`

**Acceptance Criteria**:
- Method `store()` - sau khi tạo phiếu thành công, gọi `NotificationService::notifyExportCreated()`
- Method `approve()` - sau khi duyệt phiếu thành công, gọi `NotificationService::notifyDocumentApproved()`
- Method `reject()` - sau khi từ chối phiếu thành công, gọi `NotificationService::notifyDocumentRejected()`
- Thông báo gửi đến đúng người

---

## Task 10: Integrate Notification vào TransferController

**Requirements**: REQ-5, REQ-6

**Description**: Thêm logic tạo thông báo vào TransferController khi tạo/duyệt/từ chối phiếu chuyển kho.

**Files to create/modify**:
- `app/Http/Controllers/TransferController.php`

**Acceptance Criteria**:
- Method `store()` - sau khi tạo phiếu thành công, gọi `NotificationService::notifyTransferCreated()`
- Method `approve()` - sau khi duyệt phiếu thành công, gọi `NotificationService::notifyDocumentApproved()`
- Method `reject()` - sau khi từ chối phiếu thành công, gọi `NotificationService::notifyDocumentRejected()`
- Thông báo gửi đến cả người quản lý kho nguồn và kho đích khi tạo phiếu

---

## Task 11: Run Migration và Test Database

**Requirements**: REQ-1

**Description**: Chạy migration để tạo bảng notifications và test insert/query.

**Files to create/modify**:
- N/A (command line)

**Acceptance Criteria**:
- Chạy `php artisan migrate` thành công
- Bảng `notifications` được tạo với đầy đủ columns và indexes
- Foreign key constraint hoạt động đúng
- Test insert và query thông báo

---

## Task 12: Testing và Bug Fixes

**Requirements**: ALL

**Description**: Test toàn bộ notification system và fix bugs nếu có.

**Files to create/modify**:
- Various files (bug fixes)

**Acceptance Criteria**:
- Test tạo phiếu nhập/xuất/chuyển kho → thông báo được tạo
- Test duyệt/từ chối phiếu → thông báo được tạo
- Test click vào thông báo → đánh dấu đã đọc và chuyển trang
- Test "Đánh dấu tất cả đã đọc" → tất cả thông báo được đánh dấu
- Test polling → thông báo tự động cập nhật mỗi 30s
- Test filter và pagination trong trang "Tất cả thông báo"
- Test responsive design trên mobile
- Fix bugs nếu có

---

## Implementation Order

1. Task 1: Migration và Model
2. Task 2: NotificationService
3. Task 3: NotificationController
4. Task 4: Routes
5. Task 5: Notification Bell UI
6. Task 6: JavaScript Logic
7. Task 7: Notifications Index Page
8. Task 8: Integrate ImportController
9. Task 9: Integrate ExportController
10. Task 10: Integrate TransferController
11. Task 11: Run Migration
12. Task 12: Testing và Bug Fixes

**Estimated Time**: 4-6 hours
