# Implementation Plan

## Phase 1: Database Schema Updates

- [x] 1. Update Products table migration




  - [ ] 1.1 Create migration to remove unnecessary columns from products table
    - Remove columns: price, cost, stock, min_stock, max_stock, management_type, auto_generate_serial, serial_prefix, expiry_months, track_expiry
    - Update category column to CHAR(1)


    - Keep columns: id, code, name, category, unit, description, note, timestamps
    - _Requirements: 1.4_
  - [ ] 1.2 Create product_items table migration
    - Create table with columns: id, product_id, sku, description, price, price_1yr, price_2yr, price_3yr, price_4yr, price_5yr, quantity, comments, warehouse_id, inventory_transaction_id, status, timestamps
    - Add foreign keys to products, warehouses, inventory_transactions




    - Add unique index on (product_id, sku)
    - _Requirements: 3.1_


## Phase 2: Models Update

- [ ] 2. Update Product Model
  - [x] 2.1 Simplify Product model fillable and casts

    - Update $fillable to only: code, name, category, unit, description, note
    - Remove price, cost, stock related casts
    - Remove stock-related accessor methods
    - _Requirements: 1.1, 1.2, 1.3_
  - [ ] 2.2 Add ProductItem relationship to Product model
    - Add items() hasMany relationship
    - Add getTotalQuantityAttribute() accessor
    - Add getInStockQuantityAttribute() accessor
    - _Requirements: 6.4, 7.4_

  - [x] 2.3 Update Product model scopes



    - Update scopeSearch to search only basic fields
    - Add scopeFilterByCategory for single letter filter

    - _Requirements: 2.3_
  - [ ]* 2.4 Write property test for category validation
    - **Property 2: Category Validation**
    - **Validates: Requirements 2.1, 2.2**
  - [ ]* 2.5 Write property test for category filter
    - **Property 3: Category Filter Correctness**
    - **Validates: Requirements 2.3**

- [ ] 3. Create ProductItem Model
  - [x] 3.1 Create ProductItem model with relationships

    - Define $fillable with all columns
    - Add product(), warehouse(), inventoryTransaction() relationships
    - Add status constants: IN_STOCK, SOLD, DAMAGED, TRANSFERRED
    - _Requirements: 3.2, 3.3_




  - [ ] 3.2 Add NO_SKU generation method
    - Create static generateNoSku(int $productId): string method
    - Format: NOSKU-{product_id}-{sequential_number} (3-digit padded)
    - Add isNoSku(): bool helper method
    - _Requirements: 3.4_
  - [ ]* 3.3 Write property test for NO_SKU format
    - **Property 6: NO_SKU Format Correctness**
    - **Validates: Requirements 3.4**
  - [ ]* 3.4 Write property test for SKU uniqueness
    - **Property 5: SKU Uniqueness Within Product**
    - **Validates: Requirements 3.3, 4.5**





- [ ] 4. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.


## Phase 3: Services Layer

- [x] 5. Create ProductItemService

  - [ ] 5.1 Create ProductItemService class
    - Create createItemsFromImport() method
    - Create generateNoSku() method

    - Create validateSkuUniqueness() method
    - Create updateItemStatus() method
    - _Requirements: 4.2, 4.3, 7.3_
  - [ ]* 5.2 Write property test for import item creation
    - **Property 7: Import Creates Correct Number of Items**
    - **Validates: Requirements 4.2, 4.3**
  - [ ]* 5.3 Write property test for transaction linkage
    - **Property 10: Import Links Items to Transaction**
    - **Validates: Requirements 7.1**





## Phase 4: Controllers Update


- [ ] 6. Update ProductController
  - [ ] 6.1 Update index method for simplified product list
    - Remove stock-related joins and calculations
    - Return only basic product fields
    - Add category filter support

    - _Requirements: 1.1, 1.2, 2.3, 6.1, 6.2_
  - [ ] 6.2 Update create/store methods
    - Remove price, stock, management_type fields from form/validation
    - Add category validation (single uppercase letter)
    - _Requirements: 1.3, 2.2_

  - [ ] 6.3 Update show method to include product items
    - Load product with items relationship
    - Return product basic info + items list with SKUs and price tiers
    - _Requirements: 6.3, 6.4_




  - [ ] 6.4 Update edit/update methods
    - Remove unnecessary fields from form/validation
    - Keep only basic product fields


    - _Requirements: 1.3_
  - [ ]* 6.5 Write property test for product display fields
    - **Property 1: Product Display Contains Only Basic Fields**
    - **Validates: Requirements 1.1, 1.2, 6.2**


  - [x]* 6.6 Write property test for product detail with items

    - **Property 9: Product Detail Includes Items**

    - **Validates: Requirements 3.5, 6.4**





