# Requirements Document

## Introduction

Tài liệu này mô tả các yêu cầu cho giai đoạn Tháng 1 - Nền tảng của hệ thống Mini ERP/CRM. Giai đoạn này tập trung vào việc xây dựng các module Master Data cơ bản, hệ thống phân quyền và Dashboard tổng quan. Đây là nền tảng quan trọng để phát triển các tính năng nâng cao trong các tháng tiếp theo.

## Glossary

- **ERP_System**: Hệ thống Mini ERP/CRM quản lý doanh nghiệp
- **Customer**: Khách hàng - đối tác mua hàng của doanh nghiệp
- **Supplier**: Nhà cung cấp - đối tác cung cấp hàng hóa cho doanh nghiệp
- **Employee**: Nhân viên - người làm việc trong doanh nghiệp
- **Product**: Sản phẩm/Hàng hóa được kinh doanh
- **Serial_Number**: Mã định danh duy nhất cho từng đơn vị sản phẩm
- **Lot_Number**: Số lô - mã định danh cho một lô sản phẩm
- **Internal_Serial**: Serial nội bộ - mã định danh do doanh nghiệp tự tạo
- **User**: Người dùng hệ thống
- **Role**: Vai trò - nhóm quyền được gán cho người dùng
- **Permission**: Quyền truy cập vào các chức năng cụ thể
- **Dashboard**: Bảng điều khiển tổng quan hiển thị các chỉ số quan trọng
- **Import_Module**: Chức năng nhập dữ liệu hàng loạt từ file Excel/CSV
- **VIP_Customer**: Khách hàng VIP - khách hàng có ưu đãi đặc biệt
- **Debt_Limit**: Hạn mức công nợ - số tiền tối đa khách hàng được nợ

## Requirements

### Requirement 1: Quản lý thông tin khách hàng

**User Story:** As a sales staff, I want to manage customer information, so that I can track and serve customers effectively.

#### Acceptance Criteria

1. WHEN a user opens the customer management module THEN THE ERP_System SHALL display a list of all customers with columns: customer code, name, email, phone, address, and customer type
2. WHEN a user clicks the "Add Customer" button THEN THE ERP_System SHALL display a form with fields: customer code, name, email, phone, address, customer type (Normal/VIP), tax code, website, contact person, debt limit, debt days, and notes
3. WHEN a user submits a new customer form with valid data THEN THE ERP_System SHALL create a new customer record and display it in the customer list
4. WHEN a user submits a customer form with empty required fields (code, name, email, phone) THEN THE ERP_System SHALL display validation error messages and prevent form submission
5. WHEN a user clicks the edit button on a customer row THEN THE ERP_System SHALL display the edit form pre-filled with the customer's current data
6. WHEN a user updates customer information and saves THEN THE ERP_System SHALL update the customer record and reflect changes in the list
7. WHEN a user clicks the delete button on a customer row THEN THE ERP_System SHALL display a confirmation dialog before deleting
8. WHEN a user confirms deletion THEN THE ERP_System SHALL remove the customer record from the system
9. WHEN a user types in the search box THEN THE ERP_System SHALL filter the customer list to show only matching records in real-time
10. WHEN a user selects a customer type filter THEN THE ERP_System SHALL display only customers of the selected type (VIP/Normal)
11. WHEN a user sets debt limit and debt days for a customer THEN THE ERP_System SHALL store these values for credit control purposes

### Requirement 2: Quản lý thông tin nhà cung cấp

**User Story:** As a purchasing staff, I want to manage supplier information, so that I can maintain supplier relationships and procurement processes.

#### Acceptance Criteria

