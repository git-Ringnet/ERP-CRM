# Requirements Document - Notification System

## Introduction

Hệ thống Mini ERP cần một hệ thống thông báo realtime để thông báo cho người dùng về các sự kiện quan trọng như: phiếu nhập/xuất/chuyển kho được tạo, phiếu được duyệt, phiếu bị từ chối, hàng sắp hết hạn bảo hành, v.v. Thông báo sẽ hiển thị qua icon chuông ở header với badge đếm số thông báo chưa đọc, và dropdown list khi click vào.

## Glossary

- **Notification**: Thông báo về một sự kiện trong hệ thống
- **Notification Bell**: Icon chuông thông báo ở header
- **Badge**: Số đếm thông báo chưa đọc hiển thị trên icon chuông
- **Dropdown**: Menu thả xuống hiển thị danh sách thông báo
- **Read/Unread**: Trạng thái đã đọc/chưa đọc của thông báo
- **Realtime**: Cập nhật tức thời không cần reload trang

## Requirements

### Requirement 1

**User Story:** Là người dùng, tôi muốn thấy icon chuông thông báo với số đếm thông báo chưa đọc, để biết có bao nhiêu thông báo mới.

#### Acceptance Criteria

1. WHEN người dùng đăng nhập THEN hệ thống SHALL hiển thị icon chuông thông báo ở header
2. WHEN có thông báo chưa đọc THEN hệ thống SHALL hiển thị badge màu đỏ với số đếm trên icon chuông
3. WHEN không có thông báo chưa đọc THEN hệ thống SHALL ẩn badge
4. WHEN số thông báo chưa đọc > 99 THEN hệ thống SHALL hiển thị "99+"
5. WHEN có thông báo mới THEN badge SHALL tự động cập nhật số đếm

### Requirement 2

**User Story:** Là người dùng, tôi muốn click vào icon chuông để xem danh sách thông báo, để biết chi tiết các thông báo.

#### Acceptance Criteria

1. WHEN người dùng click icon chuông THEN hệ thống SHALL hiển thị dropdown danh sách thông báo
2. WHEN dropdown hiển thị THEN hệ thống SHALL hiển thị tối đa 10 thông báo gần nhất
3. WHEN dropdown hiển thị THEN mỗi thông báo SHALL có icon, tiêu đề, nội dung, và thời gian
4. WHEN thông báo chưa đọc THEN hệ thống SHALL highlight với background màu xanh nhạt
5. WHEN dropdown hiển thị THEN hệ thống SHALL có link "Xem tất cả" ở cuối

### Requirement 3

**User Story:** Là người dùng, tôi muốn click vào thông báo để đánh dấu đã đọc và chuyển đến trang liên quan, để xử lý thông báo đó.

#### Acceptance Criteria

1. WHEN người dùng click vào thông báo THEN hệ thống SHALL đánh dấu thông báo đó là đã đọc
2. WHEN thông báo được đánh dấu đã đọc THEN badge số đếm SHALL giảm đi 1
3. WHEN người dùng click vào thông báo THEN hệ thống SHALL chuyển đến trang liên quan (VD: trang chi tiết phiếu)
4. WHEN người dùng click vào thông báo THEN dropdown SHALL tự động đóng
5. WHEN thông báo được đánh dấu đã đọc THEN background highlight SHALL biến mất

### Requirement 4

**User Story:** Là người dùng, tôi muốn có nút "Đánh dấu tất cả đã đọc", để nhanh chóng xóa tất cả thông báo chưa đọc.

#### Acceptance Criteria

1. WHEN dropdown thông báo hiển thị THEN hệ thống SHALL hiển thị nút "Đánh dấu tất cả đã đọc" ở header
2. WHEN người dùng click nút "Đánh dấu tất cả đã đọc" THEN hệ thống SHALL đánh dấu tất cả thông báo là đã đọc
3. WHEN tất cả thông báo được đánh dấu đã đọc THEN badge SHALL biến mất
4. WHEN tất cả thông báo được đánh dấu đã đọc THEN tất cả highlight SHALL biến mất
5. WHEN không có thông báo chưa đọc THEN nút "Đánh dấu tất cả đã đọc" SHALL bị disable

