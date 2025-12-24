# Requirements Document - Liên kết Xuất Kho với Dự Án

## Introduction

Tính năng này cho phép liên kết phiếu xuất kho với dự án cụ thể, giúp theo dõi sản phẩm được xuất cho dự án nào, quản lý tốt hơn việc phân bổ tài nguyên và chi phí dự án.

## Glossary

- **Export Transaction**: Phiếu xuất kho - giao dịch xuất hàng ra khỏi kho
- **Project**: Dự án - đơn vị công việc có thời gian bắt đầu và kết thúc
- **Inventory**: Tồn kho - số lượng sản phẩm hiện có trong kho
- **Product Item**: Sản phẩm cụ thể có serial number

## Requirements

### Requirement 1

**User Story:** Là nhân viên kho, tôi muốn chọn dự án khi tạo phiếu xuất kho, để biết sản phẩm được xuất cho dự án nào.

#### Acceptance Criteria

1. WHEN tạo phiếu xuất kho mới THEN hệ thống SHALL hiển thị dropdown chọn dự án
2. WHEN chọn dự án THEN hệ thống SHALL lưu project_id vào phiếu xuất kho
3. WHEN không chọn dự án THEN hệ thống SHALL cho phép xuất kho bình thường (project_id = null)
4. WHEN xem phiếu xuất kho THEN hệ thống SHALL hiển thị tên dự án nếu có liên kết

### Requirement 2

**User Story:** Là quản lý dự án, tôi muốn xem danh sách sản phẩm đã xuất cho dự án, để theo dõi tài nguyên đã sử dụng.

#### Acceptance Criteria

1. WHEN xem chi tiết dự án THEN hệ thống SHALL hiển thị danh sách phiếu xuất kho liên quan
2. WHEN xem danh sách phiếu xuất THEN hệ thống SHALL hiển thị tổng số lượng và giá trị đã xuất
3. WHEN xem chi tiết phiếu xuất THEN hệ thống SHALL hiển thị danh sách sản phẩm với serial number

### Requirement 3

**User Story:** Là kế toán, tôi muốn lọc phiếu xuất kho theo dự án, để tính toán chi phí vật tư cho từng dự án.

#### Acceptance Criteria

1. WHEN ở trang danh sách xuất kho THEN hệ thống SHALL có filter theo dự án
2. WHEN chọn dự án trong filter THEN hệ thống SHALL chỉ hiển thị phiếu xuất của dự án đó
3. WHEN xem báo cáo THEN hệ thống SHALL tính tổng giá trị xuất theo dự án

### Requirement 4

**User Story:** Là quản lý, tôi muốn xem báo cáo xuất kho theo dự án, để đánh giá chi phí vật tư của các dự án.

#### Acceptance Criteria

1. WHEN xem trang dự án THEN hệ thống SHALL hiển thị tổng giá trị vật tư đã xuất
2. WHEN xem báo cáo dự án THEN hệ thống SHALL hiển thị chi tiết sản phẩm đã xuất
3. WHEN so sánh dự án THEN hệ thống SHALL hiển thị biểu đồ chi phí vật tư theo dự án
