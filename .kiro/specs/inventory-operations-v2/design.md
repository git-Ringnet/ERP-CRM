# Design Document - Inventory Operations V2

## Overview

This design document outlines the architecture and implementation details for updating the Export, Transfer, and Bulk Import modules to work with the new product structure that uses product_items with SKU management, USD pricing, and dynamic price tiers.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Presentation Layer                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Export View  │  │Transfer View │  │ Import View  │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                     Controller Layer                         │
│  ┌──────────────────────────────────────────────────────┐   │
│  │         TransactionController                         │   │
│  │  - export() - transfer() - bulkImport()              │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                      Service Layer                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Transaction  │  │ ProductItem  │  │ ExcelImport  │      │
│  │   Service    │  │   Service    │  │   Service    │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                       Model Layer                            │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Inventory    │  │ ProductItem  │  │   Product    │      │
│  │ Transaction  │  │              │  │              │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. TransactionController Updates

**Purpose**: Handle export, transfer, and edit operations

**Methods**:
- `export()`: Display export form with available SKUs
- `storeExport()`: Process export transaction
- `transfer()`: Display transfer form
- `storeTransfer()`: Process transfer transaction
- `edit($id)`: Display edit form for pending transactions
- `update($id)`: Update transaction details

### 2. ExcelImportController

**Purpose**: Handle bulk import operations

**Methods**:
- `index()`: Display import interface
- `downloadTemplate($type)`: Generate and download Excel templates
- `import(Request $request)`: Process uploaded Excel file
- `validateImportData($data, $type)`: Validate imported data

### 3. ExcelImportService

**Purpose**: Business logic for Excel import/export

**Methods**:
- `generateProductTemplate()`: Create product template with examples
- `generateInventoryTemplate()`: Create inventory template with examples
- `importProducts($file)`: Import products from Excel
- `importInventory($file)`: Import inventory with product_items
- `validateRow($row, $rules)`: Validate single row
- `parsePriceTiers($jsonString)`: Parse price_tiers JSON

### 4. ProductItemService Updates

**Purpose**: Extended functionality for product items

**New Methods**:
- `getAvailableItemsForExport($productId, $warehouseId)`: Get in_stock items
- `exportItems($itemIds, $transactionId)`: Update status to 'sold'
- `transferItems($itemIds, $toWarehouseId, $transactionId)`: Update warehouse_id
- `bulkCreateItems($data)`: Create multiple items from import

## Data Models

### InventoryTransaction (Updated)

```php
class InventoryTransaction extends Model
{
    // Existing fields...
    
    // New relationships
    public function productItems()
    {
        return $this->hasMany(ProductItem::class, 'inventory_transaction_id');
    }
    
    // New methods
    public function getTotalSkuCountAttribute()
    {
        return $this->productItems()->count();
    }
}
```

### ProductItem (No changes needed)

Already has all necessary fields and relationships.

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Export SKU Availability
*For any* export transaction and selected SKU list, all SKUs must have status 'in_stock' in the source warehouse before export is allowed
**Validates: Requirements 1.1, 1.3**

### Property 2: Export Status Update
*For any* completed export transaction, all associated product_items must have status changed from 'in_stock' to 'sold'
**Validates: Requirements 1.4**

### Property 3: Transfer Warehouse Consistency
*For any* transfer transaction, all product_items must have warehouse_id updated from source to destination warehouse
**Validates: Requirements 2.3**

### Property 4: Transfer Status Preservation
*For any* transfer transaction, all product_items must maintain status 'in_stock' after transfer
**Validates: Requirements 2.4**

### Property 5: SKU Uniqueness in Import
*For any* bulk import operation, no duplicate SKUs should be created within the same product
**Validates: Requirements 7.3**

### Property 6: Price Tiers JSON Validity
*For any* imported price_tiers data, the JSON must be parseable and contain valid tier objects with 'name' and 'price' fields
**Validates: Requirements 3.4, 7.5**

### Property 7: Import Rollback on Error
*For any* bulk import operation that encounters validation errors, no partial data should be saved to database
**Validates: Requirements 3.7**

