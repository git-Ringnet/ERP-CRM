# Implementation Plan - Liên kết Xuất Kho với Dự Án

## Task List

- [x] 1. Database Migration


  - Tạo migration thêm cột project_id vào inventory_transactions
  - Thêm foreign key constraint với projects table
  - Thêm index trên project_id
  - _Requirements: 1.2_

- [x] 2. Update Models


  - [ ] 2.1 Cập nhật InventoryTransaction model
    - Thêm project_id vào fillable
    - Thêm relationship project()

    - _Requirements: 1.2, 1.4_
  
  - [x] 2.2 Cập nhật Project model

    - Thêm relationship exports()
    - Thêm method getTotalExportValue()
    - _Requirements: 2.1, 4.1_

- [ ] 3. Update ExportController
  - [x] 3.1 Cập nhật method create()


    - Load danh sách projects active
    - Truyền projects vào view
    - _Requirements: 1.1_
  
  - [x] 3.2 Cập nhật method store()


    - Validate project_id (nullable, exists:projects,id)
    - Lưu project_id vào transaction
    - _Requirements: 1.2, 1.3_
  
  - [x] 3.3 Cập nhật method index()


    - Thêm filter theo project_id
    - Load relationship project với eager loading
    - _Requirements: 3.1, 3.2_
  
  - [x] 3.4 Cập nhật method show()


    - Load relationship project
    - _Requirements: 1.4_

- [ ] 4. Update Views - Export Module
  - [x] 4.1 Cập nhật exports/create.blade.php



    - Thêm dropdown chọn dự án (optional)
    - Thêm option "Không chọn dự án"
    - _Requirements: 1.1, 1.3_
  
  - [x] 4.2 Cập nhật exports/index.blade.php
    - Thêm filter dropdown theo dự án
    - Hiển thị tên dự án trong bảng
    - Thêm cột "Dự án" vào table
    - _Requirements: 3.1, 3.2_
  
  - [x] 4.3 Cập nhật exports/show.blade.php
    - Hiển thị thông tin dự án nếu có
    - Thêm link đến trang chi tiết dự án
    - _Requirements: 1.4_

- [x] 5. Update ProjectController
  - [x] 5.1 Cập nhật method show()
    - Tính tổng giá trị vật tư đã xuất
    - Load danh sách phiếu xuất liên quan
    - _Requirements: 2.1, 2.2, 4.1_

- [x] 6. Update Views - Project Module
  - [x] 6.1 Cập nhật projects/show.blade.php
    - Thêm section "Vật tư đã xuất"
    - Hiển thị tổng giá trị
    - Hiển thị danh sách phiếu xuất
    - Thêm link xem chi tiết từng phiếu
    - _Requirements: 2.1, 2.2, 2.3, 4.1, 4.2_

- [x] 7. Testing & Validation
  - [x] 7.1 Test tạo phiếu xuất với dự án
    - Test chọn dự án
    - Test không chọn dự án
    - Test với dự án không tồn tại
  
  - [x] 7.2 Test filter theo dự án
    - Test filter với dự án có phiếu xuất
    - Test filter với dự án không có phiếu xuất
    - Test xóa filter
  
  - [x] 7.3 Test hiển thị trong project
    - Test tính tổng giá trị
    - Test danh sách phiếu xuất
    - Test với project không có phiếu xuất

- [x] 8. Checkpoint - Đảm bảo tất cả hoạt động tốt
  - Ensure all tests pass, ask the user if questions arise.
