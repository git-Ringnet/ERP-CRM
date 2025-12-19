# Template Prompt: Viết Tests cho Module Laravel

## Prompt để copy-paste:

```
Viết tests cho module [TÊN MODULE] trong Laravel project.

## YÊU CẦU QUAN TRỌNG:

### 1. Database & Authentication
- Sử dụng database MySQL hiện có (KHÔNG dùng SQLite, KHÔNG dùng RefreshDatabase)
- Tests phải tự động login bằng admin user có sẵn trong database
- Nếu không có admin user, tạo user trong setUp() bằng firstOrCreate()
- Email admin: admin@erp.com / password: password

### 2. Kiểm tra Database Schema TRƯỚC KHI VIẾT
- Đọc tất cả migrations liên quan đến module
- Đọc tất cả Models để xem fillable fields và relationships
- Đọc Controllers để hiểu routes và business logic
- KHÔNG giả định columns - phải check thực tế trong migrations

### 3. Factories
- Tạo factories cho TẤT CẢ models liên quan
- Factories phải match CHÍNH XÁC với database schema
- Sử dụng unique codes với range lớn (10000-99999) để tránh conflicts
- Test factories bằng cách tạo 1 record trước khi viết tests

### 4. Test Assertions
- Sử dụng assertGreaterThanOrEqual() thay vì assertEquals() cho counts
- Count existing records TRƯỚC khi test: $countBefore = Model::count()
- Assert: $this->assertGreaterThanOrEqual($countBefore + X, $result->total())
- Cho phép tolerance cho date/time calculations (±1 day)

### 5. Unique Constraints
- Nếu có unique constraints (product_id + warehouse_id), tạo records ở các warehouses khác nhau
- Sử dụng random codes trong tests thay vì hardcode
- Check unique constraints trong migrations trước khi viết tests

### 6. Test Structure
```php
protected function setUp(): void
{
    parent::setUp();
    
    // Create or get admin user
    $this->user = User::firstOrCreate(
        ['email' => 'admin@erp.com'],
        [
            'name' => 'Admin',
            'password' => bcrypt('password'),
            'employee_code' => 'ADMIN001',
        ]
    );
    
    $this->actingAs($this->user);
    
    // Get existing records if needed
    $this->warehouse = Warehouse::first();
    $this->product = Product::first();
}
```

### 7. Checklist trước khi viết tests:

- [ ] Đọc tất cả migrations của module
- [ ] Đọc tất cả Models (fillable, casts, relationships)
- [ ] Đọc Controllers (routes, methods, business logic)
- [ ] Đọc Services nếu có
- [ ] Tạo factories cho tất cả models
- [ ] Test factories bằng cách tạo 1 record
- [ ] Viết tests với assertions linh hoạt
- [ ] Chạy tests và fix lỗi

### 8. Modules cần test:

[LIỆT KÊ CÁC MODULE CẦN TEST]

Ví dụ:
- Quản lý Kho (Warehouses)
- Quản lý Tồn kho (Inventory)
- Nhập kho (Import)
- Xuất kho (Export)
- Chuyển kho (Transfer)
- Hàng hỏng (Damaged Goods)
- Báo cáo (Reports)

### 9. Test Coverage mong muốn:

- Feature Tests: Test CRUD operations, filtering, search, export
- Unit Tests: Test model methods, calculations, business logic
- Không test authentication (đã có sẵn từ Laravel Breeze)

### 10. Output mong muốn:

1. Tạo spec folder: `.kiro/specs/[module-name]-testing/`
2. Tạo files:
   - requirements.md
   - design.md
   - tasks.md
   - test-results.md (sau khi chạy tests)
3. Tạo factories trong `database/factories/`
4. Tạo tests trong `tests/Feature/` và `tests/Unit/`
5. Chạy tests và báo cáo kết quả

## BẮT ĐẦU:

Hãy bắt đầu bằng cách:
1. Liệt kê tất cả migrations liên quan
2. Liệt kê tất cả models liên quan
3. Đề xuất test plan
4. Sau khi tôi confirm, bắt đầu implement
```

---

## Ví dụ sử dụng cụ thể:

### Prompt cho module Quản lý Kho:

```
Viết tests cho module Quản lý Kho (Warehouses, Inventory, Import, Export, Transfer) trong Laravel project.

## YÊU CẦU QUAN TRỌNG:

[Copy toàn bộ phần YÊU CẦU QUAN TRỌNG từ trên]

### 8. Modules cần test:

1. **Quản lý Kho (Warehouses)**
   - CRUD operations
   - Filter by status, type
   - Search by name, code
   - Validate không xóa kho có tồn kho

2. **Quản lý Tồn kho (Inventory)**
   - View inventory list
   - Filter by warehouse, product, stock status
   - View expiring items
   - Search by product name

3. **Nhập kho (Import)**
   - Create import transaction
   - Update inventory stock
   - View import history
   - Export import report

4. **Xuất kho (Export)**
   - Create export transaction
   - Decrease inventory stock
   - Validate sufficient stock
   - View export history

5. **Chuyển kho (Transfer)**
   - Transfer between warehouses
   - Update both warehouses
   - View transfer history

6. **Hàng hỏng (Damaged Goods)**
   - Record damaged items
   - Decrease inventory
   - View damaged goods report

7. **Báo cáo (Reports)**
   - Inventory summary by warehouse
   - Stock movement report
   - Low stock alert
   - Export to Excel

Hãy bắt đầu bằng cách kiểm tra database schema và đề xuất test plan.
```

---

## Tips quan trọng:

### ✅ DO (Nên làm):
- Đọc migrations trước khi viết tests
- Sử dụng firstOrCreate() cho admin user
- Count existing records trước khi assert
- Sử dụng assertGreaterThanOrEqual()
- Test factories trước khi viết tests
- Sử dụng unique codes với range lớn

### ❌ DON'T (Không nên):
- Không dùng RefreshDatabase trait
- Không hardcode codes (WH001, PROD001)
- Không dùng assertEquals() cho counts
- Không giả định columns trong database
- Không tạo duplicate records vi phạm unique constraints
- Không test authentication (đã có sẵn)

---

## Workflow chuẩn:

1. **Preparation Phase**
   ```
   Đọc migrations → Đọc models → Đọc controllers → Đề xuất test plan
   ```

2. **Factory Phase**
   ```
   Tạo factories → Test factories → Fix nếu có lỗi
   ```

3. **Test Writing Phase**
   ```
   Viết Unit Tests → Viết Feature Tests → Chạy tests
   ```

4. **Fix & Verify Phase**
   ```
   Fix lỗi → Chạy lại tests → Verify 100% pass
   ```

5. **Documentation Phase**
   ```
   Tạo test-results.md → Tạo SUMMARY.md
   ```

---

## Command để chạy tests:

```bash
# Seed admin user trước
php artisan db:seed --class=AdminUserSeeder

# Chạy tests
php artisan test --filter="[TestClassName]"

# Chạy tất cả tests của module
php artisan test --filter="Warehouse|Inventory|Import|Export"
```

---

## Expected Output Structure:

```
.kiro/specs/[module-name]-testing/
├── requirements.md      # Test requirements
├── design.md           # Test strategy & approach
├── tasks.md            # Implementation tasks
├── test-cases.md       # Detailed test cases
├── test-results.md     # Test run results
└── SUMMARY.md          # Final summary

database/factories/
├── WarehouseFactory.php
├── InventoryFactory.php
└── ...

tests/
├── Feature/
│   ├── WarehouseTest.php
│   ├── InventoryTest.php
│   └── ...
└── Unit/
    ├── WarehouseModelTest.php
    └── ...
```