### Property 8: NO_SKU Generation for Empty SKU
*For any* import row with empty SKU field, the system must auto-generate a unique NO_SKU identifier
**Validates: Requirements 3.5**

### Property 9: Edit Restriction for Approved Transactions
*For any* transaction with status 'approved', edit operations must be rejected
**Validates: Requirements 4.3**

### Property 10: Transaction Display Completeness
*For any* transaction view, all associated product_items with their SKU, cost_usd, and price_tiers must be displayed
**Validates: Requirements 5.1, 5.2**

## Error Handling

### Export Errors
- Insufficient SKU availability → Display error with available count
- Invalid warehouse selection → Validation error
- SKU not found → Display specific SKU error

### Transfer Errors
- Source and destination warehouse same → Validation error
- SKU not in source warehouse → Display error
- Invalid warehouse IDs → Validation error

### Import Errors
- Invalid file format → Display format error
- Missing required columns → Display column list
- Row validation failure → Display row number and specific error
- Duplicate SKU → Display SKU and product code
- Invalid JSON in price_tiers → Display row and JSON error
- Product/Warehouse not found → Display code and row number

## Testing Strategy

### Unit Tests
- Test ExcelImportService methods with sample data
- Test ProductItemService export/transfer methods
- Test validation logic for each field type
- Test NO_SKU generation logic
- Test price_tiers JSON parsing

### Property-Based Tests
- Property 1: Generate random in_stock items, attempt export, verify all are available
- Property 2: Generate random export transaction, verify all items status changed to 'sold'
- Property 3: Generate random transfer, verify all items warehouse_id updated
- Property 4: Generate random transfer, verify all items remain 'in_stock'
- Property 5: Generate random import data with duplicate SKUs, verify rejection
- Property 6: Generate random price_tiers JSON, verify parsing succeeds or fails appropriately
- Property 7: Generate import with intentional errors, verify no data saved
- Property 8: Generate import rows with empty SKUs, verify NO_SKU generated
- Property 9: Generate approved transaction, attempt edit, verify rejection
- Property 10: Generate random transaction, verify all product_items displayed

### Integration Tests
- Test complete export flow from form to database
- Test complete transfer flow between warehouses
- Test complete import flow from Excel to database
- Test edit flow for pending transactions

## UI/UX Considerations

### Export View
- Display available SKUs in a selectable list with checkboxes
- Show SKU details: cost_usd, price_tiers, status
- Real-time availability check
- Clear visual feedback for selected items

### Transfer View
- Two-step selection: source warehouse → destination warehouse
- Display available SKUs from source
- Show transfer summary before confirmation
- Visual indication of warehouse change

### Import View
- Drag-and-drop file upload
- Template download buttons with clear labels
- Progress indicator during import
- Detailed error report with row numbers
- Success summary with counts

### Edit View
- Reuse import form layout
- Pre-populate with existing data
- Disable editing for approved transactions
- Show warning before saving changes

## Excel Template Structure

### Product Template
| Code | Name | Category | Unit | Description | Note |
|------|------|----------|------|-------------|------|
| SP001 | Product 1 | A | Cái | Sample product | Note |

### Inventory Template
| Product_Code | Warehouse_Code | Quantity | SKU | Cost_USD | Price_Tiers_JSON | Description | Comments | Transaction_Date |
|--------------|----------------|----------|-----|----------|------------------|-------------|----------|------------------|
| SP001 | WH01 | 1 | SKU001 | 100.00 | [{"name":"1yr","price":120},{"name":"2yr","price":200}] | Item desc | Comment | 2024-12-10 |
| SP001 | WH01 | 1 | | 100.00 | [{"name":"1yr","price":120}] | No SKU item | | 2024-12-10 |

## Implementation Notes

- Use Laravel Excel package for import/export
- Implement chunked reading for large files
- Use database transactions for import operations
- Cache available SKUs for performance
- Implement queue jobs for large imports
- Add progress tracking for long-running imports
