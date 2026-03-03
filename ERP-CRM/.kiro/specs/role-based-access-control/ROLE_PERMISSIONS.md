# Kế hoạch Phân quyền theo Vai trò

## Tổng quan

Hệ thống có **9 vai trò** được định nghĩa sẵn với phân quyền phù hợp cho từng vị trí công việc.

Tổng số permissions: **170 quyền** (43 modules)

---

## 1. Quản trị viên (Super Admin)
**Slug:** `super_admin`

**Mô tả:** Toàn quyền truy cập hệ thống

**Quyền:** TẤT CẢ (170 quyền)
- Tất cả quyền view, create, edit, delete, export, approve cho mọi module
- Quản lý roles, permissions, users
- Xem audit logs
- Quản lý settings

---

## 2. Quản lý Kho (Warehouse Manager)
**Slug:** `warehouse_manager`

**Mô tả:** Quản lý toàn bộ hoạt động kho

**Modules có toàn quyền:**
- ✅ Kho hàng (warehouses) - 4 quyền (view, create, edit, delete)
- ✅ Tồn kho (inventory) - 2 quyền (view, export)
- ✅ Nhập kho (imports) - 6 quyền (view, create, edit, delete, export, **approve**)
- ✅ Xuất kho (exports) - 6 quyền (view, create, edit, delete, export, **approve**)
- ✅ Chuyển kho (transfers) - 5 quyền (view, create, edit, delete, export)
- ✅ Hàng hư hỏng (damaged_goods) - 5 quyền (view, create, edit, delete, export)
- ✅ Sản phẩm (products) - 5 quyền (view, create, edit, delete, export)
- ✅ Nhập Excel (excel_imports) - 2 quyền (view, create)

**Tổng:** 35 quyền

---

## 3. Nhân viên Kho (Warehouse Staff)
**Slug:** `warehouse_staff`

**Mô tả:** Xử lý hoạt động kho hàng hàng ngày

**Quyền:**
- ✅ Nhập kho (imports) - view, create, edit
- ✅ Xuất kho (exports) - view, create, edit
- ✅ Chuyển kho (transfers) - view, create, edit
- ✅ Hàng hư hỏng (damaged_goods) - view, create, edit
- ✅ Tồn kho (inventory) - view
- ✅ Sản phẩm (products) - view
- ✅ Kho hàng (warehouses) - view

**Không có quyền:**
- ❌ Delete (xóa)
- ❌ Approve (duyệt)
- ❌ Export (xuất báo cáo)

**Tổng:** 15 quyền

---

## 4. Quản lý Bán hàng (Sales Manager)
**Slug:** `sales_manager`

**Mô tả:** Quản lý toàn bộ hoạt động bán hàng và chăm sóc khách hàng

**Modules có toàn quyền:**
- ✅ Khách hàng (customers) - view, create, edit, delete, export
- ✅ Đơn hàng bán (sales) - view, create, edit, delete, export + **view_all_sales**
- ✅ Báo giá (quotations) - view, create, edit, delete, export, **approve** + **view_all_quotations**
- ✅ Đấu mối (leads) - view, create, edit, delete, export
- ✅ Cơ hội (opportunities) - view, create, edit, delete, export
- ✅ Công việc (activities) - view, create, edit, delete, export
- ✅ Dự án (projects) - view, create, edit, delete, export
- ✅ Chăm sóc KH (customer_care_stages) - view, create, edit, delete
- ✅ Công nợ KH (customer_debts) - view, export
- ✅ Báo cáo bán hàng (sale_reports) - view, export
- ✅ Bảng giá (price_lists) - view, create, edit, delete, export
- ✅ Bảo hành (warranties) - view, export
- ✅ Mẫu mốc quan trọng (milestone_templates) - view, create, edit, delete

**Tổng:** 59 quyền

---

## 5. Nhân viên Bán hàng (Sales Staff)
**Slug:** `sales_staff`

**Mô tả:** Xử lý quan hệ khách hàng và đơn hàng

