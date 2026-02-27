# Requirements Document

## Introduction

Hệ thống phân quyền truy cập dựa trên vai trò (Role-Based Access Control - RBAC) cho phép quản lý quyền truy cập của nhân viên vào các module và chức năng trong hệ thống Mini ERP Laravel. Hệ thống cho phép tạo vai trò, gán quyền cho vai trò, gán vai trò cho nhân viên, và kiểm soát truy cập dựa trên quyền được cấp.

## Glossary

- **RBAC_System**: Hệ thống phân quyền truy cập dựa trên vai trò
- **Role**: Vai trò đại diện cho một vị trí công việc (VD: Admin, Quản lý kho, Nhân viên bán hàng)
- **Permission**: Quyền thực hiện một hành động cụ thể trên một module (VD: view_customers, create_sales)
- **User**: Nhân viên sử dụng hệ thống
- **Module**: Một phân hệ chức năng trong ERP (VD: customers, warehouses, sales)
- **Action**: Hành động có thể thực hiện (view, create, edit, delete, approve, export)
- **Permission_Matrix**: Giao diện hiển thị quyền theo dạng bảng vai trò x quyền
- **Access_Control_Middleware**: Middleware Laravel kiểm tra quyền trước khi cho phép truy cập
- **Policy**: Laravel Policy class định nghĩa logic phân quyền
- **Audit_Log**: Nhật ký ghi lại các thay đổi về vai trò và quyền
- **Permission_Cache**: Bộ nhớ đệm lưu trữ quyền của người dùng
- **Direct_Permission**: Quyền được gán trực tiếp cho User, không thông qua Role

## Requirements

### Requirement 1: Quản lý Vai trò

**User Story:** Là quản trị viên hệ thống, tôi muốn tạo và quản lý các vai trò, để có thể định nghĩa các vị trí công việc trong tổ chức.

#### Acceptance Criteria

1. THE RBAC_System SHALL provide a Role management interface with create, read, update, and delete operations
2. WHEN creating a Role, THE RBAC_System SHALL require a unique role name, description, and status field
3. WHEN a Role name already exists, THE RBAC_System SHALL reject the creation and return a uniqueness violation error
4. THE RBAC_System SHALL store Role status as either active or inactive
5. WHEN updating a Role, THE RBAC_System SHALL preserve the Role identifier and update only the specified fields
6. WHEN deleting a Role, THE RBAC_System SHALL check for User assignments and prevent deletion if Users are assigned
7. THE RBAC_System SHALL support Role names in Vietnamese and English characters with a maximum length of 100 characters
8. WHEN a Role is set to inactive, THE RBAC_System SHALL maintain existing assignments but exclude the Role from new assignments

#### Correctness Properties

1. **Invariant**: FOR ALL Roles in the system, Role.name SHALL be unique
2. **Invariant**: FOR ALL Roles, Role.status SHALL be either "active" or "inactive"
3. **State Consistency**: WHEN a Role is deleted, THE RBAC_System SHALL remove all Permission assignments for that Role
4. **Referential Integrity**: THE RBAC_System SHALL NOT delete a Role that has active User assignments

### Requirement 2: Quản lý Quyền

**User Story:** Là quản trị viên hệ thống, tôi muốn định nghĩa các quyền chi tiết, để kiểm soát truy cập vào từng chức năng cụ thể.

#### Acceptance Criteria

1. THE RBAC_System SHALL support Permissions for the following Modules: customers, suppliers, employees, products, warehouses, inventory, imports, exports, transfers, damaged_goods, sales, quotations, purchase_orders, reports, settings
2. THE RBAC_System SHALL support the following Actions: view, create, edit, delete, approve, export
3. THE RBAC_System SHALL generate Permission identifiers in the format "action_module" (VD: view_customers, create_sales)
4. THE RBAC_System SHALL support special Permissions: approve_imports, approve_exports, approve_quotations, approve_purchase_orders, view_all_sales, view_own_sales
5. WHEN the system initializes, THE RBAC_System SHALL seed all standard Permissions into the database
6. THE RBAC_System SHALL store each Permission with a unique identifier, name, description, module, and action
7. THE RBAC_System SHALL group Permissions by Module for display purposes

