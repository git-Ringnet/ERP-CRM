# Implementation Plan - Tháng 1: Nền tảng (Laravel)

## Phase 1: Setup Laravel Project & Database

- [x] 1. Khởi tạo Laravel project và cấu hình cơ bản




  - [x] 1.1 Tạo Laravel project mới hoặc cấu hình project hiện có


    - Cài đặt Laravel 10.x
    - Cấu hình .env với database connection
    - _Requirements: Setup_
  - [x] 1.2 Cài đặt các packages cần thiết

    - maatwebsite/excel cho import/export
    - Cài đặt Tailwind CSS với Laravel Vite
    - Cấu hình Font Awesome CDN, Chart.js CDN
    - _Requirements: Setup_

- [x] 2. Tạo Database Migrations








  - [x] 2.1 Tạo migration cho bảng customers

    - Các fields: code, name, email, phone, address, type, tax_code, website, contact_person, debt_limit, debt_days, note
    - Indexes cho code, type, name
    - _Requirements: 1.1, 1.2, 1.3_

  - [x] 2.2 Tạo migration cho bảng suppliers

    - Các fields: code, name, email, phone, address, tax_code, website, contact_person, payment_terms, product_type, note
    - Indexes cho code, name
    - _Requirements: 2.1, 2.2, 2.3_


  - [x] 2.3 Mở rộng bảng users để chứa thông tin employees (User = Employee)
    - Thêm các fields vào bảng users: employee_code, birth_date, phone, address, id_card, department, position, join_date, salary, bank_account, bank_name, status, note, avatar
    - Indexes cho employee_code (unique), department, status, position
    - Soft deletes support
    - _Requirements: 3.1, 3.2, 3.3_
  - [x] 2.4 Tạo migration cho bảng products





    - Các fields: code, name, category, unit, price, cost, stock, min_stock, max_stock, management_type, auto_generate_serial, serial_prefix, expiry_months, track_expiry, description, note
    - Indexes cho code, category, management_type, name
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_
  - [x] 2.5 Chạy migrations và tạo seeders với dữ liệu mẫu




    - _Requirements: Setup_

## Phase 2: Models & Validation

- [x] 3. Tạo Eloquent Models





  - [x] 3.1 Tạo Customer model


    - Định nghĩa fillable, casts
    - Scope cho search và filter by type
    - _Requirements: 1.1, 1.9, 1.10_
  - [ ]* 3.2 Write property test cho Customer model
    - **Property 1: CRUD Round-Trip Consistency for Customers**
    - **Validates: Requirements 1.3, 1.5, 1.6**

  - [x] 3.3 Tạo Supplier model

    - Định nghĩa fillable, casts
    - Scope cho search
    - _Requirements: 2.1, 2.9_
  - [ ]* 3.4 Write property test cho Supplier model
    - **Property 2: CRUD Round-Trip Consistency for Suppliers**
    - **Validates: Requirements 2.3, 2.5, 2.6**

  - [x] 3.5 Tạo Employee model

    - Định nghĩa fillable, casts
    - Scope cho search và filter by department, status
    - _Requirements: 3.1, 3.9, 3.10_
  - [ ]* 3.6 Write property test cho Employee model
    - **Property 3: CRUD Round-Trip Consistency for Employees**
    - **Validates: Requirements 3.3, 3.5, 3.6**
  - [x] 3.7 Tạo Product model


    - Định nghĩa fillable, casts
    - Scope cho search và filter by management_type
    - _Requirements: 4.1, 4.11, 4.12_
  - [ ]* 3.8 Write property test cho Product model
    - **Property 4: CRUD Round-Trip Consistency for Products**
    - **Validates: Requirements 4.5, 4.7, 4.8**

- [x] 4. Tạo Form Requests (Validation)





  - [x] 4.1 Tạo CustomerRequest với validation rules


    - Required: code, name, email, phone, type
    - Unique code constraint
    - Email format validation
    - _Requirements: 1.4_
  - [x] 4.2 Tạo SupplierRequest với validation rules


    - Required: code, name, email, phone
    - Unique code constraint
    - _Requirements: 2.4_
  - [x] 4.3 Tạo EmployeeRequest với validation rules


    - Required: code, name, email, phone, department, position, status
    - Unique code constraint
    - _Requirements: 3.4_
  - [x] 4.4 Tạo ProductRequest với validation rules


    - Required: code, name, unit, price, cost, management_type
    - Unique code constraint
    - _Requirements: 4.6_
  - [ ]* 4.5 Write property test cho validation
    - **Property 7: Validation Rejects Invalid Data**
    - **Property 8: Unique Code Constraint**
    - **Validates: Requirements 1.4, 2.4, 3.4, 4.6**

