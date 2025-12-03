# Yêu cầu mới sau họp với khách hàng

## 📋 Tổng quan

Tài liệu này ghi nhận các yêu cầu bổ sung và điều chỉnh sau cuộc họp với khách hàng, bao gồm:
- Yêu cầu chức năng mới
- Điều chỉnh UI/UX
- Ước tính thời gian bổ sung

---

## 1️⃣ Import & Quản lý bảng giá nâng cao

### Yêu cầu hiện tại:
❌ Chỉ có bảng giá đơn giản (VIP, Thường, Khuyến mãi)
❌ Không hỗ trợ import từ Excel
❌ Không quản lý giá theo dự án/lô hàng

### Yêu cầu mới:

#### 1.1. Import bảng giá từ Excel
- Upload file Excel với template chuẩn
- Validate dữ liệu trước khi import
- Hiển thị preview trước khi lưu
- Log lỗi nếu có dữ liệu không hợp lệ

#### 1.2. Quản lý giá đa cấp
Một sản phẩm có nhiều mức giá theo:
- **Theo dự án:** Mỗi dự án có bảng giá riêng
- **Theo lô hàng:** Giá khác nhau cho từng lô nhập
- **Theo Bio (đơn vị/NCC):** Giá theo nguồn cung cấp
- **Theo khách hàng:** Giá đặc biệt cho từng khách

#### 1.3. Giá linh hoạt
- Cho phép override giá khi tạo đơn hàng
- Ghi nhận lý do thay đổi giá
- Lưu lịch sử thay đổi giá

### Điều chỉnh UI:

**Module: price-list.html**

```
Thêm các nút:
- [Import Excel] - Upload và import bảng giá
- [Export Template] - Tải template Excel mẫu
- [Lịch sử giá] - Xem lịch sử thay đổi giá

Thêm tab phân loại:
- Tab "Giá theo dự án"
- Tab "Giá theo lô hàng"
- Tab "Giá theo Bio/NCC"
- Tab "Giá theo khách hàng"

Form chi tiết sản phẩm:
- Hiển thị tất cả mức giá của 1 sản phẩm
- Cho phép thêm/sửa/xóa từng mức giá
- Ghi chú lý do thay đổi
```

**Thời gian bổ sung:** 1-2 tuần (FE 1 tuần + BE 1 tuần)

---

## 2️⃣ Quy trình phê duyệt đa cấp (Multi-level Approval)

### Yêu cầu hiện tại:
✅ Có phê duyệt đơn hàng 2 cấp
❌ Chưa có phê duyệt báo giá
❌ Chưa có phê duyệt hợp đồng
❌ Chưa có quy trình B/L

### Yêu cầu mới:

#### 2.1. Quy trình bán hàng hoàn chỉnh
```
Bán hàng → Báo giá → Hợp đồng → Đơn hàng → B/L (Biên bản giao nhận)
```

#### 2.2. Phê duyệt từng bước
- **Báo giá:** Cần duyệt trước khi gửi khách
- **Hợp đồng:** Duyệt đa cấp (Trưởng phòng → Giám đốc → Pháp chế)
- **Đơn hàng:** Duyệt 2 cấp (đã có)
- **B/L:** Xác nhận giao hàng và nghiệm thu

#### 2.3. Phân cấp phê duyệt
- Cấu hình số cấp duyệt theo loại chứng từ
- Thiết lập người duyệt cho từng cấp
- Cho phép duyệt song song hoặc tuần tự
- Gửi thông báo tự động khi cần duyệt

### Điều chỉnh UI:

**Module mới: quotations.html (Báo giá)**
```
Tạo module mới cho Báo giá:
- Form tạo báo giá (tương tự đơn hàng)
- Trạng thái: Nháp → Chờ duyệt → Đã duyệt → Đã gửi khách → Chấp nhận/Từ chối
- Nút [Gửi duyệt] → [Duyệt] → [Gửi khách] → [Chuyển thành hợp đồng]
- In/Export PDF báo giá
```

**Module mới: contracts.html (Hợp đồng)**
```
Tạo module mới cho Hợp đồng:
- Tạo từ báo giá đã được chấp nhận
- Thông tin hợp đồng: Số HĐ, ngày ký, giá trị, điều khoản
- Quy trình duyệt 3 cấp: Trưởng phòng → Giám đốc → Pháp chế
- Upload file hợp đồng scan
- Trạng thái: Nháp → Chờ duyệt cấp 1 → Cấp 2 → Cấp 3 → Đã ký → Đang thực hiện → Hoàn thành
```