- [ ] 7. Update InventoryTransactionController for import
  - [x] 7.1 Update import form to support multiple SKUs

    - Add dynamic SKU input fields with add/remove buttons

    - Add price tier inputs (1yr-5yr)
    - _Requirements: 4.1, 4.6, 5.1_
  - [x] 7.2 Update storeImport method



    - Validate multiple SKUs input

    - Call ProductItemService to create items
    - Handle NO_SKU generation for items without SKU
    - Link items to transaction
    - _Requirements: 4.2, 4.3, 4.4, 4.5, 7.1_



  - [-] 7.3 Update export method to update item status

    - Update product_items status when exporting
    - Change status from 'in_stock' to 'sold' or 'transferred'
    - _Requirements: 7.3_
  - [ ]* 7.4 Write property test for export status update
    - **Property 11: Export Updates Item Status**
    - **Validates: Requirements 7.3**





- [ ] 8. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.


## Phase 5: Views Update





- [-] 9. Update Product Views

  - [x] 9.1 Update products/index.blade.php

    - Show only columns: Code, Name, Category, Unit, Description, Actions

    - Remove SKU, Price, Stock, Min Stock, Max Stock columns
    - Add category filter dropdown (A-Z)
    - _Requirements: 6.1, 6.2_
  - [ ] 9.2 Update products/create.blade.php
    - Remove price, cost, stock, min_stock, max_stock fields
    - Remove management_type, serial settings fields
    - Add category dropdown with A-Z options
    - Keep only: code, name, category, unit, description, note
    - _Requirements: 1.3, 2.1_
  - [ ] 9.3 Update products/edit.blade.php
    - Same changes as create form
    - _Requirements: 1.3, 2.1_
  - [ ] 9.4 Update products/show.blade.php
    - Show basic product info
    - Add product_items table with columns: SKU, Description, Price, 1yr, 2yr, 3yr, 4yr, 5yr, Quantity, Status
    - _Requirements: 3.5, 6.3, 6.4_

- [ ] 10. Update Inventory Transaction Views
  - [ ] 10.1 Update transactions/import.blade.php
    - Add dynamic SKU input section with add/remove buttons
    - Add price tier inputs (price, 1yr, 2yr, 3yr, 4yr, 5yr)
    - Add JavaScript for dynamic form handling
    - _Requirements: 4.1, 4.6, 5.1, 5.2_
  - [ ] 10.2 Update transactions/show.blade.php
    - Show related product_items with SKUs
    - Display all price tiers
    - _Requirements: 7.2_

## Phase 6: Request Validation Update

- [ ] 11. Update ProductRequest validation
  - [ ] 11.1 Update ProductRequest class
    - Remove price, cost, stock validation rules
    - Add category validation: nullable|string|size:1|regex:/^[A-Z]$/
    - Keep only basic field validations
    - _Requirements: 1.3, 2.2_

## Phase 7: Stock Calculation Update

- [ ] 12. Update stock calculation logic
  - [ ] 12.1 Update InventoryService for new stock calculation
    - Calculate stock from product_items where status = 'in_stock'
    - Sum quantities grouped by product
    - _Requirements: 7.4_
  - [ ]* 12.2 Write property test for stock calculation
    - **Property 12: Stock Calculation from In-Stock Items**
    - **Validates: Requirements 7.4**

## Phase 8: Routes Update

- [ ] 13. Update routes
  - [ ] 13.1 Update web.php routes if needed
    - Ensure product routes work with simplified controller
    - Add route for product items API if needed
    - _Requirements: 6.3_

## Phase 9: Final Testing and Cleanup

- [ ] 14. Final Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 15. Update seeders and cleanup
  - [ ] 15.1 Update ProductSeeder for new schema
    - Remove price, stock related data
    - Add sample product_items with SKUs and price tiers
    - _Requirements: 1.4, 3.1_
  - [ ] 15.2 Update ProductFactory if exists
    - Adjust factory for new simplified schema
    - _Requirements: 1.4_
