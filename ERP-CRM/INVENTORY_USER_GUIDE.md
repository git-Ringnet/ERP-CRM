# HƯỚNG DẪN SỬ DỤNG PHÂN HỆ QUẢN LÝ TỒN KHO
*(Inventory Management Module User Guide)*

Tài liệu này hướng dẫn chi tiết cách vận hành, quản lý dữ liệu thiết bị và các quy tắc nghiệp vụ trong phân hệ **Quản lý Tồn kho** mới nâng cấp.

---

## 1. Giao diện Phân chia 3 Tab
Màn hình quản lý tồn kho được tổ chức thành 3 tab chuyên biệt để phân loại thiết bị:

1. **Hàng Stocking (Hàng dự trữ / Runrate)**:
   * **Định nghĩa**: Các mặt hàng được nhập kho theo các đơn đặt hàng PO gom (Consolidated PO - PO liên kết từ 2 SO trở lên) hoặc PO không có dự án/SO liên kết nào (0 SO).
   * **Mục đích**: Quản lý hàng sẵn có hoặc hàng dự trữ chung phục vụ kinh doanh đại trà.
2. **Hàng Dự án**:
   * **Định nghĩa**: Các mặt hàng được nhập về theo các đơn PO chuyên biệt (Dedicated PO - PO liên kết với **đúng 1 SO/Dự án duy nhất**).
   * **Mục đích**: Theo dõi chặt chẽ hàng hóa dành riêng cho từng khách hàng hoặc dự án trọng điểm, tránh xuất nhầm hàng.
3. **Hàng R và NFR**:
   * **Định nghĩa**: Các thiết bị có mã sản phẩm kết thúc bằng chữ **R** (Hàng Demo/Thay thế) hoặc **NFR** (Not For Resale - Không dùng để bán).
   * **Mục đích**: Quản lý riêng biệt hàng phục vụ kỹ thuật, demo, bảo hành hãng.

---

## 2. Quy tắc Cộng gộp Số lượng (Grouping)
Nhằm giữ cho giao diện gọn gàng và dễ thao tác, các mặt hàng giống nhau sẽ tự động được gộp chung thành một dòng duy nhất trên bảng:

* **Điều kiện gộp dòng**: Các thiết bị phải có cùng **Mã thiết bị**, **Số PO**, **Kho chứa**, **Người mượn**, **Ghi chú**, và cùng giá trị trong các **Cột động (Custom Columns)**.
* **Số lượng hiển thị (SL)**: Là tổng số lượng của tất cả thiết bị đơn lẻ thỏa mãn điều kiện gộp.
* **Số Serial (S/N)**: 
  * Nếu thiết bị có Serial vật lý: Hiển thị danh sách các mã S/N ngăn cách bởi dấu phẩy (ví dụ: `SN001, SN002, SN003`).
  * Nếu thiết bị không có Serial vật lý: Hiển thị nhãn `Không serial`.

---

## 3. Quản lý Cột Động tại chỗ (Custom Columns)
Mỗi tab tồn kho có hệ thống cột dữ liệu động độc lập để tùy biến thông tin theo nhu cầu:

* **Thêm cột mới**: 
  * Nút **`+ Thêm cột cho [Tên Tab]`** nằm ngay góc phải của thanh tab điều hướng.
  * Việc thêm cột ở tab này **hoàn toàn không ảnh hưởng** đến cấu trúc bảng của các tab khác.
* **Xóa cột**: 
  * Rê chuột vào tiêu đề cột động cần xóa, click vào biểu tượng **Thùng rác màu đỏ** $\boldsymbol{\square}$ và xác nhận. 
  * Hệ thống sẽ tự động xóa cột trên giao diện và dọn sạch dữ liệu liên quan trong database.

---

## 4. Nhập liệu trực tiếp (Inline Editing)
Bạn có thể cập nhật các trường **Người mượn thiết bị**, **Ghi chú**, và các **Cột động** trực tiếp trên bảng mà không cần mở trang chỉnh sửa:

1. Click chuột trực tiếp vào ô cần sửa, nhập nội dung mới.
2. Click chuột ra ngoài (blur) hoặc nhấn Enter để lưu.
3. **Hiệu ứng màu sắc**: 
   * Màu vàng nhạt: Đang đồng bộ dữ liệu lên máy chủ.
   * Màu xanh lá cây nhạt: Đã lưu thành công (kèm thông báo toast ở góc màn hình).
* **Đồng bộ hàng loạt**: Khi chỉnh sửa trên một dòng đã gộp nhiều thiết bị, toàn bộ các bản ghi con đơn lẻ trong nhóm đó dưới database sẽ được cập nhật đồng bộ cùng lúc.

---

## 5. Quy tắc hiển thị Cột "Dự án / End User"
Thông tin dự án và khách hàng cuối được hệ thống truy vấn tự động dựa trên độ ưu tiên 3 cấp (Click vào tiêu đề cột trên giao diện để xem lại quy tắc bất cứ lúc nào):

* **Mức 1 (Dự án liên kết)**: Hiển thị định dạng **`Mã dự án - Tên dự án`** từ đơn SO (Sale Order) tương ứng của thiết bị. *(Ví dụ: `DA-0001 - DA từ 4324234`)*
* **Mức 2 (EU của Phiếu đặt hàng)**: Tên End-User/Mã số thuế của phiếu yêu cầu đặt hàng (SOR) nếu đơn SO không liên kết dự án. *(Ví dụ: `sadadda - abvcs`)*
* **Mức 3 (Khách hàng đơn SO)**: Tên khách hàng (`customer_name`) của đơn SO làm phương án dự phòng cuối cùng.

---

## 6. Tìm kiếm và Bộ lọc
* **Tìm kiếm đa năng (Per-Tab Search)**: Ô tìm kiếm ở đầu trang hỗ trợ tìm kiếm trên toàn bộ các cột thông tin hiển thị bao gồm: Tên sản phẩm, Mã sản phẩm, S/N, Người mượn, Ghi chú, Số PO, Dự án/End User và Người đặt hàng.
* **Lọc theo kho**: Lọc nhanh các thiết bị có mặt tại từng kho vật lý cụ thể.
* **Xuất Excel**: Tải xuống file báo cáo tồn kho định dạng Excel theo bộ lọc hiện hành.