#### Correctness Properties

1. **Invariant**: FOR ALL Permissions, Permission.identifier SHALL be unique
2. **Format Validation**: FOR ALL standard Permissions, Permission.identifier SHALL match the pattern "^(view|create|edit|delete|approve|export)_[a-z_]+$"
3. **Completeness**: FOR EACH Module in the system, THE RBAC_System SHALL have at minimum a "view" Permission

### Requirement 3: Gán Quyền cho Vai trò

**User Story:** Là quản trị viên hệ thống, tôi muốn gán nhiều quyền cho một vai trò, để định nghĩa phạm vi truy cập của vai trò đó.

#### Acceptance Criteria

1. THE RBAC_System SHALL allow assigning multiple Permissions to a single Role
2. THE RBAC_System SHALL allow a single Permission to be assigned to multiple Roles
3. WHEN assigning a Permission to a Role, THE RBAC_System SHALL verify both the Role and Permission exist
4. WHEN a Permission is already assigned to a Role, THE RBAC_System SHALL ignore duplicate assignment requests
5. THE RBAC_System SHALL provide a Permission_Matrix interface displaying Roles as rows and Permissions as columns
6. WHEN a User interacts with the Permission_Matrix, THE RBAC_System SHALL allow toggling Permission assignments via checkboxes
7. WHEN removing a Permission from a Role, THE RBAC_System SHALL update all affected User permission caches within 5 seconds
8. THE RBAC_System SHALL record the timestamp and administrator User for each Permission assignment change

#### Correctness Properties

1. **Invariant**: FOR ALL Role-Permission assignments, both the Role and Permission SHALL exist in the database
2. **Idempotence**: Assigning the same Permission to a Role multiple times SHALL produce the same result as assigning it once
3. **Set Property**: FOR ANY Role, the set of assigned Permissions SHALL contain no duplicates

### Requirement 4: Gán Vai trò cho Nhân viên

**User Story:** Là quản trị viên hệ thống, tôi muốn gán vai trò cho nhân viên, để nhân viên kế thừa các quyền từ vai trò.

#### Acceptance Criteria

1. THE RBAC_System SHALL allow assigning multiple Roles to a single User
2. WHEN assigning a Role to a User, THE RBAC_System SHALL verify both the User and Role exist
3. WHEN a Role is assigned to a User, THE RBAC_System SHALL grant the User all Permissions associated with that Role
4. WHEN a User has multiple Roles, THE RBAC_System SHALL grant the union of all Permissions from all assigned Roles
5. THE RBAC_System SHALL provide an interface to view all Roles assigned to a specific User
6. WHEN removing a Role from a User, THE RBAC_System SHALL revoke only the Permissions exclusive to that Role
7. WHEN a Role is set to inactive, THE RBAC_System SHALL exclude its Permissions from User permission calculations
8. THE RBAC_System SHALL update the User's Permission_Cache within 5 seconds after Role assignment changes

#### Correctness Properties

1. **Permission Inheritance**: FOR ANY User with assigned Roles, User.effective_permissions SHALL equal the union of all Permissions from all active assigned Roles plus any Direct_Permissions
2. **Monotonicity**: Adding a Role to a User SHALL NOT decrease the User's effective Permissions count
3. **Cache Consistency**: FOR ANY User, the Permission_Cache SHALL match the computed effective Permissions within 5 seconds of any change

### Requirement 5: Gán Quyền Trực tiếp cho Nhân viên

**User Story:** Là quản trị viên hệ thống, tôi muốn gán quyền trực tiếp cho nhân viên, để xử lý các trường hợp đặc biệt không phù hợp với vai trò chuẩn.

#### Acceptance Criteria

