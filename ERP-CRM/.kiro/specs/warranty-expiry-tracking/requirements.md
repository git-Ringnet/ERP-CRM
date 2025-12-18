# Requirements Document

## Introduction

Module Theo dõi bảo hành / hạn sử dụng cho phép hệ thống ERP quản lý và theo dõi thông tin bảo hành của sản phẩm đã bán cho khách hàng. Hệ thống sẽ tự động tính toán ngày hết hạn bảo hành dựa trên ngày bán và thời gian bảo hành mặc định của sản phẩm hoặc thời gian bảo hành tùy chỉnh khi bán. Module cũng cung cấp báo cáo chi tiết về tình trạng bảo hành và cảnh báo sản phẩm sắp hết hạn bảo hành.

## Glossary

- **Warranty_System**: Hệ thống theo dõi bảo hành sản phẩm
- **Product**: Sản phẩm trong hệ thống với thời gian bảo hành mặc định (warranty_months)
- **ProductItem**: Đơn vị sản phẩm cụ thể với serial number
- **Sale**: Đơn hàng bán cho khách hàng
- **SaleItem**: Chi tiết sản phẩm trong đơn hàng bán
- **Warranty_Start_Date**: Ngày bắt đầu bảo hành (ngày bán hàng)
- **Warranty_End_Date**: Ngày kết thúc bảo hành
- **Warranty_Months**: Số tháng bảo hành
- **Warranty_Status**: Trạng thái bảo hành (active, expired, voided)

## Requirements

### Requirement 1

**User Story:** As a warehouse manager, I want to track warranty information for sold products, so that I can provide accurate warranty support to customers.

#### Acceptance Criteria

1. WHEN a product is sold THEN the Warranty_System SHALL record warranty start date as the sale date
2. WHEN a product is sold THEN the Warranty_System SHALL calculate warranty end date based on warranty_months
3. WHEN warranty_months is not specified at sale time THEN the Warranty_System SHALL use the default warranty_months from Product
4. WHEN warranty_months is specified at sale time THEN the Warranty_System SHALL use the specified value instead of default
5. WHEN viewing a sold product item THEN the Warranty_System SHALL display warranty status (active/expired)

### Requirement 2

**User Story:** As a sales staff, I want to specify custom warranty period when selling products, so that I can offer flexible warranty terms to customers.

#### Acceptance Criteria

1. WHEN creating a sale order THEN the Warranty_System SHALL allow specifying warranty_months for each item
2. WHEN warranty_months field is empty THEN the Warranty_System SHALL default to product's warranty_months value
3. WHEN warranty_months is set to 0 THEN the Warranty_System SHALL treat the product as having no warranty
4. WHEN saving sale item with warranty THEN the Warranty_System SHALL validate warranty_months is between 0 and 120

### Requirement 3

**User Story:** As a manager, I want to view a list of products with warranty information, so that I can monitor warranty status across all sold products.

#### Acceptance Criteria

1. WHEN accessing warranty tracking page THEN the Warranty_System SHALL display list of sold products with warranty info
2. WHEN displaying warranty list THEN the Warranty_System SHALL show: product code, product name, serial, customer name, sale date, warranty start, warranty end, warranty status
3. WHEN filtering by warranty status THEN the Warranty_System SHALL return only products matching the selected status
4. WHEN filtering by date range THEN the Warranty_System SHALL return products with warranty end date within the range
5. WHEN searching by serial or product code THEN the Warranty_System SHALL return matching products

### Requirement 4

**User Story:** As a manager, I want to see products with warranty expiring soon, so that I can proactively contact customers about warranty renewal.

#### Acceptance Criteria

1. WHEN accessing expiring warranty page THEN the Warranty_System SHALL display products expiring within configurable days (default 30)
2. WHEN displaying expiring list THEN the Warranty_System SHALL sort by warranty end date ascending (soonest first)
3. WHEN a product warranty expires within 7 days THEN the Warranty_System SHALL highlight it with warning color
4. WHEN a product warranty expires within 3 days THEN the Warranty_System SHALL highlight it with danger color
5. WHEN clicking on a product THEN the Warranty_System SHALL show detailed warranty information

### Requirement 5

**User Story:** As a manager, I want to generate warranty reports, so that I can analyze warranty data and make business decisions.

#### Acceptance Criteria

1. WHEN generating warranty summary report THEN the Warranty_System SHALL show total products under warranty, expired, and expiring soon
2. WHEN generating warranty report by customer THEN the Warranty_System SHALL group products by customer with warranty counts
3. WHEN generating warranty report by product THEN the Warranty_System SHALL group by product code with warranty statistics
4. WHEN exporting warranty report THEN the Warranty_System SHALL generate Excel file with all warranty data
5. WHEN filtering report by date range THEN the Warranty_System SHALL include only products sold within the range

### Requirement 6

**User Story:** As a system administrator, I want warranty data to be automatically calculated, so that the system maintains accurate warranty information without manual intervention.

#### Acceptance Criteria

1. WHEN warranty_end_date is queried THEN the Warranty_System SHALL calculate it as warranty_start_date plus warranty_months
2. WHEN warranty_status is queried THEN the Warranty_System SHALL return 'active' if current date is before warranty_end_date
3. WHEN warranty_status is queried THEN the Warranty_System SHALL return 'expired' if current date is after warranty_end_date
4. WHEN product has no warranty (warranty_months = 0 or null) THEN the Warranty_System SHALL return 'no_warranty' status

### Requirement 7

**User Story:** As a user, I want the warranty tracking interface to be intuitive and consistent with the ERP design, so that I can easily navigate and use the feature.

#### Acceptance Criteria

1. WHEN displaying warranty tracking page THEN the Warranty_System SHALL use the existing ERP layout and styling
2. WHEN displaying warranty status THEN the Warranty_System SHALL use color coding (green=active, red=expired, yellow=expiring)
3. WHEN displaying dates THEN the Warranty_System SHALL format as DD/MM/YYYY consistent with ERP
4. WHEN paginating results THEN the Warranty_System SHALL show 20 items per page with navigation controls
