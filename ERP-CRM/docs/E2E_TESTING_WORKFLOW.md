# Quy trình kiểm thử E2E bằng Playwright (bắt buộc sau mỗi lần chỉnh sửa)

Mỗi khi thực hiện chỉnh sửa tính năng hoặc sửa lỗi liên quan đến checklist nghiệm thu, cần thực hiện đầy đủ 5 bước dưới đây:

## 1. Xác định mã Test Case (TC) bị ảnh hưởng
- Đối chiếu với file checklist nghiệm thu để lấy đúng `Mã TC` (ví dụ: `ADM-PUR-004`, `ADM-OUT-002`, `SYS-PERM-003`).

## 2. Chuẩn bị fixture dữ liệu cô lập
- Bổ sung/tái sử dụng action trong `tests/e2e/scripts/e2e_fixture.php` để tạo dữ liệu mẫu có tiền tố `E2E-` và user test đuôi `@test.local`.
- Luôn chạy `cleanup` ở `beforeAll` và `afterAll` để không làm bẩn dữ liệu thật.

## 3. Viết / cập nhật kịch bản Playwright
- Tạo file `tests/e2e/<MA_TC>.spec.js`.
- Dùng `loginAs(page, email)` để đăng nhập qua giao diện thật.
- Dùng `captureStep(page, '<MA_TC>', '<ten_buoc>')` để chụp ảnh toàn trang tại từng thao tác quan trọng (ảnh lưu tại `tests/e2e/screenshots/<MA_TC>/`).

## 4. Chạy kiểm thử và kiểm tra ảnh chụp
- Đảm bảo server Laravel đang chạy tại `http://127.0.0.1:8000` (hoặc đặt `E2E_BASE_URL`).
- Chạy toàn bộ suite hoặc từng TC:
  ```bash
  npm run test:e2e
  # hoặc chạy riêng 1 TC:
  npx playwright test tests/e2e/ADM-PUR-004.spec.js
  # hoặc mở trình duyệt trực quan:
  npm run test:e2e:headed
  ```
- Mở ảnh trong `tests/e2e/screenshots/<MA_TC>/` (hoặc `playwright-report/index.html`) để đối chiếu trực quan từng bước thao tác.

## 5. Nghiệm thu
- Chỉ đánh dấu hoàn tất khi:
  - Tất cả test Playwright pass (`0 failed`).
  - Ảnh chụp các bước khớp với "Kết quả mong đợi" trong checklist.
