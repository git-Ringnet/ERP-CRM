# Design Document - Warranty & Expiry Tracking

## Overview

Module Theo dõi bảo hành / hạn sử dụng mở rộng hệ thống ERP hiện tại để quản lý thông tin bảo hành của sản phẩm đã bán. Module sử dụng dữ liệu từ bảng `sales`, `sale_items`, `products`, và `product_items` để tính toán và hiển thị thông tin bảo hành.

Thay vì tạo bảng mới, module sẽ thêm các cột cần thiết vào bảng `sale_items` để lưu thông tin bảo hành khi bán hàng.

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Warranty Module                          │
├─────────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │ WarrantyController│  │ WarrantyService │  │ WarrantyReport │ │
│  │                 │  │                 │  │    Service      │ │
│  └────────┬────────┘  └────────┬────────┘  └────────┬────────┘ │
│           │                    │                    │          │
│           └────────────────────┼────────────────────┘          │
│                                │                               │
├────────────────────────────────┼───────────────────────────────┤
│                                ▼                               │
│  ┌─────────────────────────────────────────────────────────┐  │
│  │                    Data Layer                            │  │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐ │  │
│  │  │  Sales   │  │SaleItems │  │ Products │  │ProductItems│ │  │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────┘ │  │
│  └─────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. Database Changes

**Thêm cột vào bảng `sale_items`:**
```sql
ALTER TABLE sale_items ADD COLUMN warranty_months INT UNSIGNED NULL COMMENT 'Số tháng bảo hành';
ALTER TABLE sale_items ADD COLUMN warranty_start_date DATE NULL COMMENT 'Ngày bắt đầu bảo hành';
```

### 2. WarrantyService

Service xử lý logic nghiệp vụ bảo hành:

```php
interface WarrantyServiceInterface
{
    // Tính ngày hết hạn bảo hành
    public function calculateWarrantyEndDate(?Carbon $startDate, ?int $months): ?Carbon;
    
    // Lấy trạng thái bảo hành
    public function getWarrantyStatus(?Carbon $startDate, ?int $months): string;
    
    // Lấy danh sách sản phẩm có bảo hành
    public function getWarrantyList(array $filters): LengthAwarePaginator;
    
    // Lấy danh sách sản phẩm sắp hết hạn
    public function getExpiringWarranties(int $days = 30): Collection;
    
    // Lấy số ngày còn lại của bảo hành
    public function getDaysRemaining(?Carbon $startDate, ?int $months): ?int;
}
```

### 3. WarrantyReportService

Service tạo báo cáo bảo hành:

```php
interface WarrantyReportServiceInterface
{
    // Báo cáo tổng hợp
    public function getSummaryReport(array $filters): array;
    
    // Báo cáo theo khách hàng
    public function getReportByCustomer(array $filters): Collection;
    
    // Báo cáo theo sản phẩm
    public function getReportByProduct(array $filters): Collection;
    
    // Xuất Excel
    public function exportToExcel(array $filters): string;
}
```

### 4. WarrantyController

Controller xử lý HTTP requests:

```php
class WarrantyController extends Controller
{
    // GET /warranties - Danh sách bảo hành
    public function index(Request $request);
    
    // GET /warranties/expiring - Sản phẩm sắp hết hạn
    public function expiring(Request $request);
    
    // GET /warranties/{saleItem} - Chi tiết bảo hành
    public function show(SaleItem $saleItem);
    
    // GET /warranties/report - Báo cáo bảo hành
    public function report(Request $request);
    
    // GET /warranties/export - Xuất Excel
    public function export(Request $request);
}
```

## Data Models

### SaleItem Model (Updated)

```php
class SaleItem extends Model
{
    protected $fillable = [
        // ... existing fields
        'warranty_months',
        'warranty_start_date',
    ];
    
    protected $casts = [
        'warranty_start_date' => 'date',
        'warranty_months' => 'integer',
    ];
    
    // Accessor: Ngày hết hạn bảo hành
    public function getWarrantyEndDateAttribute(): ?Carbon
    {
        if (!$this->warranty_start_date || !$this->warranty_months) {
            return null;
        }
        return $this->warranty_start_date->copy()->addMonths($this->warranty_months);
    }
    
    // Accessor: Trạng thái bảo hành
    public function getWarrantyStatusAttribute(): string
    {
        if (!$this->warranty_months || $this->warranty_months === 0) {
            return 'no_warranty';
        }
        if (!$this->warranty_end_date) {
            return 'no_warranty';
        }
        return now()->lte($this->warranty_end_date) ? 'active' : 'expired';
    }
    
    // Accessor: Số ngày còn lại
    public function getWarrantyDaysRemainingAttribute(): ?int
    {
        if (!$this->warranty_end_date) {
            return null;
        }
        return now()->diffInDays($this->warranty_end_date, false);
    }
}
```

