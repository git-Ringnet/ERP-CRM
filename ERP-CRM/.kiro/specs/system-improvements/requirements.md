# Requirements Document - Đánh giá và Cải tiến Hệ thống ERP/CRM

## Introduction

Tài liệu này phân tích toàn bộ hệ thống ERP/CRM hiện tại, đánh giá các module và chức năng, đề xuất các cải tiến cần thiết với mức độ ưu tiên rõ ràng.

## Glossary

- **ERP**: Enterprise Resource Planning - Hệ thống hoạch định nguồn lực doanh nghiệp
- **CRM**: Customer Relationship Management - Quản lý quan hệ khách hàng
- **Module**: Một phần chức năng độc lập trong hệ thống
- **CRUD**: Create, Read, Update, Delete - Các thao tác cơ bản với dữ liệu

---

## PHÂN TÍCH CÁC MODULE HIỆN CÓ

### 1. MASTER DATA (Dữ liệu chính)
| Module | Trạng thái | Export Excel | Import Excel | Ghi chú |
|--------|-----------|--------------|--------------|---------|
| Khách hàng | ✅ Hoàn chỉnh | ✅ | ✅ | Có phân loại VIP/Thường |
| Nhà cung cấp | ✅ Hoàn chỉnh | ✅ | ❌ | Cần thêm Import |
| Nhân viên | ✅ Hoàn chỉnh | ✅ | ✅ | Có khóa tài khoản |
| Sản phẩm | ✅ Hoàn chỉnh | ✅ | ❌ | Quản lý theo Serial |

### 2. KHO HÀNG (Warehouse Management)
| Module | Trạng thái | Export Excel | Thông báo | Ghi chú |
|--------|-----------|--------------|-----------|---------|
| Quản lý Kho | ✅ Hoàn chỉnh | ✅ | - | |
| Tồn kho | ✅ Hoàn chỉnh | ✅ | - | Có cảnh báo sắp hết |
| Nhập kho | ✅ Hoàn chỉnh | ✅ | ✅ | Có duyệt phiếu |
| Xuất kho | ✅ Hoàn chỉnh | ✅ | ✅ | Liên kết Dự án |
| Chuyển kho | ✅ Hoàn chỉnh | ✅ | ✅ | Có duyệt phiếu |
| Hàng hư hỏng | ✅ Hoàn chỉnh | ✅ | ❌ | Cần thêm thông báo |

### 3. BÁN HÀNG (Sales)
| Module | Trạng thái | Export Excel | Ghi chú |
|--------|-----------|--------------|---------|
| Đơn hàng | ✅ Hoàn chỉnh | ✅ | Có PDF, Email |
| Báo giá | ✅ Hoàn chỉnh | ❌ | Cần Export |
| Công nợ KH | ✅ Hoàn chỉnh | ✅ | |
| Bảng giá | ✅ Hoàn chỉnh | ❌ | Cần Export |

### 4. MUA HÀNG (Purchasing)
| Module | Trạng thái | Export Excel | Ghi chú |
|--------|-----------|--------------|---------|
| Yêu cầu báo giá | ✅ Hoàn chỉnh | ❌ | Cần Export |
| Báo giá NCC | ✅ Hoàn chỉnh | ❌ | Có so sánh |
| Đơn mua hàng | ✅ Hoàn chỉnh | ❌ | Cần Export |
| Bảng giá NCC | ✅ Hoàn chỉnh | ✅ | Import Excel |
| Phân bổ VC | ✅ Hoàn chỉnh | ❌ | |

### 5. DỰ ÁN & BÁO CÁO
| Module | Trạng thái | Export Excel | Ghi chú |
|--------|-----------|--------------|---------|
| Dự án | ✅ Hoàn chỉnh | ❌ | Cần Export |
| Bảo hành | ✅ Hoàn chỉnh | ✅ | |
| BC Tồn kho | ✅ Hoàn chỉnh | ✅ | |
| BC Giao dịch | ✅ Hoàn chỉnh | ✅ | |
| BC Hư hỏng | ✅ Hoàn chỉnh | ✅ | |
| BC Mua hàng | ✅ Hoàn chỉnh | ✅ | |

### 6. HỆ THỐNG
| Module | Trạng thái | Ghi chú |
|--------|-----------|---------|
| Dashboard | ✅ Hoàn chỉnh | Có bộ lọc thời gian |
| Thông báo | ✅ Hoàn chỉnh | Realtime polling |
| Cài đặt | ✅ Cơ bản | Chỉ có Email |
| Quy trình duyệt | ✅ Hoàn chỉnh | |

