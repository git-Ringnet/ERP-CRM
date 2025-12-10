# Requirements Document

## Introduction

Tài liệu này mô tả yêu cầu cập nhật lại module Sản phẩm (Products) trong hệ thống ERP-CRM. Mục tiêu là đơn giản hóa bảng products chỉ chứa thông tin cơ bản của sản phẩm, tạo bảng product_items mới để quản lý từng đơn vị sản phẩm với SKU riêng biệt và các gói giá theo năm (1yr-5yr). Việc tạo product_items chỉ xảy ra khi thực hiện nhập kho, cho phép nhập nhiều SKU cho cùng một sản phẩm.

## Glossary

- **Product**: Thông tin cơ bản của một loại sản phẩm (tên, mô tả, danh mục, đơn vị tính)
- **Product Item**: Đơn vị sản phẩm cụ thể với SKU riêng, được tạo khi nhập kho
- **SKU (Stock Keeping Unit)**: Mã định danh duy nhất cho từng đơn vị sản phẩm (thay thế serial number)
- **Category**: Danh mục sản phẩm theo ký tự (A, B, C, D, E, F, J, S...)
- **Price Tier (1yr-5yr)**: Các gói giá theo thời hạn hợp đồng/bảo hành 1-5 năm
- **NO_SKU**: Mã SKU đặc biệt dùng cho các sản phẩm không có SKU cụ thể, format: `NOSKU-{product_id}-{timestamp}`
- **Inventory Transaction**: Giao dịch nhập/xuất kho

## Requirements

### Requirement 1: Đơn giản hóa bảng Products

**User Story:** As a warehouse manager, I want to see only basic product information, so that I can quickly identify and manage products without unnecessary details.

#### Acceptance Criteria

1. WHEN the system displays product information THEN the Product_Module SHALL show only: code, name, category, unit, description, and note fields
2. WHEN a user views the product list THEN the Product_Module SHALL hide SKU, price, cost, stock, min_stock, max_stock, and location fields from the products table
3. WHEN a user creates or edits a product THEN the Product_Module SHALL only require and accept basic product fields (code, name, category, unit, description, note)
4. WHEN the database migration runs THEN the Product_Module SHALL remove price, cost, stock, min_stock, max_stock, management_type, auto_generate_serial, serial_prefix, expiry_months, track_expiry columns from products table

### Requirement 2: Hệ thống danh mục sản phẩm (Category)

**User Story:** As a product manager, I want to categorize products using letter codes (A-Z), so that I can organize products systematically.

#### Acceptance Criteria

1. WHEN a user selects a category THEN the Product_Module SHALL display category options as single letters: A, B, C, D, E, F, J, S and other letters as needed
2. WHEN a user creates or edits a product THEN the Product_Module SHALL validate that category is a single uppercase letter
3. WHEN displaying products THEN the Product_Module SHALL allow filtering by category letter

### Requirement 3: Tạo bảng Product Items

**User Story:** As a warehouse staff, I want to track individual product units with unique SKUs and pricing tiers, so that I can manage inventory at the item level.

#### Acceptance Criteria

1. WHEN the database migration runs THEN the Product_Module SHALL create a product_items table with columns: id, product_id, sku, description, price, price_1yr, price_2yr, price_3yr, price_4yr, price_5yr, quantity, comments, warehouse_id, status, created_at, updated_at
2. WHEN a product item is created THEN the Product_Module SHALL require a valid product_id reference
3. WHEN a product item is created THEN the Product_Module SHALL store SKU as a unique identifier within the same product
4. WHEN a product item has no physical SKU THEN the Product_Module SHALL generate a NO_SKU identifier using format: NOSKU-{product_id}-{sequential_number}
5. WHEN displaying product items THEN the Product_Module SHALL show all price tiers (1yr through 5yr) in separate columns

### Requirement 4: Nhập kho với nhiều SKU

**User Story:** As a warehouse staff, I want to import products with multiple SKUs in a single transaction, so that I can efficiently record inventory receipts.

#### Acceptance Criteria

1. WHEN a user performs an import transaction THEN the Inventory_Module SHALL allow adding multiple SKU inputs for the same product using a dynamic form with add/remove buttons
2. WHEN a user enters SKUs during import THEN the Inventory_Module SHALL create corresponding product_items records
3. WHEN the quantity exceeds the number of provided SKUs THEN the Inventory_Module SHALL automatically generate NO_SKU identifiers for remaining items
4. WHEN a user imports 10 items with only 5 SKUs provided THEN the Inventory_Module SHALL create 5 items with provided SKUs and 5 items with NO_SKU identifiers
5. WHEN an import transaction is saved THEN the Inventory_Module SHALL validate that all SKUs are unique within the product scope
6. WHEN displaying the import form THEN the Inventory_Module SHALL show a button to add more SKU input fields dynamically

### Requirement 5: Gói giá theo năm (Price Tiers)

**User Story:** As a sales manager, I want to set different prices for different contract durations (1-5 years), so that I can offer flexible pricing options to customers.

#### Acceptance Criteria

1. WHEN a user creates or edits a product item THEN the Product_Module SHALL allow entering prices for 1yr, 2yr, 3yr, 4yr, and 5yr tiers
2. WHEN displaying product item details THEN the Product_Module SHALL show all price tier columns clearly labeled
3. WHEN a price tier is not applicable THEN the Product_Module SHALL allow storing null or zero value
4. WHEN calculating total value THEN the Product_Module SHALL use the base price field for default calculations

### Requirement 6: Cập nhật giao diện danh sách sản phẩm

**User Story:** As a user, I want to see a clean product list with only essential information, so that I can quickly find and manage products.

#### Acceptance Criteria

1. WHEN displaying the product index page THEN the Product_Module SHALL show columns: Code, Name, Category, Unit, Description, Actions
2. WHEN displaying the product index page THEN the Product_Module SHALL NOT show: SKU, Price, Stock, Min Stock, Max Stock, Location columns
3. WHEN a user clicks on a product THEN the Product_Module SHALL navigate to product detail page showing basic info and related product_items
4. WHEN displaying product detail THEN the Product_Module SHALL show a list of all product_items with their SKUs and price tiers

### Requirement 7: Liên kết Product Items với Inventory

**User Story:** As a warehouse manager, I want product items to be linked to inventory transactions, so that I can track the movement of each item.

#### Acceptance Criteria

1. WHEN a product item is created through import THEN the Inventory_Module SHALL link it to the corresponding inventory transaction
2. WHEN displaying inventory details THEN the Inventory_Module SHALL show all related product_items with their SKUs
3. WHEN exporting inventory THEN the Inventory_Module SHALL update the status of related product_items
4. WHEN querying product stock THEN the Inventory_Module SHALL calculate total from product_items quantity where status is 'in_stock'