**Quyền:**
- ✅ Khách hàng (customers) - view, create, edit
- ✅ Đơn hàng bán (sales) - view, create, edit + **view_own_sales** (chỉ xem đơn của mình)
- ✅ Báo giá (quotations) - view, create, edit + **view_own_quotations**
- ✅ Đấu mối (leads) - view, create, edit
- ✅ Cơ hội (opportunities) - view, create, edit
- ✅ Công việc (activities) - view, create, edit
- ✅ Dự án (projects) - view, create, edit
- ✅ Chăm sóc KH (customer_care_stages) - view, create, edit
- ✅ Công nợ KH (customer_debts) - view
- ✅ Bảo hành (warranties) - view
- ✅ Bảng giá (price_lists) - view

**Không có quyền:**
- ❌ Delete (xóa)
- ❌ Approve (duyệt)
- ❌ Export (xuất báo cáo)
- ❌ View all sales/quotations (chỉ xem của mình)

**Tổng:** 31 quyền

---

## 6. Quản lý Mua hàng (Purchase Manager)
**Slug:** `purchase_manager`

**Mô tả:** Quản lý toàn bộ hoạt động mua hàng

**Modules có toàn quyền:**
- ✅ Nhà cung cấp (suppliers) - view, create, edit, delete, export
- ✅ Đơn mua hàng (purchase_orders) - view, create, edit, delete, export, **approve** + **view_all_purchase_orders**
- ✅ Yêu cầu báo giá (purchase_requests) - view, create, edit, delete, export
- ✅ Báo giá NCC (supplier_quotations) - view, create, edit, delete, export
- ✅ Bảng giá NCC (supplier_price_lists) - view, create, edit, delete, export
- ✅ Phân bổ vận chuyển (shipping_allocations) - view, create, edit, delete, export
- ✅ Báo cáo mua hàng (purchase_reports) - view, export
- ✅ Công thức chi phí (cost_formulas) - view, create, edit, delete

**Tổng:** 39 quyền

---

## 7. Nhân viên Mua hàng (Purchase Staff)
**Slug:** `purchase_staff`

**Mô tả:** Xử lý quan hệ nhà cung cấp và đơn mua hàng

**Quyền:**
- ✅ Nhà cung cấp (suppliers) - view, create, edit
- ✅ Đơn mua hàng (purchase_orders) - view, create, edit + **view_own_purchase_orders**
- ✅ Yêu cầu báo giá (purchase_requests) - view, create, edit
- ✅ Báo giá NCC (supplier_quotations) - view, create, edit
- ✅ Bảng giá NCC (supplier_price_lists) - view, create, edit
- ✅ Phân bổ vận chuyển (shipping_allocations) - view, create, edit
- ✅ Công thức chi phí (cost_formulas) - view

**Không có quyền:**
- ❌ Delete (xóa)
- ❌ Approve (duyệt)
- ❌ Export (xuất báo cáo)
- ❌ View all purchase orders (chỉ xem của mình)

**Tổng:** 21 quyền

---

## 8. Kế toán (Accountant)
**Slug:** `accountant`

**Mô tả:** Xem và xuất dữ liệu tài chính, báo cáo

**Quyền:**
- ✅ **View** tất cả modules
- ✅ **Export** tất cả modules
- ✅ **view_all_sales** - Xem tất cả đơn hàng bán
- ✅ **view_all_quotations** - Xem tất cả báo giá
- ✅ **view_all_purchase_orders** - Xem tất cả đơn mua hàng

**Không có quyền:**
- ❌ Create, Edit, Delete (không được thay đổi dữ liệu)
- ❌ Approve (không được duyệt)

**Tổng:** 75 quyền (view + export cho tất cả modules)

---

## 9. Giám đốc (Director)
**Slug:** `director`

**Mô tả:** Xem tất cả dữ liệu và duyệt các hoạt động quan trọng

**Quyền:**
- ✅ **View** tất cả modules
- ✅ **Approve** tất cả (imports, exports, quotations, purchase_orders)
- ✅ **Export** tất cả modules
- ✅ Xem tất cả báo cáo (reports, sale_reports, purchase_reports)
- ✅ **view_all_sales**, **view_all_quotations**, **view_all_purchase_orders**

