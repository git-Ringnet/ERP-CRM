# Mô tả chức năng các Module - Hệ thống Mini ERP/CRM

## 📋 Mục lục
- [Master Data](#master-data)
- [Bán hàng](#bán-hàng)
- [Mua hàng](#mua-hàng)
- [Kho](#kho)
- [CRM](#crm)
- [Kế toán](#kế-toán)
- [Nhân sự](#nhân-sự)
- [Hệ thống](#hệ-thống)

---

## 🏢 Master Data

### 1. Khách hàng (customers.html)
**Mục đích:** Quản lý thông tin khách hàng và đối tác kinh doanh

**Chức năng chính:**
- Lưu trữ thông tin chi tiết khách hàng (mã KH, tên, email, SĐT, địa chỉ)
- Phân loại khách hàng (Doanh nghiệp, Cá nhân, VIP)
- Quản lý thông tin pháp lý (MST, website, người liên hệ)
- Thiết lập hạn mức công nợ và số ngày công nợ cho phép
- Tìm kiếm và lọc khách hàng theo loại
- Xuất danh sách khách hàng ra Excel

**Dữ liệu quản lý:**
- Thông tin cơ bản: Mã, tên, email, SĐT, địa chỉ
- Phân loại: Loại khách hàng (Doanh nghiệp/Cá nhân/VIP)
- Thông tin pháp lý: MST, website
- Quản lý công nợ: Hạn mức, số ngày công nợ
- Ghi chú bổ sung

### 2. Nhà cung cấp (suppliers.html)
**Mục đích:** Quản lý thông tin nhà cung cấp và đối tác mua hàng

**Chức năng chính:**
- Lưu trữ thông tin nhà cung cấp (mã NCC, tên, email, SĐT, địa chỉ)
- Quản lý thông tin liên hệ và pháp lý (MST, website, người liên hệ)
- Thiết lập điều khoản thanh toán (số ngày)
- Phân loại theo loại sản phẩm cung cấp
- Tìm kiếm nhà cung cấp
- Xuất danh sách ra Excel

**Dữ liệu quản lý:**
- Thông tin cơ bản: Mã NCC, tên, email, SĐT, địa chỉ
- Thông tin pháp lý: MST, website
- Người liên hệ chính
- Điều khoản thanh toán (số ngày)
- Loại sản phẩm cung cấp
- Ghi chú

### 3. Nhân viên (employees.html)
**Mục đích:** Quản lý thông tin nhân sự trong công ty

**Chức năng chính:**
- Lưu trữ hồ sơ nhân viên đầy đủ (thông tin cá nhân, công việc, lương)
- Quản lý theo phòng ban và chức vụ
- Theo dõi trạng thái làm việc (Đang làm việc, Nghỉ phép, Đã nghỉ việc)
- Quản lý thông tin ngân hàng để chi lương
- Lọc nhân viên theo phòng ban
- Xuất danh sách nhân viên

**Dữ liệu quản lý:**
- Thông tin cá nhân: Mã NV, họ tên, ngày sinh, CMND/CCCD, địa chỉ
- Thông tin liên lạc: Email, SĐT
- Thông tin công việc: Phòng ban, chức vụ, ngày vào làm
- Thông tin lương: Lương cơ bản, tài khoản ngân hàng, tên ngân hàng
- Trạng thái: Đang làm việc/Nghỉ phép/Đã nghỉ việc

### 4. Hàng hóa (products.html)
**Mục đích:** Quản lý danh mục sản phẩm và hàng hóa kinh doanh

**Chức năng chính:**
- Quản lý thông tin sản phẩm (mã, tên, nhóm, đơn vị)
- Thiết lập giá bán và giá vốn
- Quản lý tồn kho tối thiểu và tối đa
- Hỗ trợ 3 loại quản lý: Thông thường, Serial Number, Số lô
- Phân loại theo nhóm sản phẩm
- Tìm kiếm và lọc theo loại quản lý

**Dữ liệu quản lý:**
- Thông tin cơ bản: Mã SP, tên, nhóm sản phẩm, đơn vị tính
- Giá cả: Giá bán, giá vốn
- Tồn kho: Tồn kho tối thiểu, tối đa
- Loại quản lý: Thông thường/Serial Number/Số lô
- Mô tả sản phẩm

### 5. Sơ đồ cơ cấu công ty (company-structure.html)
**Mục đích:** Quản lý cơ cấu tổ chức và phòng ban

**Chức năng chính:**
- Xây dựng sơ đồ tổ chức công ty
- Quản lý các phòng ban và bộ phận
- Phân cấp quản lý
- Gán nhân viên vào từng phòng ban

### 6. Quản lý tài sản (asset-management.html)
**Mục đích:** Quản lý tài sản cố định của công ty

**Chức năng chính:**
- Theo dõi tài sản cố định (máy móc, thiết bị, xe cộ)
- Quản lý khấu hao tài sản
- Lịch sử bảo trì và sửa chữa
- Phân bổ tài sản cho phòng ban/nhân viên

---

## 💰 Bán hàng

### 7. Bảng giá sản phẩm (price-list.html)
**Mục đích:** Quản lý nhiều bảng giá cho các nhóm khách hàng khác nhau

**Chức năng chính:**
- Tạo và quản lý nhiều bảng giá (VIP, Thường, Khuyến mãi)
- Thiết lập giá riêng cho từng sản phẩm trong mỗi bảng giá
- Áp dụng chiết khấu theo bảng giá
- Sao chép bảng giá để tạo bảng giá mới
- Kích hoạt/vô hiệu hóa bảng giá

**Dữ liệu quản lý:**
- Tên bảng giá
- Danh sách sản phẩm và giá tương ứng
- Chiết khấu áp dụng
- Thời gian hiệu lực
- Trạng thái (Kích hoạt/Vô hiệu hóa)

### 8. Đơn hàng bán (sales-orders.html)
**Mục đích:** Quản lý quy trình bán hàng từ tạo đơn đến hoàn thành

**Chức năng chính:**
- Tạo đơn hàng bán (Bán lẻ hoặc Bán theo dự án)
- Chọn khách hàng và sản phẩm
- Tính toán tự động: Tổng tiền, chiết khấu, VAT
- Theo dõi trạng thái đơn hàng (Chờ duyệt, Đã duyệt, Đang giao, Hoàn thành, Đã hủy)
- Gửi đơn hàng qua email
- Xuất hóa đơn
- Lọc theo trạng thái và loại đơn hàng

**Dữ liệu quản lý:**
- Thông tin đơn hàng: Mã đơn, loại đơn, ngày tạo
- Khách hàng và địa chỉ giao hàng
- Chi tiết sản phẩm: Tên, số lượng, đơn giá, thành tiền
- Tính toán: Tổng tiền hàng, chiết khấu, VAT, tổng cộng
- Margin (lợi nhuận)
- Trạng thái đơn hàng

### 9. Xét duyệt đơn hàng (sales-approval.html)
**Mục đích:** Quản lý quy trình phê duyệt đơn hàng 2 cấp

**Chức năng chính:**
- Quy trình duyệt 2 cấp: Trưởng phòng → Giám đốc
- Kiểm tra công nợ và hạn mức khách hàng tự động
- Phân loại đơn hàng: Chờ duyệt cấp 1, Cấp 2, Đã duyệt, Từ chối
- Ghi nhận lý do từ chối
- Thông báo cho người tạo đơn

**Dữ liệu quản lý:**
- Thông tin đơn hàng cần duyệt
- Cấp duyệt hiện tại
- Người duyệt
- Lý do từ chối (nếu có)
- Lịch sử phê duyệt

### 10. Hạn mức công nợ (sales-debt.html)
**Mục đích:** Quản lý và theo dõi công nợ khách hàng

**Chức năng chính:**
- Thiết lập hạn mức công nợ cho từng khách hàng
- Theo dõi công nợ hiện tại và tỷ lệ sử dụng hạn mức
- Cảnh báo khi vượt hạn mức
- Lịch sử thay đổi hạn mức
- Báo cáo công nợ quá hạn

**Dữ liệu quản lý:**
- Khách hàng
- Hạn mức công nợ
- Công nợ hiện tại
- Tỷ lệ sử dụng (%)
- Số ngày quá hạn
- Lịch sử điều chỉnh

### 11. Chi phí bán hàng (sales-expenses.html)
**Mục đích:** Quản lý các chi phí phát sinh trong quá trình bán hàng

**Chức năng chính:**
- Ghi nhận chi phí bán hàng (vận chuyển, tiếp khách, quảng cáo)
- Phân bổ chi phí theo đơn hàng hoặc dự án
- Theo dõi chi phí theo nhân viên bán hàng
- Báo cáo chi phí theo thời gian

**Dữ liệu quản lý:**
- Loại chi phí
- Số tiền
- Ngày phát sinh
- Đơn hàng/dự án liên quan
- Nhân viên phụ trách
- Chứng từ đính kèm

### 12. Margin đơn hàng (sales-margin.html)
**Mục đích:** Phân tích lợi nhuận và margin của đơn hàng

**Chức năng chính:**
- Tính toán margin tự động (Doanh thu - Giá vốn - Chi phí)
- Phân tích lợi nhuận theo đơn hàng
- Báo cáo margin theo sản phẩm
- Dashboard thống kê margin trung bình
- So sánh margin giữa các đơn hàng

**Dữ liệu quản lý:**
- Mã đơn hàng
- Doanh thu
- Giá vốn
- Chi phí bán hàng
- Margin (số tiền và %)
- Phân tích theo sản phẩm

### 13. Bán theo dự án (sales-project.html)
**Mục đích:** Quản lý bán hàng theo dự án lớn

**Chức năng chính:**
- Tạo và quản lý dự án bán hàng
- Theo dõi tiến độ dự án
- Quản lý nhiều đơn hàng trong một dự án
- Tính toán tổng doanh thu và lợi nhuận dự án
- Phân bổ nguồn lực cho dự án

**Dữ liệu quản lý:**
- Thông tin dự án
- Khách hàng
- Danh sách đơn hàng trong dự án
- Tiến độ thực hiện
- Tổng doanh thu và margin
- Nhân viên phụ trách

---

## 🛒 Mua hàng

### 14. Đơn mua hàng (purchase-orders.html)
**Mục đích:** Quản lý quy trình mua hàng từ nhà cung cấp

**Chức năng chính:**
- Tạo yêu cầu đặt hàng
- Quản lý báo giá từ nhà cung cấp
- Ghi nhận chi phí phục vụ nhập hàng (vận chuyển, bốc xếp, chi phí khác)
- Quản lý chiết khấu từ nhà cung cấp
- Gửi đơn mua hàng qua email
- Xác nhận nhận hàng
- Theo dõi trạng thái đơn mua

**Dữ liệu quản lý:**
- Thông tin đơn mua: Mã đơn, ngày tạo
- Nhà cung cấp
- Chi tiết sản phẩm: Tên, số lượng, đơn giá
- Chi phí phát sinh: Vận chuyển, bốc xếp, khác
- Chiết khấu từ NCC
- Tổng giá trị đơn hàng
- Trạng thái: Chờ duyệt, Đã đặt, Đã nhận, Hoàn thành

---

## 📦 Kho

### 15. Quản lý kho (warehouses.html)
**Mục đích:** Quản lý thông tin kho hàng và địa điểm lưu trữ

**Chức năng chính:**
- Quản lý kho thực và kho ảo
- Thiết lập thông tin kho (diện tích, sức chứa, địa chỉ)
- Phân công người quản lý kho
- Kiểm soát nhiệt độ, độ ẩm (nếu cần)
- Quản lý hệ thống an ninh kho
- Xem tồn kho theo từng kho

**Dữ liệu quản lý:**
- Mã kho, tên kho
- Loại kho (Thực/Ảo)
- Địa chỉ, diện tích, sức chứa
- Người quản lý
- Điều kiện bảo quản
- Hệ thống an ninh
- Trạng thái hoạt động

### 16. Tồn kho (inventory.html)
**Mục đích:** Theo dõi tồn kho theo thời gian thực

**Chức năng chính:**
- Báo cáo tồn kho theo thời gian thực
- Cảnh báo tồn kho tối thiểu (sắp hết hàng)
- Cảnh báo hết hàng
- Theo dõi hạn sử dụng và thời gian bảo hành
- Tính toán giá trị tồn kho (số lượng × giá vốn trung bình)
- Chức năng kiểm kê định kỳ
- Lọc theo kho và trạng thái

**Dữ liệu quản lý:**
- Sản phẩm
- Kho
- Số lượng tồn kho
- Tồn kho tối thiểu
- Giá vốn trung bình
- Giá trị tồn kho
- Hạn sử dụng
- Trạng thái: Bình thường/Sắp hết/Hết hàng/Sắp hết hạn

**Dashboard:**
- Tổng giá trị tồn kho
- Số sản phẩm
- Số sản phẩm sắp hết hàng
- Số sản phẩm hết hàng

### 17. Xuất nhập kho (inventory-transactions.html)
**Mục đích:** Quản lý các giao dịch xuất nhập kho

**Chức năng chính:**
- Tạo 3 loại phiếu: Nhập kho, Xuất kho, Điều chuyển kho
- Quản lý Serial Number/Số lô cho từng sản phẩm
- Liên kết với đơn hàng bán/mua
- Ghi nhận người xuất/nhập kho
- In phiếu xuất nhập kho
- Theo dõi lịch sử giao dịch

**Dữ liệu quản lý:**
- Loại phiếu: Nhập/Xuất/Điều chuyển
- Mã phiếu, ngày tạo
- Kho xuất/nhập
- Chi tiết sản phẩm: Tên, số lượng, Serial/Lô
- Liên kết đơn hàng (nếu có)
- Người thực hiện
- Lý do xuất/nhập
- Trạng thái: Nháp/Đã duyệt/Hoàn thành

### 18. Hàng hủy, thanh lý (damaged-goods.html)
**Mục đích:** Quản lý hàng hỏng, hết hạn và quy trình thanh lý

**Chức năng chính:**
- Ghi nhận hàng hỏng, hết hạn, lỗi
- Quy trình thanh lý hàng hóa
- Ghi nhận tổn thất tài chính
- Báo cáo hàng hủy theo thời gian
- Phê duyệt thanh lý

**Dữ liệu quản lý:**
- Sản phẩm bị hủy
- Số lượng
- Lý do hủy (Hỏng/Hết hạn/Lỗi/Khác)
- Giá trị tổn thất
- Ngày phát hiện
- Phương án xử lý
- Trạng thái thanh lý

---

## 👥 CRM

### 19. Quản lý liên hệ (crm-contacts.html)
**Mục đích:** Quản lý tương tác và cơ hội bán hàng với khách hàng

**Chức năng chính:**
- Ghi nhận 4 loại tương tác: Cuộc gọi, Cuộc họp, Email, Thăm khách hàng
- Quản lý cơ hội bán hàng và tỷ lệ thành công
- Ghi nhận kết quả và hành động tiếp theo
- Lên lịch hẹn tiếp theo
- Theo dõi lịch sử tương tác với khách hàng

**Dữ liệu quản lý:**
- Loại liên hệ: Cuộc gọi/Cuộc họp/Email/Thăm khách
- Khách hàng
- Ngày giờ liên hệ
- Người phụ trách
- Nội dung trao đổi
- Cơ hội bán hàng (giá trị, tỷ lệ thành công)
- Kết quả
- Hành động tiếp theo
- Lịch hẹn tiếp theo

---

## 💵 Kế toán

### 20. Kế toán (accounting.html)
**Mục đích:** Quản lý tài chính và kế toán doanh nghiệp

**Chức năng chính:**
- Dashboard tài chính với 4 chỉ số chính
- Theo dõi đơn hàng bán và đơn mua hàng
- Quản lý thanh toán từ khách hàng
- Quản lý thanh toán cho nhà cung cấp
- Quản lý đa tiền tệ và lịch sử tỷ giá
- Báo cáo lãi lỗ
- Báo cáo cân đối kế toán
- Báo cáo dòng tiền
- Xuất file kế toán cho phần mềm Misa

**Dashboard:**
- Doanh thu tháng này
- Chi phí tháng này
- Lợi nhuận tháng này
- Công nợ phải thu

**Dữ liệu quản lý:**
- Các khoản thu/chi
- Công nợ phải thu/phải trả
- Tỷ giá ngoại tệ
- Sổ cái kế toán
- Báo cáo tài chính

---

## 👨‍💼 Nhân sự

### 21. Chấm công (attendance.html)
**Mục đích:** Quản lý chấm công và giờ làm việc của nhân viên

**Chức năng chính:**
- Dashboard với 4 chỉ số chính
- Tích hợp API chấm công (vân tay, thẻ từ)
- Theo dõi giờ vào/ra hàng ngày
- Ghi nhận đi muộn, về sớm
- Quản lý tăng ca
- Quản lý nghỉ phép (có phép, không phép)
- Xuất báo cáo chấm công Excel

**Dashboard:**
- Tổng nhân viên
- Đi làm hôm nay
- Đi muộn
- Nghỉ phép

**Dữ liệu quản lý:**
- Nhân viên
- Ngày chấm công
- Giờ vào, giờ ra
- Số giờ làm việc
- Đi muộn (phút)
- Về sớm (phút)
- Tăng ca (giờ)
- Nghỉ phép (có phép/không phép)

### 22. Tính lương (payroll.html)
**Mục đích:** Tính toán và quản lý lương nhân viên

**Chức năng chính:**
- Dashboard với 4 chỉ số chính
- Tính lương tự động dựa trên chấm công
- Lương cơ bản + phụ cấp + thưởng + tăng ca
- Khấu trừ (BHXH, BHYT, thuế TNCN, phạt)
- Tính lương thực lĩnh
- Tổng hợp lương phải trả
- In phiếu lương cho nhân viên

**Dashboard:**
- Tổng lương phải trả
- Số nhân viên
- Lương trung bình
- Tổng khấu trừ

**Dữ liệu quản lý:**
- Nhân viên
- Tháng tính lương
- Lương cơ bản
- Phụ cấp (ăn trưa, xăng xe, điện thoại, khác)
- Thưởng
- Lương tăng ca
- Khấu trừ (BHXH, BHYT, thuế, phạt)
- Thực lĩnh

### 23. KPI (kpi.html)
**Mục đích:** Đánh giá hiệu suất làm việc theo chỉ số KPI

**Chức năng chính:**
- Dashboard với 4 chỉ số chính
- Thiết lập KPI cho từng bộ phận/nhân viên
- Theo dõi mục tiêu và thực tế đạt được
- Tính toán tỷ lệ hoàn thành (%)
- Đánh giá hiệu suất (Xuất sắc/Tốt/Trung bình/Kém)
- Báo cáo KPI theo tháng/quý/năm

**Dashboard:**
- Tổng KPI đang theo dõi
- KPI đạt mục tiêu
- KPI chưa đạt
- Tỷ lệ hoàn thành trung bình

**Dữ liệu quản lý:**
- Tên KPI
- Bộ phận/Nhân viên
- Mục tiêu
- Thực tế đạt được
- Tỷ lệ hoàn thành (%)
- Đánh giá
- Thời gian đánh giá

### 24. Doanh số bán hàng (sales-target.html)
**Mục đích:** Quản lý mục tiêu doanh số và hoa hồng bán hàng

**Chức năng chính:**
- Dashboard với 4 chỉ số (Top performer)
- Thiết lập mục tiêu doanh số cho nhân viên bán hàng
- Theo dõi doanh số thực tế
- Tính hoa hồng tự động theo tỷ lệ
- Theo dõi tỷ lệ hoàn thành mục tiêu
- Báo cáo doanh số theo nhân viên/tháng/quý
- Xếp hạng nhân viên bán hàng

**Dashboard:**
- Top 1 performer
- Top 2 performer
- Top 3 performer
- Tổng doanh số

**Dữ liệu quản lý:**
- Nhân viên bán hàng
- Mục tiêu doanh số
- Doanh số thực tế
- Tỷ lệ hoàn thành (%)
- Tỷ lệ hoa hồng (%)
- Hoa hồng nhận được
- Thời gian (tháng/quý/năm)

### 25. Công cụ dụng cụ (tools.html)
**Mục đích:** Quản lý công cụ làm việc và tài sản di động

**Chức năng chính:**
- Dashboard với 4 chỉ số chính
- Quản lý công cụ, dụng cụ làm việc (laptop, điện thoại, máy móc)
- Cấp phát công cụ cho nhân viên
- Thu hồi công cụ khi nhân viên nghỉ việc hoặc chuyển bộ phận
- Theo dõi lịch sử bảo trì, sửa chữa
- Quản lý giá trị tài sản
- Tính khấu hao công cụ

**Dashboard:**
- Tổng công cụ
- Đang sử dụng
- Còn trống
- Cần bảo trì

**Dữ liệu quản lý:**
- Mã công cụ, tên
- Loại công cụ
- Giá trị
- Ngày mua
- Nhân viên đang sử dụng
- Trạng thái (Mới/Đang dùng/Hỏng/Bảo trì)
- Lịch sử cấp phát
- Lịch sử bảo trì

---

## 📊 Tổng kết

**Tổng số module: 25**

### Phân loại theo nhóm chức năng:
- **Master Data:** 6 modules
- **Bán hàng:** 7 modules
- **Mua hàng:** 1 module
- **Kho:** 4 modules
- **CRM:** 1 module
- **Kế toán:** 1 module
- **Nhân sự:** 5 modules

### Tính năng chung của tất cả modules:
✅ Form thêm/sửa với validation
✅ Bảng dữ liệu với phân trang
✅ Tìm kiếm real-time
✅ Lọc theo tiêu chí
✅ Thao tác CRUD (Create, Read, Update, Delete)
✅ Xuất Excel
✅ Responsive design
✅ Icons Font Awesome

---

**Lưu ý:** Đây là hệ thống Mini ERP/CRM với giao diện HTML/CSS/JavaScript thuần, dữ liệu hiện tại lưu trong JavaScript. Để sử dụng thực tế, cần kết nối backend API và database.


---

## 🔧 Hệ thống

### 26. Người dùng & Phân quyền (users.html)
**Mục đích:** Quản lý tài khoản người dùng và phân quyền truy cập hệ thống

**Chức năng chính:**
- Tạo và quản lý tài khoản người dùng
- Gán vai trò (Admin, Manager, Sales, Accountant, Warehouse)
- Phân quyền chi tiết theo module và chức năng
- Quản lý team/nhóm làm việc
- Phân quyền xem Margin theo vai trò
- Kích hoạt/Vô hiệu hóa tài khoản

**Dữ liệu quản lý:**
- **Tab Người dùng:**
  - Username, Password, Email, SĐT
  - Liên kết với nhân viên
  - Vai trò và Team
  - Trạng thái (Active/Inactive)

- **Tab Vai trò & Quyền:**
  - Danh sách vai trò (Admin, Manager, Sales...)
  - Phân quyền chi tiết:
    + Master Data (Xem/Thêm/Sửa/Xóa)
    + Bán hàng (Xem/Thêm/Duyệt/Xem Margin)
    + Kho (Xem/Xuất/Nhập)
    + Kế toán (Xem/Chỉnh sửa)
  - Checkbox tree dễ quản lý

- **Tab Teams:**
  - Tên team, Trưởng team
  - Danh sách thành viên
  - Quyền team

**Tính năng đặc biệt:**
- ✅ Phân quyền xem Margin riêng biệt (chỉ Manager và Admin)
- ✅ Phân quyền theo tài khoản và nhóm
- ✅ Toggle section (chọn tất cả quyền trong module)

### 27. Cấu hình quy trình duyệt (approval-workflow.html) ⭐ MỚI
**Mục đích:** Tùy chỉnh quy trình phê duyệt linh hoạt cho từng loại chứng từ

**Chức năng chính:**
- Xem tổng quan tất cả quy trình duyệt
- Thêm/Bớt cấp duyệt theo nhu cầu
- Gán người duyệt cho từng cấp
- Thiết lập điều kiện duyệt (theo giá trị, loại...)
- Bật/Tắt quy trình duyệt
- Cài đặt nâng cao (tự động duyệt, thông báo email)

**Dữ liệu quản lý:**
- **Quy trình cho từng loại chứng từ:**
  - Báo giá (mặc định 2 cấp)
  - Hợp đồng (mặc định 3 cấp)
  - Đơn hàng (mặc định 2 cấp)
  - Đơn mua hàng
  - Phiếu chi

- **Mỗi cấp duyệt bao gồm:**
  - Tên cấp duyệt (VD: Trưởng phòng, Giám đốc, Pháp chế)
  - Người duyệt (theo vai trò hoặc user cụ thể)
  - Điều kiện áp dụng (VD: Giá trị > 10,000,000)
  - Thứ tự cấp duyệt

- **Cài đặt nâng cao:**
  - Cho phép bỏ qua cấp nếu người duyệt vắng mặt
  - Tự động duyệt sau X ngày không phản hồi
  - Gửi email thông báo khi cần duyệt
  - Trạng thái: Đang hoạt động/Tạm dừng

**Workflow hiển thị:**
```
Cấp 1 (Trưởng phòng) → Cấp 2 (Giám đốc) → Cấp 3 (Pháp chế) → Hoàn thành
```

**Tính năng đặc biệt:**
- ✅ Linh hoạt thêm/bớt cấp duyệt (từ 1-10 cấp)
- ✅ Gán người duyệt theo vai trò hoặc user cụ thể
- ✅ Điều kiện duyệt thông minh (theo giá trị, loại, khách hàng...)
- ✅ Xem trước quy trình trước khi lưu
- ✅ Workflow card hiển thị trực quan
- ✅ Thống kê số cấp, trạng thái, ngày cập nhật

**Ví dụ cấu hình:**

**Báo giá (2 cấp):**
- Cấp 1: Trưởng phòng (Giá trị < 50 triệu)
- Cấp 2: Giám đốc (Giá trị >= 50 triệu)

**Hợp đồng (3 cấp):**
- Cấp 1: Trưởng phòng
- Cấp 2: Giám đốc
- Cấp 3: Pháp chế (Chỉ với hợp đồng dài hạn)

**Đơn hàng (2 cấp):**
- Cấp 1: Trưởng phòng (Kiểm tra công nợ)
- Cấp 2: Giám đốc (Giá trị > 100 triệu)

---

## 📊 Tổng kết modules mới (Yêu cầu bổ sung)

### Modules mới đã tạo: 8 files
1. ✅ `modules/quotations.html` - Báo giá
2. ✅ `modules/contracts.html` - Hợp đồng
3. ✅ `modules/delivery-notes.html` - Biên bản giao nhận (B/L)
4. ✅ `modules/cost-formula.html` - Công thức chi phí
5. ✅ `modules/customer-detail.html` - Chi tiết khách hàng 360°
6. ✅ `modules/tasks.html` - Quản lý công việc
7. ✅ `modules/users.html` - Người dùng & Phân quyền
8. ✅ `modules/approval-workflow.html` - Cấu hình quy trình duyệt ⭐ MỚI

### Modules đã cập nhật: 2 files
1. ✅ `modules/price-list.html` - Bảng giá nâng cao
2. ✅ `modules/sales-approval.html` - Duyệt đa cấp

### Tổng số modules trong hệ thống: 33 modules
- Phase 1 (Master Data): 6 modules
- Phase 2 (Bán hàng, Mua hàng, Kho, CRM): 13 modules
- Phase 3 (Kế toán & Nhân sự): 6 modules
- **Yêu cầu bổ sung:** 8 modules mới

---

## 🎯 Quy trình nghiệp vụ hoàn chỉnh

### Quy trình bán hàng đầy đủ:
```
1. Bảng giá (price-list.html)
   ↓
2. Báo giá (quotations.html) → Duyệt 2 cấp
   ↓
3. Hợp đồng (contracts.html) → Duyệt 3 cấp
   ↓
4. Đơn hàng (sales-orders.html) → Duyệt 2 cấp
   ↓
5. Xuất kho (inventory-transactions.html)
   ↓
6. Biên bản giao nhận (delivery-notes.html) → Chữ ký điện tử
   ↓
7. Hóa đơn & Thanh toán (accounting.html)
```

### Quy trình phê duyệt linh hoạt:
- Cấu hình tại: `approval-workflow.html`
- Thực hiện duyệt tại: `sales-approval.html`
- Có thể thêm/bớt cấp duyệt theo nhu cầu
- Gán người duyệt theo vai trò hoặc user cụ thể
- Điều kiện duyệt thông minh

---

**Lưu ý:** Tất cả modules đều có giao diện đẹp, chuyên nghiệp, responsive và sẵn sàng tích hợp backend API.
