# Design Document - Authentication

## Overview

Tính năng Authentication sử dụng Laravel Breeze để cung cấp chức năng đăng nhập/đăng xuất cho hệ thống ERP. Bảng `users` hiện có (đã chứa thông tin nhân viên) được sử dụng làm bảng xác thực. Giao diện login được customize để phù hợp với thiết kế ERP hiện tại.

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Browser                                 │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   Laravel Application                        │
│  ┌─────────────────────────────────────────────────────┐   │
│  │              Auth Middleware                          │   │
│  │  - Check authentication                               │   │
│  │  - Redirect to login if not authenticated             │   │
│  └─────────────────────────────────────────────────────┘   │
│                              │                               │
│              ┌───────────────┴───────────────┐              │
│              ▼                               ▼              │
│  ┌─────────────────────┐       ┌─────────────────────┐     │
│  │  AuthController     │       │  Other Controllers   │     │
│  │  - login()          │       │  (Protected)         │     │
│  │  - logout()         │       │                      │     │
│  └─────────────────────┘       └─────────────────────┘     │
│              │                               │              │
│              ▼                               ▼              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                    User Model                         │   │
│  │  - email, password                                    │   │
│  │  - employee_code, name, position, department          │   │
│  └─────────────────────────────────────────────────────┘   │
│                              │                               │
│                              ▼                               │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                   users Table                         │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. Laravel Breeze Installation
- Cài đặt Laravel Breeze với Blade stack
- Sử dụng authentication scaffolding có sẵn
- Customize views để phù hợp với thiết kế ERP

### 2. Controllers

#### AuthenticatedSessionController
- `create()` - Hiển thị form login
- `store()` - Xử lý đăng nhập
- `destroy()` - Xử lý đăng xuất

### 3. Middleware

#### auth Middleware
- Áp dụng cho tất cả routes (trừ login)
- Redirect về login nếu chưa đăng nhập

### 4. Views

#### resources/views/auth/login.blade.php
- Form đăng nhập với email, password
- Checkbox "Remember Me"
- Nút đăng nhập
- Thiết kế phù hợp với ERP

#### resources/views/layouts/app.blade.php (Update)
- Hiển thị tên user trong header
- Dropdown menu với logout

## Data Models

### User Model (Existing)
```php
// Các trường liên quan đến authentication
- id
- name
- email (unique)
- password (hashed)
- remember_token
- employee_code
- position
- department
- status (active, leave, resigned)
```

### Authentication Flow
1. User truy cập hệ thống → Middleware kiểm tra auth
2. Nếu chưa đăng nhập → Redirect đến /login
3. User nhập email/password → Submit form
4. Laravel validate credentials → Tạo session
5. Redirect về dashboard

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Valid credentials grant access
*For any* user with valid email and password in the database, submitting those credentials should result in successful authentication and redirect to dashboard.
**Validates: Requirements 1.2**

### Property 2: Unauthenticated access redirects to login
*For any* protected route in the system, accessing it without authentication should result in a redirect to the login page.
**Validates: Requirements 3.1**

### Property 3: Authenticated users can access protected routes
*For any* authenticated user and any protected route, the user should be able to access that route without being redirected to login.
**Validates: Requirements 3.3**

## Error Handling

| Error | Handling |
|-------|----------|
| Invalid credentials | Display error message, stay on login page |
| Account inactive (status != active) | Display "Account is inactive" message |
| Session expired | Redirect to login with message |
| CSRF token mismatch | Redirect to login |

## Testing Strategy

### Unit Tests
- Test User model authentication methods
- Test password hashing/verification

### Feature Tests (Laravel HTTP Tests)
- Test login with valid credentials
- Test login with invalid credentials
- Test logout functionality
- Test protected route access without auth
- Test protected route access with auth

### Property-Based Tests
- Use Pest PHP with faker to generate random valid users
- Test that all protected routes redirect when unauthenticated
- Test that authenticated users can access all routes

### Testing Framework
- PHPUnit (Laravel default)
- Pest PHP for property-based testing (optional)