1. THE RBAC_System SHALL allow assigning Direct_Permissions to a User independent of Roles
2. WHEN a Direct_Permission is assigned to a User, THE RBAC_System SHALL add it to the User's effective Permissions
3. WHEN a User has both Role-based and Direct_Permissions, THE RBAC_System SHALL grant the union of both
4. THE RBAC_System SHALL display Direct_Permissions separately from Role-based Permissions in the User interface
5. WHEN removing a Direct_Permission from a User, THE RBAC_System SHALL NOT affect the User's Role assignments
6. THE RBAC_System SHALL update the Permission_Cache within 5 seconds after Direct_Permission changes

#### Correctness Properties

1. **Union Property**: FOR ANY User, effective_permissions = (union of Role Permissions) ∪ (Direct_Permissions)
2. **Independence**: Removing a Role from a User SHALL NOT affect the User's Direct_Permissions
3. **Independence**: Removing a Direct_Permission from a User SHALL NOT affect the User's Role-based Permissions

### Requirement 6: Kiểm soát Truy cập Route và Controller

**User Story:** Là người dùng hệ thống, tôi muốn hệ thống chặn truy cập vào các chức năng tôi không có quyền, để bảo vệ dữ liệu và tuân thủ phân quyền.

#### Acceptance Criteria

1. WHEN a User attempts to access a protected route, THE Access_Control_Middleware SHALL verify the User has the required Permission
2. WHEN a User lacks the required Permission, THE Access_Control_Middleware SHALL return HTTP status code 403 with an error message
3. THE RBAC_System SHALL provide Laravel Policy classes for each Module to encapsulate authorization logic
4. WHEN a Controller action is invoked, THE Policy SHALL be evaluated before executing the action logic
5. THE RBAC_System SHALL retrieve Permissions from Permission_Cache to minimize database queries
6. WHEN Permission_Cache is empty for a User, THE RBAC_System SHALL compute and cache the effective Permissions within 200 milliseconds
7. THE RBAC_System SHALL support Policy methods for each Action: viewAny, view, create, update, delete, approve, export

#### Correctness Properties

1. **Authorization Correctness**: FOR ANY protected route, access SHALL be granted IF AND ONLY IF the User has the required Permission
2. **Performance**: Permission verification SHALL complete within 50 milliseconds for cached Permissions
3. **Fail-Safe**: WHEN Permission_Cache is unavailable, THE RBAC_System SHALL deny access rather than allow unrestricted access

### Requirement 7: Kiểm soát Giao diện Người dùng

**User Story:** Là người dùng hệ thống, tôi muốn chỉ nhìn thấy các menu và nút bấm tôi có quyền sử dụng, để giao diện rõ ràng và tránh nhầm lẫn.

#### Acceptance Criteria

1. THE RBAC_System SHALL provide Blade directives to conditionally render UI elements based on Permissions
2. WHEN rendering a menu item, THE RBAC_System SHALL hide the menu item if the User lacks the required Permission
3. WHEN rendering action buttons (create, edit, delete, approve), THE RBAC_System SHALL hide buttons for Actions the User cannot perform
4. THE RBAC_System SHALL support the Blade directive syntax: @can('permission_name') ... @endcan
5. THE RBAC_System SHALL support checking multiple Permissions with @canany(['permission1', 'permission2']) ... @endcanany
6. WHEN a User views a list page, THE RBAC_System SHALL display action buttons only for permitted Actions
7. THE RBAC_System SHALL evaluate Blade directives using the same Permission_Cache as middleware

#### Correctness Properties

1. **UI-Authorization Consistency**: FOR ANY UI element, visibility SHALL match the authorization result for the corresponding Action
2. **No Hidden Functionality**: IF a UI element is visible to a User, THEN the User SHALL have Permission to perform the associated Action

### Requirement 8: Lọc Dữ liệu theo Quyền

**User Story:** Là nhân viên bán hàng, tôi muốn chỉ xem được đơn hàng của mình, để tập trung vào công việc được giao và bảo mật thông tin khách hàng.

#### Acceptance Criteria

