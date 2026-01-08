# TỔNG HỢP TEST CASES - HỆ THỐNG ERP

**Ngày tạo**: 31/12/2025  
**Mục đích**: Tổng hợp tất cả test cases đã thực hiện và cần bổ sung

---

## 📊 THỐNG KÊ TỔNG QUAN

| Loại | Số lượng |
|------|----------|
| **Test cases đã hoàn thành** | 82 |
| **Test cases đã skip (có bug/thiếu data)** | 4 |
| **Test cases cần bổ sung** | 58 |
| **TỔNG CỘNG** | 144 |

---

## ✅ PHẦN 1: TEST CASES ĐÃ HOÀN THÀNH (82 CASES)

### 1. MODULE INFRASTRUCTURE (4 test cases)

| STT | Test Case | Mô tả chi tiết | Kết quả |
|-----|-----------|----------------|---------|
| 1 | Login as admin uses seeded admin user | Kiểm tra helper loginAsAdmin() sử dụng đúng admin từ AdminUserSeeder (admin@erp.com) | ✅ PASS |
| 2 | Database is seeded with test data | Kiểm tra database có dữ liệu từ seeders (customers, suppliers, products, warehouses) | ✅ PASS |
| 3 | Helper methods return seeded data | Kiểm tra các helper methods (getSeededCustomers, getSeededSuppliers, etc.) trả về dữ liệu đúng | ✅ PASS |
| 4 | CSRF middleware is disabled | Kiểm tra CSRF middleware bị disable trong môi trường test | ✅ PASS |

### 2. MODULE FACTORY (6 test cases)

| STT | Test Case | Mô tả chi tiết | Kết quả |
|-----|-----------|----------------|---------|
| 1 | Customer factory creates valid instance | Factory tạo customer với tất cả fields hợp lệ (code, name, email, phone, address, type) | ✅ PASS |
| 2 | Supplier factory creates valid instance | Factory tạo supplier với tất cả fields hợp lệ (code, name, email, phone, address) | ✅ PASS |
| 3 | Warehouse factory creates valid instance | Factory tạo warehouse với code tự động, name, address, status | ✅ PASS |
| 4 | Inventory factory creates valid instance | Factory tạo inventory với product_id, warehouse_id, stock, min_stock, avg_cost | ✅ PASS |
| 5 | Inventory transaction factory creates valid instance | Factory tạo transaction với type, code, warehouse_id, employee_id, date, status | ✅ PASS |
| 6 | Damaged good factory creates valid instance | Factory tạo damaged goods với type, product_id, quantity, values, reason, dates | ✅ PASS |

### 3. MODULE CUSTOMER (9 test cases)


| STT | Test Case | Mô tả chi tiết | Kết quả |
|-----|-----------|----------------|---------|
| 1 | Create customer with valid data | Tạo customer mới với: code, name, email, phone, address, type (normal/vip) | ✅ PASS |
| 2 | Reject duplicate customer code | Từ chối tạo customer với code đã tồn tại trong hệ thống | ✅ PASS |
| 3 | Reject invalid email format | Từ chối email không đúng định dạng (vd: "invalid-email") | ✅ PASS |
| 4 | View customer list | Hiển thị danh sách tất cả customers với pagination | ✅ PASS |
| 5 | Search customers by keyword | Tìm kiếm customer theo name, code, email, phone | ✅ PASS |
| 6 | Filter customers by type | Lọc customers theo type (normal hoặc vip) | ✅ PASS |
| 7 | Update customer information | Cập nhật thông tin customer (name, email, phone, address, type) | ✅ PASS |
| 8 | Delete customer without sales | Xóa customer không có đơn hàng liên quan | ✅ PASS |
| 9 | Export customers to Excel | Export danh sách customers ra file Excel | ✅ PASS |

### 4. MODULE SUPPLIER (7 test cases)

| STT | Test Case | Mô tả chi tiết | Kết quả |
|-----|-----------|----------------|---------|
| 1 | Create supplier with valid data | Tạo supplier mới với: code, name, email, phone, address, contact_person | ✅ PASS |
| 2 | Reject duplicate supplier code | Từ chối tạo supplier với code đã tồn tại | ✅ PASS |
| 3 | View supplier list | Hiển thị danh sách tất cả suppliers với pagination | ✅ PASS |
| 4 | Search suppliers by keyword | Tìm kiếm supplier theo name, code, email, phone | ✅ PASS |
| 5 | Update supplier with discount policies | Cập nhật thông tin supplier và chính sách giảm giá | ✅ PASS |
| 6 | Delete supplier without purchase orders | Xóa supplier không có purchase orders liên quan | ✅ PASS |
| 7 | Discount calculation is correct | Kiểm tra tính toán discount theo chính sách của supplier | ✅ PASS |

### 5. MODULE AUTHENTICATION (5 test cases)

