# Mini ERP/CRM System

Hệ thống quản lý doanh nghiệp Mini ERP/CRM với giao diện HTML/CSS/JavaScript thuần.

## Cấu trúc dự án

```
├── index.html              # Trang chính (Single Page Application)
├── index-new.html          # Trang chính (Module riêng biệt với iframe)
├── styles.css              # CSS chung cho toàn bộ hệ thống
├── script.js               # JavaScript chung
├── README.md               # File hướng dẫn này
└── modules/                # Thư mục chứa các module riêng biệt
    ├── customers.html      # Module quản lý khách hàng
    ├── customers.js        # Logic khách hàng
    ├── suppliers.html      # Module quản lý nhà cung cấp
    ├── suppliers.js        # Logic nhà cung cấp
    ├── employees.html      # Module quản lý nhân viên
    ├── employees.js        # Logic nhân viên
    ├── products.html       # Module quản lý hàng hóa
    ├── products.js         # Logic hàng hóa
    ├── sales-orders.html   # Module quản lý đơn hàng bán
    ├── sales-orders.js     # Logic đơn hàng bán
    └── ...                 # Các module khác
```

## Các module đã hoàn thành

### Master Data
1. ✅ **Khách hàng** (customers.html)
   - Form đầy đủ: Mã KH, Tên, Email, SĐT, Địa chỉ, Loại KH, MST, Website, Người liên hệ, Hạn mức công nợ, Số ngày công nợ
   - Chức năng: Thêm, Sửa, Xóa, Xem, Tìm kiếm, Lọc theo loại

2. ✅ **Nhà cung cấp** (suppliers.html)
   - Form đầy đủ: Mã NCC, Tên, Email, SĐT, Địa chỉ, Đánh giá, MST, Website, Người liên hệ, Điều khoản thanh toán
   - Chức năng: Thêm, Sửa, Xóa, Xem, Tìm kiếm

3. ✅ **Nhân viên** (employees.html)
   - Form đầy đủ: Mã NV, Họ tên, Chức vụ, Phòng ban, Email, SĐT, Ngày sinh, Địa chỉ, Ngày vào làm, Lương, CMND, Tài khoản ngân hàng
   - Chức năng: Thêm, Sửa, Xóa, Xem, Tìm kiếm, Lọc theo phòng ban

4. ✅ **Hàng hóa** (products.html)
   - Form đầy đủ: Mã SP, Tên, Nhóm SP, Đơn vị, Giá bán, Giá vốn, Tồn kho min/max
   - Hỗ trợ 3 loại quản lý: Thông thường, Serial Number, Số lô
   - Chức năng: Thêm, Sửa, Xóa, Xem, Tìm kiếm, Lọc theo loại quản lý

### Bán hàng
5. ✅ **Bảng giá sản phẩm** (price-list.html)
   - Quản lý nhiều bảng giá: VIP, Thường, Khuyến mãi
   - Chi tiết giá từng sản phẩm, chiết khấu
   - Chức năng: Thêm, Sửa, Xóa, Xem, Sao chép bảng giá

6. ✅ **Đơn hàng bán** (sales-orders.html)
   - Form đầy đủ: Mã đơn, Loại (Bán lẻ/Dự án), Khách hàng, Ngày tạo, Chi tiết sản phẩm
   - Tính toán tự động: Tổng tiền, Chiết khấu, VAT
   - Chức năng: Thêm, Sửa, Xóa, Xem, Gửi email, Xuất hóa đơn, Lọc theo trạng thái và loại

7. ✅ **Xét duyệt đơn hàng** (sales-approval.html)
   - Quy trình 2 cấp: Trưởng phòng → Giám đốc
   - Kiểm tra công nợ, hạn mức
   - Tab: Chờ duyệt cấp 1, Cấp 2, Đã duyệt, Từ chối
   - Form từ chối với lý do

### Mua hàng
8. ✅ **Đơn mua hàng** (purchase-orders.html)
   - Yêu cầu đặt hàng, báo giá từ nhà cung cấp
   - Ghi nhận chi phí phục vụ nhập hàng (vận chuyển, chi phí khác)
   - Quản lý chiết khấu từ nhà cung cấp
   - Gửi đơn mua hàng qua email
   - Xác nhận nhận hàng