1. WHEN a User has the "view_own_sales" Permission but not "view_all_sales", THE RBAC_System SHALL filter sales records to show only records where the User is the assigned salesperson
2. WHEN a User has the "view_all_sales" Permission, THE RBAC_System SHALL display all sales records without filtering
3. THE RBAC_System SHALL apply data filtering at the query level before retrieving records from the database
4. WHEN a User attempts to view a specific record by ID, THE RBAC_System SHALL verify the User has Permission to view that specific record
5. WHEN a User lacks Permission to view a specific record, THE RBAC_System SHALL return HTTP status code 404 to prevent information disclosure
6. THE RBAC_System SHALL support scope-based filtering for the following Modules: sales, quotations, purchase_orders
7. WHERE a Module supports ownership filtering, THE RBAC_System SHALL provide both "view_own_MODULE" and "view_all_MODULE" Permissions

#### Correctness Properties

1. **Data Isolation**: FOR ANY User with "view_own_X" Permission, the query result SHALL contain only records where User is the owner
2. **No Leakage**: WHEN a User requests a record by ID without Permission, THE RBAC_System SHALL return 404, not 403, to prevent existence disclosure
3. **Query Efficiency**: Data filtering SHALL be applied in the SQL WHERE clause, not in application code after retrieval

### Requirement 9: Audit và Logging

**User Story:** Là quản trị viên hệ thống, tôi muốn xem lịch sử thay đổi về vai trò và quyền, để theo dõi và kiểm toán các thay đổi phân quyền.

#### Acceptance Criteria

1. WHEN a Role is created, updated, or deleted, THE RBAC_System SHALL record an Audit_Log entry with timestamp, administrator User, action type, and Role details
2. WHEN Permissions are assigned to or removed from a Role, THE RBAC_System SHALL record an Audit_Log entry with the Permission list changes
3. WHEN a Role is assigned to or removed from a User, THE RBAC_System SHALL record an Audit_Log entry with User identifier, Role identifier, and action type
4. WHEN a Direct_Permission is assigned to or removed from a User, THE RBAC_System SHALL record an Audit_Log entry
5. THE RBAC_System SHALL store Audit_Log entries with the following fields: id, timestamp, actor_user_id, action_type, entity_type, entity_id, old_value, new_value, ip_address
6. THE RBAC_System SHALL provide an Audit_Log viewing interface filterable by date range, User, action type, and entity type
7. THE RBAC_System SHALL retain Audit_Log entries for a minimum of 365 days
8. WHEN an unauthorized access attempt occurs, THE RBAC_System SHALL log the attempt with User identifier, requested resource, and timestamp

#### Correctness Properties

1. **Completeness**: FOR EVERY Role, Permission, or assignment change, an Audit_Log entry SHALL be created
2. **Immutability**: Audit_Log entries SHALL NOT be modifiable or deletable through the application interface
3. **Temporal Ordering**: Audit_Log entries SHALL be retrievable in chronological order by timestamp

### Requirement 10: Khởi tạo Vai trò Mẫu

**User Story:** Là quản trị viên triển khai hệ thống, tôi muốn hệ thống tự động tạo các vai trò mẫu, để nhanh chóng bắt đầu sử dụng mà không cần cấu hình thủ công.

#### Acceptance Criteria

1. WHEN the RBAC_System is initialized, THE RBAC_System SHALL create the following predefined Roles: Super_Admin, Warehouse_Manager, Warehouse_Staff, Sales_Manager, Sales_Staff, Purchase_Manager, Purchase_Staff, Accountant, Director
2. THE RBAC_System SHALL assign the following Permissions to Super_Admin: all available Permissions
3. THE RBAC_System SHALL assign the following Permissions to Warehouse_Manager: all warehouse, inventory, imports, exports, transfers, damaged_goods Permissions including approve_imports and approve_exports
4. THE RBAC_System SHALL assign the following Permissions to Warehouse_Staff: view, create, edit for imports, exports, transfers, and view for inventory
5. THE RBAC_System SHALL assign the following Permissions to Sales_Manager: all customers, sales, quotations Permissions including approve_quotations and view_all_sales
6. THE RBAC_System SHALL assign the following Permissions to Sales_Staff: view, create, edit for customers, sales, quotations, and view_own_sales
7. THE RBAC_System SHALL assign the following Permissions to Purchase_Manager: all suppliers, purchase_orders Permissions including approve_purchase_orders
8. THE RBAC_System SHALL assign the following Permissions to Purchase_Staff: view, create, edit for suppliers and purchase_orders
9. THE RBAC_System SHALL assign the following Permissions to Accountant: view and export for all Modules, with emphasis on reports
10. THE RBAC_System SHALL assign the following Permissions to Director: view and approve for all Modules, and all report Permissions
11. WHEN predefined Roles already exist, THE RBAC_System SHALL skip creation and preserve existing configurations

