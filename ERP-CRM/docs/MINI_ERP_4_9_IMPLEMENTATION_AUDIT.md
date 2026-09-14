# Mini ERP 4.9 – ma trận triển khai

Tài liệu này đối chiếu các nhóm vấn đề trong `Mini ERP_4.9.docx` với phần đã
triển khai trong mã nguồn. Mục tiêu là nêu rõ phần đã có kiểm tra kỹ thuật và
các quyết định nghiệp vụ còn cần chốt trước khi nghiệm thu người dùng.

| Nhóm yêu cầu | Trạng thái triển khai | Bằng chứng chính |
| --- | --- | --- |
| Dashboard BOD/Manager có drill-down, lọc và số liệu margin không gồm VAT | Đã thực hiện | `BODDashboardService`, `BODDashboardApiController`, `dashboard/bod-overview.blade.php` |
| Giá trị kho USD/VND và tỷ giá | Đã thực hiện theo tiền tệ cơ sở | `CurrencyService`, `BODDashboardService` |
| Kho: chia theo hãng, lọc PO/Sales/dự án, truy vết PO/Sales, quay lại đúng ngữ cảnh | Đã thực hiện | `InventoryController`, `inventory/*.blade.php`, `warehouses/show.blade.php` |
| Kho: chỉ mượn Runrate, mượn qua ticket, hiển thị người mượn/số lượng | Đã thực hiện | `TicketController`, `InventoryController` |
| Kho: gộp cùng model + PO + kho | Đã thực hiện và chạy thử MySQL | `InventoryController` dùng `imports.reference_type/reference_id` |
| Nhập/xuất/chuyển kho: lọc, ghi chú bắt buộc, không chuyển license, chọn kho nguồn trước | Đã thực hiện | `ImportController`, `ExportController`, `TransferController`, request validation |
| Cơ hội: Admin xem được, BOD duyệt trước khi hoàn thành, ticket Marketing/Kỹ thuật | Đã thực hiện | `OpportunityController`, `OpportunityPolicy`, migration marketing ticket |
| Marketing Event: Sales chỉ thấy event liên quan; tạo event có thể yêu cầu Marketing/Kỹ thuật sau khi BOD duyệt | Đã thực hiện | `MarketingEventPolicy`, `MarketingEventController`, `marketing-events/create.blade.php` |
| Đơn bán: PO không xem nháp Sales, vendor bắt buộc, trạng thái/filter đồng bộ, hủy hàng đã về cần BOD | Đã thực hiện | `SaleController`, `Sale` model, `sales/index.blade.php` |
| Điều khoản thanh toán, UNC/Finance, bảo lãnh và đơn >= 1 tỷ | Đã thực hiện | `SaleController`, `Sale`, migration `000003` |
| Hạn dùng theo S/N | Đã thực hiện | `SaleOrderRequestItem`, `sales/order-request-create.blade.php`, migration `000004` |
| Một đơn nhiều dự án | Đã thực hiện ở form tạo và sửa theo từng dòng hàng | `sales/create.blade.php`, `sales/edit.blade.php` |
| Hóa đơn: Sales xác nhận bản nháp, Finance phát hành chính thức; bỏ route hóa đơn Excel trực tiếp | Đã thực hiện | `InvoiceRequestController`, `InvoiceRequest`, `routes/web.php` |
| Dự án: scope PM/PO/Sales, lịch sử chi tiết, đóng dự án lấy đơn bán gần nhất | Đã thực hiện | `ProjectPolicy`, `ProjectController`, `Project` |
| Công nợ: UNC bắt buộc, Finance xác nhận; ngăn ghi nhận trực tiếp từ màn công nợ | Đã thực hiện | `SaleController`, `CustomerDebtController` |
| Báo cáo bán hàng: quyền Sales, lọc hãng/thanh toán, cột Margin theo mẫu | Đã thực hiện | `SaleReportController`, `sale-reports/index.blade.php` |
| Tổng doanh số: thanh cuộn và xóa có lý do/BOD | Đã thực hiện | `SalesRevenueController`, `sales-revenues/index.blade.php` |
| VAT mặc định 8% toàn luồng có thể chỉnh | Đã thực hiện | migrations VAT, controller/form báo giá và PO |
| PO gửi duyệt phải hiển thị để xử lý tiếp | Đã thực hiện | `PurchaseOrderController`, `purchase-orders/index.blade.php` |

## Kiểm tra đã chạy

- Migrations `2026_09_09_000001` đến `2026_09_09_000004` đã chạy trên dữ liệu đã khôi phục.
- `php -l` đã chạy thành công với toàn bộ file PHP/Blade thay đổi.
- `php artisan route:cache`, `php artisan view:cache` và `git diff --check` đã thành công.
- Truy vấn tồn kho gộp theo PO và báo cáo Margin đã được chạy với MySQL hiện có.
- Kiểm thử Dashboard BOD với MySQL ngày 10/09/2026: tài khoản `Administrator`
  (role slug `super_admin`) lọc **Năm nay** nhận 35 đơn; lọc theo vendor và
  model thực tế đều trả về đúng tập con. Đã sửa scope để dùng role slug
  (`super_admin`, `director`, `admin`) thay vì tên role hiển thị.
- Bộ lọc vendor/model hiện cũng áp dụng nhất quán cho đơn bán, chỉ số tồn kho,
  danh sách tồn kho và số lượng hàng sẵn sàng/đang giữ. Đã chạy truy vấn MySQL
  kết hợp vendor + model, không có lỗi SQL và số lượng trong các KPI khớp tập
  dữ liệu đã lọc.

## Cần xác nhận nghiệp vụ trước nghiệm thu

1. **License:** đã chốt chọn trực tiếp loại `License` trong màn hình tạo yêu cầu đặt hàng. Không cần bổ sung trường phân loại trùng lặp ở đây. Báo cáo vẫn dùng quy tắc hiện hữu cho dữ liệu lịch sử chưa được phân loại.
2. **Gộp nhiều dự án:** đã chốt cho phép gộp, nhưng form tạo/sửa đơn hiển thị cảnh báo bắt buộc xác nhận; backend cũng từ chối request chưa có xác nhận. Partner/EU và PO tiếp tục được truy vết theo từng dòng hàng.
3. **Tỷ giá:** tài liệu không yêu cầu thêm quy tắc tỷ giá. Hệ thống giữ tỷ giá của chứng từ hiện có; không thêm giả định về tỷ giá ngày nhập/ngày bán/kỳ báo cáo.
4. **Phân quyền linh động:** cấu hình tại **Quản trị → Ma trận quyền** cho role và tại **Nhân sự → Quyền riêng** cho ngoại lệ từng người. `view_all_sales` được dùng làm quyền xem toàn bộ Dashboard BOD; nếu không có, Manager theo team và Sales theo dữ liệu của mình. Super Admin vẫn toàn quyền.
5. **Kiểm thử tự động:** đã bật `pdo_sqlite` và `sqlite3` trong PHP XAMPP. Do migration lịch sử dùng các thao tác khóa ngoại đặc thù MySQL, cấu hình PHPUnit chạy trên database riêng `crm_testing`, hoàn toàn tách biệt với `crm`. `tests/Feature/MigrationTest.php` đã chạy xanh 7/7; các assertion đã được cập nhật theo schema hiện hành (customer và product), không áp đặt các cột lịch sử đã bị loại bỏ.
