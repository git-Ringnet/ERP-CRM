# Implementation Plan - Module Kho (Warehouse Module)

## Phase 1: Warehouses (Quản lý Kho)

- [x] 1. Tạo Migration và Model cho Warehouses



  - [x] 1.1 Tạo migration create_warehouses_table

    - Fields: id, code, name, type, address, area, capacity, manager_id, phone, status, product_type, has_temperature_control, has_security_system, note, timestamps
    - Foreign key: manager_id references users(id)
    - _Requirements: 1.1, 1.5_

  - [x] 1.2 Tạo Warehouse Model với relationships và scopes

    - Relationships: manager(), inventories(), transactions()
    - Scopes: scopeActive(), scopeByType(), scopeByStatus()
    - Fillable fields và casts
    - _Requirements: 1.1, 1.2_
  - [ ]* 1.3 Write property test for warehouse code uniqueness
    - **Property 1: Warehouse Code Uniqueness**
    - **Validates: Requirements 1.1**

- [x] 2. Tạo Controller và Views cho Warehouses



  - [x] 2.1 Tạo WarehouseRequest với validation rules

    - Validate: code (unique), name (required), type (in:physical,virtual), status (in:active,maintenance,inactive)
    - _Requirements: 1.3_

  - [x] 2.2 Tạo WarehouseController với CRUD methods

    - Methods: index, create, store, show, edit, update, destroy
    - Check inventory before delete
    - _Requirements: 1.1, 1.2, 1.3, 1.4_
  - [ ]* 2.3 Write property test for warehouse deletion integrity
    - **Property 2: Warehouse Deletion Integrity**
    - **Validates: Requirements 1.4, 1.5**

  - [x] 2.4 Tạo Warehouse Views (index, create, edit, show)

    - index.blade.php: Danh sách kho với filter
    - create.blade.php: Form tạo kho mới
    - edit.blade.php: Form chỉnh sửa kho
    - show.blade.php: Chi tiết kho
    - _Requirements: 1.1, 1.2, 1.3_

- [x] 3. Tạo Routes và Seeder cho Warehouses



  - [x] 3.1 Đăng ký routes cho warehouses

    - Resource routes: warehouses.index, warehouses.create, warehouses.store, warehouses.show, warehouses.edit, warehouses.update, warehouses.destroy
    - _Requirements: 1.1, 1.2, 1.3, 1.4_

  - [x] 3.2 Tạo WarehouseSeeder với sample data

    - Tạo 5-10 warehouses mẫu với các loại khác nhau
    - _Requirements: 1.1_
  - [ ]* 3.3 Write unit tests for Warehouse model
    - Test relationships, scopes, và validation
    - _Requirements: 1.1, 1.2_

- [x] 4. Checkpoint - Phase 1


  - Ensure all tests pass, ask the user if questions arise.

## Phase 2: Inventory (Tồn kho)

- [x] 5. Tạo Migration và Model cho Inventory



  - [x] 5.1 Tạo migration create_inventories_table

    - Fields: id, product_id, warehouse_id, stock, min_stock, avg_cost, expiry_date, warranty_months, timestamps
    - Foreign keys: product_id, warehouse_id
    - Unique constraint: (product_id, warehouse_id)
    - _Requirements: 2.1_

  - [x] 5.2 Tạo Inventory Model với relationships và scopes

    - Relationships: product(), warehouse()
    - Scopes: scopeLowStock(), scopeExpiringSoon()
    - Computed attributes: isLowStock, isExpiringSoon
    - _Requirements: 2.1, 2.2, 2.5_
  - [ ]* 5.3 Write property test for low stock detection
    - **Property 3: Low Stock Detection**
    - **Validates: Requirements 2.2**
  - [ ]* 5.4 Write property test for expiry warning detection
    - **Property 4: Expiry Warning Detection**
    - **Validates: Requirements 2.5**

- [x] 6. Tạo InventoryService



  - [x] 6.1 Implement InventoryService với các methods

    - updateStock(): Cập nhật số lượng tồn kho
    - calculateAverageCost(): Tính giá vốn trung bình
    - checkLowStock(): Kiểm tra tồn kho thấp
    - getExpiringItems(): Lấy danh sách hàng sắp hết hạn
    - _Requirements: 2.2, 2.4, 2.5_

- [x] 7. Tạo Controller và Views cho Inventory



  - [x] 7.1 Tạo InventoryController

    - Methods: index, show, lowStock, expiringSoon
    - Filter by warehouse, product, stock status, expiry
    - _Requirements: 2.1, 2.2, 2.3, 2.5_

  - [x] 7.2 Tạo Inventory Views

    - index.blade.php: Danh sách tồn kho với filter và warning badges
    - show.blade.php: Chi tiết tồn kho
    - _Requirements: 2.1, 2.2, 2.3, 2.5_

