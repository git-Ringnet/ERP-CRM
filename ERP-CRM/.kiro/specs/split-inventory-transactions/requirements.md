# Requirements Document

## Introduction

Tách bảng `inventory_transactions` thành 3 bảng riêng biệt: `imports`, `exports`, `transfers` để cải thiện hiệu suất, dễ bảo trì và mở rộng trong tương lai. Hiện tại dữ liệu chỉ là seeder ảo nên việc tách không ảnh hưởng đến production.

## Glossary

- **Import**: Phiếu nhập kho - ghi nhận hàng hóa nhập vào kho
- **Export**: Phiếu xuất kho - ghi nhận hàng hóa xuất ra khỏi kho
- **Transfer**: Phiếu chuyển kho - ghi nhận hàng hóa chuyển giữa các kho
- **InventoryTransaction**: Bảng hiện tại chứa cả 3 loại giao dịch (sẽ bị xóa)
- **InventoryTransactionItem**: Bảng chi tiết items của giao dịch (sẽ tách thành 3)

## Requirements

### Requirement 1: Tạo bảng imports mới

**User Story:** As a developer, I want to have a separate `imports` table, so that import transactions are stored independently and can scale better.

#### Acceptance Criteria

1. WHEN the migration runs THEN the system SHALL create table `imports` with columns: id, code, warehouse_id, date, employee_id, total_qty, reference_type, reference_id, note, status, created_at, updated_at
2. WHEN creating an import THEN the system SHALL auto-generate code with prefix 'IMP' (e.g., IMP00001)
3. WHEN an import is created THEN the system SHALL validate that warehouse_id exists and is active
4. WHEN an import status changes THEN the system SHALL only allow values: pending, completed, cancelled, rejected

### Requirement 2: Tạo bảng exports mới

**User Story:** As a developer, I want to have a separate `exports` table, so that export transactions are stored independently with project linking capability.

#### Acceptance Criteria

1. WHEN the migration runs THEN the system SHALL create table `exports` with columns: id, code, warehouse_id, project_id, date, employee_id, total_qty, reference_type, reference_id, note, status, created_at, updated_at
2. WHEN creating an export THEN the system SHALL auto-generate code with prefix 'EXP' (e.g., EXP00001)
3. WHEN an export is linked to a project THEN the system SHALL validate that project_id exists
4. WHEN an export status changes THEN the system SHALL only allow values: pending, completed, cancelled, rejected

### Requirement 3: Tạo bảng transfers mới

**User Story:** As a developer, I want to have a separate `transfers` table, so that transfer transactions between warehouses are stored independently.

#### Acceptance Criteria

1. WHEN the migration runs THEN the system SHALL create table `transfers` with columns: id, code, from_warehouse_id, to_warehouse_id, date, employee_id, total_qty, note, status, created_at, updated_at
2. WHEN creating a transfer THEN the system SHALL auto-generate code with prefix 'TRF' (e.g., TRF00001)
3. WHEN a transfer is created THEN the system SHALL validate that from_warehouse_id differs from to_warehouse_id
4. WHEN a transfer status changes THEN the system SHALL only allow values: pending, completed, cancelled, rejected

### Requirement 4: Tạo bảng items riêng cho mỗi loại giao dịch

**User Story:** As a developer, I want separate item tables for each transaction type, so that data is properly normalized.

#### Acceptance Criteria

1. WHEN the migration runs THEN the system SHALL create table `import_items` with columns: id, import_id, product_id, quantity, unit, cost, serial_number, comments, created_at, updated_at
2. WHEN the migration runs THEN the system SHALL create table `export_items` with columns: id, export_id, product_id, quantity, unit, serial_number, comments, created_at, updated_at
3. WHEN the migration runs THEN the system SHALL create table `transfer_items` with columns: id, transfer_id, product_id, quantity, unit, serial_number, comments, created_at, updated_at
4. WHEN an item is created THEN the system SHALL validate that product_id exists

### Requirement 5: Migrate dữ liệu từ bảng cũ sang bảng mới

**User Story:** As a developer, I want existing data migrated to new tables, so that no data is lost during the transition.

#### Acceptance Criteria

1. WHEN migration runs THEN the system SHALL copy all records with type='import' from inventory_transactions to imports table
2. WHEN migration runs THEN the system SHALL copy all records with type='export' from inventory_transactions to exports table
3. WHEN migration runs THEN the system SHALL copy all records with type='transfer' from inventory_transactions to transfers table
4. WHEN migration runs THEN the system SHALL copy all related items from inventory_transaction_items to respective item tables
5. WHEN migration completes THEN the system SHALL verify record counts match between old and new tables

### Requirement 6: Cập nhật Models

**User Story:** As a developer, I want new Eloquent models for each transaction type, so that code is cleaner and type-safe.

#### Acceptance Criteria

1. WHEN Import model is used THEN the system SHALL provide all relationships: warehouse, employee, items, with proper type hints
2. WHEN Export model is used THEN the system SHALL provide all relationships: warehouse, project, employee, items
3. WHEN Transfer model is used THEN the system SHALL provide all relationships: fromWarehouse, toWarehouse, employee, items
4. WHEN generating codes THEN each model SHALL have its own generateCode() method with correct prefix

### Requirement 7: Cập nhật Controllers

**User Story:** As a developer, I want controllers updated to use new models, so that CRUD operations work with new tables.

#### Acceptance Criteria

1. WHEN ImportController performs CRUD THEN the system SHALL use Import model instead of InventoryTransaction
2. WHEN ExportController performs CRUD THEN the system SHALL use Export model instead of InventoryTransaction
3. WHEN TransferController performs CRUD THEN the system SHALL use Transfer model instead of InventoryTransaction
4. WHEN reject action is called THEN the system SHALL update status to 'rejected' without errors

### Requirement 8: Cập nhật Services

**User Story:** As a developer, I want TransactionService updated to work with new models, so that business logic remains intact.

#### Acceptance Criteria

1. WHEN processImport is called THEN the system SHALL create Import record and update inventory correctly
2. WHEN processExport is called THEN the system SHALL create Export record and decrease inventory correctly
3. WHEN processTransfer is called THEN the system SHALL create Transfer record and update both warehouses correctly
4. WHEN any transaction fails THEN the system SHALL rollback all changes

### Requirement 9: Cập nhật Reports và Dashboard

**User Story:** As a developer, I want reports and dashboard updated to query new tables, so that statistics remain accurate.

#### Acceptance Criteria

1. WHEN dashboard loads THEN the system SHALL display correct counts from imports, exports, transfers tables
2. WHEN transaction report is generated THEN the system SHALL merge data from all 3 tables correctly
3. WHEN filtering by date range THEN the system SHALL apply filters to all 3 tables
4. WHEN exporting reports THEN the system SHALL include data from correct table based on type

### Requirement 10: Xóa bảng cũ và cleanup

**User Story:** As a developer, I want old tables removed after successful migration, so that database is clean.

#### Acceptance Criteria

1. WHEN all data is verified migrated THEN the system SHALL drop table inventory_transactions
2. WHEN all data is verified migrated THEN the system SHALL drop table inventory_transaction_items
3. WHEN cleanup runs THEN the system SHALL remove InventoryTransaction model file
4. WHEN cleanup runs THEN the system SHALL remove InventoryTransactionItem model file