1. WHEN a user opens the supplier management module THEN THE ERP_System SHALL display a list of all suppliers with columns: supplier code, name, email, phone, and address
2. WHEN a user clicks the "Add Supplier" button THEN THE ERP_System SHALL display a form with fields: supplier code, name, email, phone, address, tax code, website, contact person, payment terms (days), product type, and notes
3. WHEN a user submits a new supplier form with valid data THEN THE ERP_System SHALL create a new supplier record and display it in the supplier list
4. WHEN a user submits a supplier form with empty required fields (code, name, email, phone) THEN THE ERP_System SHALL display validation error messages and prevent form submission
5. WHEN a user clicks the edit button on a supplier row THEN THE ERP_System SHALL display the edit form pre-filled with the supplier's current data
6. WHEN a user updates supplier information and saves THEN THE ERP_System SHALL update the supplier record and reflect changes in the list
7. WHEN a user clicks the delete button on a supplier row THEN THE ERP_System SHALL display a confirmation dialog before deleting
8. WHEN a user confirms deletion THEN THE ERP_System SHALL remove the supplier record from the system
9. WHEN a user types in the search box THEN THE ERP_System SHALL filter the supplier list to show only matching records in real-time
10. WHEN a user sets payment terms for a supplier THEN THE ERP_System SHALL store the number of days for payment scheduling

### Requirement 3: Quản lý thông tin nhân viên

**User Story:** As an HR staff, I want to manage employee information, so that I can maintain accurate personnel records.

#### Acceptance Criteria

1. WHEN a user opens the employee management module THEN THE ERP_System SHALL display a list of all employees with columns: employee code, name, position, department, email, phone, and status
2. WHEN a user clicks the "Add Employee" button THEN THE ERP_System SHALL display a form with fields: employee code, name, birth date, email, phone, address, department, position, join date, salary, ID card number, bank account, bank name, status, and notes
3. WHEN a user submits a new employee form with valid data THEN THE ERP_System SHALL create a new employee record and display it in the employee list
4. WHEN a user submits an employee form with empty required fields (code, name, email, phone, department, position) THEN THE ERP_System SHALL display validation error messages and prevent form submission
5. WHEN a user clicks the edit button on an employee row THEN THE ERP_System SHALL display the edit form pre-filled with the employee's current data
6. WHEN a user updates employee information and saves THEN THE ERP_System SHALL update the employee record and reflect changes in the list
7. WHEN a user clicks the delete button on an employee row THEN THE ERP_System SHALL display a confirmation dialog before deleting
8. WHEN a user confirms deletion THEN THE ERP_System SHALL remove the employee record from the system
9. WHEN a user types in the search box THEN THE ERP_System SHALL filter the employee list to show only matching records in real-time
10. WHEN a user selects a department filter THEN THE ERP_System SHALL display only employees of the selected department
11. WHEN a user changes employee status THEN THE ERP_System SHALL update the status badge to reflect: Active (green), On Leave (yellow), or Resigned (red)

### Requirement 4: Quản lý sản phẩm

**User Story:** As a warehouse staff, I want to manage product information with serial numbers and lot tracking, so that I can maintain accurate inventory records.

#### Acceptance Criteria

1. WHEN a user opens the product management module THEN THE ERP_System SHALL display a list of all products with columns: product code, name, unit, selling price, cost price, stock quantity, and management type
2. WHEN a user clicks the "Add Product" button THEN THE ERP_System SHALL display a form with fields: product code, name, category, unit, selling price, cost price, min stock, max stock, management type (Normal/Serial/Lot), description, and notes
3. WHEN a user selects "Serial Number" management type THEN THE ERP_System SHALL display additional fields: auto-generate serial checkbox and serial prefix
4. WHEN a user selects "Lot Number" management type THEN THE ERP_System SHALL display additional fields: expiry months and track expiry checkbox
5. WHEN a user submits a new product form with valid data THEN THE ERP_System SHALL create a new product record and display it in the product list
6. WHEN a user submits a product form with empty required fields (code, name, unit, selling price, cost price) THEN THE ERP_System SHALL display validation error messages and prevent form submission
7. WHEN a user clicks the edit button on a product row THEN THE ERP_System SHALL display the edit form pre-filled with the product's current data
8. WHEN a user updates product information and saves THEN THE ERP_System SHALL update the product record and reflect changes in the list
9. WHEN a user clicks the delete button on a product row THEN THE ERP_System SHALL display a confirmation dialog before deleting
10. WHEN a user confirms deletion THEN THE ERP_System SHALL remove the product record from the system
11. WHEN a user types in the search box THEN THE ERP_System SHALL filter the product list to show only matching records in real-time
12. WHEN a user selects a management type filter THEN THE ERP_System SHALL display only products of the selected type (Normal/Serial/Lot)
13. WHEN auto-generate serial is enabled THEN THE ERP_System SHALL generate unique serial numbers using the format: [prefix]-[year]-[6-digit-random]
14. WHEN a product has lot management THEN THE ERP_System SHALL track expiry dates based on the configured expiry months