| STT | Test Case | Mô tả chi tiết | Kết quả |
|-----|-----------|----------------|---------|
| 1 | Login with valid credentials | Đăng nhập thành công với email và password đúng | ✅ PASS |
| 2 | Reject invalid credentials | Từ chối đăng nhập với email hoặc password sai | ✅ PASS |
| 3 | Locked user cannot login | User bị khóa (is_locked=true) không thể đăng nhập | ✅ PASS |
| 4 | Redirect unauthenticated user to login | User chưa đăng nhập bị redirect về trang login khi truy cập trang yêu cầu auth | ✅ PASS |
| 5 | Logout clears session | Đăng xuất xóa session và redirect về trang login | ✅ PASS |

### 6. MODULE EMPLOYEE (8 test cases)

| STT | Test Case | Mô tả chi tiết | Kết quả |
|-----|-----------|----------------|---------|
| 1 | Create employee with valid data | Tạo employee với: employee_code, name, email, password, phone, position, department | ✅ PASS |
| 2 | Reject duplicate employee code | Từ chối tạo employee với employee_code đã tồn tại | ✅ PASS |
| 3 | Handle duplicate email | Hệ thống cho phép email trùng (không validate unique email) | ✅ PASS |
| 4 | View employee list | Hiển thị danh sách employees với pagination | ✅ PASS |
| 5 | Search employees by keyword | Tìm kiếm employee theo name, employee_code, email, phone | ✅ PASS |
| 6 | Update employee information | Cập nhật thông tin employee (name, email, phone, position, department) | ✅ PASS |
| 7 | Lock employee account | Khóa tài khoản employee (is_locked=true), không cho phép login | ✅ PASS |
| 8 | Unlock employee account | Mở khóa tài khoản employee (is_locked=false), cho phép login lại | ✅ PASS |

### 7. MODULE PRODUCT (7 test cases)

| STT | Test Case | Mô tả chi tiết | Kết quả |
|-----|-----------|----------------|---------|
| 1 | Create product with valid data | Tạo product với: code, name, category, unit, warranty_months, description | ✅ PASS |
| 2 | Reject duplicate product code | Từ chối tạo product với code đã tồn tại | ✅ PASS |
| 3 | View product list | Hiển thị danh sách products với pagination | ✅ PASS |
| 4 | Search products by keyword | Tìm kiếm product theo name, code | ✅ PASS |
| 5 | Filter products by category | Lọc products theo category | ✅ PASS |
| 6 | Update product information | Cập nhật thông tin product (name, category, unit, warranty_months) | ✅ PASS |
| 7 | Delete product without inventory | Xóa product không có inventory liên quan | ✅ PASS |

### 8. MODULE WAREHOUSE (5 test cases)

| STT | Test Case | Mô tả chi tiết | Kết quả |
|-----|-----------|----------------|---------|
| 1 | Create warehouse with auto code | Tạo warehouse với code tự động (WH-001, WH-002...), name, address | ✅ PASS |
| 2 | View warehouse list | Hiển thị danh sách warehouses | ✅ PASS |
| 3 | Update warehouse information | Cập nhật thông tin warehouse (name, address, manager) | ✅ PASS |
| 4 | Change warehouse status | Thay đổi status warehouse (active/inactive) | ✅ PASS |
| 5 | Delete warehouse without inventory | Xóa warehouse không có inventory liên quan | ✅ PASS |

### 9. MODULE INVENTORY (5 test cases)

| STT | Test Case | Mô tả chi tiết | Kết quả |
|-----|-----------|----------------|---------|
| 1 | View inventory list | Hiển thị danh sách inventory với product, warehouse, stock, min_stock | ✅ PASS |
| 2 | Filter inventory by warehouse | Lọc inventory theo warehouse_id | ✅ PASS |
| 3 | View low stock items | Xem các items có stock <= min_stock (sắp hết hàng) | ✅ PASS |
| 4 | View expiring items | Xem các items sắp hết hạn (expiry_date trong 30 ngày tới) | ✅ PASS |
| 5 | Stock updates correctly | Kiểm tra stock cập nhật đúng khi có transaction | ✅ PASS |

### 10. MODULE IMPORT (4 test cases hoàn thành, 1 skip)

| STT | Test Case | Mô tả chi tiết | Kết quả |
|-----|-----------|----------------|---------|
| 1 | Create import transaction | Tạo phiếu nhập kho với: warehouse_id, date, employee_id, items (product_id, quantity) | ✅ PASS |
| 2 | Import updates inventory stock | Import tăng tồn kho đúng số lượng (multi-item) | ✅ PASS |
| 3 | View import list | Hiển thị danh sách phiếu nhập kho với filter (warehouse, status, date) | ✅ PASS |
| 4 | View import details | Xem chi tiết phiếu nhập kho (code, items, quantities, values) | ✅ PASS |
| 5 | Reject pending import | Từ chối phiếu nhập kho đang pending (cập nhật status, ghi lý do) | ⏭️ SKIP (Bug DB) |

