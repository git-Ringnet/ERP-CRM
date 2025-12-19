# Quick Guide: Viết Tests Laravel một lần đúng

## 🚀 Copy-Paste Prompt (Ngắn gọn):

```
Viết tests cho module [TÊN MODULE].

QUAN TRỌNG:
1. Dùng database MySQL hiện có, KHÔNG dùng RefreshDatabase
2. Auto-login admin user (admin@erp.com) bằng firstOrCreate() trong setUp()
3. ĐỌC migrations/models/controllers TRƯỚC KHI VIẾT
4. Factories phải match CHÍNH XÁC với database schema
5. Dùng assertGreaterThanOrEqual() thay vì assertEquals() cho counts
6. Unique codes dùng range 10000-99999
7. Count existing records trước: $countBefore = Model::count()

Modules cần test:
[LIỆT KÊ MODULES]

Workflow:
1. Đọc migrations → models → controllers
2. Tạo factories → test factories
3. Viết tests với flexible assertions
4. Chạy tests → fix lỗi → verify 100% pass

Bắt đầu bằng cách liệt kê migrations và đề xuất test plan.
```

---

## 📋 Checklist nhanh:

### Trước khi viết tests:
- [ ] Đọc tất cả migrations của module
- [ ] Đọc Models (fillable, relationships)
- [ ] Đọc Controllers (routes, logic)
- [ ] Check unique constraints trong database

### Khi viết factories:
- [ ] Match chính xác với database columns
- [ ] Unique codes: 10000-99999
- [ ] Test factory: `Model::factory()->create()`
- [ ] Check enum values nếu có

### Khi viết tests:
- [ ] setUp() tạo admin user bằng firstOrCreate()
- [ ] Count existing records trước test
- [ ] Dùng assertGreaterThanOrEqual()
- [ ] Tránh hardcode codes
- [ ] Handle unique constraints

### Sau khi viết:
- [ ] Seed admin: `php artisan db:seed --class=AdminUserSeeder`
- [ ] Chạy tests: `php artisan test --filter="TestName"`
- [ ] Verify 100% pass
- [ ] Tạo documentation

---

## 🔧 Code Templates:

### setUp() chuẩn:
```php
protected function setUp(): void
{
    parent::setUp();
    
    // Auto-create admin user
    $this->user = User::firstOrCreate(
        ['email' => 'admin@erp.com'],
        [
            'name' => 'Admin',
            'password' => bcrypt('password'),
            'employee_code' => 'ADMIN001',
        ]
    );
    $this->actingAs($this->user);
    
    // Get existing records
    $this->warehouse = Warehouse::first();
    $this->product = Product::first();
}
```

### Factory chuẩn:
```php
public function definition(): array
{
    return [
        'code' => 'PREFIX' . fake()->unique()->numberBetween(10000, 99999),
        'name' => fake()->words(3, true),
        // ... other fields match migrations
    ];
}
```

### Test assertion chuẩn:
```php
// Count trước
$countBefore = Model::where('status', 'active')->count();

// Tạo test data
Model::factory()->count(2)->create(['status' => 'active']);

// Assert với tolerance
$response = $this->get(route('models.index', ['status' => 'active']));
$models = $response->viewData('models');
$this->assertGreaterThanOrEqual($countBefore + 2, $models->total());
```

### Handle unique constraints:
```php
// Nếu có unique (product_id, warehouse_id)
$warehouse1 = Warehouse::factory()->create();
$warehouse2 = Warehouse::factory()->create();

Inventory::factory()->create([
    'warehouse_id' => $warehouse1->id,
    'product_id' => $this->product->id,
]);

Inventory::factory()->create([
    'warehouse_id' => $warehouse2->id, // Khác warehouse
    'product_id' => $this->product->id,
]);
```

---

## ⚡ Commands thường dùng:

```bash
# Seed admin user
php artisan db:seed --class=AdminUserSeeder

# Chạy tests cụ thể
php artisan test --filter="WarehouseTest"

# Chạy nhiều test classes
php artisan test --filter="Warehouse|Inventory|Warranty"

# Chạy 1 test method
php artisan test --filter="test_can_create_warehouse"

# Xem chi tiết lỗi
php artisan test --filter="TestName" --stop-on-failure
```

---

## 🎯 Ví dụ prompt cụ thể:

### Test module Bán hàng:
```
Viết tests cho module Bán hàng (Sales, Sale Items, Invoices).

QUAN TRỌNG:
1. Dùng database MySQL hiện có, KHÔNG dùng RefreshDatabase
2. Auto-login admin user (admin@erp.com) bằng firstOrCreate()
3. ĐỌC migrations/models/controllers TRƯỚC
4. Factories match database schema
5. assertGreaterThanOrEqual() cho counts
6. Unique codes: 10000-99999

Modules:
1. Sales - CRUD, filter by customer/date, search, export
2. Sale Items - Add/remove items, calculate totals
3. Invoices - Generate, print, send email

Bắt đầu: Liệt kê migrations và đề xuất test plan.
```

### Test module Báo cáo:
```
Viết tests cho module Báo cáo (Reports).

QUAN TRỌNG: [copy 6 điểm trên]

Modules:
1. Sales Report - By date, customer, product
2. Inventory Report - Stock levels, movements
3. Financial Report - Revenue, profit, expenses
4. Export to Excel/PDF

Bắt đầu: Đọc ReportController và Services, đề xuất test plan.
```

---

## 🐛 Common Issues & Solutions:

### Issue: "Cannot assign null to property $user"
**Solution**: Database không có admin user
```bash
php artisan db:seed --class=AdminUserSeeder
```

### Issue: "Duplicate entry for key 'unique'"
**Solution**: Tăng range cho unique codes hoặc tạo ở records khác
```php
'code' => 'PREFIX' . fake()->unique()->numberBetween(10000, 99999),
```

### Issue: "Expected 2 but got 5"
**Solution**: Database có dữ liệu cũ, dùng assertGreaterThanOrEqual()
```php
$countBefore = Model::count();
// ... create 2 records
$this->assertGreaterThanOrEqual($countBefore + 2, $result->total());
```

### Issue: "Column not found: 'price'"
**Solution**: Factory không match với migration, đọc lại migration
```php
// Đọc migration để biết columns thực tế
Schema::table('products', function (Blueprint $table) {
    $table->string('code');
    $table->string('name');
    // KHÔNG có 'price' column!
});
```

---

## 📊 Success Metrics:

- ✅ 100% tests pass
- ✅ Không có lỗi authentication
- ✅ Không có lỗi database schema
- ✅ Không có lỗi unique constraints
- ✅ Tests chạy nhanh (< 5s cho 40 tests)
- ✅ Code coverage > 80%

---

## 📚 Files cần tạo:

```
.kiro/specs/[module]-testing/
├── requirements.md
├── design.md
├── tasks.md
└── test-results.md

database/factories/
└── [Model]Factory.php

tests/
├── Feature/[Module]Test.php
└── Unit/[Model]Test.php
```

---

## 🎓 Lessons Learned:

1. **Luôn đọc migrations trước** - Tránh giả định columns
2. **Test factories trước** - Phát hiện lỗi sớm
3. **Flexible assertions** - Database có dữ liệu cũ
4. **Large unique ranges** - Tránh conflicts
5. **firstOrCreate() cho admin** - Tránh lỗi authentication
6. **No RefreshDatabase** - Test với database thực tế

---

**Lưu file này và dùng mỗi khi cần viết tests!** 🚀