#### Correctness Properties

1. **Idempotence**: Running the initialization process multiple times SHALL produce the same Role and Permission configuration as running it once
2. **Completeness**: After initialization, all predefined Roles SHALL exist with their specified Permission sets
3. **Non-Destructive**: Initialization SHALL NOT modify or delete existing custom Roles or Permission assignments

### Requirement 11: Quản lý Cache Quyền

**User Story:** Là người dùng hệ thống, tôi muốn hệ thống phản hồi nhanh khi kiểm tra quyền, để trải nghiệm sử dụng mượt mà.

#### Acceptance Criteria

1. THE RBAC_System SHALL cache each User's effective Permissions in Permission_Cache using the User identifier as the cache key
2. WHEN a User's effective Permissions are computed, THE RBAC_System SHALL store the result in Permission_Cache with a time-to-live of 3600 seconds
3. WHEN a User's Role assignments change, THE RBAC_System SHALL invalidate the User's Permission_Cache entry
4. WHEN a Role's Permission assignments change, THE RBAC_System SHALL invalidate Permission_Cache entries for all Users assigned to that Role
5. WHEN a User's Direct_Permissions change, THE RBAC_System SHALL invalidate the User's Permission_Cache entry
6. THE RBAC_System SHALL use Laravel Cache facade with Redis as the cache driver
7. WHEN Permission_Cache is unavailable, THE RBAC_System SHALL compute Permissions from the database and continue operation
8. THE RBAC_System SHALL provide an administrative command to clear all Permission_Cache entries

#### Correctness Properties

1. **Cache Consistency**: FOR ANY User, Permission_Cache SHALL match the computed effective Permissions or be empty
2. **Invalidation Correctness**: WHEN any change affects a User's Permissions, the User's Permission_Cache SHALL be invalidated within 5 seconds
3. **Graceful Degradation**: WHEN cache is unavailable, authorization SHALL still function correctly using database queries

### Requirement 12: API Kiểm tra Quyền

**User Story:** Là developer, tôi muốn có API đơn giản để kiểm tra quyền trong code, để dễ dàng tích hợp phân quyền vào các chức năng mới.

#### Acceptance Criteria

1. THE RBAC_System SHALL provide a method `User->can($permission)` that returns true if the User has the specified Permission, false otherwise
2. THE RBAC_System SHALL provide a method `User->hasRole($roleName)` that returns true if the User is assigned the specified Role, false otherwise
3. THE RBAC_System SHALL provide a method `User->hasAnyRole($roleNames)` that returns true if the User is assigned any of the specified Roles
4. THE RBAC_System SHALL provide a method `User->hasAllRoles($roleNames)` that returns true if the User is assigned all of the specified Roles
5. THE RBAC_System SHALL provide a method `User->getAllPermissions()` that returns the complete set of effective Permissions
6. THE RBAC_System SHALL provide a Gate facade method `Gate::allows($permission)` for checking Permissions in controllers
7. THE RBAC_System SHALL provide a Gate facade method `Gate::denies($permission)` for checking Permission denial
8. WHEN checking Permissions via API methods, THE RBAC_System SHALL use Permission_Cache when available

#### Correctness Properties

1. **API Consistency**: FOR ANY Permission, `User->can($permission)` SHALL return the same result as `Gate::allows($permission)` for the same User
2. **Set Semantics**: `User->getAllPermissions()` SHALL return a set with no duplicate Permissions
3. **Boolean Complement**: FOR ANY Permission, `Gate::allows($permission)` SHALL return the logical NOT of `Gate::denies($permission)`