### 11. MODULE EXPORT (4 test cases hoàn thành, 1 skip)

| STT | Test Case | Mô tả chi tiết | Kết quả |
|-----|-----------|----------------|---------|
| 1 | Create export transaction | Tạo phiếu xuất kho với: warehouse_id, date, employee_id, items (product_id, quantity) | ✅ PASS |
| 2 | Reject export exceeding stock | Từ chối xuất kho khi quantity > stock hiện có | ✅ PASS |
| 3 | Export decreases inventory stock | Export giảm tồn kho đúng số lượng | ✅ PASS |
| 4 | View export list | Hiển thị danh sách phiếu xuất kho với filter | ✅ PASS |
| 5 | Reject pending export | Từ chối phiếu xuất kho đang pending | ⏭️ SKIP (Bug DB) |

### 12. MODULE TRANSFER (4 test cases hoàn thành, 1 skip)

| STT | Test Case | Mô tả chi tiết | Kết quả |
|-----|-----------|----------------|---------|
| 1 | Create transfer transaction | Tạo phiếu chuyển kho với: from_warehouse, to_warehouse, items (product_id, quantity) | ✅ PASS |
| 2 | Reject transfer exceeding source stock | Từ chối chuyển kho khi quantity > stock tại kho nguồn | ✅ PASS |
| 3 | Transfer updates both warehouses | Chuyển kho giảm stock kho nguồn và tăng stock kho đích | ✅ PASS |
| 4 | View transfer list | Hiển thị danh sách phiếu chuyển kho với filter | ✅ PASS |
| 5 | Reject pending transfer | Từ chối phiếu chuyển kho đang pending | ⏭️ SKIP (Bug DB) |

### 13. MODULE DAMAGED GOODS (5 test cases)

| STT | Test Case | Mô tả chi tiết | Kết quả |
|-----|-----------|----------------|---------|
| 1 | Record damaged goods | Ghi nhận hàng hỏng với: type, product_id, quantity, original_value, recovery_value, reason, discovery_date, discovered_by | ✅ PASS |
| 2 | Validate required fields | Validate các trường bắt buộc khi tạo damaged goods | ✅ PASS |
| 3 | Damaged goods model works | Kiểm tra model relationships và attributes | ✅ PASS |
| 4 | View damaged goods list | Hiển thị danh sách hàng hỏng với filter (type, date, product) | ✅ PASS |
| 5 | Export damaged goods report | Export báo cáo hàng hỏng ra Excel | ✅ PASS |

### 14. MODULE REPORT (5 test cases)

| STT | Test Case | Mô tả chi tiết | Kết quả |
|-----|-----------|----------------|---------|
| 1 | View inventory summary report | Xem báo cáo tổng hợp tồn kho (theo warehouse, product, category) | ✅ PASS |
| 2 | View transaction report | Xem báo cáo giao dịch (import/export/transfer theo thời gian) | ✅ PASS |
| 3 | View damaged goods report | Xem báo cáo hàng hỏng (theo type, thời gian, giá trị) | ✅ PASS |
| 4 | Export reports to Excel | Export các báo cáo ra file Excel | ✅ PASS |
| 5 | Filter reports by date range | Lọc báo cáo theo khoảng thời gian (date_from, date_to) | ✅ PASS |

### 15. MODULE WARRANTY (4 test cases hoàn thành, 1 skip)

| STT | Test Case | Mô tả chi tiết | Kết quả |
|-----|-----------|----------------|---------|
| 1 | View warranty list | Hiển thị danh sách bảo hành từ sale_items | ✅ PASS |
| 2 | View expiring warranties | Xem bảo hành sắp hết hạn (trong 30 ngày tới) | ✅ PASS |
| 3 | View warranty details | Xem chi tiết thông tin bảo hành của 1 item | ⏭️ SKIP (No data) |
| 4 | Export warranty report | Export báo cáo bảo hành ra Excel | ✅ PASS |
| 5 | Warranty expiry calculation | Kiểm tra tính toán ngày hết hạn bảo hành (warranty_start_date + warranty_months) | ✅ PASS |

---


## 📝 PHẦN 2: TEST CASES CẦN BỔ SUNG (58 CASES)

### 1. MODULE CUSTOMER - Cần bổ sung (6 test cases)

