# Requirements Document - Inventory Operations V2

## Introduction

This document specifies the requirements for updating the Export (Xuất kho), Transfer (Chuyển kho), and Bulk Import (Import Excel) modules to work with the new product structure that uses product_items with SKU management, USD pricing, and dynamic price tiers.

## Glossary

- **System**: The ERP-CRM inventory management system
- **Product**: Basic product information (code, name, category, unit, description)
- **Product Item**: Individual inventory item with unique SKU, cost_usd, and price_tiers
- **SKU**: Stock Keeping Unit - unique identifier for each product item
- **NO_SKU**: Auto-generated SKU format (NOSKU-{product_id}-{sequential_number}) for items without physical SKU
- **Export Transaction**: Warehouse export operation (xuất kho)
- **Transfer Transaction**: Warehouse transfer operation (chuyển kho)
- **Bulk Import**: Excel file import for mass data entry
- **Price Tier**: Dynamic pricing structure (e.g., 1yr: $100, 2yr: $180, 3yr: $250)
- **USD**: United States Dollar - base currency for cost and pricing
- **VND**: Vietnamese Dong - display currency for sales (converted from USD)

## Requirements

### Requirement 1: Export Transaction (Xuất kho)

**User Story:** As a warehouse manager, I want to export products from warehouse by selecting specific SKUs, so that I can track which exact items are being sold or transferred out.

#### Acceptance Criteria

1. WHEN creating an export transaction THEN the System SHALL display available product_items with status 'in_stock' from the selected warehouse
2. WHEN selecting products to export THEN the System SHALL allow selection of specific SKUs for each product
3. WHEN quantity exceeds available SKUs THEN the System SHALL prevent the export and display an error message
4. WHEN export is confirmed THEN the System SHALL update selected product_items status to 'sold' or 'transferred'
5. WHEN displaying export details THEN the System SHALL show SKU list, cost_usd, and price_tiers for each exported item

### Requirement 2: Transfer Transaction (Chuyển kho)

**User Story:** As a warehouse manager, I want to transfer products between warehouses by selecting specific SKUs, so that I can maintain accurate inventory across multiple locations.

#### Acceptance Criteria

1. WHEN creating a transfer transaction THEN the System SHALL require both source warehouse and destination warehouse selection
2. WHEN selecting products to transfer THEN the System SHALL display available product_items from the source warehouse
3. WHEN transfer is confirmed THEN the System SHALL update product_items warehouse_id to the destination warehouse
4. WHEN transfer is confirmed THEN the System SHALL maintain product_items status as 'in_stock'
5. WHEN displaying transfer details THEN the System SHALL show both source and destination warehouse information with SKU details

### Requirement 3: Bulk Import via Excel (Import dữ liệu hàng loạt)

**User Story:** As a data entry staff, I want to import products and inventory data from Excel files, so that I can quickly populate the system with large datasets.

#### Acceptance Criteria

1. WHEN accessing bulk import THEN the System SHALL provide downloadable Excel templates for Products and Inventory
2. WHEN uploading Products Excel THEN the System SHALL validate and import basic product information (code, name, category, unit, description)
3. WHEN uploading Inventory Excel THEN the System SHALL create product_items with SKU, cost_usd, and price_tiers from the file
4. WHEN price_tiers column contains JSON string THEN the System SHALL parse and store as JSON array
5. WHEN SKU is empty in Excel THEN the System SHALL auto-generate NO_SKU format for that item
6. WHEN import completes THEN the System SHALL display summary report showing success count, error count, and error details
7. WHEN import encounters errors THEN the System SHALL rollback the entire import and display specific error messages

### Requirement 4: Edit Transaction

**User Story:** As a warehouse manager, I want to edit pending transactions before approval, so that I can correct mistakes or update information.

#### Acceptance Criteria

1. WHEN transaction status is 'pending' THEN the System SHALL allow editing of transaction details
2. WHEN editing import transaction THEN the System SHALL allow modification of SKUs and price_tiers
3. WHEN transaction status is 'approved' THEN the System SHALL prevent any modifications
4. WHEN saving edits THEN the System SHALL validate all data before updating
5. WHEN editing affects product_items THEN the System SHALL update or recreate product_items accordingly

### Requirement 5: Transaction Display Consistency

**User Story:** As a user, I want consistent display of transaction information across all views, so that I can easily understand the data.

#### Acceptance Criteria

1. WHEN viewing any transaction THEN the System SHALL display product_items with SKU, cost_usd, and price_tiers
2. WHEN displaying price_tiers THEN the System SHALL show as badges with tier name and USD price
3. WHEN displaying NO_SKU items THEN the System SHALL visually distinguish them with italic gray text
4. WHEN viewing transaction list THEN the System SHALL show summary information including total SKU count
5. WHEN exporting transaction report THEN the System SHALL include all product_items details

### Requirement 6: Excel Template Structure

**User Story:** As a system administrator, I want well-structured Excel templates, so that users can easily prepare import data.

#### Acceptance Criteria

1. WHEN downloading Product template THEN the System SHALL provide columns: Code, Name, Category, Unit, Description, Note
2. WHEN downloading Inventory template THEN the System SHALL provide columns: Product_Code, Warehouse_Code, Quantity, SKU, Cost_USD, Price_Tiers_JSON, Description, Comments, Transaction_Date
3. WHEN template includes examples THEN the System SHALL provide sample rows with correct data format
4. WHEN Price_Tiers_JSON column is used THEN the System SHALL provide example JSON format: [{"name":"1yr","price":100},{"name":"2yr","price":180}]
5. WHEN template is opened THEN the System SHALL include instructions sheet explaining each column

### Requirement 7: Data Validation for Bulk Import

**User Story:** As a system, I want to validate imported data thoroughly, so that data integrity is maintained.

#### Acceptance Criteria

1. WHEN validating Product_Code THEN the System SHALL check if product exists in database
2. WHEN validating Warehouse_Code THEN the System SHALL check if warehouse exists in database
3. WHEN validating SKU THEN the System SHALL check for duplicates within the same product
4. WHEN validating Cost_USD THEN the System SHALL ensure it is a positive decimal number
5. WHEN validating Price_Tiers_JSON THEN the System SHALL ensure it is valid JSON array format
6. WHEN validating Category THEN the System SHALL ensure it is a single letter A-Z
7. WHEN validation fails THEN the System SHALL provide row number and specific error message