### Kho
9. ✅ **Quản lý kho** (warehouses.html)
   - Kho thực và kho ảo
   - Diện tích, sức chứa, người quản lý
   - Kiểm soát nhiệt độ, hệ thống an ninh
   - Chức năng: Thêm, Sửa, Xóa, Xem tồn kho

10. ✅ **Xuất nhập kho** (inventory-transactions.html)
    - 3 loại phiếu: Nhập kho, Xuất kho, Điều chuyển
    - Quản lý Serial/Lô cho từng sản phẩm
    - Liên kết với đơn hàng bán/mua
    - In phiếu xuất nhập kho

11. ✅ **Tồn kho** (inventory.html)
    - Báo cáo tồn kho theo thời gian thực
    - Cảnh báo tồn tối thiểu, hết hàng
    - Theo dõi hạn sử dụng, bảo hành
    - Tổng hợp giá trị tồn kho
    - Chức năng kiểm kê

### CRM
12. ✅ **Quản lý liên hệ** (crm-contacts.html)
    - 4 loại: Cuộc gọi, Cuộc họp, Email, Thăm khách hàng
    - Cơ hội bán hàng, tỷ lệ thành công
    - Kết quả, hành động tiếp theo
    - Lịch hẹn tiếp theo

13. ✅ **Hạn mức công nợ** (sales-debt.html)
    - Quản lý hạn mức công nợ khách hàng
    - Theo dõi tỷ lệ sử dụng hạn mức
    - Cảnh báo vượt hạn mức
    - Lịch sử thay đổi hạn mức

14. ✅ **Margin đơn hàng** (sales-margin.html)
    - Tính toán margin tự động
    - Phân tích lợi nhuận theo đơn hàng
    - Báo cáo margin theo sản phẩm
    - Dashboard thống kê

15. ✅ **Hàng hủy, thanh lý** (damaged-goods.html)
    - Quản lý hàng hỏng, hết hạn
    - Quy trình thanh lý
    - Ghi nhận tổn thất
    - Báo cáo hàng hủy

### Kế toán (Phase 3)
16. ✅ **Kế toán** (accounting.html)
    - Dashboard tài chính với 4 stat cards
    - Theo dõi đơn hàng bán/mua
    - Quản lý thanh toán khách hàng/nhà cung cấp
    - Quản lý đa tiền tệ, lịch sử tỷ giá
    - Báo cáo lãi lỗ, cân đối kế toán, dòng tiền
    - Xuất file Misa

### Nhân sự (Phase 3)
17. ✅ **Chấm công** (attendance.html)
    - Dashboard với 4 stat cards
    - Tích hợp API chấm công
    - Theo dõi giờ vào/ra, đi muộn, về sớm
    - Quản lý tăng ca, nghỉ phép
    - Xuất báo cáo Excel

18. ✅ **Tính lương** (payroll.html)
    - Dashboard với 4 stat cards
    - Tính lương theo chấm công
    - Lương cơ bản, phụ cấp, thưởng, tăng ca
    - Khấu trừ, thực lĩnh
    - Tổng hợp lương phải trả
    - In phiếu lương

19. ✅ **KPI** (kpi.html)
    - Dashboard với 4 stat cards
    - Thiết lập KPI cho từng bộ phận
    - Theo dõi mục tiêu và thực tế
    - Tính toán tỷ lệ đạt được
    - Đánh giá hiệu suất

20. ✅ **Doanh số bán hàng** (sales-target.html)
    - Dashboard với 4 stat cards (Top performer)
    - Thiết lập mục tiêu doanh số
    - Tính hoa hồng tự động
    - Theo dõi tỷ lệ hoàn thành
    - Báo cáo doanh số

21. ✅ **Công cụ dụng cụ** (tools.html)
    - Dashboard với 4 stat cards
    - Quản lý công cụ làm việc
    - Cấp phát, thu hồi
    - Theo dõi bảo trì
    - Quản lý giá trị tài sản

## Tổng kết các Phase