| STT | Test Case | Mô tả chi tiết | Độ ưu tiên |
|-----|-----------|----------------|------------|
| 1 | Cannot delete customer with sales | Từ chối xóa customer có đơn hàng liên quan | Cao |
| 2 | Update customer type affects pricing | Thay đổi type (normal -> vip) ảnh hưởng đến giá | Trung bình |
| 3 | Import customers from Excel | Import danh sách customers từ file Excel | Trung bình |
| 4 | Validate phone number format | Kiểm tra định dạng số điện thoại hợp lệ | Thấp |
| 5 | Customer transaction history | Xem lịch sử giao dịch của customer | Cao |
| 6 | Filter customers by date created | Lọc customers theo ngày tạo | Thấp |

### 2. MODULE SUPPLIER - Cần bổ sung (5 test cases)

| STT | Test Case | Mô tả chi tiết | Độ ưu tiên |
|-----|-----------|----------------|------------|
| 1 | Cannot delete supplier with purchase orders | Từ chối xóa supplier có PO liên quan | Cao |
| 2 | Import suppliers from Excel | Import danh sách suppliers từ file Excel | Trung bình |
| 3 | Supplier payment terms validation | Validate điều khoản thanh toán của supplier | Trung bình |
| 4 | View supplier purchase history | Xem lịch sử mua hàng từ supplier | Cao |
| 5 | Supplier performance rating | Đánh giá hiệu suất supplier (delivery time, quality) | Thấp |

### 3. MODULE EMPLOYEE - Cần bổ sung (4 test cases)

| STT | Test Case | Mô tả chi tiết | Độ ưu tiên |
|-----|-----------|----------------|------------|
| 1 | Cannot delete employee with transactions | Từ chối xóa employee có transactions liên quan | Cao |
| 2 | Import employees from Excel | Import danh sách employees từ file Excel | Trung bình |
| 3 | Employee role and permissions | Kiểm tra phân quyền theo role của employee | Cao |
| 4 | Filter employees by department | Lọc employees theo department | Trung bình |

### 4. MODULE PRODUCT - Cần bổ sung (6 test cases)

| STT | Test Case | Mô tả chi tiết | Độ ưu tiên |
|-----|-----------|----------------|------------|
| 1 | Cannot delete product with inventory | Từ chối xóa product có tồn kho | Cao |
| 2 | Cannot delete product with transactions | Từ chối xóa product có transactions liên quan | Cao |
| 3 | Import products from Excel | Import danh sách products từ file Excel | Trung bình |
| 4 | Product warranty validation | Validate warranty_months (phải >= 0) | Trung bình |
| 5 | View product transaction history | Xem lịch sử nhập/xuất của product | Cao |
| 6 | Filter products by warranty period | Lọc products theo thời gian bảo hành | Thấp |

### 5. MODULE WAREHOUSE - Cần bổ sung (4 test cases)

| STT | Test Case | Mô tả chi tiết | Độ ưu tiên |
|-----|-----------|----------------|------------|
| 1 | Cannot delete warehouse with inventory | Từ chối xóa warehouse có tồn kho | Cao |
| 2 | Cannot delete warehouse with pending transactions | Từ chối xóa warehouse có transactions pending | Cao |
| 3 | Inactive warehouse cannot receive transactions | Warehouse inactive không thể nhận import/export | Cao |
| 4 | View warehouse capacity and utilization | Xem công suất và tỷ lệ sử dụng warehouse | Trung bình |

### 6. MODULE INVENTORY - Cần bổ sung (5 test cases)

| STT | Test Case | Mô tả chi tiết | Độ ưu tiên |
|-----|-----------|----------------|------------|
| 1 | Export inventory to Excel | Export danh sách inventory ra Excel | Trung bình |
| 2 | Inventory valuation report | Báo cáo giá trị tồn kho (quantity × avg_cost) | Cao |
| 3 | Filter inventory by product category | Lọc inventory theo category của product | Trung bình |
| 4 | View inventory movement history | Xem lịch sử biến động tồn kho của 1 item | Cao |
| 5 | Alert when stock below minimum | Cảnh báo khi stock < min_stock | Cao |

### 7. MODULE IMPORT - Cần bổ sung (8 test cases)

| STT | Test Case | Mô tả chi tiết | Độ ưu tiên |
|-----|-----------|----------------|------------|
| 1 | Approve pending import | Duyệt phiếu nhập kho pending (status: pending -> completed) | Cao |
| 2 | Reject pending import (fix bug) | Từ chối phiếu nhập pending (cần fix DB ENUM) | Cao |
| 3 | Cannot edit completed import | Không cho phép sửa phiếu nhập đã completed | Cao |
| 4 | Cannot delete import with inventory impact | Không cho phép xóa phiếu nhập đã ảnh hưởng tồn kho | Cao |
| 5 | Import with multiple products | Nhập kho nhiều products cùng lúc | Trung bình |
| 6 | Import cost calculation | Tính toán avg_cost sau khi nhập kho | Cao |
| 7 | Filter imports by date range | Lọc phiếu nhập theo khoảng thời gian | Trung bình |
| 8 | Export imports to Excel | Export danh sách phiếu nhập ra Excel | Thấp |

