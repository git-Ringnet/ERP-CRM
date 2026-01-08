# Implementation Plan

## Phase 1: Tạo Database Schema

- [x] 1. Tạo migration cho 6 bảng mới


  - [x] 1.1 Tạo migration file `create_imports_table`


    - Tạo bảng `imports` với các cột: id, code, warehouse_id, date, employee_id, total_qty, reference_type, reference_id, note, status (ENUM với 'rejected'), timestamps
    - Thêm foreign keys và indexes
    - _Requirements: 1.1, 1.4_
  - [x] 1.2 Tạo migration file `create_exports_table`


    - Tạo bảng `exports` với các cột: id, code, warehouse_id, project_id, date, employee_id, total_qty, reference_type, reference_id, note, status, timestamps
    - Thêm foreign keys và indexes
    - _Requirements: 2.1, 2.4_

  - [x] 1.3 Tạo migration file `create_transfers_table`

    - Tạo bảng `transfers` với các cột: id, code, from_warehouse_id, to_warehouse_id, date, employee_id, total_qty, note, status, timestamps
    - Thêm foreign keys và indexes
    - _Requirements: 3.1, 3.4_
  - [x] 1.4 Tạo migration file `create_import_items_table`


    - Tạo bảng `import_items` với các cột: id, import_id, product_id, quantity, unit, cost, serial_number, comments, timestamps
    - _Requirements: 4.1_
  - [x] 1.5 Tạo migration file `create_export_items_table`


    - Tạo bảng `export_items` với các cột: id, export_id, product_id, quantity, unit, serial_number, comments, timestamps
    - _Requirements: 4.2_
  - [x] 1.6 Tạo migration file `create_transfer_items_table`


    - Tạo bảng `transfer_items` với các cột: id, transfer_id, product_id, quantity, unit, serial_number, comments, timestamps
    - _Requirements: 4.3_

## Phase 2: Tạo Models

- [x] 2. Tạo Eloquent Models mới


  - [x] 2.1 Tạo Import model (`app/Models/Import.php`)


    - Định nghĩa $fillable, $casts
    - Tạo relationships: warehouse(), employee(), items()
    - Tạo method generateCode() với prefix 'IMP'
    - Tạo scopes: byStatus(), byDateRange(), byWarehouse()
    - Tạo accessors: getStatusLabelAttribute(), getStatusColorAttribute()
    - _Requirements: 6.1, 1.2_
  - [x] 2.2 Tạo ImportItem model (`app/Models/ImportItem.php`)


    - Định nghĩa $fillable
    - Tạo relationships: import(), product()
    - _Requirements: 4.1_
  - [x] 2.3 Tạo Export model (`app/Models/Export.php`)


    - Định nghĩa $fillable, $casts
    - Tạo relationships: warehouse(), project(), employee(), items()
    - Tạo method generateCode() với prefix 'EXP'
    - Tạo scopes và accessors tương tự Import
    - _Requirements: 6.2, 2.2_
  - [x] 2.4 Tạo ExportItem model (`app/Models/ExportItem.php`)


    - Định nghĩa $fillable
    - Tạo relationships: export(), product()
    - _Requirements: 4.2_
  - [x] 2.5 Tạo Transfer model (`app/Models/Transfer.php`)


    - Định nghĩa $fillable, $casts
    - Tạo relationships: fromWarehouse(), toWarehouse(), employee(), items()
    - Tạo method generateCode() với prefix 'TRF'
    - Tạo scopes và accessors tương tự Import
    - _Requirements: 6.3, 3.2_
  - [x] 2.6 Tạo TransferItem model (`app/Models/TransferItem.php`)


    - Định nghĩa $fillable
    - Tạo relationships: transfer(), product()
    - _Requirements: 4.3_

## Phase 3: Cập nhật 3 Controllers riêng biệt

- [x] 3. Cập nhật ImportController (đã có sẵn, chỉ đổi model)


  - [x] 3.1 Thay thế InventoryTransaction bằng Import model



    - Thay `use App\Models\InventoryTransaction` bằng `use App\Models\Import`
    - Thay tất cả `InventoryTransaction::` bằng `Import::`
    - Bỏ điều kiện `where('type', 'import')` vì không cần nữa
    - Thay `$import->items()` dùng ImportItem
    - _Requirements: 7.1, 7.4_

  - [x] 3.2 Cập nhật type hints trong methods

    - `show(Import $import)`, `edit(Import $import)`, `update(ImportRequest $request, Import $import)`, etc.
    - _Requirements: 7.1_

