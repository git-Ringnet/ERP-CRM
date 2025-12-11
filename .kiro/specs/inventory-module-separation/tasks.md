# Implementation Plan

## Phase 1: Module Nhập Kho (Import)

- [x] 1. Tạo Import Module

  - [x] 1.1 Tạo ImportController với các methods: index, create, store, show, edit, update, destroy, approve
    - Xử lý CRUD cho import transactions
    - Filter transactions by type='import'
    - _Requirements: 1.1, 1.4_

  - [x] 1.2 Tạo ImportRequest form validation
    - Validate warehouse_id, date, items array
    - Validate SKU, cost_usd, price_tiers fields
    - _Requirements: 1.5_

  - [x] 1.3 Tạo views cho Import module
    - `resources/views/imports/index.blade.php` - Danh sách phiếu nhập
    - `resources/views/imports/create.blade.php` - Form tạo phiếu nhập
    - `resources/views/imports/show.blade.php` - Chi tiết phiếu nhập
    - `resources/views/imports/edit.blade.php` - Form chỉnh sửa phiếu nhập
    - _Requirements: 1.2, 1.5_

  - [x] 1.4 Tạo routes cho Import module với prefix `/imports`
    - Route::resource('imports', ImportController::class)
    - Route::post('imports/{import}/approve', [ImportController::class, 'approve'])
    - _Requirements: 1.3_
  - [ ]* 1.5 Write property test for import code prefix
    - **Property 2: Transaction Code Prefix Consistency (IMP)**
    - **Validates: Requirements 1.6**

- [x] 2. Checkpoint - Kiểm tra Import Module

  - Ensure all tests pass, ask the user if questions arise.

## Phase 2: Module Xuất Kho (Export)

- [x] 3. Tạo Export Module

  - [x] 3.1 Tạo ExportController với các methods: index, create, store, show, edit, update, destroy, approve, getAvailableItems
    - Xử lý CRUD cho export transactions
    - Filter transactions by type='export'
    - API endpoint để lấy available SKUs
    - _Requirements: 2.1, 2.4, 2.5_

  - [x] 3.2 Tạo ExportRequest form validation
    - Validate warehouse_id, date, items array
    - Validate product_item_ids for specific SKU selection
    - _Requirements: 2.5_

  - [x] 3.3 Tạo views cho Export module
    - `resources/views/exports/index.blade.php` - Danh sách phiếu xuất
    - `resources/views/exports/create.blade.php` - Form tạo phiếu xuất với SKU selection
    - `resources/views/exports/show.blade.php` - Chi tiết phiếu xuất
    - `resources/views/exports/edit.blade.php` - Form chỉnh sửa phiếu xuất
    - _Requirements: 2.2, 2.5_

  - [x] 3.4 Tạo routes cho Export module với prefix `/exports`
    - Route::resource('exports', ExportController::class)
    - Route::post('exports/{export}/approve', [ExportController::class, 'approve'])
    - Route::get('exports/available-items', [ExportController::class, 'getAvailableItems'])
    - _Requirements: 2.3_
  - [ ]* 3.5 Write property test for export stock validation
    - **Property 3: Stock Validation for Outgoing Transactions (Export)**
    - **Validates: Requirements 2.7**
  - [ ]* 3.6 Write property test for export code prefix
    - **Property 2: Transaction Code Prefix Consistency (EXP)**
    - **Validates: Requirements 2.6**


- [ ] 4. Checkpoint - Kiểm tra Export Module
  - Ensure all tests pass, ask the user if questions arise.

## Phase 3: Module Chuyển Kho (Transfer)



- [x] 5. Tạo Transfer Module

  - [x] 5.1 Tạo TransferController với các methods: index, create, store, show, edit, update, destroy, approve
    - Xử lý CRUD cho transfer transactions
    - Filter transactions by type='transfer'
    - Validate source ≠ destination warehouse
    - _Requirements: 3.1, 3.4, 3.5_

  - [x] 5.2 Tạo TransferRequest form validation
    - Validate warehouse_id, to_warehouse_id (must be different)
    - Validate date, items array
    - _Requirements: 3.5_

  - [x] 5.3 Tạo views cho Transfer module
    - `resources/views/transfers/index.blade.php` - Danh sách phiếu chuyển
    - `resources/views/transfers/create.blade.php` - Form tạo phiếu chuyển
    - `resources/views/transfers/show.blade.php` - Chi tiết phiếu chuyển
    - `resources/views/transfers/edit.blade.php` - Form chỉnh sửa phiếu chuyển
    - _Requirements: 3.2_

  - [x] 5.4 Tạo routes cho Transfer module với prefix `/transfers`
    - Route::resource('transfers', TransferController::class)
    - Route::post('transfers/{transfer}/approve', [TransferController::class, 'approve'])
    - _Requirements: 3.3_
  - [ ]* 5.5 Write property test for transfer warehouse validation
    - **Property 4: Transfer Warehouse Validation**
    - **Validates: Requirements 3.5**
  - [ ]* 5.6 Write property test for transfer stock validation
    - **Property 3: Stock Validation for Outgoing Transactions (Transfer)**
    - **Validates: Requirements 3.7**
  - [ ]* 5.7 Write property test for transfer code prefix
    - **Property 2: Transaction Code Prefix Consistency (TRF)**
    - **Validates: Requirements 3.6**

- [x] 6. Checkpoint - Kiểm tra Transfer Module
  - Routes verified via `php artisan route:list`
  - All controllers, views, and routes created successfully

## Phase 4: Navigation và Integration

- [x] 7. Cập nhật Navigation Menu
  - [x] 7.1 Cập nhật sidebar layout với 3 menu items riêng biệt
    - Thêm menu "Nhập kho" với route imports.index (blue icon)
    - Thêm menu "Xuất kho" với route exports.index (orange icon)
    - Thêm menu "Chuyển kho" với route transfers.index (purple icon)
    - Xóa menu "Quản lý Xuất nhập kho" cũ
    - _Requirements: 5.1, 5.2, 5.3, 5.4_
  - [ ] 7.2 Cập nhật route model binding trong RouteServiceProvider (optional)
    - Bind 'import' to InventoryTransaction where type='import'
    - Bind 'export' to InventoryTransaction where type='export'
    - Bind 'transfer' to InventoryTransaction where type='transfer'
    - _Requirements: 1.4, 2.4, 3.4_

- [x] 8. Checkpoint - Kiểm tra Navigation
  - Sidebar menu updated with 3 separate items
  - Routes verified working

## Phase 5: Cleanup


- [x] 9. Xóa Code Cũ


  - [x] 9.1 Xóa InventoryTransactionController


    - Backup file trước khi xóa
    - _Requirements: 7.1_

  - [x] 9.2 Xóa views cũ trong resources/views/transactions/

    - Xóa tất cả blade files trong thư mục transactions
    - _Requirements: 7.2_

  - [x] 9.3 Xóa routes cũ cho /transactions

    - Remove Route::resource('transactions', ...)
    - _Requirements: 7.3_

  - [x] 9.4 Cập nhật các references còn lại

    - Tìm và cập nhật các link/redirect đến routes cũ
    - _Requirements: 7.4_

- [ ]* 10. Write property test for module filtering
  - **Property 1: Module Filtering Consistency**
  - **Validates: Requirements 1.4, 2.4, 3.4**

- [ ]* 11. Write property test for data preservation
  - **Property 5: Data Preservation**
  - **Validates: Requirements 6.1, 6.2, 6.3**

- [x] 12. Final Checkpoint - Kiểm tra toàn bộ hệ thống



  - Ensure all tests pass, ask the user if questions arise.