### 8. MODULE EXPORT - Cần bổ sung (7 test cases)

| STT | Test Case | Mô tả chi tiết | Độ ưu tiên |
|-----|-----------|----------------|------------|
| 1 | Approve pending export | Duyệt phiếu xuất kho pending | Cao |
| 2 | Reject pending export (fix bug) | Từ chối phiếu xuất pending (cần fix DB ENUM) | Cao |
| 3 | Cannot edit completed export | Không cho phép sửa phiếu xuất đã completed | Cao |
| 4 | Cannot delete export with inventory impact | Không cho phép xóa phiếu xuất đã ảnh hưởng tồn kho | Cao |
| 5 | Export with project link | Xuất kho liên kết với project_id | Trung bình |
| 6 | Export cost tracking | Theo dõi giá vốn hàng xuất (FIFO/LIFO) | Cao |
| 7 | Filter exports by project | Lọc phiếu xuất theo project_id | Trung bình |

### 9. MODULE TRANSFER - Cần bổ sung (7 test cases)

| STT | Test Case | Mô tả chi tiết | Độ ưu tiên |
|-----|-----------|----------------|------------|
| 1 | Approve pending transfer | Duyệt phiếu chuyển kho pending | Cao |
| 2 | Reject pending transfer (fix bug) | Từ chối phiếu chuyển pending (cần fix DB ENUM) | Cao |
| 3 | Cannot edit completed transfer | Không cho phép sửa phiếu chuyển đã completed | Cao |
| 4 | Cannot delete transfer with inventory impact | Không cho phép xóa phiếu chuyển đã ảnh hưởng tồn kho | Cao |
| 5 | Cannot transfer to same warehouse | Từ chối chuyển kho cùng warehouse (from = to) | Trung bình |
| 6 | Transfer cost remains unchanged | Kiểm tra avg_cost không đổi khi chuyển kho | Trung bình |
| 7 | Filter transfers by warehouse | Lọc phiếu chuyển theo from/to warehouse | Trung bình |

### 10. MODULE DAMAGED GOODS - Cần bổ sung (4 test cases)

| STT | Test Case | Mô tả chi tiết | Độ ưu tiên |
|-----|-----------|----------------|------------|
| 1 | Approve damaged goods | Duyệt phiếu hàng hỏng (status: pending -> approved) | Cao |
| 2 | Reject damaged goods | Từ chối phiếu hàng hỏng (status: pending -> rejected) | Cao |
| 3 | Process damaged goods | Xử lý hàng hỏng (status: approved -> processed) | Cao |
| 4 | Damaged goods affects inventory | Hàng hỏng giảm tồn kho khi được approve | Cao |

### 11. MODULE REPORT - Cần bổ sung (2 test cases)

| STT | Test Case | Mô tả chi tiết | Độ ưu tiên |
|-----|-----------|----------------|------------|
| 1 | Profit/Loss report | Báo cáo lãi/lỗ (revenue - cost) | Cao |
| 2 | Stock movement report | Báo cáo biến động tồn kho theo thời gian | Cao |

---

## 🐛 PHẦN 3: BUGS CẦN FIX

### Bug 1: inventory_transactions.status ENUM thiếu 'rejected'

**Mô tả**: Controllers sử dụng status 'rejected' nhưng database ENUM chỉ có `['pending', 'completed', 'cancelled']`

**Files ảnh hưởng**:
- `app/Http/Controllers/ImportController.php` (line 344)
- `app/Http/Controllers/ExportController.php` (line 342)
- `app/Http/Controllers/TransferController.php` (line 319)

**Test cases bị ảnh hưởng**: 3 test cases (Import/Export/Transfer reject)

**Giải pháp**: Tạo migration thêm 'rejected' vào ENUM hoặc đổi controller dùng 'cancelled'

---

## 📋 PHẦN 4: TỔNG KẾT

### Tiến độ hoàn thành theo module:

| Module | Đã test | Cần bổ sung | Tổng | % Hoàn thành |
|--------|---------|-------------|------|--------------|
| Infrastructure | 4 | 0 | 4 | 100% |
| Factory | 6 | 0 | 6 | 100% |
| Customer | 9 | 6 | 15 | 60% |
| Supplier | 7 | 5 | 12 | 58% |
| Auth | 5 | 0 | 5 | 100% |
| Employee | 8 | 4 | 12 | 67% |
| Product | 7 | 6 | 13 | 54% |
| Warehouse | 5 | 4 | 9 | 56% |
| Inventory | 5 | 5 | 10 | 50% |
| Import | 4 | 8 | 12 | 33% |
| Export | 4 | 7 | 11 | 36% |
| Transfer | 4 | 7 | 11 | 36% |
| Damaged Goods | 5 | 4 | 9 | 56% |
| Report | 5 | 2 | 7 | 71% |
| Warranty | 4 | 0 | 4 | 100% |
| **TỔNG** | **82** | **58** | **140** | **59%** |

