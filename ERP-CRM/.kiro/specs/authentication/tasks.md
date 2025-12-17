# Implementation Plan

## Authentication Feature

- [x] 1. Cài đặt Laravel Breeze



  - [x] 1.1 Cài đặt package Laravel Breeze

    - Chạy `composer require laravel/breeze --dev`
    - Chạy `php artisan breeze:install blade`
    - Chạy `npm install && npm run build`
    - _Requirements: 1.1, 1.2_
  - [ ]* 1.2 Write property test for authentication
    - **Property 1: Valid credentials grant access**
    - **Validates: Requirements 1.2**

- [x] 2. Cập nhật User Model


  - [x] 2.1 Đảm bảo User model có đầy đủ fillable và casts


    - Kiểm tra và cập nhật `app/Models/User.php`
    - Thêm các trường employee vào fillable nếu cần
    - _Requirements: 1.2_

- [x] 3. Customize giao diện Login



  - [x] 3.1 Tạo giao diện login đẹp phù hợp với ERP

    - Cập nhật `resources/views/auth/login.blade.php`
    - Sử dụng màu sắc và style của ERP (Tailwind CSS)
    - Thêm logo và tên hệ thống
    - Responsive cho mobile
    - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [x] 4. Cập nhật Layout với User Info


  - [x] 4.1 Cập nhật header hiển thị thông tin user


    - Sửa `resources/views/layouts/app.blade.php`
    - Hiển thị tên user và position
    - Thêm dropdown menu với logout
    - _Requirements: 2.3, 4.1, 4.2, 4.3_

- [x] 5. Bảo vệ Routes


  - [x] 5.1 Áp dụng middleware auth cho tất cả routes


    - Cập nhật `routes/web.php` để wrap routes trong middleware auth
    - Giữ route login/logout public
    - _Requirements: 3.1, 3.3_
  - [ ]* 5.2 Write property test for route protection
    - **Property 2: Unauthenticated access redirects to login**
    - **Validates: Requirements 3.1**
  - [ ]* 5.3 Write property test for authenticated access
    - **Property 3: Authenticated users can access protected routes**
    - **Validates: Requirements 3.3**

- [x] 6. Tạo Seeder cho User test


  - [x] 6.1 Tạo seeder để tạo user admin mặc định


    - Tạo user với email: admin@erp.com, password: password
    - Thêm thông tin employee cơ bản
    - _Requirements: 1.2_


- [x] 7. Checkpoint - Kiểm tra chức năng


  - Ensure all tests pass, ask the user if questions arise.
