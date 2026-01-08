# Design Document

## Overview

Tách bảng `inventory_transactions` thành 3 bảng riêng biệt: `imports`, `exports`, `transfers`. Giữ nguyên cấu trúc cột, chỉ tách ra để dễ quản lý và scale. Tương tự tách `inventory_transaction_items` thành 3 bảng items tương ứng.

## Architecture

### Hiện tại (Before):
```
┌─────────────────────────────────┐
│   inventory_transactions        │
│   (type: import/export/transfer)│
└─────────────────────────────────┘
              │
              ▼
┌─────────────────────────────────┐
│   inventory_transaction_items   │
│   (transaction_id)              │
└─────────────────────────────────┘
```

### Sau khi tách (After):
```
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│   imports    │  │   exports    │  │  transfers   │
└──────────────┘  └──────────────┘  └──────────────┘
       │                 │                 │
       ▼                 ▼                 ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│ import_items │  │ export_items │  │transfer_items│
└──────────────┘  └──────────────┘  └──────────────┘
```

## Components and Interfaces

### Hiện trạng Controllers (đã có sẵn 3 controllers riêng)

```
app/Http/Controllers/
├── ImportController.php   ← Đã có, chỉ cần đổi model
├── ExportController.php   ← Đã có, chỉ cần đổi model
└── TransferController.php ← Đã có, chỉ cần đổi model
```

**Lưu ý**: 3 controllers này đã tồn tại và hoạt động riêng biệt. Chỉ cần thay thế `InventoryTransaction` model bằng `Import`, `Export`, `Transfer` models tương ứng.

### Database Tables

#### 1. imports (giữ nguyên cột từ inventory_transactions)
```sql
CREATE TABLE imports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    employee_id BIGINT UNSIGNED NULL,
    total_qty INT DEFAULT 0,
    reference_type VARCHAR(50) NULL,
    reference_id BIGINT NULL,
    note TEXT NULL,
    status ENUM('pending', 'completed', 'cancelled', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (date),
    INDEX (status)
);
```

#### 2. exports (giữ nguyên cột + project_id)
```sql
CREATE TABLE exports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NULL,
    date DATE NOT NULL,
    employee_id BIGINT UNSIGNED NULL,
    total_qty INT DEFAULT 0,
    reference_type VARCHAR(50) NULL,
    reference_id BIGINT NULL,
    note TEXT NULL,
    status ENUM('pending', 'completed', 'cancelled', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (date),
    INDEX (status)
);
```

#### 3. transfers (from_warehouse_id, to_warehouse_id)
```sql
CREATE TABLE transfers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    from_warehouse_id BIGINT UNSIGNED NOT NULL,
    to_warehouse_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    employee_id BIGINT UNSIGNED NULL,
    total_qty INT DEFAULT 0,
    note TEXT NULL,
    status ENUM('pending', 'completed', 'cancelled', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (from_warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    FOREIGN KEY (to_warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (date),
    INDEX (status)
);
```

#### 4. import_items (giữ nguyên cột từ inventory_transaction_items)
```sql
CREATE TABLE import_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    unit VARCHAR(20) NULL,
    cost DECIMAL(15,2) DEFAULT 0,
    serial_number TEXT NULL,
    comments TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

#### 5. export_items
```sql
CREATE TABLE export_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    export_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    unit VARCHAR(20) NULL,
    serial_number TEXT NULL,
    comments TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (export_id) REFERENCES exports(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

#### 6. transfer_items
```sql
CREATE TABLE transfer_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transfer_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    unit VARCHAR(20) NULL,
    serial_number TEXT NULL,
    comments TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (transfer_id) REFERENCES transfers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

### Models

#### Import Model
```php
// app/Models/Import.php
class Import extends Model
{
    protected $fillable = [
        'code', 'warehouse_id', 'date', 'employee_id',
        'total_qty', 'reference_type', 'reference_id', 'note', 'status'
    ];
    
    protected $casts = ['date' => 'date'];
    
    public function warehouse(): BelongsTo
    public function employee(): BelongsTo
    public function items(): HasMany
    public static function generateCode(): string // Prefix: IMP
}
```

#### Export Model
```php
// app/Models/Export.php
class Export extends Model
{
    protected $fillable = [
        'code', 'warehouse_id', 'project_id', 'date', 'employee_id',
        'total_qty', 'reference_type', 'reference_id', 'note', 'status'
    ];
    
    public function warehouse(): BelongsTo
    public function project(): BelongsTo
    public function employee(): BelongsTo
    public function items(): HasMany
    public static function generateCode(): string // Prefix: EXP
}
```

#### Transfer Model
```php
// app/Models/Transfer.php
class Transfer extends Model
{
    protected $fillable = [
        'code', 'from_warehouse_id', 'to_warehouse_id', 'date',
        'employee_id', 'total_qty', 'note', 'status'
    ];
    
    public function fromWarehouse(): BelongsTo
    public function toWarehouse(): BelongsTo
    public function employee(): BelongsTo
    public function items(): HasMany
    public static function generateCode(): string // Prefix: TRF
}
```

## Data Models

### Migration Strategy

```
Step 1: Tạo 6 bảng mới (imports, exports, transfers, import_items, export_items, transfer_items)
Step 2: Copy data từ inventory_transactions → imports/exports/transfers theo type
Step 3: Copy data từ inventory_transaction_items → import_items/export_items/transfer_items
Step 4: Update product_items.inventory_transaction_id → import_id (polymorphic)
Step 5: Drop bảng cũ (inventory_transactions, inventory_transaction_items)
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Data integrity after migration
*For any* record in old inventory_transactions table, there SHALL exist exactly one corresponding record in imports, exports, or transfers table with identical field values.
**Validates: Requirements 5.1, 5.2, 5.3**

### Property 2: Item relationship preservation
*For any* item in old inventory_transaction_items table, there SHALL exist exactly one corresponding record in import_items, export_items, or transfer_items with correct foreign key.
**Validates: Requirements 5.4**

### Property 3: Code generation uniqueness
*For any* new import/export/transfer created, the generated code SHALL be unique within its respective table.
**Validates: Requirements 1.2, 2.2, 3.2**

### Property 4: Status transition validity
*For any* status update on import/export/transfer, the new status SHALL be one of: pending, completed, cancelled, rejected.
**Validates: Requirements 1.4, 2.4, 3.4**

### Property 5: Warehouse validation for transfers
*For any* transfer created, from_warehouse_id SHALL differ from to_warehouse_id.
**Validates: Requirements 3.3**

## Error Handling

1. **Migration Errors**: Wrap trong DB::transaction, rollback nếu lỗi
2. **Foreign Key Violations**: Check existence trước khi insert
3. **Duplicate Code**: Catch unique constraint exception, regenerate code
4. **Invalid Status**: Validate against ENUM values

## Testing Strategy

### Unit Tests
- Test model relationships
- Test code generation
- Test status validation

### Integration Tests
- Test CRUD operations qua controllers
- Test inventory updates
- Test report queries

### Migration Tests
- Verify record counts match
- Verify data integrity
- Verify foreign keys work