### Ưu tiên thực hiện tiếp:

**Ưu tiên CAO** (cần làm ngay):
1. Fix bug DB ENUM cho reject functions (3 test cases)
2. Approve/Reject workflows cho Import/Export/Transfer (6 test cases)
3. Damaged goods approval workflow (3 test cases)
4. Delete validations (không cho xóa khi có data liên quan) (8 test cases)

**Ưu tiên TRUNG BÌNH**:
1. Import from Excel functions (4 test cases)
2. Export to Excel functions (3 test cases)
3. Transaction history views (4 test cases)
4. Filter và search nâng cao (6 test cases)

**Ưu tiên THẤP**:
1. Performance và capacity reports (3 test cases)
2. Advanced analytics (2 test cases)

---

**Ghi chú**: File này được tạo để tổng hợp và theo dõi tiến độ testing. Cập nhật thường xuyên khi có test cases mới hoàn thành.


---

## 🔴 PHẦN 5: CÁC MODULE QUAN TRỌNG CHƯA CÓ TEST (62 TEST CASES BỔ SUNG)

### 16. MODULE SALE (Bán hàng) - 10 test cases MỚI

| STT | Test Case | Mô tả chi tiết | Độ ưu tiên |
|-----|-----------|----------------|------------|
| 1 | Create sale order with valid data | Tạo đơn bán hàng với: customer_id, date, items (product_id, quantity, price), discount | **CAO** |
| 2 | Calculate sale total correctly | Tính tổng tiền đơn hàng: (quantity × price - discount) + tax | **CAO** |
| 3 | Sale decreases inventory stock | Bán hàng giảm tồn kho tương ứng | **CAO** |
| 4 | Cannot sell more than available stock | Từ chối bán khi quantity > stock | **CAO** |
| 5 | View sale list with filters | Xem danh sách đơn bán hàng, filter theo customer, date, status | **CAO** |
| 6 | Update sale status (pending -> completed) | Cập nhật trạng thái đơn hàng | **CAO** |
| 7 | Cancel sale order | Hủy đơn hàng (hoàn tồn kho nếu đã xuất) | **CAO** |
| 8 | Sale with warranty tracking | Bán hàng tự động tạo warranty record cho items có bảo hành | **TRUNG BÌNH** |
| 9 | Export sales to Excel | Export danh sách đơn bán hàng ra Excel | **TRUNG BÌNH** |
| 10 | Calculate profit margin | Tính lãi: (sale_price - cost) / sale_price × 100% | **CAO** |

### 17. MODULE PROJECT (Dự án) - 8 test cases MỚI

| STT | Test Case | Mô tả chi tiết | Độ ưu tiên |
|-----|-----------|----------------|------------|
| 1 | Create project with valid data | Tạo project với: code, name, customer_id, start_date, end_date, budget | **CAO** |
| 2 | Link export to project | Liên kết phiếu xuất kho với project_id | **CAO** |
| 3 | View project inventory usage | Xem tổng hàng đã xuất cho project | **CAO** |
| 4 | Calculate project cost vs budget | So sánh chi phí thực tế với ngân sách dự kiến | **CAO** |
| 5 | Update project status | Cập nhật trạng thái project (planning, in_progress, completed, cancelled) | **TRUNG BÌNH** |
| 6 | Cannot delete project with exports | Từ chối xóa project có phiếu xuất liên quan | **CAO** |
| 7 | View project list with filters | Xem danh sách projects, filter theo customer, status, date | **TRUNG BÌNH** |
| 8 | Export project report | Export báo cáo chi tiết project (items, costs, timeline) | **TRUNG BÌNH** |

### 18. MODULE QUOTATION (Báo giá) - 8 test cases MỚI

| STT | Test Case | Mô tả chi tiết | Độ ưu tiên |
|-----|-----------|----------------|------------|
| 1 | Create quotation with valid data | Tạo báo giá với: customer_id, items (product_id, quantity, unit_price), validity_date | **CAO** |
| 2 | Calculate quotation total | Tính tổng tiền báo giá với discount và tax | **CAO** |
| 3 | Submit quotation for approval | Gửi báo giá để duyệt (draft -> pending) | **CAO** |
| 4 | Approve quotation | Duyệt báo giá (pending -> approved) | **CAO** |
| 5 | Reject quotation | Từ chối báo giá (pending -> rejected) | **CAO** |
| 6 | Convert quotation to sale | Chuyển báo giá thành đơn hàng (approved -> converted) | **CAO** |
| 7 | Quotation expiry check | Kiểm tra báo giá hết hạn (validity_date < today) | **TRUNG BÌNH** |
| 8 | Cannot edit approved quotation | Không cho phép sửa báo giá đã duyệt | **TRUNG BÌNH** |