- [x] 5. Checkpoint - Đảm bảo migrations và models hoạt động




  - Ensure all tests pass, ask the user if questions arise.

## Phase 3: Controllers & Routes

- [x] 6. Tạo Controllers cho CRUD operations






  - [x] 6.1 Tạo CustomerController

    - index, create, store, show, edit, update, destroy
    - Search và filter functionality
    - _Requirements: 1.1, 1.2, 1.3, 1.5, 1.6, 1.7, 1.8, 1.9, 1.10_
  - [ ]* 6.2 Write property test cho Customer CRUD
    - **Property 5: Create Increases Count**
    - **Property 6: Delete Decreases Count**
    - **Validates: Requirements 1.3, 1.8**

  - [x] 6.3 Tạo SupplierController

    - index, create, store, show, edit, update, destroy
    - Search functionality
    - _Requirements: 2.1, 2.2, 2.3, 2.5, 2.6, 2.7, 2.8, 2.9_

  - [x] 6.4 Tạo EmployeeController

    - index, create, store, show, edit, update, destroy
    - Search và filter by department
    - _Requirements: 3.1, 3.2, 3.3, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10, 3.11_

  - [x] 6.5 Tạo ProductController

    - index, create, store, show, edit, update, destroy
    - Search và filter by management_type
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.7, 4.8, 4.9, 4.10, 4.11, 4.12_
  - [ ]* 6.6 Write property test cho Search và Filter
    - **Property 9: Search Returns Matching Results**
    - **Property 10: Filter Returns Correct Subset**
    - **Validates: Requirements 1.9, 1.10, 2.9, 3.9, 3.10, 4.11, 4.12**

- [x] 7. Cấu hình Routes




  - [x] 7.1 Định nghĩa resource routes trong web.php


    - Route::resource('customers', CustomerController::class)
    - Route::resource('suppliers', SupplierController::class)
    - Route::resource('employees', EmployeeController::class)
    - Route::resource('products', ProductController::class)
    - _Requirements: All CRUD_

## Phase 4: Blade Views & UI (Tailwind CSS)

- [x] 8. Tạo Layout chính với Tailwind CSS



  - [x] 8.1 Tạo layouts/app.blade.php

    - Sử dụng Tailwind CSS classes
    - Header với navigation và user info
    - Sidebar menu responsive
    - Main content area với @yield('content')
    - Include Font Awesome CDN, Chart.js CDN
    - _Requirements: UI_



- [x] 9. Tạo Views cho Customer module (Tailwind CSS)




  - [x] 9.1 Tạo customers/index.blade.php

    - Table với Tailwind classes
    - Search input và filter dropdown
    - Action buttons (view, edit, delete)
    - _Requirements: 1.1, 1.9, 1.10_


  - [x] 9.2 Tạo customers/create.blade.php và edit.blade.php




    - Form với Tailwind styling
    - Validation error display
    - @csrf, @method, old() helpers
    - _Requirements: 1.2, 1.4, 1.5_
  - [x] 9.3 Tạo customers/show.blade.php




    - Chi tiết khách hàng với Tailwind cards
    - _Requirements: 1.1_

- [x] 10. Tạo Views cho Supplier module (Tailwind CSS)




  - [x] 10.1 Tạo suppliers/index.blade.php


    - Table với search functionality
    - _Requirements: 2.1, 2.9_
  - [x] 10.2 Tạo suppliers/create.blade.php và edit.blade.php


    - Form với validation
    - _Requirements: 2.2, 2.4, 2.5_

  - [x] 10.3 Tạo suppliers/show.blade.php

    - _Requirements: 2.1_