- [x] 4. Cập nhật ExportController (đã có sẵn, chỉ đổi model)



  - [x] 4.1 Thay thế InventoryTransaction bằng Export model

    - Thay `use App\Models\InventoryTransaction` bằng `use App\Models\Export`
    - Thay tất cả `InventoryTransaction::` bằng `Export::`
    - Bỏ điều kiện `where('type', 'export')`
    - Thay items dùng ExportItem
    - _Requirements: 7.2, 7.4_


  - [x] 4.2 Cập nhật type hints trong methods
    - `show(Export $export)`, `edit(Export $export)`, etc.
    - _Requirements: 7.2_

- [x] 5. Cập nhật TransferController (đã có sẵn, chỉ đổi model)
  - [x] 5.1 Thay thế InventoryTransaction bằng Transfer model
    - Thay `use App\Models\InventoryTransaction` bằng `use App\Models\Transfer`
    - Thay tất cả `InventoryTransaction::` bằng `Transfer::`
    - Đổi `warehouse_id` thành `from_warehouse_id` trong queries
    - Bỏ điều kiện `where('type', 'transfer')`
    - Thay items dùng TransferItem
    - _Requirements: 7.3, 7.4_

  - [x] 5.2 Cập nhật type hints trong methods
    - `show(Transfer $transfer)`, `edit(Transfer $transfer)`, etc.
    - _Requirements: 7.3_

- [x] 6. Cập nhật routes/web.php



  - [x] 6.1 Cập nhật route model binding cho imports

    - Laravel sẽ tự động bind `{import}` với Import model (không cần sửa route)
    - Kiểm tra các routes: imports.show, imports.edit, imports.update, imports.destroy, imports.approve, imports.reject
    - _Requirements: 7.1_

  - [x] 6.2 Cập nhật route model binding cho exports

    - Kiểm tra các routes: exports.show, exports.edit, exports.update, exports.destroy, exports.approve, exports.reject

    - _Requirements: 7.2_

  - [x] 6.3 Cập nhật route model binding cho transfers
    - Kiểm tra các routes: transfers.show, transfers.edit, transfers.update, transfers.destroy, transfers.approve, transfers.reject
    - _Requirements: 7.3_

## Phase 4: Cập nhật Services

- [x] 6. Cập nhật TransactionService

  - [x] 6.1 Cập nhật processImport() method


    - Thay InventoryTransaction::create() bằng Import::create()
    - Thay $transaction->items()->create() bằng ImportItem
    - _Requirements: 8.1_
  - [x] 6.2 Cập nhật processExport() method

    - Thay InventoryTransaction bằng Export model
    - Thay items bằng ExportItem
    - _Requirements: 8.2_
  - [x] 6.3 Cập nhật processTransfer() method

    - Thay InventoryTransaction bằng Transfer model
    - Đổi warehouse_id thành from_warehouse_id
    - Thay items bằng TransferItem
    - _Requirements: 8.3_
  - [x] 6.4 Cập nhật generateTransactionCode() method

    - Gọi đúng model tương ứng: Import::generateCode(), Export::generateCode(), Transfer::generateCode()
    - _Requirements: 8.1, 8.2, 8.3_

- [x] 7. Cập nhật NotificationService


  - [x] 7.1 Cập nhật type hints trong các methods

    - notifyImportCreated(Import $import, ...)
    - notifyExportCreated(Export $export, ...)
    - notifyTransferCreated(Transfer $transfer, ...)
    - notifyDocumentApproved() - cần xử lý polymorphic
    - notifyDocumentRejected() - cần xử lý polymorphic
    - _Requirements: 8.1, 8.2, 8.3_

## Phase 5: Cập nhật Reports và Dashboard

- [x] 8. Cập nhật ReportController


  - [x] 8.1 Cập nhật transactionReport() method

    - Query từ 3 bảng riêng: Import, Export, Transfer
    - Merge kết quả và sort theo date
    - _Requirements: 9.2_


  - [x] 8.2 Cập nhật exportTransactionReport() method
    - Tương tự transactionReport()
    - _Requirements: 9.4_