### Requirement 5: Module Import sản phẩm/khách hàng cơ bản

**User Story:** As an admin, I want to import products and customers from Excel files, so that I can quickly populate the system with existing data.

#### Acceptance Criteria

1. WHEN a user opens the import module THEN THE ERP_System SHALL display options to import: Customers or Products
2. WHEN a user selects an import type THEN THE ERP_System SHALL provide a downloadable Excel template with the correct column headers
3. WHEN a user uploads an Excel file THEN THE ERP_System SHALL validate the file format and display a preview of the data to be imported
4. WHEN the uploaded file contains invalid data format THEN THE ERP_System SHALL display specific error messages indicating which rows and columns have issues
5. WHEN a user confirms the import THEN THE ERP_System SHALL create new records for all valid rows and display a summary of imported records
6. WHEN duplicate codes are found during import THEN THE ERP_System SHALL skip duplicate records and report them in the import summary
7. WHEN required fields are missing in import data THEN THE ERP_System SHALL reject those rows and include them in the error report
8. WHEN import completes THEN THE ERP_System SHALL display a summary showing: total rows processed, successful imports, failed imports, and skipped duplicates

### Requirement 6: Dashboard nền tảng (tổng quan)

**User Story:** As a manager, I want to see a dashboard with key business metrics, so that I can monitor business performance at a glance.

#### Acceptance Criteria

1. WHEN a user opens the dashboard THEN THE ERP_System SHALL display summary cards showing: total customers, total suppliers, total employees, and total products
2. WHEN a user views the dashboard THEN THE ERP_System SHALL display a chart showing customer distribution by type (VIP vs Normal)
3. WHEN a user views the dashboard THEN THE ERP_System SHALL display a chart showing employee distribution by department
4. WHEN a user views the dashboard THEN THE ERP_System SHALL display a chart showing product distribution by management type
5. WHEN a user views the dashboard THEN THE ERP_System SHALL display recent activities including: newly added customers, suppliers, employees, and products
6. WHEN underlying data changes THEN THE ERP_System SHALL update dashboard metrics to reflect current data
7. WHEN a user clicks on a summary card THEN THE ERP_System SHALL navigate to the corresponding module (customers, suppliers, employees, or products)

### Requirement 7: Xuất dữ liệu Excel

**User Story:** As a user, I want to export data to Excel, so that I can analyze data offline or share with others.

#### Acceptance Criteria

1. WHEN a user clicks the "Export Excel" button in any module THEN THE ERP_System SHALL generate an Excel file containing all visible data
2. WHEN exporting customer data THEN THE ERP_System SHALL include columns: customer code, name, email, phone, address, type, tax code, debt limit, and debt days
3. WHEN exporting supplier data THEN THE ERP_System SHALL include columns: supplier code, name, email, phone, address, tax code, payment terms, and product type
4. WHEN exporting employee data THEN THE ERP_System SHALL include columns: employee code, name, position, department, email, phone, status, join date, and salary
5. WHEN exporting product data THEN THE ERP_System SHALL include columns: product code, name, unit, selling price, cost price, stock, management type, and category
6. WHEN a filter is applied before export THEN THE ERP_System SHALL export only the filtered data
7. WHEN export completes THEN THE ERP_System SHALL trigger a file download with filename format: [module-name]-[date].xlsx
