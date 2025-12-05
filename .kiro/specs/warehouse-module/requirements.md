# Requirements Document - Module Kho (Warehouse Module)

## Introduction

Module Kho là một phần quan trọng của hệ thống ERP-CRM, quản lý toàn bộ hoạt động kho bãi bao gồm: quản lý thông tin kho, theo dõi tồn kho, xử lý xuất nhập kho, và quản lý hàng hư hỏng/thanh lý. Module này đảm bảo việc kiểm soát hàng hóa chính xác và hiệu quả.

## Glossary

- **Warehouse**: Kho vật lý hoặc ảo dùng để lưu trữ hàng hóa
- **Inventory**: Tồn kho - số lượng hàng hóa hiện có trong kho
- **Inventory Transaction**: Giao dịch xuất nhập kho
- **Damaged Goods**: Hàng hư hỏng hoặc cần thanh lý
- **Stock**: Số lượng tồn kho hiện tại
- **Min Stock**: Mức tồn kho tối thiểu cần duy trì
- **Transfer**: Chuyển kho - di chuyển hàng từ kho này sang kho khác
- **Import**: Nhập kho - đưa hàng vào kho
- **Export**: Xuất kho - lấy hàng ra khỏi kho
- **Serial Number**: Số serial của sản phẩm
- **ERP-CRM System**: Hệ thống quản lý tổng thể doanh nghiệp

## Requirements

### Requirement 1: Quản lý Kho (Warehouse Management)

**User Story:** As a warehouse manager, I want to manage warehouse information, so that I can organize and track storage locations effectively.

#### Acceptance Criteria

1. WHEN a user creates a new warehouse THEN the ERP-CRM System SHALL generate a unique warehouse code and save all warehouse details including name, type, address, area, capacity, manager, phone, status, product type, temperature control flag, and security system flag
2. WHEN a user views the warehouse list THEN the ERP-CRM System SHALL display all warehouses with filtering options by status and type
3. WHEN a user updates warehouse information THEN the ERP-CRM System SHALL validate all required fields and save the changes
4. WHEN a user deletes a warehouse THEN the ERP-CRM System SHALL check for existing inventory and prevent deletion if inventory exists
5. WHEN a warehouse has inventory transactions THEN the ERP-CRM System SHALL maintain referential integrity and prevent orphaned records

### Requirement 2: Quản lý Tồn kho (Inventory Management)

**User Story:** As a warehouse staff, I want to track inventory levels for each product in each warehouse, so that I can monitor stock availability and prevent stockouts.

#### Acceptance Criteria

1. WHEN a user views inventory THEN the ERP-CRM System SHALL display current stock, minimum stock level, average cost, expiry date, and warranty information for each product-warehouse combination
2. WHEN stock level falls below minimum stock THEN the ERP-CRM System SHALL highlight the item as low stock warning
3. WHEN a user searches inventory THEN the ERP-CRM System SHALL allow filtering by warehouse, product, stock status, and expiry date
4. WHEN inventory is updated through transactions THEN the ERP-CRM System SHALL automatically recalculate stock levels and average cost
5. WHEN a product has expiry date approaching THEN the ERP-CRM System SHALL display expiry warning for items expiring within 30 days

### Requirement 3: Quản lý Xuất Nhập Kho (Inventory Transactions)

**User Story:** As a warehouse operator, I want to record all inventory movements, so that I can maintain accurate stock records and audit trail.

#### Acceptance Criteria

1. WHEN a user creates an import transaction THEN the ERP-CRM System SHALL generate a unique transaction code, record all items with quantities and serial numbers, and increase inventory stock accordingly
2. WHEN a user creates an export transaction THEN the ERP-CRM System SHALL validate sufficient stock exists, generate a unique transaction code, record all items, and decrease inventory stock accordingly
3. WHEN a user creates a transfer transaction THEN the ERP-CRM System SHALL decrease stock from source warehouse and increase stock in destination warehouse atomically
4. WHEN a transaction is saved THEN the ERP-CRM System SHALL record the transaction date, employee, reference type, reference ID, and all line items
5. WHEN a user views transaction history THEN the ERP-CRM System SHALL display all transactions with filtering by type, date range, warehouse, and status
6. WHEN a transaction references an order THEN the ERP-CRM System SHALL link the transaction to the source document via reference_type and reference_id
7. WHEN parsing transaction data from import files THEN the ERP-CRM System SHALL validate data against the transaction schema
8. WHEN serializing transaction data for export THEN the ERP-CRM System SHALL format data according to the export schema and allow round-trip parsing

### Requirement 4: Quản lý Hàng Hư Hỏng/Thanh Lý (Damaged Goods Management)

**User Story:** As a quality control staff, I want to record and track damaged or liquidation goods, so that I can manage losses and recovery processes.

#### Acceptance Criteria

1. WHEN a user reports damaged goods THEN the ERP-CRM System SHALL generate a unique code, record product details, quantity, original value, recovery value, reason, and discovery information
2. WHEN a user creates a liquidation record THEN the ERP-CRM System SHALL record the liquidation type, expected recovery value, and solution plan
3. WHEN damaged goods status changes THEN the ERP-CRM System SHALL update the status and maintain history of status changes
4. WHEN a user views damaged goods list THEN the ERP-CRM System SHALL display all records with filtering by type, status, date range, and product
5. WHEN damaged goods are processed THEN the ERP-CRM System SHALL update inventory accordingly and record the final solution

### Requirement 5: Báo cáo và Thống kê Kho (Warehouse Reports)

**User Story:** As a manager, I want to view warehouse reports and statistics, so that I can make informed decisions about inventory management.

#### Acceptance Criteria

1. WHEN a user requests inventory summary THEN the ERP-CRM System SHALL display total stock value, low stock items count, and expiring items count per warehouse
2. WHEN a user requests transaction report THEN the ERP-CRM System SHALL display import/export/transfer totals by date range and warehouse
3. WHEN a user requests damaged goods report THEN the ERP-CRM System SHALL display total loss value and recovery rate statistics

### Requirement 6: Tích hợp và Liên kết (Integration)

**User Story:** As a system administrator, I want the warehouse module to integrate with other modules, so that data flows seamlessly across the system.

#### Acceptance Criteria

1. WHEN a purchase order is received THEN the ERP-CRM System SHALL allow creating import transaction linked to the purchase order
2. WHEN a sales order is fulfilled THEN the ERP-CRM System SHALL allow creating export transaction linked to the sales order
3. WHEN inventory changes THEN the ERP-CRM System SHALL update product availability across all related modules