- [x] 8. Tạo Routes và Seeder cho Inventory


  - [x] 8.1 Đăng ký routes cho inventory


    - Routes: inventory.index, inventory.show, inventory.low-stock, inventory.expiring
    - _Requirements: 2.1, 2.2, 2.3, 2.5_
  - [x] 8.2 Tạo InventorySeeder với sample data


    - Tạo inventory records cho các products và warehouses
    - Include low stock và expiring items
    - _Requirements: 2.1_
  - [ ]* 8.3 Write unit tests for Inventory model và service
    - Test scopes, relationships, và service methods
    - _Requirements: 2.1, 2.2, 2.4, 2.5_

- [x] 9. Checkpoint - Phase 2


  - Ensure all tests pass, ask the user if questions arise.

## Phase 3: Inventory Transactions (Xuất Nhập Kho)

- [x] 10. Tạo Migrations và Models cho Transactions



  - [x] 10.1 Tạo migration create_inventory_transactions_table

    - Fields: id, code, type, warehouse_id, to_warehouse_id, date, employee_id, total_qty, reference_type, reference_id, note, status, timestamps
    - Foreign keys: warehouse_id, to_warehouse_id, employee_id
    - _Requirements: 3.1, 3.4_

  - [x] 10.2 Tạo migration create_inventory_transaction_items_table

    - Fields: id, transaction_id, product_id, quantity, unit, serial_number, timestamps
    - Foreign keys: transaction_id, product_id
    - _Requirements: 3.1, 3.4_
  - [x] 10.3 Tạo InventoryTransaction Model


    - Relationships: warehouse(), toWarehouse(), employee(), items()
    - Scopes: scopeByType(), scopeByDateRange(), scopeByStatus()
    - _Requirements: 3.1, 3.5_
  - [x] 10.4 Tạo InventoryTransactionItem Model


    - Relationships: transaction(), product()
    - _Requirements: 3.1, 3.4_

- [x] 11. Tạo TransactionService




  - [x] 11.1 Implement TransactionService

    - generateTransactionCode(): Tạo mã giao dịch unique
    - validateStock(): Kiểm tra tồn kho đủ cho xuất
    - processImport(): Xử lý nhập kho
    - processExport(): Xử lý xuất kho
    - processTransfer(): Xử lý chuyển kho
    - _Requirements: 3.1, 3.2, 3.3_
  - [ ]* 11.2 Write property test for import transaction stock increase
    - **Property 5: Import Transaction Stock Increase**
    - **Validates: Requirements 3.1, 2.4**
  - [ ]* 11.3 Write property test for export transaction stock decrease
    - **Property 6: Export Transaction Stock Decrease**
    - **Validates: Requirements 3.2, 2.4**
  - [ ]* 11.4 Write property test for export validation
    - **Property 7: Export Validation**
    - **Validates: Requirements 3.2**
  - [ ]* 11.5 Write property test for transfer stock conservation
    - **Property 8: Transfer Stock Conservation**
    - **Validates: Requirements 3.3**

- [x] 12. Tạo Controller và Views cho Transactions



  - [x] 12.1 Tạo InventoryTransactionRequest với validation

    - Validate: type, warehouse_id, items array, quantities
    - Custom validation for stock availability on export
    - _Requirements: 3.1, 3.2_
  - [x] 12.2 Tạo InventoryTransactionController


    - Methods: index, create, store, show
    - Handle import/export/transfer types
    - _Requirements: 3.1, 3.2, 3.3, 3.5_

  - [x] 12.3 Tạo Transaction Views

    - index.blade.php: Danh sách giao dịch với filter
    - import.blade.php: Form tạo phiếu nhập kho (full-width, table layout)
    - export.blade.php: Form tạo phiếu xuất kho (full-width, table layout)
    - transfer.blade.php: Form tạo phiếu chuyển kho (full-width, table layout)
    - import-data.blade.php: Form import CSV/JSON data
    - show.blade.php: Chi tiết giao dịch với line items
    - _Requirements: 3.1, 3.4, 3.5_

- [x] 13. Tạo Routes và Seeder cho Transactions





  - [x] 13.1 Đăng ký routes cho inventory-transactions

    - Resource routes cho transactions
    - _Requirements: 3.1, 3.5_
  - [x] 13.2 Tạo InventoryTransactionSeeder
    - Tạo sample import/export/transfer transactions
    - _Requirements: 3.1_
  - [ ]* 13.3 Write unit tests for Transaction models và service
    - Test relationships, scopes, và transaction processing
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 14. Implement Transaction Data Import/Export
  - [x] 14.1 Tạo TransactionExporter service
    - Export transactions to CSV/JSON format
    - _Requirements: 3.8_
  - [x] 14.2 Tạo TransactionImporter service
    - Import transactions from CSV/JSON
    - Validate against schema
    - _Requirements: 3.7_
  - [ ]* 14.3 Write property test for transaction data round-trip
    - **Property 9: Transaction Data Round-Trip**

    - **Validates: Requirements 3.7, 3.8**