### Warranty Status Constants

```php
class WarrantyStatus
{
    public const ACTIVE = 'active';
    public const EXPIRED = 'expired';
    public const NO_WARRANTY = 'no_warranty';
    
    public static function getLabels(): array
    {
        return [
            self::ACTIVE => 'Đang bảo hành',
            self::EXPIRED => 'Hết hạn',
            self::NO_WARRANTY => 'Không bảo hành',
        ];
    }
    
    public static function getColors(): array
    {
        return [
            self::ACTIVE => 'green',
            self::EXPIRED => 'red',
            self::NO_WARRANTY => 'gray',
        ];
    }
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Warranty end date calculation
*For any* sale item with warranty_start_date and warranty_months > 0, the warranty_end_date should equal warranty_start_date plus warranty_months months.
**Validates: Requirements 1.2, 6.1**

### Property 2: Default warranty months inheritance
*For any* sale item where warranty_months is not specified, the system should use the product's default warranty_months value.
**Validates: Requirements 1.3, 2.2**

### Property 3: Custom warranty months override
*For any* sale item where warranty_months is explicitly specified, the system should use the specified value regardless of product default.
**Validates: Requirements 1.4**

### Property 4: Warranty status calculation
*For any* sale item with warranty, if current date is before or equal to warranty_end_date then status is 'active', otherwise status is 'expired'.
**Validates: Requirements 1.5, 6.2, 6.3**

### Property 5: No warranty status
*For any* sale item where warranty_months is 0 or null, the warranty_status should be 'no_warranty'.
**Validates: Requirements 2.3, 6.4**

### Property 6: Warranty months validation
*For any* warranty_months value, it must be between 0 and 120 (inclusive) to be valid.
**Validates: Requirements 2.4**

### Property 7: Warranty list contains required fields
*For any* warranty list query result, each item should contain: product_code, product_name, serial, customer_name, sale_date, warranty_start, warranty_end, warranty_status.
**Validates: Requirements 3.2**

### Property 8: Status filter correctness
*For any* warranty list filtered by status, all returned items should have the matching warranty_status.
**Validates: Requirements 3.3**

### Property 9: Date range filter correctness
*For any* warranty list filtered by date range, all returned items should have warranty_end_date within the specified range.
**Validates: Requirements 3.4, 5.5**

### Property 10: Expiring warranties filter
*For any* expiring warranties query with X days, all returned items should have warranty_end_date within X days from now and status 'active'.
**Validates: Requirements 4.1**

### Property 11: Expiring list sorting
*For any* expiring warranties list, items should be sorted by warranty_end_date in ascending order.
**Validates: Requirements 4.2**

### Property 12: Summary report accuracy
*For any* warranty summary report, the sum of active + expired + no_warranty counts should equal total sold items count.
**Validates: Requirements 5.1**

## Error Handling

| Error Case | Handling |
|------------|----------|
| Invalid warranty_months (< 0 or > 120) | Validation error, reject save |
| Missing warranty_start_date | Use sale date as default |
| Product without default warranty_months | Allow null, treat as no warranty |
| Date calculation overflow | Cap at reasonable max date |

## Testing Strategy

### Unit Tests
- Test warranty end date calculation with various dates and months
- Test warranty status determination logic
- Test edge cases: 0 months, null values, boundary dates

### Property-Based Tests
Using Pest PHP with Faker for property-based testing:

- **Property 1**: Generate random dates and months, verify end date calculation
- **Property 4**: Generate random dates, verify status is correct based on current date
- **Property 5**: Generate items with 0 or null warranty_months, verify status is 'no_warranty'
- **Property 6**: Generate random integers, verify validation accepts 0-120 and rejects others
- **Property 8**: Generate items with various statuses, verify filter returns correct subset
- **Property 11**: Generate random expiring items, verify sorting order

### Integration Tests
- Test warranty tracking page loads with correct data
- Test filter combinations work correctly
- Test Excel export contains all required data