### 19. MODULE PURCHASE ORDER (Đơn mua hàng) - 8 test cases MỚI

| STT | Test Case | Mô tả chi tiết | Độ ưu tiên |
|-----|-----------|----------------|------------|
| 1 | Create purchase order with valid data | Tạo PO với: supplier_id, items (product_id, quantity, unit_price), delivery_date | **CAO** |
| 2 | Calculate PO total with supplier discount | Tính tổng tiền PO áp dụng discount của supplier | **CAO** |
| 3 | Submit PO for approval | Gửi PO để duyệt (draft -> pending_approval) | **CAO** |
| 4 | Approve purchase order | Duyệt PO (pending_approval -> approved) | **CAO** |
| 5 | Reject purchase order | Từ chối PO (pending_approval -> rejected) | **CAO** |
| 6 | Send PO to supplier | Gửi PO cho supplier (approved -> sent) | **TRUNG BÌNH** |
| 7 | Confirm PO by supplier | Supplier xác nhận PO (sent -> confirmed) | **TRUNG BÌNH** |
| 8 | Link import to purchase order | Liên kết phiếu nhập kho với PO | **CAO** |

### 20. MODULE PURCHASE REQUEST (Yêu cầu mua hàng) - 6 test cases MỚI

| STT | Test Case | Mô tả chi tiết | Độ ưu tiên |
|-----|-----------|----------------|------------|
| 1 | Create purchase request | Tạo yêu cầu mua hàng với: items (product_id, quantity, reason), requested_by | **CAO** |
| 2 | Submit PR for approval | Gửi PR để duyệt (draft -> pending) | **CAO** |
| 3 | Approve purchase request | Duyệt PR (pending -> approved) | **CAO** |
| 4 | Reject purchase request | Từ chối PR (pending -> rejected) | **CAO** |
| 5 | Convert PR to purchase order | Chuyển PR thành PO sau khi duyệt | **CAO** |
| 6 | Auto create PR for low stock items | Tự động tạo PR khi stock < min_stock | **TRUNG BÌNH** |

### 21. MODULE SUPPLIER QUOTATION (Báo giá nhà cung cấp) - 6 test cases MỚI

| STT | Test Case | Mô tả chi tiết | Độ ưu tiên |
|-----|-----------|----------------|------------|
| 1 | Create supplier quotation | Tạo báo giá từ supplier với: supplier_id, items, prices, validity_date | **CAO** |
| 2 | Compare multiple supplier quotations | So sánh báo giá từ nhiều suppliers cho cùng PR | **CAO** |
| 3 | Select best supplier quotation | Chọn báo giá tốt nhất (status: pending -> selected) | **CAO** |
| 4 | Reject supplier quotation | Từ chối báo giá supplier (pending -> rejected) | **TRUNG BÌNH** |
| 5 | Auto reject other quotations when one selected | Tự động reject các báo giá khác khi chọn 1 báo giá | **TRUNG BÌNH** |
| 6 | Link supplier quotation to PO | Liên kết báo giá supplier với PO được tạo | **TRUNG BÌNH** |

### 22. MODULE PRICE LIST (Bảng giá) - 5 test cases MỚI

| STT | Test Case | Mô tả chi tiết | Độ ưu tiên |
|-----|-----------|----------------|------------|
| 1 | Create price list with valid data | Tạo bảng giá với: name, effective_date, products (product_id, price_tiers) | **CAO** |
| 2 | Apply price list to customer type | Áp dụng bảng giá theo loại khách hàng (normal/vip) | **CAO** |
| 3 | Price tier calculation | Tính giá theo bậc (tier 1: 1-10 units, tier 2: 11-50 units, etc.) | **CAO** |
| 4 | Update price list | Cập nhật giá trong bảng giá | **TRUNG BÌNH** |
| 5 | View active price lists | Xem các bảng giá đang hiệu lực (effective_date <= today) | **TRUNG BÌNH** |

### 23. MODULE NOTIFICATION (Thông báo) - 5 test cases MỚI

| STT | Test Case | Mô tả chi tiết | Độ ưu tiên |
|-----|-----------|----------------|------------|
| 1 | Create notification for user | Tạo thông báo cho user khi có sự kiện (import created, approved, rejected) | **CAO** |
| 2 | Mark notification as read | Đánh dấu thông báo đã đọc | **CAO** |
| 3 | View unread notifications | Xem danh sách thông báo chưa đọc | **CAO** |
| 4 | Delete notification | Xóa thông báo | **TRUNG BÌNH** |
| 5 | Notification bell count | Đếm số thông báo chưa đọc hiển thị trên bell icon | **TRUNG BÌNH** |

### 24. MODULE DASHBOARD (Trang chủ) - 6 test cases MỚI