- [x] 15. Checkpoint - Phase 3
  - Ensure all tests pass, ask the user if questions arise.

## Phase 4: Damaged Goods (Hàng Hư Hỏng/Thanh Lý)

- [x] 16. Tạo Migration và Model cho Damaged Goods
  - [x] 16.1 Tạo migration create_damaged_goods_table
    - Fields: id, code, type, product_id, quantity, original_value, recovery_value, reason, status, discovery_date, discovered_by, solution, note, timestamps
    - Foreign keys: product_id, discovered_by
    - _Requirements: 4.1_
  - [x] 16.2 Tạo DamagedGood Model
    - Relationships: product(), discoveredBy()
    - Scopes: scopeByType(), scopeByStatus(), scopeByDateRange()
    - _Requirements: 4.1, 4.4_
  - [ ]* 16.3 Write property test for damaged goods code uniqueness
    - **Property 10: Damaged Goods Code Uniqueness**
    - **Validates: Requirements 4.1**

- [x] 17. Tạo Controller và Views cho Damaged Goods
  - [x] 17.1 Tạo DamagedGoodRequest với validation
    - Validate: type, product_id, quantity, values
    - _Requirements: 4.1, 4.2_
  - [x] 17.2 Tạo DamagedGoodController
    - Methods: index, create, store, show, edit, update, updateStatus
    - Handle status transitions
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_
  - [x] 17.3 Tạo Damaged Goods Views
    - index.blade.php: Danh sách với filter
    - create.blade.php: Form báo cáo hàng hư hỏng
    - edit.blade.php: Form chỉnh sửa
    - show.blade.php: Chi tiết với status history
    - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [x] 18. Tạo Routes và Seeder cho Damaged Goods
  - [x] 18.1 Đăng ký routes cho damaged-goods
    - Resource routes + updateStatus route
    - _Requirements: 4.1, 4.3, 4.4_
  - [x] 18.2 Tạo DamagedGoodSeeder
    - Tạo sample damaged và liquidation records
    - _Requirements: 4.1_
  - [ ]* 18.3 Write unit tests for DamagedGood model
    - Test relationships, scopes, status transitions
    - _Requirements: 4.1, 4.3, 4.4_

- [x] 19. Checkpoint - Phase 4
  - Ensure all tests pass, ask the user if questions arise.

## Phase 5: Reports và Integration

- [x] 20. Implement Warehouse Reports
  - [x] 20.1 Tạo ReportController cho warehouse reports
    - inventorySummary(): Tổng hợp tồn kho
    - transactionReport(): Báo cáo xuất nhập
    - damagedGoodsReport(): Báo cáo hàng hư hỏng
    - _Requirements: 5.1, 5.2, 5.3_
  - [x] 20.2 Tạo Report Views
    - inventory-summary.blade.php
    - transaction-report.blade.php
    - damaged-goods-report.blade.php
    - _Requirements: 5.1, 5.2, 5.3_
  - [ ]* 20.3 Write property test for report aggregation accuracy
    - **Property 11: Report Aggregation Accuracy**
    - **Validates: Requirements 5.1, 5.2**

- [x] 21. Update Sidebar Menu
  - [x] 21.1 Thêm menu items cho warehouse module
    - Kho hàng (Warehouses)
    - Tồn kho (Inventory)
    - Xuất nhập kho (Transactions)
    - Hàng hư hỏng (Damaged Goods)
    - Báo cáo kho (Reports)
    - _Requirements: 1.2, 2.1, 3.5, 4.4, 5.1_

- [x] 22. Update DatabaseSeeder
  - [x] 22.1 Thêm warehouse seeders vào DatabaseSeeder
    - Call WarehouseSeeder, InventorySeeder, etc.
    - Đảm bảo thứ tự đúng (warehouses trước inventory)
    - _Requirements: 1.1, 2.1, 3.1, 4.1_

- [ ]* 23. Write integration tests
  - [ ]* 23.1 Write property test for filter results correctness
    - **Property 12: Filter Results Correctness**
    - **Validates: Requirements 1.2, 2.3, 3.5, 4.4**
  - [ ]* 23.2 Write feature tests for complete workflows
    - Test import → inventory update flow
    - Test export → inventory update flow
    - Test transfer → both warehouses update flow
    - _Requirements: 3.1, 3.2, 3.3_

- [x] 24. Final Checkpoint
  - Ensure all tests pass, ask the user if questions arise.
