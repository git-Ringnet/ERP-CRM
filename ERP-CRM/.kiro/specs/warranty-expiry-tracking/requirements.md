# Requirements Document

## Introduction

Tính năng Theo dõi Bảo hành và Hạn sử dụng cho phép quản lý thông tin bảo hành mặc định của sản phẩm và theo dõi hạn sử dụng của từng lô hàng trong kho. Hệ thống sẽ cảnh báo khi sản phẩm sắp hết hạn để người dùng có thể xử lý kịp thời.

**Phạm vi:** Chỉ tập trung vào quản lý kho, không liên quan đến module bán hàng.

## Glossary

- **Product**: Sản phẩm trong hệ thống
- **Product Item**: Đơn vị sản phẩm cụ thể trong kho (có SKU riêng)
- **Warranty Period**: Thời gian bảo hành mặc định của sản phẩm (tính theo tháng)
- **Expiry Date**: Ngày hết hạn sử dụng của sản phẩm
- **Expiring Soon**: Sản phẩm sắp hết hạn (trong vòng 30/60/90 ngày)

## Requirements

### Requirement 1: Thông tin bảo hành mặc định trên sản phẩm

**User Story:** As a warehouse manager, I want to set default warranty period for each product, so that I can track warranty information consistently.

#### Acceptance Criteria

1. WHEN a user creates or edits a product THEN the system SHALL display a warranty period field (in months)
2. WHEN a user enters warranty period THEN the system SHALL validate that the value is a positive integer or null
3. WHEN viewing product details THEN the system SHALL display the default warranty period if configured
4. WHERE warranty tracking is enabled for a product THEN the system SHALL allow setting warranty period from 1 to 120 months

### Requirement 2: Hạn sử dụng cho Product Items

**User Story:** As a warehouse staff, I want to record expiry date for each product item when importing, so that I can track which items are expiring soon.

#### Acceptance Criteria

1. WHEN importing products into warehouse THEN the system SHALL allow entering expiry date for each product item
2. WHEN a user enters expiry date THEN the system SHALL validate that the date is in the future
3. WHEN viewing product item details THEN the system SHALL display the expiry date if configured
4. WHEN listing product items THEN the system SHALL show expiry status (valid, expiring soon, expired)

### Requirement 3: Danh sách sản phẩm sắp hết hạn

**User Story:** As a warehouse manager, I want to see a list of products expiring soon, so that I can take action before they expire.

#### Acceptance Criteria

1. WHEN a user accesses the expiring products page THEN the system SHALL display all product items with expiry dates within the configured warning period
2. WHEN displaying expiring products THEN the system SHALL show product name, SKU, warehouse, expiry date, and days remaining
3. WHEN filtering expiring products THEN the system SHALL allow filtering by warehouse, product category, and time range (30/60/90 days)
4. WHEN sorting expiring products THEN the system SHALL default to sorting by expiry date ascending (soonest first)
5. WHEN a product item has expired THEN the system SHALL highlight it with a different color (red)

### Requirement 4: Cảnh báo trên Dashboard

**User Story:** As a warehouse manager, I want to see expiry warnings on the dashboard, so that I am immediately aware of urgent items.

#### Acceptance Criteria

1. WHEN loading the dashboard THEN the system SHALL display a widget showing count of expiring products
2. WHEN products are expiring within 30 days THEN the system SHALL show an urgent warning indicator
3. WHEN clicking on the expiry widget THEN the system SHALL navigate to the expiring products list
4. WHEN no products are expiring soon THEN the system SHALL display a positive status message

### Requirement 5: Export danh sách hết hạn

**User Story:** As a warehouse manager, I want to export the expiring products list to Excel, so that I can share it with relevant teams.

#### Acceptance Criteria

1. WHEN a user clicks export on expiring products page THEN the system SHALL generate an Excel file
2. WHEN exporting THEN the system SHALL include product code, name, SKU, warehouse, expiry date, days remaining, and status
3. WHEN exporting THEN the system SHALL apply current filters to the exported data
