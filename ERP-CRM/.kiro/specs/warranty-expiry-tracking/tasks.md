# Implementation Plan

## Warranty & Expiry Tracking Module

- [x] 1. Database Migration




  - [ ] 1.1 Tạo migration thêm cột warranty vào sale_items
    - Thêm cột `warranty_months` (INT UNSIGNED NULL)




    - Thêm cột `warranty_start_date` (DATE NULL)
    - _Requirements: 1.1, 1.2, 2.1_



- [ ] 2. Update SaleItem Model
  - [ ] 2.1 Thêm fillable và casts cho warranty fields
    - Thêm `warranty_months`, `warranty_start_date` vào fillable
    - Thêm casts cho date và integer
    - _Requirements: 1.1, 2.1_
  - [ ] 2.2 Tạo accessors cho warranty calculations
    - `getWarrantyEndDateAttribute()` - tính ngày hết hạn
    - `getWarrantyStatusAttribute()` - trả về trạng thái
    - `getWarrantyDaysRemainingAttribute()` - số ngày còn lại
    - _Requirements: 1.2, 1.5, 6.1, 6.2, 6.3, 6.4_
  - [x]* 2.3 Write property test for warranty end date calculation




    - **Property 1: Warranty end date calculation**
    - **Validates: Requirements 1.2, 6.1**
  - [x]* 2.4 Write property test for warranty status calculation

    - **Property 4: Warranty status calculation**
    - **Validates: Requirements 1.5, 6.2, 6.3**
  - [ ]* 2.5 Write property test for no warranty status
    - **Property 5: No warranty status**

    - **Validates: Requirements 2.3, 6.4**

- [ ] 3. Create WarrantyService
  - [ ] 3.1 Tạo WarrantyService class
    - Implement `calculateWarrantyEndDate()`
    - Implement `getWarrantyStatus()`
    - Implement `getDaysRemaining()`
    - _Requirements: 6.1, 6.2, 6.3, 6.4_
  - [ ] 3.2 Implement getWarrantyList method
    - Query sale_items với warranty info
    - Join với products, sales, customers
    - Support filters: status, date range, search

    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_
  - [x] 3.3 Implement getExpiringWarranties method




    - Query sản phẩm sắp hết hạn trong X ngày
    - Sort by warranty_end_date ascending
    - _Requirements: 4.1, 4.2_


  - [ ]* 3.4 Write property test for status filter
    - **Property 8: Status filter correctness**
    - **Validates: Requirements 3.3**
  - [ ]* 3.5 Write property test for expiring filter
    - **Property 10: Expiring warranties filter**




    - **Validates: Requirements 4.1**
  - [ ]* 3.6 Write property test for expiring list sorting
    - **Property 11: Expiring list sorting**
    - **Validates: Requirements 4.2**



- [ ] 4. Checkpoint - Kiểm tra Service Layer
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 5. Create WarrantyReportService
  - [-] 5.1 Tạo WarrantyReportService class



    - Implement `getSummaryReport()` - tổng hợp active/expired/no_warranty
    - Implement `getReportByCustomer()` - group by customer
    - Implement `getReportByProduct()` - group by product


    - _Requirements: 5.1, 5.2, 5.3_
  - [ ] 5.2 Implement exportToExcel method
    - Sử dụng Maatwebsite Excel
    - Export tất cả warranty data với filters
    - _Requirements: 5.4_
  - [ ]* 5.3 Write property test for summary report accuracy
    - **Property 12: Summary report accuracy**
    - **Validates: Requirements 5.1**

- [ ] 6. Create WarrantyController
  - [ ] 6.1 Tạo WarrantyController với các methods
    - `index()` - danh sách bảo hành
    - `expiring()` - sản phẩm sắp hết hạn
    - `show()` - chi tiết bảo hành
    - `report()` - báo cáo bảo hành
    - `export()` - xuất Excel
    - _Requirements: 3.1, 4.1, 5.1, 5.4_
  - [ ] 6.2 Đăng ký routes trong web.php
    - GET /warranties
    - GET /warranties/expiring
    - GET /warranties/{saleItem}
    - GET /warranties/report
    - GET /warranties/export
    - _Requirements: 3.1, 4.1, 5.1_

- [ ] 7. Create Views
  - [ ] 7.1 Tạo warranties/index.blade.php
    - Hiển thị danh sách bảo hành với pagination
    - Form filter: status, date range, search
    - Color coding cho warranty status
    - _Requirements: 3.1, 3.2, 7.1, 7.2, 7.3, 7.4_
  - [ ] 7.2 Tạo warranties/expiring.blade.php
    - Hiển thị sản phẩm sắp hết hạn
    - Highlight warning (≤7 days) và danger (≤3 days)
    - Input để thay đổi số ngày filter
    - _Requirements: 4.1, 4.2, 4.3, 4.4_
  - [x] 7.3 Tạo warranties/show.blade.php


    - Chi tiết bảo hành của sản phẩm
    - Thông tin khách hàng, đơn hàng
    - _Requirements: 4.5_


  - [ ] 7.4 Tạo warranties/report.blade.php
    - Báo cáo tổng hợp với charts
    - Tabs: Summary, By Customer, By Product
    - Nút xuất Excel




    - _Requirements: 5.1, 5.2, 5.3, 5.4_


- [ ] 8. Update Sale Module
  - [ ] 8.1 Update SaleController store/update methods
    - Tự động set warranty_start_date = sale date

    - Lấy warranty_months từ input hoặc product default
    - _Requirements: 1.1, 1.3, 1.4_
  - [ ] 8.2 Update sale form views
    - Thêm input warranty_months cho mỗi item
    - Hiển thị default value từ product
    - _Requirements: 2.1, 2.2_
  - [ ] 8.3 Add validation for warranty_months
    - Validate 0-120 range




    - _Requirements: 2.4_
  - [x]* 8.4 Write property test for warranty months validation



    - **Property 6: Warranty months validation**
    - **Validates: Requirements 2.4**
  - [ ]* 8.5 Write property test for default warranty inheritance
    - **Property 2: Default warranty months inheritance**
    - **Validates: Requirements 1.3, 2.2**

- [ ] 9. Update Sidebar Navigation
  - [ ] 9.1 Thêm menu Theo dõi bảo hành vào sidebar
    - Thêm vào section Báo cáo hoặc tạo section mới
    - Link đến /warranties
    - _Requirements: 7.1_

- [ ] 10. Final Checkpoint
  - Ensure all tests pass, ask the user if questions arise.