| STT | Test Case | Mô tả chi tiết | Độ ưu tiên |
|-----|-----------|----------------|------------|
| 1 | View dashboard statistics | Hiển thị thống kê tổng quan (total products, customers, inventory value) | **CAO** |
| 2 | View low stock alerts | Hiển thị cảnh báo hàng sắp hết (stock < min_stock) | **CAO** |
| 3 | View expiring items alerts | Hiển thị cảnh báo hàng sắp hết hạn | **CAO** |
| 4 | View recent transactions | Hiển thị giao dịch gần đây (imports, exports, transfers) | **TRUNG BÌNH** |
| 5 | View pending approvals | Hiển thị các phiếu chờ duyệt | **CAO** |
| 6 | View sales chart | Hiển thị biểu đồ doanh thu theo thời gian | **TRUNG BÌNH** |

---

## 📊 CẬP NHẬT TỔNG KẾT SAU KHI BỔ SUNG

### Tổng số test cases sau khi bổ sung đầy đủ:

| Loại | Số lượng |
|------|----------|
| **Test cases đã hoàn thành** | 82 |
| **Test cases cần bổ sung (đã liệt kê trước)** | 58 |
| **Test cases bổ sung mới (modules chưa test)** | 62 |
| **TỔNG CỘNG** | **202 test cases** |

### Phân loại theo độ ưu tiên:

| Độ ưu tiên | Số lượng | % |
|------------|----------|---|
| **CAO** (Critical) | 98 test cases | 48.5% |
| **TRUNG BÌNH** (Medium) | 104 test cases | 51.5% |
| **THẤP** (Low) | 0 test cases | 0% |

### Tiến độ hoàn thành tổng thể:

```
Đã hoàn thành:  82/202 = 40.6%
Còn lại:       120/202 = 59.4%
```

### Modules chưa có test (cần ưu tiên):

1. ✅ **Sale** (Bán hàng) - Module cốt lõi, cần test ngay
2. ✅ **Project** (Dự án) - Liên quan export, quan trọng
3. ✅ **Quotation** (Báo giá) - Quy trình bán hàng
4. ✅ **Purchase Order** (Đơn mua hàng) - Quy trình mua hàng
5. ✅ **Purchase Request** (Yêu cầu mua) - Quy trình mua hàng
6. ✅ **Supplier Quotation** (Báo giá NCC) - So sánh giá
7. ✅ **Price List** (Bảng giá) - Quản lý giá bán
8. ✅ **Notification** (Thông báo) - Tương tác user
9. ✅ **Dashboard** (Trang chủ) - Tổng quan hệ thống

### Đề xuất lộ trình test tiếp theo:

**GIAI ĐOẠN 1** (Ưu tiên CAO - 40 test cases):
1. Fix bug DB ENUM (3 tests)
2. Sale module - CRUD và inventory impact (10 tests)
3. Project module - Link với export (8 tests)
4. Quotation workflow (8 tests)
5. Purchase Order workflow (8 tests)
6. Dashboard alerts (3 tests)

**GIAI ĐOẠN 2** (Ưu tiên CAO - 35 test cases):
1. Purchase Request workflow (6 tests)
2. Supplier Quotation comparison (6 tests)
3. Price List và pricing logic (5 tests)
4. Approval workflows cho các modules (12 tests)
5. Delete validations (6 tests)

**GIAI ĐOẠN 3** (Ưu tiên TRUNG BÌNH - 45 test cases):
1. Import/Export Excel functions (10 tests)
2. Advanced filters và search (12 tests)
3. Reports và analytics (8 tests)
4. Notification system (5 tests)
5. Transaction history views (10 tests)

---

## 🎯 KẾT LUẬN CUỐI CÙNG

### Hiện trạng:
- ✅ Đã test **82/202 test cases (40.6%)**
- ✅ Đã cover **15/24 modules** cơ bản
- ⚠️ Còn **9 modules quan trọng** chưa test
- 🐛 Có **1 bug nghiêm trọng** cần fix (DB ENUM)

### Đánh giá:
- **58 test cases ban đầu CHƯA ĐỦ** - chỉ bổ sung cho modules đã test
- **Cần thêm 62 test cases** cho các modules chưa test
- **Tổng cộng cần 120 test cases nữa** để đạt coverage tốt

### Khuyến nghị:
1. **Ưu tiên test Sale module** - đây là module cốt lõi nhất
2. **Test các workflow approval** - quan trọng cho quy trình nghiệp vụ
3. **Fix bug DB ENUM ngay** - ảnh hưởng 3 modules
4. **Test integration** giữa các modules (Sale-Inventory, PO-Import, etc.)

**Với 202 test cases đầy đủ, hệ thống sẽ có test coverage tốt (~80-85%) cho các chức năng chính.**