**Module mới: delivery-notes.html (Biên bản giao nhận - B/L)**
```
Tạo module mới cho B/L:
- Liên kết với đơn hàng
- Thông tin giao hàng: Ngày giao, người nhận, địa điểm
- Danh sách sản phẩm giao (có thể giao từng phần)
- Ảnh chụp hiện trường (nếu có)
- Chữ ký điện tử khách hàng
- Trạng thái: Chờ giao → Đang giao → Đã giao → Đã nghiệm thu
```

**Module cập nhật: sales-approval.html**
```
Mở rộng để duyệt nhiều loại chứng từ:
- Tab "Báo giá chờ duyệt"
- Tab "Hợp đồng chờ duyệt"
- Tab "Đơn hàng chờ duyệt"
- Hiển thị cấp duyệt hiện tại
- Lịch sử phê duyệt đầy đủ
```

**Thời gian bổ sung:** 3-4 tuần
- Báo giá: 1 tuần
- Hợp đồng: 1 tuần
- B/L: 1 tuần
- Cập nhật approval workflow: 1 tuần

---

## 3️⃣ Chi phí bán hàng & Margin nâng cao

### Yêu cầu hiện tại:
✅ Có module chi phí bán hàng
✅ Có module margin
❌ Chưa có công thức tính tự động
❌ Margin tính theo giá vốn hệ thống, không phải giá xuất kho thực tế

### Yêu cầu mới:

#### 3.1. Công thức tính chi phí bán hàng
Cho phép thiết lập công thức:
```
Chi phí BH = Chiết khấu + Hoa hồng + Vận chuyển + Chi phí khác

Trong đó:
- Chiết khấu: % hoặc số tiền cố định
- Hoa hồng: % doanh số hoặc % lợi nhuận
- Vận chuyển: Tính theo km, trọng lượng, hoặc cố định
- Chi phí khác: Tiếp khách, quà tặng, marketing...
```

#### 3.2. Margin theo giá xuất kho thực tế
```
Margin = Doanh thu - Giá xuất kho thực tế - Chi phí BH

Giá xuất kho thực tế:
- Lấy từ phiếu xuất kho
- Theo phương pháp: FIFO, LIFO, hoặc Bình quân
- Cập nhật real-time khi xuất kho
```

### Điều chỉnh UI:

**Module mới: cost-formula.html (Công thức chi phí)**
```
Thiết lập công thức tính chi phí:
- Chọn loại chi phí (Chiết khấu, Hoa hồng, Vận chuyển...)
- Thiết lập cách tính (%, số tiền, theo điều kiện)
- Điều kiện áp dụng (theo dự án, khách hàng, sản phẩm)
- Preview kết quả tính toán
```

**Module cập nhật: sales-margin.html**
```
Bổ sung:
- Chọn phương pháp tính giá vốn: FIFO/LIFO/Bình quân
- Hiển thị chi tiết:
  + Doanh thu
  + Giá xuất kho thực tế (từ phiếu xuất)
  + Chi phí BH (theo công thức)
  + Margin (số tiền và %)
- So sánh margin dự kiến vs thực tế
- Cảnh báo nếu margin âm hoặc thấp hơn ngưỡng
```

**Thời gian bổ sung:** 2 tuần
- Công thức chi phí: 1 tuần
- Margin theo giá thực tế: 1 tuần

---

## 4️⃣ Thông tin & Dữ liệu dự án chi tiết

### Yêu cầu hiện tại:
✅ Có module bán theo dự án
❌ Thông tin dự án còn đơn giản
❌ Chưa theo dõi tiến độ chi tiết

### Yêu cầu mới:

#### 4.1. Thông tin dự án đầy đủ
- Mức giá riêng cho dự án
- Tiến độ thực hiện (%)
- Milestone và deadline
- Công việc liên quan đến khách hàng
- Tài liệu đính kèm (hợp đồng, thiết kế, báo cáo...)
- Team thực hiện
- Ngân sách và chi phí thực tế

#### 4.2. Quản lý tiến độ
- Gantt chart hiển thị timeline
- Checklist công việc
- Cập nhật tiến độ theo %
- Cảnh báo trễ deadline

### Điều chỉnh UI:

**Module cập nhật: sales-project.html**
```
Thêm các tab:
- Tab "Thông tin chung" (đã có)
- Tab "Bảng giá dự án" (mới)
- Tab "Tiến độ & Milestone" (mới)
- Tab "Công việc" (mới)
- Tab "Tài liệu" (mới)
- Tab "Ngân sách & Chi phí" (mới)

Tab "Tiến độ & Milestone":
- Timeline dự án
- Các milestone với deadline
- % hoàn thành
- Trạng thái: Đúng hạn/Trễ hạn/Hoàn thành

Tab "Công việc":
- Danh sách công việc
- Người phụ trách
- Deadline
- Trạng thái
- Liên kết với khách hàng

Tab "Tài liệu":
- Upload/Download files
- Phân loại tài liệu
- Phân quyền xem
```

**Thời gian bổ sung:** 2-3 tuần

---

## 5️⃣ Dữ liệu khách hàng (CRM mở rộng)

### Yêu cầu hiện tại:
✅ Có thông tin khách hàng cơ bản
✅ Có CRM quản lý liên hệ
❌ Chưa có view tổng hợp đầy đủ

### Yêu cầu mới:

#### 5.1. Trang chi tiết khách hàng (360° View)
Khi click vào một khách hàng, hiển thị:
- **Thông tin cơ bản:** Tên, địa chỉ, liên hệ
- **Lịch sử giao dịch:** Tất cả đơn hàng, báo giá, hợp đồng
- **Dự án liên quan:** Các dự án đang/đã thực hiện
- **Team phụ trách:** 
  - Nhân viên nào đã làm việc
  - Ai đang follow hiện tại
- **Workflow đã thực hiện:**
  - Các bước đã làm
  - Kết quả từng bước
- **Tình trạng giao hàng:**
  - Đơn hàng nào đã giao
  - Đơn nào đang giao
  - Đơn nào chưa giao
- **Hóa đơn:**
  - Đã xuất hóa đơn
  - Chưa xuất hóa đơn
- **Sản phẩm cho mượn:**
  - Danh sách SP đang cho mượn
  - Ngày mượn, hạn trả
  - Trạng thái

#### 5.2. Quản lý sản phẩm cho mượn
- Tạo phiếu cho mượn
- Theo dõi hạn trả
- Nhắc nhở tự động khi đến hạn
- Thu hồi sản phẩm

### Điều chỉnh UI:

**Module cập nhật: customers.html**
```
Thay đổi cách hiển thị:
- Khi click vào khách hàng → Mở trang chi tiết (không phải modal)
- Trang chi tiết có sidebar menu với các tab:
  
Sidebar menu:
├─ Thông tin chung
├─ Lịch sử giao dịch
│  ├─ Báo giá
│  ├─ Hợp đồng
│  ├─ Đơn hàng
│  └─ Thanh toán
├─ Dự án
├─ Team & Follow
├─ Giao hàng
├─ Hóa đơn
├─ Sản phẩm cho mượn
└─ Ghi chú & Tài liệu
```

**Module mới: customer-detail.html**
```
Trang chi tiết khách hàng với layout:

Header:
- Tên khách hàng
- Trạng thái (Active/Inactive)
- Loại khách hàng
- Nút [Chỉnh sửa] [Tạo đơn hàng] [Tạo báo giá]

Content (tabs):
1. Tab "Tổng quan":
   - 4 stat cards: Tổng đơn hàng, Doanh thu, Công nợ, Dự án
   - Timeline hoạt động gần đây
   
2. Tab "Lịch sử giao dịch":
   - Bảng tất cả giao dịch (Báo giá, HĐ, Đơn hàng)
   - Lọc theo loại, thời gian, trạng thái
   
3. Tab "Team & Follow":
   - Nhân viên đã làm việc (lịch sử)
   - Nhân viên đang follow (hiện tại)
   - Workflow đã thực hiện
   
4. Tab "Giao hàng & Hóa đơn":
   - Tình trạng giao hàng từng đơn
   - Danh sách hóa đơn (đã/chưa xuất)
   
5. Tab "Sản phẩm cho mượn":
   - Danh sách SP đang cho mượn
   - Nút [Tạo phiếu cho mượn] [Thu hồi]
```

**Module mới: product-lending.html**
```
Quản lý sản phẩm cho mượn:
- Form tạo phiếu cho mượn
- Chọn khách hàng, sản phẩm
- Ngày mượn, hạn trả
- Lý do cho mượn
- Trạng thái: Đang mượn/Đã trả/Quá hạn
- Nhắc nhở tự động khi đến hạn
```

**Thời gian bổ sung:** 2-3 tuần

---

## 6️⃣ Giao việc & Quản lý công việc (Task Management)

### Yêu cầu hiện tại:
❌ Chưa có module quản lý công việc

### Yêu cầu mới:

#### 6.1. Giao việc cho sales
- Tạo task và gán cho nhân viên
- Thiết lập deadline
- Mức độ ưu tiên (Cao/Trung bình/Thấp)
- Liên kết với khách hàng/dự án/đơn hàng

#### 6.2. Theo dõi tiến độ
- Dashboard task theo nhân viên
- Trạng thái: Mới/Đang làm/Hoàn thành/Quá hạn
- % hoàn thành
- Checklist con trong task

#### 6.3. Nhắc nhở tự động
- Email/notification khi đến deadline
- Nhắc trước 1 ngày, 3 ngày
- Cảnh báo task quá hạn

#### 6.4. Báo cáo
- Báo cáo theo nhân viên
- Báo cáo theo team
- Báo cáo theo dự án
- Báo cáo theo khách hàng

### Điều chỉnh UI:

**Module mới: tasks.html**
```
Layout chính:
- Sidebar: Lọc theo trạng thái, người phụ trách, dự án
- Main: Bảng danh sách task hoặc Kanban board

Dashboard (4 stat cards):
- Tổng task
- Đang làm
- Hoàn thành
- Quá hạn

Bảng task:
Cột: Tên task | Người phụ trách | Khách hàng/Dự án | Deadline | Ưu tiên | Trạng thái | Thao tác

Form tạo task:
- Tên task
- Mô tả chi tiết
- Gán cho (nhân viên/team)
- Liên kết (khách hàng/dự án/đơn hàng)
- Deadline
- Ưu tiên
- Checklist con
- File đính kèm

View chi tiết task:
- Thông tin task
- Checklist con (có thể tick)
- Comments/Notes
- Lịch sử thay đổi
- File đính kèm
```

**Module mới: task-reports.html**
```
Báo cáo task:
- Tab "Theo nhân viên"
- Tab "Theo team"
- Tab "Theo dự án"
- Tab "Theo khách hàng"

Biểu đồ:
- Số task hoàn thành theo thời gian
- Tỷ lệ hoàn thành đúng hạn
- Top performer
```

**Thời gian bổ sung:** 2 tuần

---

## 7️⃣ Phân quyền & Bảo mật nâng cao

### Yêu cầu hiện tại:
❌ Chưa có hệ thống phân quyền

### Yêu cầu mới:

#### 7.1. Phân quyền theo tài khoản
- Tạo user account
- Gán vai trò (Role): Admin, Manager, Sales, Accountant, Warehouse...
- Mỗi role có quyền khác nhau

#### 7.2. Phân quyền theo nhóm/team
- Tạo team/nhóm
- Gán nhân viên vào team
- Phân quyền theo team

#### 7.3. Phân quyền xem margin
- Chỉ Manager và Admin xem được margin
- Sales không xem được margin
- Có thể cấu hình linh hoạt

#### 7.4. Phân quyền dữ liệu
- Mỗi sales chỉ xem được khách hàng của mình
- Manager xem được toàn bộ team
- Admin xem được tất cả

### Điều chỉnh UI:

**Module mới: users.html**
```
Quản lý user:
- Danh sách user
- Form tạo/sửa user:
  + Username, Password
  + Email, SĐT
  + Liên kết nhân viên
  + Vai trò (Role)
  + Team
  + Trạng thái (Active/Inactive)
```

**Module mới: roles-permissions.html**
```
Quản lý vai trò và quyền:
- Danh sách role
- Form tạo/sửa role:
  + Tên role
  + Mô tả
  + Danh sách quyền (checkbox tree):
    ├─ Master Data
    │  ├─ Khách hàng (Xem/Thêm/Sửa/Xóa)
    │  ├─ Nhà cung cấp (Xem/Thêm/Sửa/Xóa)
    │  └─ ...
    ├─ Bán hàng
    │  ├─ Báo giá (Xem/Thêm/Sửa/Xóa/Duyệt)
    │  ├─ Đơn hàng (Xem/Thêm/Sửa/Xóa/Duyệt)
    │  ├─ Xem margin (Có/Không)
    │  └─ ...
    └─ ...
```

**Module mới: teams.html**
```
Quản lý team:
- Danh sách team
- Form tạo/sửa team:
  + Tên team
  + Trưởng team
  + Thành viên
  + Quyền team
```

**Cập nhật tất cả module:**
```
- Kiểm tra quyền trước khi hiển thị nút/chức năng
- Ẩn cột margin nếu user không có quyền
- Lọc dữ liệu theo quyền của user
- Hiển thị thông báo "Không có quyền" nếu truy cập trái phép
```