---

## ĐỀ XUẤT CẢI TIẾN

### 🔴 ƯU TIÊN CAO (Nên làm ngay)

#### Requirement 1: Thêm Export Excel cho các module còn thiếu
**User Story:** Là người quản lý, tôi muốn xuất dữ liệu ra Excel từ tất cả các module, để có thể báo cáo và phân tích offline.

**Acceptance Criteria:**
1. WHEN người dùng click nút "Xuất Excel" ở trang Báo giá THEN hệ thống SHALL tạo file Excel chứa danh sách báo giá với filters hiện tại
2. WHEN người dùng click nút "Xuất Excel" ở trang Đơn mua hàng THEN hệ thống SHALL tạo file Excel chứa danh sách đơn mua với filters hiện tại
3. WHEN người dùng click nút "Xuất Excel" ở trang Dự án THEN hệ thống SHALL tạo file Excel chứa danh sách dự án với filters hiện tại
4. WHEN người dùng click nút "Xuất Excel" ở trang Bảng giá THEN hệ thống SHALL tạo file Excel chứa danh sách bảng giá

**Độ phức tạp:** Thấp | **Thời gian ước tính:** 2-3 giờ

---

#### Requirement 2: Thêm Import Excel cho Nhà cung cấp
**User Story:** Là nhân viên mua hàng, tôi muốn import danh sách nhà cung cấp từ Excel, để tiết kiệm thời gian nhập liệu.

**Acceptance Criteria:**
1. WHEN người dùng upload file Excel đúng template THEN hệ thống SHALL validate và import danh sách nhà cung cấp
2. WHEN file Excel có dữ liệu không hợp lệ THEN hệ thống SHALL hiển thị chi tiết lỗi từng dòng
3. WHEN import thành công THEN hệ thống SHALL hiển thị số lượng bản ghi đã import

**Độ phức tạp:** Trung bình | **Thời gian ước tính:** 3-4 giờ

---

### 🟡 ƯU TIÊN TRUNG BÌNH (Nên làm khi có thời gian)

#### Requirement 3: Thêm thông báo cho module Hàng hư hỏng
**User Story:** Là quản lý kho, tôi muốn nhận thông báo khi có báo cáo hàng hư hỏng mới, để xử lý kịp thời.

**Acceptance Criteria:**
1. WHEN nhân viên tạo báo cáo hàng hư hỏng mới THEN hệ thống SHALL gửi thông báo cho quản lý kho
2. WHEN báo cáo được duyệt/từ chối THEN hệ thống SHALL gửi thông báo cho người tạo báo cáo

**Độ phức tạp:** Thấp | **Thời gian ước tính:** 1-2 giờ

---

#### Requirement 4: Cải thiện trang Cài đặt hệ thống
**User Story:** Là admin, tôi muốn có trang cài đặt đầy đủ hơn, để quản lý các thông số hệ thống.

**Acceptance Criteria:**
1. WHEN admin truy cập trang Cài đặt THEN hệ thống SHALL hiển thị các tab: Thông tin công ty, Email, Thông báo, Sao lưu
2. WHEN admin cập nhật thông tin công ty THEN hệ thống SHALL lưu và hiển thị trên các báo cáo/hóa đơn
3. WHEN admin cấu hình thông báo THEN hệ thống SHALL cho phép bật/tắt từng loại thông báo

**Độ phức tạp:** Trung bình | **Thời gian ước tính:** 4-6 giờ

---

#### Requirement 5: Thêm Import Excel cho Sản phẩm
**User Story:** Là nhân viên kho, tôi muốn import danh sách sản phẩm từ Excel, để cập nhật hàng loạt.

**Acceptance Criteria:**
1. WHEN người dùng upload file Excel đúng template THEN hệ thống SHALL validate và import danh sách sản phẩm
2. WHEN sản phẩm đã tồn tại (theo mã) THEN hệ thống SHALL cập nhật thông tin thay vì tạo mới
3. WHEN import thành công THEN hệ thống SHALL hiển thị số lượng tạo mới và cập nhật

**Độ phức tạp:** Trung bình | **Thời gian ước tính:** 4-5 giờ

---

### 🟢 ƯU TIÊN THẤP (Làm khi rảnh)

#### Requirement 6: Thêm biểu đồ doanh thu vào Dashboard
**User Story:** Là quản lý, tôi muốn xem biểu đồ doanh thu trên Dashboard, để nắm bắt tình hình kinh doanh.