- [x] 11. Tạo Views cho Employee module (Tailwind CSS)




  - [x] 11.1 Tạo employees/index.blade.php


    - Table với filter by department và status
    - Status badges với Tailwind colors
    - _Requirements: 3.1, 3.9, 3.10_
  - [x] 11.2 Tạo employees/create.blade.php và edit.blade.php


    - Form với validation
    - _Requirements: 3.2, 3.4, 3.5_
  - [x] 11.3 Tạo employees/show.blade.php


    - _Requirements: 3.1_

- [x] 12. Tạo Views cho Product module (Tailwind CSS)





  - [x] 12.1 Tạo products/index.blade.php


    - Table với filter by management_type
    - Badges cho Serial/Lot/Normal
    - _Requirements: 4.1, 4.11, 4.12_
  - [x] 12.2 Tạo products/create.blade.php và edit.blade.php


    - Form với dynamic fields cho Serial/Lot
    - JavaScript toggle cho management_type options
    - _Requirements: 4.2, 4.3, 4.4, 4.6, 4.7_
  - [x] 12.3 Tạo products/show.blade.php


    - _Requirements: 4.1_

- [x] 13. Checkpoint - Đảm bảo CRUD hoạt động đầy đủ





  - Ensure all tests pass, ask the user if questions arise.

## Phase 5: Services & Advanced Features

- [x] 14. Tạo SerialService




  - [x] 14.1 Implement generate() method


    - Format: [prefix]-[year]-[6-digit-random]
    - Đảm bảo unique
    - _Requirements: 4.13_
  - [ ]* 14.2 Write property test cho Serial generation
    - **Property 11: Serial Number Uniqueness**
    - **Validates: Requirements 4.13**

- [x] 15. Tạo ExportService




  - [x] 15.1 Implement export methods cho từng entity


    - Sử dụng maatwebsite/excel
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_
  - [x] 15.2 Thêm export routes và controller methods


    - _Requirements: 7.6, 7.7_
  - [ ]* 15.3 Write property test cho Export
    - **Property 14: Export Data Completeness**
    - **Validates: Requirements 7.1, 7.6**

- [x] 16. Tạo ImportService




  - [x] 16.1 Implement parseExcel() method


    - _Requirements: 5.3_
  - [x] 16.2 Implement validateData() method


    - Validate required fields
    - Check duplicates
    - _Requirements: 5.4, 5.6, 5.7_
  - [x] 16.3 Implement importCustomers() và importProducts()


    - _Requirements: 5.5, 5.8_
  - [ ]* 16.4 Write property test cho Import
    - **Property 12: Import Validation Consistency**
    - **Validates: Requirements 5.5, 5.6, 5.7, 5.8**

- [x] 17. Tạo ImportController và Views
  - [x] 17.1 Tạo ImportController
    - index, template, preview, store
    - _Requirements: 5.1, 5.2, 5.3, 5.5_
  - [x] 17.2 Tạo import/index.blade.php



    - Upload form
    - Preview table
    - Import summary
    - _Requirements: 5.1, 5.3, 5.8_

## Phase 6: Dashboard

- [x] 18. Tạo Dashboard








  - [x] 18.1 Tạo DashboardController


    - Tính toán summary counts
    - Lấy recent activities
    - _Requirements: 6.1, 6.5_
  - [x] 18.2 Tạo dashboard/index.blade.php


    - Summary cards (customers, suppliers, employees, products)
    - Charts (customer types, departments, product types)
    - Recent activities list
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.7_
  - [ ]* 18.3 Write property test cho Dashboard
    - **Property 13: Dashboard Count Consistency**
    - **Validates: Requirements 6.1, 6.6**

- [x] 19. Cập nhật Navigation




  - [x] 19.1 Thêm Dashboard vào menu

    - Set Dashboard làm trang mặc định
    - _Requirements: 6.7_

## Phase 7: Final Testing & Polish

- [x] 20. Final Checkpoint - Đảm bảo tất cả tests pass





  - Ensure all tests pass, ask the user if questions arise.

- [x] 21. UI Polish





  - [x] 21.1 Responsive design với Tailwind breakpoints


    - Mobile-first approach
    - Sidebar collapse trên mobile
    - _Requirements: UI_
  - [x] 21.2 Thêm JavaScript interactions


    - Confirm dialogs cho delete
    - Loading states
    - _Requirements: UI_