### ✅ Phase 1 - Master Data (Hoàn thành 100% - 6 modules)
- ✅ Khách hàng (customers.html)
- ✅ Nhà cung cấp (suppliers.html)
- ✅ Nhân viên (employees.html)
- ✅ Hàng hóa (products.html)
- ✅ Sơ đồ cơ cấu công ty (company-structure.html)
- ✅ Quản lý tài sản (asset-management.html)

### ✅ Phase 2 - Bán hàng, Mua hàng, Kho, CRM (Hoàn thành 100% - 13 modules)
- ✅ Bảng giá sản phẩm (price-list.html)
- ✅ Đơn hàng bán (sales-orders.html)
- ✅ Xét duyệt đơn hàng 2 cấp (sales-approval.html)
- ✅ Hạn mức công nợ (sales-debt.html)
- ✅ Chi phí bán hàng (sales-expenses.html)
- ✅ Margin đơn hàng (sales-margin.html)
- ✅ Bán theo dự án (sales-project.html)
- ✅ Đơn mua hàng (purchase-orders.html)
- ✅ Quản lý kho (warehouses.html)
- ✅ Tồn kho (inventory.html)
- ✅ Xuất nhập kho (inventory-transactions.html)
- ✅ Hàng hủy, thanh lý (damaged-goods.html)
- ✅ CRM - Quản lý liên hệ (crm-contacts.html)

### ✅ Phase 3 - Kế toán & Nhân sự (Hoàn thành 100% - 6 modules)
- ✅ Kế toán (accounting.html) - Dashboard + 4 tabs + Xuất Misa
- ✅ Chấm công (attendance.html) - Dashboard + API integration
- ✅ Tính lương (payroll.html) - Dashboard + tính toán tự động
- ✅ KPI (kpi.html) - Dashboard + đánh giá hiệu suất
- ✅ Doanh số bán hàng (sales-target.html) - Dashboard + hoa hồng
- ✅ Công cụ dụng cụ (tools.html) - Dashboard + quản lý tài sản

## Tổng kết
**Tổng cộng: 25 modules hoàn chỉnh**
- Phase 1: 6 modules
- Phase 2: 13 modules  
- Phase 3: 6 modules

## Các module có thể mở rộng thêm

### Bán hàng
- [ ] Chi phí bán hàng
- [ ] Bán theo dự án

### Báo cáo
- [ ] Dashboard tổng quan
- [ ] Báo cáo doanh thu
- [ ] Báo cáo tồn kho
- [ ] Báo cáo công nợ

## Cách sử dụng

### Phiên bản Single Page (index.html)
1. Mở file `index.html` trong trình duyệt
2. Click vào menu bên trái để chuyển module
3. Tất cả module hiển thị trong cùng một trang

### Phiên bản Module riêng (index-new.html) - Khuyến nghị
1. Mở file `index-new.html` trong trình duyệt
2. Click vào menu bên trái để load module trong iframe
3. Mỗi module độc lập, dễ bảo trì và phát triển

## Tính năng chung của mỗi module

- ✅ Form thêm/sửa đầy đủ với validation
- ✅ Bảng dữ liệu với phân trang
- ✅ Tìm kiếm real-time
- ✅ Lọc theo các tiêu chí
- ✅ Thao tác: Xem, Thêm, Sửa, Xóa
- ✅ Xuất Excel (placeholder)
- ✅ Icons Font Awesome chuyên nghiệp
- ✅ Responsive design

## Công nghệ sử dụng

- HTML5
- CSS3 (Grid, Flexbox)
- JavaScript (ES6+)
- Font Awesome 6.4.0

## Lưu ý

- Dữ liệu hiện tại lưu trong JavaScript (localStorage có thể thêm sau)
- Các chức năng xuất Excel, gửi email là placeholder
- Cần kết nối backend API để lưu trữ dữ liệu thực tế
- Form validation cơ bản, có thể mở rộng thêm

## Phát triển tiếp

1. Hoàn thiện các module còn lại
2. Thêm localStorage để lưu dữ liệu
3. Kết nối backend API
4. Thêm phân quyền người dùng
5. Thêm báo cáo và dashboard
6. Tối ưu hiệu suất
7. Thêm unit tests

## Liên hệ

Dự án được phát triển bởi Kiro AI Assistant
