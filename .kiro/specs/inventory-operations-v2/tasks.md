# Implementation Plan - Inventory Operations V2

## Overview
This plan covers implementation of Export, Transfer, and Bulk Import modules with the new product_items structure.

---

## Phase 1: Export Transaction Module

- [x] 1. Update TransactionController for Export


  - Add export() method to display form with available SKUs
  - Add storeExport() method to process export
  - Implement SKU availability checking
  - _Requirements: 1.1, 1.2, 1.3_


- [ ] 1.1 Create export view (transactions/export.blade.php)
  - Form layout similar to import with SKU selection
  - Display available product_items with status 'in_stock'
  - Checkbox selection for specific SKUs
  - Show cost_usd and price_tiers for each item
  - _Requirements: 1.1, 1.2, 1.5_


- [ ] 1.2 Update ProductItemService for export operations
  - Add getAvailableItemsForExport($productId, $warehouseId)
  - Add exportItems($itemIds, $transactionId) to update status to 'sold'
  - Add validation for SKU availability
  - _Requirements: 1.3, 1.4_

- [ ]* 1.3 Write property test for export SKU availability
  - **Property 1: Export SKU Availability**
  - **Validates: Requirements 1.1, 1.3**

- [ ]* 1.4 Write property test for export status update
  - **Property 2: Export Status Update**
  - **Validates: Requirements 1.4**

---




## Phase 2: Transfer Transaction Module

- [ ] 2. Update TransactionController for Transfer
  - Add transfer() method to display form

  - Add storeTransfer() method to process transfer
  - Implement source/destination warehouse validation
  - _Requirements: 2.1, 2.2_

- [x] 2.1 Create transfer view (transactions/transfer.blade.php)

  - Two-step form: select source warehouse, then destination
  - Display available SKUs from source warehouse
  - Show transfer summary before confirmation
  - _Requirements: 2.1, 2.2, 2.5_

- [ ] 2.2 Update ProductItemService for transfer operations
  - Add transferItems($itemIds, $toWarehouseId, $transactionId)
  - Update warehouse_id while maintaining 'in_stock' status
  - Add validation for source/destination warehouses
  - _Requirements: 2.3, 2.4_

- [ ]* 2.3 Write property test for transfer warehouse consistency
  - **Property 3: Transfer Warehouse Consistency**
  - **Validates: Requirements 2.3**



- [ ]* 2.4 Write property test for transfer status preservation
  - **Property 4: Transfer Status Preservation**
  - **Validates: Requirements 2.4**

---


## Phase 3: Edit Transaction Module

- [ ] 3. Update TransactionController for Edit
  - Add edit($id) method to display edit form

  - Add update($id) method to process updates
  - Implement status checking (only 'pending' can be edited)
  - _Requirements: 4.1, 4.3_

- [ ] 3.1 Create edit view (transactions/edit.blade.php)
  - Reuse import form layout with pre-populated data
  - Load existing product_items and display in form
  - Disable form if status is 'approved'
  - _Requirements: 4.1, 4.2_

- [ ] 3.2 Update TransactionService for edit operations
  - Add updateTransaction($id, $data) method


  - Handle product_items updates (delete old, create new)
  - Validate status before allowing updates
  - _Requirements: 4.3, 4.4, 4.5_

- [x]* 3.3 Write property test for edit restriction

  - **Property 9: Edit Restriction for Approved Transactions**
  - **Validates: Requirements 4.3**

---

## Phase 4: Excel Import Service


- [ ] 4. Create ExcelImportService
  - Create app/Services/ExcelImportService.php
  - Implement generateProductTemplate() method
  - Implement generateInventoryTemplate() method

  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 4.1 Implement Excel template generation
  - Use Laravel Excel to create templates
  - Add example rows with correct data format
  - Add instructions sheet
  - Include price_tiers JSON example
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 4.2 Implement import validation logic
  - Add validateRow($row, $rules) method
  - Add parsePriceTiers($jsonString) method
  - Validate Product_Code, Warehouse_Code, SKU, Cost_USD, Category
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7_

- [ ] 4.3 Implement bulk import methods
  - Add importProducts($file) method
  - Add importInventory($file) method
  - Use database transactions for rollback on error
  - Generate NO_SKU for empty SKU fields
  - _Requirements: 3.2, 3.3, 3.5, 3.7_

- [ ]* 4.4 Write property test for SKU uniqueness
  - **Property 5: SKU Uniqueness in Import**
  - **Validates: Requirements 7.3**



- [ ]* 4.5 Write property test for price tiers JSON validity
  - **Property 6: Price Tiers JSON Validity**
  - **Validates: Requirements 3.4, 7.5**

- [x]* 4.6 Write property test for import rollback

  - **Property 7: Import Rollback on Error**
  - **Validates: Requirements 3.7**

- [ ]* 4.7 Write property test for NO_SKU generation
  - **Property 8: NO_SKU Generation for Empty SKU**
  - **Validates: Requirements 3.5**


---

## Phase 5: Excel Import Controller and Views

- [x] 5. Create ExcelImportController

  - Create app/Http/Controllers/ExcelImportController.php
  - Add index() method for import interface
  - Add downloadTemplate($type) method
  - Add import(Request $request) method
  - _Requirements: 3.1, 3.6_

- [ ] 5.1 Create import views
  - Create resources/views/import/index.blade.php
  - Add file upload interface with drag-and-drop

  - Add template download buttons
  - Add progress indicator
  - _Requirements: 3.1, 3.6_

- [ ] 5.2 Create import result view
  - Create resources/views/import/result.blade.php

  - Display success/error summary
  - Show detailed error report with row numbers
  - Provide option to download error log
  - _Requirements: 3.6, 3.7_

- [ ] 5.3 Add routes for import
  - Add routes in web.php for import controller
  - Route::get('/import', 'index')
  - Route::get('/import/template/{type}', 'downloadTemplate')
  - Route::post('/import', 'import')
  - _Requirements: 3.1_



---

## Phase 6: Update Transaction Display Views

- [ ] 6. Update transaction show view
  - Already updated to show product_items with SKU, cost_usd, price_tiers
  - Ensure NO_SKU items are visually distinguished
  - Add total SKU count display
  - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [ ] 6.1 Update transaction index view
  - Add SKU count column
  - Update filters to work with new structure
  - _Requirements: 5.4_

- [ ]* 6.2 Write property test for transaction display completeness
  - **Property 10: Transaction Display Completeness**
  - **Validates: Requirements 5.1, 5.2**

---

## Phase 7: Install and Configure Laravel Excel

- [ ] 7. Install Laravel Excel package
  - Run: composer require maatwebsite/excel
  - Publish config: php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider"
  - _Requirements: 3.1, 6.1_

---

## Phase 8: Testing and Validation

- [ ] 8. Checkpoint - Ensure all tests pass
  - Run all property-based tests
  - Run all unit tests
  - Test export flow manually
  - Test transfer flow manually
  - Test import flow with sample Excel files
  - Ask the user if questions arise

---

## Phase 9: Documentation and Cleanup

- [ ]* 9. Create user documentation
  - Write guide for export operations
  - Write guide for transfer operations
  - Write guide for bulk import with Excel examples
  - Document Excel template structure

- [ ]* 9.2 Code cleanup and optimization
  - Remove unused code
  - Add code comments
  - Optimize database queries
  - Add indexes if needed