- [x] 9. Cập nhật DashboardController

  - [x] 9.1 Cập nhật statistics queries
    - Thay `DB::table('inventory_transactions')` bằng queries từ 3 bảng
    - totalTransactions = imports + exports + transfers count
    - pendingTransactions = sum of pending từ 3 bảng
    - _Requirements: 9.1_

  - [x] 9.2 Cập nhật transactionsByType
    - Query trực tiếp từ imports, exports, transfers tables
    - _Requirements: 9.1_

  - [x] 9.3 Cập nhật recentTransactions
    - Union 3 queries từ imports, exports, transfers
    - _Requirements: 9.1_

  - [x] 9.4 Cập nhật chart data
    - Aggregate từ 3 bảng
    - _Requirements: 9.3_

## Phase 6: Cập nhật Exports (Excel)

- [x] 10. Cập nhật Export classes


  - [x] 10.1 Cập nhật ImportsExport.php

    - Thay InventoryTransaction bằng Import model
    - Bỏ `where('type', 'import')`

    - _Requirements: 9.4_

  - [x] 10.2 Cập nhật ExportsExport.php

    - Thay InventoryTransaction bằng Export model
    - _Requirements: 9.4_

  - [x] 10.3 Cập nhật TransfersExport.php
    - Thay InventoryTransaction bằng Transfer model
    - _Requirements: 9.4_

## Phase 7: Cập nhật các Models liên quan

- [x] 11. Cập nhật relationships trong các Models khác

  - [x] 11.1 Cập nhật Warehouse model
    - Thay transactions() bằng imports(), exports(), transfers()
    - _Requirements: 6.1, 6.2, 6.3_

  - [x] 11.2 Cập nhật Project model
    - Thay exports() relationship để dùng Export model
    - _Requirements: 6.2_

  - [x] 11.3 Cập nhật ProductItem model
    - Cập nhật inventoryTransaction() relationship (polymorphic hoặc nullable)
    - _Requirements: 6.1_

## Phase 8: Migrate Data và Cleanup

- [x] 12. Tạo migration để migrate data

  - [x] 12.1 Tạo migration `migrate_inventory_transactions_data`

    - Copy records từ inventory_transactions sang imports/exports/transfers theo type
    - Copy records từ inventory_transaction_items sang import_items/export_items/transfer_items
    - Verify record counts
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_



- [x] 13. Cleanup

  - [x] 13.1 Tạo migration để drop bảng cũ
    - Drop table inventory_transaction_items

    - Drop table inventory_transactions

    - _Requirements: 10.1, 10.2_
  - [x] 13.2 Xóa files không còn dùng
    - Xóa app/Models/InventoryTransaction.php
    - Xóa app/Models/InventoryTransactionItem.php
    - Xóa database/factories/InventoryTransactionFactory.php (nếu có)
    - _Requirements: 10.3, 10.4_

## Phase 9: Cập nhật Seeders

- [x] 14. Cập nhật Database Seeders


  - [x] 14.1 Tạo hoặc cập nhật ImportSeeder


    - Tạo sample imports với Import model
    - _Requirements: 5.1_

  - [x] 14.2 Tạo hoặc cập nhật ExportSeeder
    - Tạo sample exports với Export model


    - _Requirements: 5.2_
  - [x] 14.3 Tạo hoặc cập nhật TransferSeeder

    - Tạo sample transfers với Transfer model
    - _Requirements: 5.3_
  - [x] 14.4 Cập nhật DatabaseSeeder
    - Gọi các seeders mới thay vì InventoryTransactionSeeder
    - _Requirements: 5.1, 5.2, 5.3_

## Phase 10: Final Verification

- [x] 15. Kiểm tra và verify




  - [x] 15.1 Chạy migrate:fresh --seed

    - Verify tất cả migrations chạy thành công
    - Verify seeders tạo data đúng
    - _Requirements: 1.1, 2.1, 3.1, 4.1, 4.2, 4.3_

  - [x] 15.2 Test thủ công các chức năng

    - Test tạo/sửa/xóa Import
    - Test tạo/sửa/xóa Export
    - Test tạo/sửa/xóa Transfer
    - Test approve/reject
    - Test Dashboard statistics
    - Test Reports
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 9.1, 9.2_
