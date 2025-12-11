# Design Document: Inventory Module Separation

## Overview

Tái cấu trúc module "Quản lý Xuất nhập kho" thành 3 module độc lập: Import (Nhập kho), Export (Xuất kho), và Transfer (Chuyển kho). Mỗi module sẽ có controller, views, routes riêng biệt nhưng chia sẻ chung services và models.

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Laravel Application                       │
├─────────────────────────────────────────────────────────────────┤
│  Routes                                                          │
│  ├── /imports/*     → ImportController                          │
│  ├── /exports/*     → ExportController                          │
│  └── /transfers/*   → TransferController                        │
├─────────────────────────────────────────────────────────────────┤
│  Controllers                                                     │
│  ├── ImportController    (CRUD for import transactions)         │
│  ├── ExportController    (CRUD for export transactions)         │
│  └── TransferController  (CRUD for transfer transactions)       │
├─────────────────────────────────────────────────────────────────┤
│  Shared Services                                                 │
│  ├── TransactionService  (common transaction logic)             │
│  ├── ProductItemService  (SKU management)                       │
│  └── InventoryService    (stock calculations)                   │
├─────────────────────────────────────────────────────────────────┤
│  Shared Models                                                   │
│  ├── InventoryTransaction                                        │
│  ├── InventoryTransactionItem                                    │
│  ├── ProductItem                                                 │
│  └── Product                                                     │
└─────────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. ImportController

```php
class ImportController extends Controller
{
    // Dependencies
    protected TransactionService $transactionService;
    protected ProductItemService $productItemService;
    
    // Methods
    public function index(Request $request);      // List import transactions
    public function create();                      // Show create form
    public function store(ImportRequest $request); // Create new import
    public function show(InventoryTransaction $import); // View details
    public function edit(InventoryTransaction $import); // Show edit form
    public function update(ImportRequest $request, InventoryTransaction $import);
    public function destroy(InventoryTransaction $import);
    public function approve(InventoryTransaction $import); // Approve pending import
}
```

### 2. ExportController

```php
class ExportController extends Controller
{
    // Dependencies
    protected TransactionService $transactionService;
    protected ProductItemService $productItemService;
    protected InventoryService $inventoryService;
    
    // Methods
    public function index(Request $request);      // List export transactions
    public function create();                      // Show create form
    public function store(ExportRequest $request); // Create new export
    public function show(InventoryTransaction $export); // View details
    public function edit(InventoryTransaction $export); // Show edit form
    public function update(ExportRequest $request, InventoryTransaction $export);
    public function destroy(InventoryTransaction $export);
    public function approve(InventoryTransaction $export); // Approve pending export
    public function getAvailableItems(Request $request); // API: Get available SKUs
}
```

### 3. TransferController

```php
class TransferController extends Controller
{
    // Dependencies
    protected TransactionService $transactionService;
    protected ProductItemService $productItemService;
    protected InventoryService $inventoryService;
    
    // Methods
    public function index(Request $request);      // List transfer transactions
    public function create();                      // Show create form
    public function store(TransferRequest $request); // Create new transfer
    public function show(InventoryTransaction $transfer); // View details
    public function edit(InventoryTransaction $transfer); // Show edit form
    public function update(TransferRequest $request, InventoryTransaction $transfer);
    public function destroy(InventoryTransaction $transfer);
    public function approve(InventoryTransaction $transfer); // Approve pending transfer
}
```

### 4. Form Requests

```php
// ImportRequest - Validation for import transactions
class ImportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'employee_id' => 'nullable|exists:users,id',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'nullable|string|max:20',
            'items.*.cost_usd' => 'nullable|numeric|min:0',
            'items.*.skus' => 'nullable|array',
            'items.*.price_tiers' => 'nullable|array',
            'items.*.description' => 'nullable|string',
            'items.*.comments' => 'nullable|string',
        ];
    }
}

// ExportRequest - Validation for export transactions
class ExportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'employee_id' => 'nullable|exists:users,id',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.product_item_ids' => 'nullable|array', // Specific SKUs to export
        ];
    }
}

// TransferRequest - Validation for transfer transactions
class TransferRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:warehouse_id',
            'date' => 'required|date',
            'employee_id' => 'nullable|exists:users,id',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.product_item_ids' => 'nullable|array',
        ];
    }
}
```

## Data Models

Không thay đổi schema database. Sử dụng các models hiện có:

### InventoryTransaction (existing)
- `type` field distinguishes: 'import', 'export', 'transfer'
- Each module filters by its respective type

### Route Binding
```php
// Custom route model binding for type-specific transactions
Route::bind('import', function ($value) {
    return InventoryTransaction::where('id', $value)
        ->where('type', 'import')
        ->firstOrFail();
});

Route::bind('export', function ($value) {
    return InventoryTransaction::where('id', $value)
        ->where('type', 'export')
        ->firstOrFail();
});

Route::bind('transfer', function ($value) {
    return InventoryTransaction::where('id', $value)
        ->where('type', 'transfer')
        ->firstOrFail();
});
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Module Filtering Consistency
*For any* module (Import, Export, or Transfer), when querying transactions, all returned transactions SHALL have the corresponding type ('import', 'export', or 'transfer' respectively).
**Validates: Requirements 1.4, 2.4, 3.4**

### Property 2: Transaction Code Prefix Consistency
*For any* newly created transaction, the generated code SHALL start with the correct prefix based on type: 'IMP' for imports, 'EXP' for exports, 'TRF' for transfers.
**Validates: Requirements 1.6, 2.6, 3.6**

### Property 3: Stock Validation for Outgoing Transactions
*For any* export or transfer transaction, the system SHALL reject the transaction if the requested quantity exceeds available stock in the source warehouse.
**Validates: Requirements 2.7, 3.7**

### Property 4: Transfer Warehouse Validation
*For any* transfer transaction, the source warehouse and destination warehouse SHALL be different.
**Validates: Requirements 3.5**

### Property 5: Data Preservation
*For any* existing transaction in the database, after module separation, the transaction SHALL be accessible through its corresponding new module based on type.
**Validates: Requirements 6.1, 6.2, 6.3**

## Error Handling

| Error Scenario | Module | Response |
|----------------|--------|----------|
| Insufficient stock | Export, Transfer | 422 with error message |
| Invalid warehouse | All | 422 validation error |
| Transaction not found | All | 404 Not Found |
| Wrong transaction type | All | 404 (via route binding) |
| Editing approved transaction | All | Redirect with error flash |

## Testing Strategy

### Unit Tests
- Test each controller method independently
- Test form request validation rules
- Test service method logic

### Property-Based Tests (using Pest + Faker)
- Property 1: Generate random transactions, verify filtering returns correct types
- Property 2: Generate multiple transactions, verify code prefixes
- Property 3: Generate export/transfer with various quantities, verify stock validation
- Property 4: Generate transfers, verify warehouse validation
- Property 5: Query existing data through new modules, verify accessibility

### Integration Tests
- Test complete CRUD flow for each module
- Test approval workflow
- Test navigation between modules

## File Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── ImportController.php      (NEW)
│   │   ├── ExportController.php      (NEW)
│   │   ├── TransferController.php    (NEW)
│   │   └── InventoryTransactionController.php (TO BE REMOVED)
│   └── Requests/
│       ├── ImportRequest.php         (NEW)
│       ├── ExportRequest.php         (NEW)
│       └── TransferRequest.php       (NEW)
│
resources/views/
├── imports/                          (NEW)
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── show.blade.php
│   └── edit.blade.php
├── exports/                          (NEW)
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── show.blade.php
│   └── edit.blade.php
├── transfers/                        (NEW)
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── show.blade.php
│   └── edit.blade.php
└── transactions/                     (TO BE REMOVED)
    └── *.blade.php

routes/
└── web.php                           (UPDATE routes)
```

## Navigation Menu Update

```php
// Sidebar menu items
[
    ['name' => 'Nhập kho', 'route' => 'imports.index', 'icon' => 'fa-arrow-down'],
    ['name' => 'Xuất kho', 'route' => 'exports.index', 'icon' => 'fa-arrow-up'],
    ['name' => 'Chuyển kho', 'route' => 'transfers.index', 'icon' => 'fa-exchange-alt'],
]
```