**Thời gian bổ sung:** 3-4 tuần
- User management: 1 tuần
- Role & Permission: 1 tuần
- Team management: 1 tuần
- Tích hợp vào tất cả module: 1 tuần

---

## 📊 Tổng hợp thời gian bổ sung

| STT | Yêu cầu | Thời gian | Độ ưu tiên |
|-----|---------|-----------|------------|
| 1 | Import & Quản lý bảng giá | 1-2 tuần | ⭐⭐⭐ Cao |
| 2 | Quy trình phê duyệt đa cấp | 3-4 tuần | ⭐⭐⭐⭐ Rất cao |
| 3 | Chi phí BH & Margin nâng cao | 2 tuần | ⭐⭐⭐ Cao |
| 4 | Thông tin dự án chi tiết | 2-3 tuần | ⭐⭐⭐ Cao |
| 5 | CRM mở rộng | 2-3 tuần | ⭐⭐⭐⭐ Rất cao |
| 6 | Task Management | 2 tuần | ⭐⭐ Trung bình |
| 7 | Phân quyền & Bảo mật | 3-4 tuần | ⭐⭐⭐⭐ Rất cao |

**Tổng thời gian bổ sung:** 15-20 tuần (4-5 tháng)

---

## 🎯 Đề xuất lộ trình triển khai

### Giai đoạn 1: Hoàn thiện Phase 1-3 cũ (21-27 tuần)
Hoàn thành 25 modules ban đầu

### Giai đoạn 2: Bổ sung yêu cầu mới (15-20 tuần)
Chia làm 2 sprint:

**Sprint 1 (8-10 tuần) - Ưu tiên cao:**
1. Quy trình phê duyệt đa cấp (3-4 tuần)
2. CRM mở rộng (2-3 tuần)
3. Phân quyền & Bảo mật (3-4 tuần)

**Sprint 2 (7-10 tuần) - Ưu tiên trung bình:**
4. Import & Quản lý bảng giá (1-2 tuần)
5. Chi phí BH & Margin nâng cao (2 tuần)
6. Thông tin dự án chi tiết (2-3 tuần)
7. Task Management (2 tuần)

### Tổng thời gian dự án:
**36-47 tuần (9-12 tháng)** với team 2 người

---

## 💡 Khuyến nghị

### Phương án 1: Làm tuần tự (9-12 tháng)
- Hoàn thành Phase 1-3 trước
- Sau đó làm yêu cầu mới
- Ưu điểm: Có sản phẩm cơ bản sớm để demo
- Nhược điểm: Thời gian dài, có thể phải refactor nhiều

### Phương án 2: Tích hợp ngay từ đầu (10-13 tháng) ⭐ Khuyến nghị
- Tích hợp yêu cầu mới vào từng Phase
- Phase 1: Thêm phân quyền ngay từ đầu
- Phase 2: Làm luôn quy trình duyệt đa cấp, CRM mở rộng
- Phase 3: Bổ sung margin nâng cao, task management
- Ưu điểm: Ít phải refactor, kiến trúc tốt hơn
- Nhược điểm: Mỗi phase lâu hơn

### Phương án 3: MVP + Iteration (Linh hoạt nhất)
- Tháng 1-4: MVP với chức năng cốt lõi
- Tháng 5-7: Iteration 1 - Bổ sung workflow & CRM
- Tháng 8-10: Iteration 2 - Bổ sung phân quyền & task
- Tháng 11-12: Iteration 3 - Hoàn thiện & polish
- Ưu điểm: Linh hoạt, có feedback sớm
- Nhược điểm: Cần quản lý tốt, dễ scope creep

---

## ✅ Checklist triển khai

### Trước khi bắt đầu:
- [ ] Xác nhận lại tất cả yêu cầu với khách hàng
- [ ] Ưu tiên các yêu cầu (Must have / Should have / Nice to have)
- [ ] Thiết kế lại database schema
- [ ] Thiết kế lại UI/UX wireframe
- [ ] Ước tính lại chi phí và timeline
- [ ] Ký hợp đồng bổ sung (nếu cần)

### Trong quá trình:
- [ ] Demo định kỳ 2 tuần/lần
- [ ] Collect feedback và điều chỉnh
- [ ] Update documentation
- [ ] Code review và testing

### Khi hoàn thành:
- [ ] UAT với khách hàng
- [ ] Training cho user
- [ ] Deployment
- [ ] Bảo hành và support

---

**Lưu ý:** Đây là ước tính dựa trên yêu cầu hiện tại. Thời gian thực tế có thể thay đổi sau khi phân tích chi tiết và thống nhất với khách hàng.