### Requirement 5

**User Story:** Là người dùng, tôi muốn nhận thông báo khi có phiếu nhập/xuất/chuyển kho mới được tạo, để biết và xử lý kịp thời.

#### Acceptance Criteria

1. WHEN phiếu nhập kho mới được tạo THEN hệ thống SHALL tạo thông báo cho người quản lý kho
2. WHEN phiếu xuất kho mới được tạo THEN hệ thống SHALL tạo thông báo cho người quản lý kho
3. WHEN phiếu chuyển kho mới được tạo THEN hệ thống SHALL tạo thông báo cho người quản lý kho nguồn và đích
4. WHEN thông báo được tạo THEN hệ thống SHALL bao gồm mã phiếu, loại phiếu, và người tạo
5. WHEN thông báo được tạo THEN badge số đếm SHALL tăng lên 1

### Requirement 6

**User Story:** Là người dùng, tôi muốn nhận thông báo khi phiếu được duyệt hoặc từ chối, để biết kết quả xử lý phiếu của mình.

#### Acceptance Criteria

1. WHEN phiếu được duyệt THEN hệ thống SHALL tạo thông báo cho người tạo phiếu
2. WHEN phiếu bị từ chối THEN hệ thống SHALL tạo thông báo cho người tạo phiếu kèm lý do
3. WHEN thông báo duyệt phiếu THEN icon SHALL là checkmark màu xanh
4. WHEN thông báo từ chối phiếu THEN icon SHALL là X màu đỏ
5. WHEN click vào thông báo THEN hệ thống SHALL chuyển đến trang chi tiết phiếu đó

### Requirement 7

**User Story:** Là người dùng, tôi muốn xem trang "Tất cả thông báo" với phân trang, để xem lại các thông báo cũ.

#### Acceptance Criteria

1. WHEN người dùng click "Xem tất cả" THEN hệ thống SHALL chuyển đến trang danh sách thông báo
2. WHEN trang danh sách hiển thị THEN hệ thống SHALL hiển thị tất cả thông báo với phân trang
3. WHEN trang danh sách hiển thị THEN hệ thống SHALL có filter theo loại thông báo (tất cả/chưa đọc/đã đọc)
4. WHEN trang danh sách hiển thị THEN hệ thống SHALL sắp xếp theo thời gian mới nhất
5. WHEN trang danh sách hiển thị THEN mỗi trang SHALL hiển thị 20 thông báo

### Requirement 8

**User Story:** Là người dùng, tôi muốn thông báo tự động cập nhật realtime, để không cần reload trang khi có thông báo mới.

#### Acceptance Criteria

1. WHEN có thông báo mới được tạo THEN badge SHALL tự động cập nhật không cần reload
2. WHEN có thông báo mới được tạo THEN dropdown (nếu đang mở) SHALL tự động thêm thông báo mới
3. WHEN thông báo được đánh dấu đã đọc ở tab khác THEN badge SHALL tự động cập nhật ở tab hiện tại
4. WHEN hệ thống cập nhật realtime THEN hệ thống SHALL sử dụng polling mỗi 30 giây
5. WHEN có thông báo mới THEN hệ thống SHALL hiển thị animation nhấp nháy ở icon chuông

### Requirement 9

**User Story:** Là người dùng, tôi muốn thông báo có icon và màu sắc phù hợp với loại sự kiện, để dễ phân biệt.

#### Acceptance Criteria

1. WHEN thông báo về phiếu nhập kho THEN icon SHALL là arrow-down màu xanh dương
2. WHEN thông báo về phiếu xuất kho THEN icon SHALL là arrow-up màu cam
3. WHEN thông báo về phiếu chuyển kho THEN icon SHALL là exchange màu tím
4. WHEN thông báo về duyệt phiếu THEN icon SHALL là check màu xanh lá
5. WHEN thông báo về từ chối phiếu THEN icon SHALL là times màu đỏ
