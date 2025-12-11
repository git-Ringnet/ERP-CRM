# Requirements Document

## Introduction

Tái cấu trúc module "Quản lý Xuất nhập kho" hiện tại thành 3 module riêng biệt: Nhập kho, Xuất kho, và Chuyển kho. Mục tiêu là tách biệt các chức năng để dễ quản lý, bảo trì và mở rộng. Mỗi module sẽ có controller, views, routes và services riêng.

## Glossary

- **Import Module (Nhập kho)**: Module quản lý các phiếu nhập hàng vào kho
- **Export Module (Xuất kho)**: Module quản lý các phiếu xuất hàng ra khỏi kho
- **Transfer Module (Chuyển kho)**: Module quản lý các phiếu chuyển hàng giữa các kho
- **ProductItem**: Đơn vị sản phẩm với SKU riêng biệt
- **InventoryTransaction**: Giao dịch kho (nhập/xuất/chuyển)

## Requirements

### Requirement 1: Tách Module Nhập Kho

**User Story:** As a warehouse manager, I want a dedicated import module, so that I can manage stock imports independently without confusion with other operations.

#### Acceptance Criteria

1. THE Import_Module SHALL have a dedicated controller `ImportController` handling all import operations
2. THE Import_Module SHALL have dedicated views in `resources/views/imports/` directory including index, create, show, and edit views
3. THE Import_Module SHALL have dedicated routes with prefix `/imports` for all import operations
4. WHEN a user accesses the import module THEN THE Import_Module SHALL display only import transactions
5. WHEN a user creates an import transaction THEN THE Import_Module SHALL support SKU entry, cost_usd, price_tiers, description, and comments fields
6. THE Import_Module SHALL generate transaction codes with prefix `IMP` followed by sequential numbers

### Requirement 2: Tách Module Xuất Kho

**User Story:** As a warehouse manager, I want a dedicated export module, so that I can manage stock exports independently and track outgoing inventory clearly.

#### Acceptance Criteria

1. THE Export_Module SHALL have a dedicated controller `ExportController` handling all export operations
2. THE Export_Module SHALL have dedicated views in `resources/views/exports/` directory including index, create, show, and edit views
3. THE Export_Module SHALL have dedicated routes with prefix `/exports` for all export operations
4. WHEN a user accesses the export module THEN THE Export_Module SHALL display only export transactions
5. WHEN a user creates an export transaction THEN THE Export_Module SHALL allow selection of specific ProductItems (SKUs) to export
6. THE Export_Module SHALL generate transaction codes with prefix `EXP` followed by sequential numbers
7. WHEN exporting products THEN THE Export_Module SHALL validate sufficient stock before allowing the transaction

### Requirement 3: Tách Module Chuyển Kho

**User Story:** As a warehouse manager, I want a dedicated transfer module, so that I can manage inter-warehouse transfers independently and track stock movements between locations.

#### Acceptance Criteria

1. THE Transfer_Module SHALL have a dedicated controller `TransferController` handling all transfer operations
2. THE Transfer_Module SHALL have dedicated views in `resources/views/transfers/` directory including index, create, show, and edit views
3. THE Transfer_Module SHALL have dedicated routes with prefix `/transfers` for all transfer operations
4. WHEN a user accesses the transfer module THEN THE Transfer_Module SHALL display only transfer transactions
5. WHEN a user creates a transfer transaction THEN THE Transfer_Module SHALL require both source warehouse and destination warehouse selection
6. THE Transfer_Module SHALL generate transaction codes with prefix `TRF` followed by sequential numbers
7. WHEN transferring products THEN THE Transfer_Module SHALL validate sufficient stock in source warehouse before allowing the transaction

### Requirement 4: Shared Services và Models

**User Story:** As a developer, I want shared services and models across modules, so that business logic remains consistent and code duplication is minimized.

#### Acceptance Criteria

1. THE System SHALL maintain shared `InventoryTransaction` model for all transaction types
2. THE System SHALL maintain shared `ProductItem` model for SKU management
3. THE System SHALL maintain shared `TransactionService` for common transaction operations
4. THE System SHALL maintain shared `ProductItemService` for product item operations
5. THE System SHALL maintain shared `InventoryService` for inventory calculations

### Requirement 5: Navigation và Menu

**User Story:** As a user, I want clear navigation between modules, so that I can easily access the specific functionality I need.

#### Acceptance Criteria

1. THE System SHALL display separate menu items for Import, Export, and Transfer modules in the sidebar
2. WHEN a user clicks on a module menu item THEN THE System SHALL navigate to that module's index page
3. THE System SHALL highlight the active module in the navigation menu
4. THE System SHALL remove the combined "Quản lý Xuất nhập kho" menu item

### Requirement 6: Data Migration và Backward Compatibility

**User Story:** As a system administrator, I want existing data to remain accessible, so that historical transactions are not lost during the restructuring.

#### Acceptance Criteria

1. THE System SHALL maintain all existing transaction data in the database
2. THE System SHALL display existing transactions in their respective new modules based on transaction type
3. THE System SHALL preserve all relationships between transactions, items, and product items
4. THE System SHALL not require any database schema changes for the separation

### Requirement 7: Xóa Code Cũ

**User Story:** As a developer, I want old combined code removed, so that the codebase remains clean and maintainable.

#### Acceptance Criteria

1. WHEN all new modules are functional THEN THE System SHALL remove the old `InventoryTransactionController`
2. WHEN all new modules are functional THEN THE System SHALL remove old views in `resources/views/transactions/` directory
3. WHEN all new modules are functional THEN THE System SHALL remove old routes for `/transactions`
4. THE System SHALL update any references to old routes in other parts of the application