**Acceptance Criteria:**
1. WHEN người dùng xem Dashboard THEN hệ thống SHALL hiển thị biểu đồ doanh thu theo thời gian
2. WHEN người dùng thay đổi bộ lọc thời gian THEN hệ thống SHALL cập nhật biểu đồ doanh thu tương ứng

**Độ phức tạp:** Trung bình | **Thời gian ước tính:** 3-4 giờ

---

#### Requirement 7: Thêm chức năng in phiếu Nhập/Xuất/Chuyển kho
**User Story:** Là nhân viên kho, tôi muốn in phiếu kho, để lưu trữ và ký xác nhận.

**Acceptance Criteria:**
1. WHEN người dùng click nút "In phiếu" THEN hệ thống SHALL tạo bản in PDF với đầy đủ thông tin
2. WHEN in phiếu THEN hệ thống SHALL hiển thị logo công ty, thông tin phiếu, danh sách sản phẩm, chữ ký

**Độ phức tạp:** Trung bình | **Thời gian ước tính:** 4-5 giờ

---

#### Requirement 8: Thêm lịch sử hoạt động (Activity Log)
**User Story:** Là admin, tôi muốn xem lịch sử hoạt động của người dùng, để kiểm soát và audit.

**Acceptance Criteria:**
1. WHEN người dùng thực hiện thao tác quan trọng THEN hệ thống SHALL ghi log với thông tin: ai, làm gì, khi nào
2. WHEN admin xem Activity Log THEN hệ thống SHALL hiển thị danh sách với filter theo user, loại thao tác, thời gian

**Độ phức tạp:** Cao | **Thời gian ước tính:** 6-8 giờ

---

### ❌ KHÔNG CẦN LÀM (Đã đủ hoặc không cần thiết)

1. **Dashboard cơ bản** - Đã có đầy đủ thống kê và biểu đồ
2. **Hệ thống thông báo** - Đã hoàn chỉnh với realtime polling
3. **Export Excel cho Kho** - Đã có đủ 6 module (Nhập/Xuất/Chuyển/Tồn kho/Kho/Hư hỏng)
4. **Quy trình duyệt phiếu** - Đã có SweetAlert2 và AJAX
5. **Quản lý Serial sản phẩm** - Đã có đầy đủ
6. **Báo cáo tồn kho** - Đã có 3 loại báo cáo với Export
7. **Theo dõi bảo hành** - Đã hoàn chỉnh với cảnh báo sắp hết hạn

---

## TÓM TẮT ƯU TIÊN

| Mức độ | Số lượng | Tổng thời gian | Trạng thái |
|--------|----------|----------------|------------|
| 🔴 Cao | 2 | 5-7 giờ | ✅ Hoàn thành |
| 🟡 Trung bình | 3 | 9-13 giờ | ✅ Hoàn thành |
| 🟢 Thấp | 3 | 13-17 giờ | ⏳ Chưa làm |
| **Tổng** | **8** | **27-37 giờ** | |

---

## TIẾN ĐỘ THỰC HIỆN

### ✅ ĐÃ HOÀN THÀNH

#### Requirement 1: Export Excel cho các module còn thiếu ✅
- `app/Exports/QuotationsExport.php` - Báo giá
- `app/Exports/PurchaseOrdersExport.php` - Đơn mua hàng
- `app/Exports/ProjectsExport.php` - Dự án
- `app/Exports/PriceListsExport.php` - Bảng giá
- Đã thêm nút "Xuất Excel" màu emerald-500 vào tất cả trang index

#### Requirement 2: Import Excel cho Nhà cung cấp ✅
- `app/Imports/SuppliersImport.php` - Import class với template
- Đã thêm nút "Mẫu Import" và "Import Excel" vào trang suppliers/index

#### Requirement 3: Thông báo cho module Hàng hư hỏng ✅
- Cập nhật `app/Services/NotificationService.php` với 3 methods mới
- Cập nhật `app/Http/Controllers/DamagedGoodController.php` để gửi thông báo

#### Requirement 5: Import Excel cho Sản phẩm ✅
- `app/Imports/ProductsImport.php` - Import class với template
- Đã thêm nút "Mẫu Import" và "Import Excel" vào trang products/index

### ⏳ CHƯA LÀM

#### Requirement 4: Cải thiện trang Cài đặt hệ thống (Bỏ qua theo yêu cầu)
#### Requirement 6: Biểu đồ doanh thu Dashboard
#### Requirement 7: In phiếu PDF Nhập/Xuất/Chuyển kho
#### Requirement 8: Activity Log (lịch sử hoạt động)