**Không có quyền:**
- ❌ Create, Edit, Delete (không trực tiếp thao tác dữ liệu)

**Tổng:** 79 quyền

---

## So sánh Quyền theo Vai trò

| Vai trò | View | Create | Edit | Delete | Export | Approve | Tổng quyền |
|---------|------|--------|------|--------|--------|---------|------------|
| Super Admin | ✅ Tất cả | ✅ Tất cả | ✅ Tất cả | ✅ Tất cả | ✅ Tất cả | ✅ Tất cả | 170 |
| Warehouse Manager | ✅ Kho | ✅ Kho | ✅ Kho | ✅ Kho | ✅ Kho | ✅ Nhập/Xuất | 35 |
| Warehouse Staff | ✅ Kho | ✅ Kho | ✅ Kho | ❌ | ❌ | ❌ | 15 |
| Sales Manager | ✅ Bán hàng | ✅ Bán hàng | ✅ Bán hàng | ✅ Bán hàng | ✅ Bán hàng | ✅ Báo giá | 59 |
| Sales Staff | ✅ Bán hàng | ✅ Bán hàng | ✅ Bán hàng | ❌ | ❌ | ❌ | 31 |
| Purchase Manager | ✅ Mua hàng | ✅ Mua hàng | ✅ Mua hàng | ✅ Mua hàng | ✅ Mua hàng | ✅ Đơn mua | 39 |
| Purchase Staff | ✅ Mua hàng | ✅ Mua hàng | ✅ Mua hàng | ❌ | ❌ | ❌ | 21 |
| Accountant | ✅ Tất cả | ❌ | ❌ | ❌ | ✅ Tất cả | ❌ | 75 |
| Director | ✅ Tất cả | ❌ | ❌ | ❌ | ✅ Tất cả | ✅ Tất cả | 79 |

---

## Quyền đặc biệt

### View Own vs View All
Một số vai trò chỉ được xem dữ liệu của mình:

- **Sales Staff**: `view_own_sales`, `view_own_quotations`
- **Purchase Staff**: `view_own_purchase_orders`

Các Manager và Director có quyền `view_all_*` để xem tất cả.

### Approve Permissions
Chỉ Manager và Director mới có quyền duyệt:

- **Warehouse Manager**: approve_imports, approve_exports
- **Sales Manager**: approve_quotations
- **Purchase Manager**: approve_purchase_orders
- **Director**: approve tất cả

---

## Cách kiểm tra phân quyền

Sau khi chạy seeder, bạn có thể kiểm tra:

```bash
# Xem tổng số permissions
SELECT COUNT(*) FROM permissions;

# Xem số quyền của từng vai trò
SELECT r.name, COUNT(rp.permission_id) as permission_count
FROM roles r
LEFT JOIN role_permissions rp ON r.id = rp.role_id
GROUP BY r.id, r.name
ORDER BY permission_count DESC;

# Xem chi tiết quyền của một vai trò
SELECT p.name, p.module, p.action
FROM permissions p
JOIN role_permissions rp ON p.id = rp.permission_id
JOIN roles r ON rp.role_id = r.id
WHERE r.slug = 'sales_staff'
ORDER BY p.module, p.action;
```

---

## Lưu ý

1. **Super Admin** có tất cả quyền - dùng cho IT và quản trị hệ thống
2. **Manager roles** có quyền đầy đủ trong phạm vi của mình + approve
3. **Staff roles** chỉ có view, create, edit - không có delete, approve, export
4. **Accountant** chỉ xem và xuất báo cáo - không thay đổi dữ liệu
5. **Director** xem tất cả và duyệt - không trực tiếp thao tác dữ liệu

Phân quyền này đảm bảo:
- ✅ Phân tách trách nhiệm rõ ràng
- ✅ Bảo mật dữ liệu (staff chỉ xem của mình)
- ✅ Kiểm soát quy trình (cần duyệt từ manager)
- ✅ Audit trail đầy đủ
