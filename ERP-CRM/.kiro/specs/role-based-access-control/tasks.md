# Implementation Plan: Role-Based Access Control

## Overview

This implementation plan breaks down the RBAC feature into incremental, testable steps. The approach follows a bottom-up strategy: database foundation → data models → business logic → authorization layer → UI integration. Each task builds on previous work, ensuring no orphaned code.

The implementation uses PHP/Laravel with the following tech stack:
- Laravel 10.x framework
- MySQL database
- Redis cache
- Pest PHP for testing (unit + property-based tests)
- Blade templating engine

## Implementation Strategy

1. Start with database schema and seeders (foundation)
2. Build models with traits and relationships (data layer)
3. Implement repositories for data access (abstraction)
4. Create services for business logic (domain layer)
5. Add middleware and policies for authorization (application layer)
6. Implement controllers and routes (API layer)
7. Build UI views with permission-aware directives (presentation layer)
8. Integrate with existing controllers (system-wide authorization)
9. Add comprehensive testing (quality assurance)

## Tasks

- [x] 1. Database Foundation
  - Create migrations for all 7 tables (roles, permissions, role_permissions, user_roles, user_permissions, permission_audit_logs, extend users table if needed)
  - Create seeders for permissions (all standard permissions based on modules and actions)
  - Create seeders for predefined roles (Super Admin, Warehouse Manager, Sales Manager, etc.) with permission assignments
  - Run migrations and seeders to initialize database
  - _Requirements: 1.1-1.8, 2.1-2.7, 10.1-10.11_


- [ ] 2. Core Models and Relationships
  - [x] 2.1 Create Role model with fillable fields, relationships, and scopes
    - Define fillable: name, slug, description, status
    - Add relationships: permissions() (belongsToMany), users() (belongsToMany)
    - Add scopeActive() for filtering active roles
    - Implement methods: assignPermission(), removePermission(), syncPermissions()
    - _Requirements: 1.1-1.8_

  - [ ]* 2.2 Write property test for Role model
    - **Property 1: Role Name Uniqueness** - Verify no two roles can have the same slug
    - **Property 2: Role Status Invariant** - Verify status is always 'active' or 'inactive'
    - **Property 3: Role Update ID Preservation** - Verify role ID never changes on update
    - **Validates: Requirements 1.2, 1.3, 1.4, 1.5**

  - [x] 2.3 Create Permission model with fillable fields, relationships, and scopes
    - Define fillable: name, slug, description, module, action
    - Add relationships: roles() (belongsToMany), users() (belongsToMany)
    - Add scopes: scopeByModule(), scopeByAction()
    - Implement static method: generateSlug(action, module)
    - _Requirements: 2.1-2.7_

  - [ ]* 2.4 Write property test for Permission model
    - **Property 7: Permission Identifier Uniqueness** - Verify no duplicate permission slugs
    - **Property 8: Permission Identifier Format** - Verify slug matches pattern for standard permissions
    - **Property 10: Permission Grouping by Module** - Verify each permission belongs to exactly one module
    - **Validates: Requirements 2.3, 2.6, 2.7**

  - [x] 2.5 Create HasRoles trait for User model
    - Implement roles() relationship (belongsToMany with user_roles pivot)
    - Implement hasRole(string $roleName): bool
    - Implement hasAnyRole(array $roleNames): bool
    - Implement hasAllRoles(array $roleNames): bool
    - Implement assignRole(string|Role $role): void
    - Implement removeRole(string|Role $role): void
    - _Requirements: 4.1-4.8, 12.2-12.4_

  - [x] 2.6 Create HasPermissions trait for User model
    - Implement directPermissions() relationship (belongsToMany with user_permissions pivot)
    - Override can($permission) method to integrate with PermissionService
    - Implement getAllPermissions(): Collection
    - Implement getEffectivePermissions(): Collection
    - _Requirements: 5.1-5.6, 12.1, 12.5_

  - [x] 2.7 Extend User model with traits
    - Add HasRoles and HasPermissions traits to User model
    - Ensure traits are properly imported and used
    - _Requirements: 4.1, 5.1, 12.1-12.5_

  - [ ]* 2.8 Write property tests for User permission methods
    - **Property 37: User HasRole Method Correctness** - Verify hasRole() returns true iff user has that role
    - **Property 38: User HasAnyRole Method Correctness** - Verify hasAnyRole() returns true iff user has at least one role
    - **Property 39: User HasAllRoles Method Correctness** - Verify hasAllRoles() returns true iff user has all roles
    - **Validates: Requirements 12.2, 12.3, 12.4**


- [ ] 3. Repositories for Data Access
  - [x] 3.1 Create RoleRepository
    - Implement findById(int $id): ?Role
    - Implement findBySlug(string $slug): ?Role
    - Implement getAll(bool $activeOnly = false): Collection
    - Implement getUserRoles(int $userId): Collection
    - Implement getUserRolePermissions(int $userId): Collection
    - Implement attachUserRole(int $userId, int $roleId, int $assignedBy): void
    - Implement detachUserRole(int $userId, int $roleId): void
    - Implement hasUsers(int $roleId): bool
    - _Requirements: 1.1-1.8, 4.1-4.8_

  - [x] 3.2 Create PermissionRepository
    - Implement findById(int $id): ?Permission
    - Implement findBySlug(string $slug): ?Permission
    - Implement getAll(): Collection
    - Implement getByModule(string $module): Collection
    - Implement getUserDirectPermissions(int $userId): Collection
    - Implement attachUserPermission(int $userId, int $permissionId, int $assignedBy): void
    - Implement detachUserPermission(int $userId, int $permissionId): void
    - _Requirements: 2.1-2.7, 5.1-5.6_

  - [ ]* 3.3 Write unit tests for repositories
    - Test RoleRepository CRUD operations
    - Test PermissionRepository query methods
    - Test relationship attachment/detachment
    - _Requirements: 1.1-1.8, 2.1-2.7, 4.1-4.8, 5.1-5.6_


- [ ] 4. Service Layer - Business Logic
  - [x] 4.1 Create CacheService with Redis integration
    - Implement remember(string $key, int $ttl, Closure $callback): mixed
    - Implement forget(string $key): bool
    - Implement forgetMany(array $keys): void
    - Implement flush(): bool
    - Add graceful degradation when Redis unavailable (catch exceptions, log warnings, execute callback directly)
    - _Requirements: 11.1-11.8_

  - [ ]* 4.2 Write property test for CacheService
    - **Property 20: Cache Miss Fallback** - Verify system works correctly when cache unavailable
    - **Validates: Requirements 6.6, 11.7_

  - [x] 4.3 Create AuditService for logging permission changes
    - Implement log(string $actionType, string $entityType, int $entityId, array $data): void
    - Implement logRoleCreated(Role $role, int $actorId): void
    - Implement logRoleUpdated(Role $role, array $changes, int $actorId): void
    - Implement logRoleDeleted(int $roleId, int $actorId): void
    - Implement logRoleAssignment(int $userId, int $roleId, int $actorId): void
    - Implement logPermissionAssignment(int $roleId, array $permissionIds, int $actorId): void
    - Implement logUnauthorizedAccess(int $userId, string $resource): void
    - Store IP address from request()->ip()
    - _Requirements: 9.1-9.8_

  - [ ]* 4.4 Write property test for AuditService
    - **Property 29: Audit Log Completeness** - Verify every permission change creates audit log
    - **Property 31: Unauthorized Access Logging** - Verify 403 responses create audit logs
    - **Validates: Requirements 9.1-9.8**

  - [x] 4.5 Create PermissionService for permission computation and checking
    - Inject CacheService, PermissionRepository, RoleRepository
    - Implement getUserPermissions(int $userId): Collection (with caching)
    - Implement checkPermission(int $userId, string $permission): bool
    - Implement computeEffectivePermissions(int $userId): Collection (union of role permissions + direct permissions)
    - Implement invalidateUserCache(int $userId): void
    - Implement invalidateRoleUsersCache(int $roleId): void (get all users with role, invalidate each)
    - Cache key format: "user_permissions:{userId}"
    - Cache TTL: 3600 seconds
    - _Requirements: 4.3-4.4, 5.2-5.3, 6.1-6.7, 11.1-11.8, 12.1_

  - [ ]* 4.6 Write property tests for PermissionService
    - **Property 14: Effective Permissions Union Property** - Verify effective permissions = union of role permissions + direct permissions
    - **Property 15: Permission Monotonicity on Role Addition** - Verify adding role never decreases permission count
    - **Property 34: Cache Invalidation on Permission Changes** - Verify cache invalidated on any permission change
    - **Property 36: User Can Method Correctness** - Verify can() returns true iff permission in effective permissions
    - **Validates: Requirements 4.3, 4.4, 5.2, 5.3, 11.3-11.5, 12.1**

  - [x] 4.7 Create RoleService for role management operations
    - Inject RoleRepository, CacheService, AuditService
    - Implement createRole(array $data): Role (validate uniqueness, log creation)
    - Implement updateRole(int $roleId, array $data): Role (preserve ID, log changes)
    - Implement deleteRole(int $roleId): bool (check for users, prevent if assigned, log deletion)
    - Implement assignRoleToUser(int $userId, int $roleId): void (verify existence, attach, invalidate cache, log)
    - Implement removeRoleFromUser(int $userId, int $roleId): void (detach, invalidate cache, log)
    - Implement syncUserRoles(int $userId, array $roleIds): void (sync, invalidate cache, log)
    - Implement assignPermissionsToRole(int $roleId, array $permissionIds): void (sync, invalidate all role users' cache, log)
    - Wrap operations in DB transactions
    - _Requirements: 1.1-1.8, 3.1-3.8, 4.1-4.8_

  - [ ]* 4.8 Write property tests for RoleService
    - **Property 4: Role Deletion Referential Integrity** - Verify role with users cannot be deleted
    - **Property 11: Role-Permission Assignment Referential Integrity** - Verify both role and permission must exist
    - **Property 12: Role-Permission Assignment Idempotence** - Verify duplicate assignments produce same result
    - **Property 35: Cascading Cache Invalidation** - Verify role permission changes invalidate all users with that role
    - **Validates: Requirements 1.6, 3.3, 3.4, 11.4**


- [x] 5. Checkpoint - Core Services Complete
  - Ensure all tests pass, ask the user if questions arise.


- [ ] 6. Middleware and Policies for Authorization
  - [x] 6.1 Create CheckPermission middleware
    - Inject PermissionService
    - Implement handle(Request $request, Closure $next, string $permission)
    - Check if user has permission via PermissionService
    - Return 403 if permission denied
    - Log unauthorized access attempts via AuditService
    - _Requirements: 6.1-6.7_

  - [ ]* 6.2 Write property tests for CheckPermission middleware
    - **Property 18: Authorization Correctness** - Verify access granted iff user has required permission
    - **Property 19: Unauthorized Access Response** - Verify 403 returned when permission missing
    - **Validates: Requirements 6.1, 6.2**

  - [x] 6.3 Create BasePolicy abstract class
    - Inject PermissionService in constructor
    - Implement protected checkPermission(User $user, string $permission): bool
    - _Requirements: 6.3-6.7_

  - [x] 6.4 Create module-specific policies extending BasePolicy
    - Create CustomerPolicy with methods: viewAny, view, create, update, delete
    - Create SupplierPolicy with methods: viewAny, view, create, update, delete
    - Create EmployeePolicy with methods: viewAny, view, create, update, delete
    - Create ProductPolicy with methods: viewAny, view, create, update, delete
    - Create WarehousePolicy with methods: viewAny, view, create, update, delete
    - Create InventoryPolicy with methods: viewAny, view
    - Create ImportPolicy with methods: viewAny, view, create, update, delete, approve
    - Create ExportPolicy with methods: viewAny, view, create, update, delete, approve
    - Create TransferPolicy with methods: viewAny, view, create, update, delete
    - Create DamagedGoodPolicy with methods: viewAny, view, create, update, delete
    - Create SalePolicy with methods: viewAny, view, create, update, delete, export
    - Create QuotationPolicy with methods: viewAny, view, create, update, delete, approve
    - Create PurchaseOrderPolicy with methods: viewAny, view, create, update, delete, approve
    - Create ReportPolicy with methods: viewAny, export
    - Create SettingPolicy with methods: viewAny, view, update
    - Each method calls checkPermission with appropriate permission slug
    - _Requirements: 6.3-6.7_

  - [ ]* 6.5 Write unit tests for policies
    - Test each policy method returns correct boolean based on user permissions
    - Test policy methods with users having/lacking permissions
    - _Requirements: 6.3-6.7_

  - [x] 6.6 Register middleware and policies in service providers
    - Register CheckPermission middleware in app/Http/Kernel.php as 'permission'
    - Register all policies in AuthServiceProvider with model mappings
    - _Requirements: 6.1-6.7_


- [ ] 7. Blade Directives for UI Control
  - [x] 7.1 Register Blade directives in AppServiceProvider
    - Register @can directive: Blade::if('can', fn($permission) => auth()->check() && auth()->user()->can($permission))
    - Register @canany directive: Blade::if('canany', fn($permissions) => auth()->check() && collect($permissions)->some(fn($p) => auth()->user()->can($p)))
    - Register @role directive: Blade::if('role', fn($roleName) => auth()->check() && auth()->user()->hasRole($roleName))
    - Register @hasrole directive: Blade::if('hasrole', fn($roleName) => auth()->check() && auth()->user()->hasRole($roleName))
    - _Requirements: 7.1-7.7_

  - [ ]* 7.2 Write property tests for Blade directives
    - **Property 21: Blade Directive Authorization Consistency** - Verify @can matches authorization result
    - **Property 22: UI Element Visibility Consistency** - Verify UI visibility matches permission state
    - **Property 23: Canany Directive Disjunction** - Verify @canany returns true iff user has at least one permission
    - **Validates: Requirements 7.1-7.7**


- [ ] 8. Controllers and API Routes
  - [x] 8.1 Create RoleController for role management
    - Implement index(): display all roles with pagination
    - Implement create(): show role creation form
    - Implement store(Request $request): validate and create role via RoleService
    - Implement edit(int $id): show role edit form
    - Implement update(Request $request, int $id): validate and update role via RoleService
    - Implement destroy(int $id): delete role via RoleService (with user check)
    - Apply CheckPermission middleware to each action
    - _Requirements: 1.1-1.8_

  - [x] 8.2 Create PermissionController for permission management
    - Implement index(): display all permissions grouped by module
    - Implement matrix(): display permission matrix (roles × permissions)
    - Implement updateMatrix(Request $request): bulk update role-permission assignments
    - Apply CheckPermission middleware
    - _Requirements: 2.1-2.7, 3.1-3.8_

  - [x] 8.3 Create UserRoleController for user-role assignments
    - Implement show(int $userId): display user's roles and available roles
    - Implement assign(Request $request, int $userId): assign role to user via RoleService
    - Implement revoke(Request $request, int $userId, int $roleId): remove role from user via RoleService
    - Implement sync(Request $request, int $userId): sync user's roles via RoleService
    - Apply CheckPermission middleware
    - _Requirements: 4.1-4.8_

  - [x] 8.4 Create UserPermissionController for direct permission assignments
    - Implement show(int $userId): display user's direct permissions
    - Implement assign(Request $request, int $userId): assign direct permission to user
    - Implement revoke(Request $request, int $userId, int $permissionId): remove direct permission from user
    - Apply CheckPermission middleware
    - _Requirements: 5.1-5.6_

  - [x] 8.5 Create AuditLogController for viewing audit logs
    - Implement index(Request $request): display audit logs with filters (date range, user, action type, entity type)
    - Apply CheckPermission middleware (only admins can view audit logs)
    - _Requirements: 9.1-9.8_

  - [x] 8.6 Define routes for RBAC management
    - Define resource routes for roles: Route::resource('roles', RoleController::class)
    - Define routes for permissions: Route::get('permissions', [PermissionController::class, 'index']), Route::get('permissions/matrix', [PermissionController::class, 'matrix']), Route::post('permissions/matrix', [PermissionController::class, 'updateMatrix'])
    - Define routes for user roles: Route::get('users/{user}/roles', [UserRoleController::class, 'show']), Route::post('users/{user}/roles', [UserRoleController::class, 'assign']), Route::delete('users/{user}/roles/{role}', [UserRoleController::class, 'revoke']), Route::put('users/{user}/roles', [UserRoleController::class, 'sync'])
    - Define routes for user permissions: Route::get('users/{user}/permissions', [UserPermissionController::class, 'show']), Route::post('users/{user}/permissions', [UserPermissionController::class, 'assign']), Route::delete('users/{user}/permissions/{permission}', [UserPermissionController::class, 'revoke'])
    - Define routes for audit logs: Route::get('audit-logs', [AuditLogController::class, 'index'])
    - Group all routes under 'admin' prefix with auth middleware
    - _Requirements: 1.1-9.8_

  - [ ]* 8.7 Write integration tests for controllers
    - Test role CRUD operations through HTTP requests
    - Test permission matrix updates
    - Test user role assignments
    - Test direct permission assignments
    - Test audit log filtering
    - Verify middleware blocks unauthorized access
    - _Requirements: 1.1-9.8_


- [ ] 9. Checkpoint - API Layer Complete
  - Ensure all tests pass, ask the user if questions arise.


- [x] 10. Views and UI Components
  - [x] 10.1 Create role management views
    - Create resources/views/roles/index.blade.php: list all roles with create/edit/delete buttons (use @can directives)
    - Create resources/views/roles/create.blade.php: form to create new role
    - Create resources/views/roles/edit.blade.php: form to edit existing role
    - Include status toggle (active/inactive)
    - Show validation errors
    - _Requirements: 1.1-1.8_

  - [x] 10.2 Create permission matrix view
    - Create resources/views/permissions/matrix.blade.php: display roles as rows, permissions as columns (grouped by module)
    - Use checkboxes for each role-permission intersection
    - Implement JavaScript for bulk toggle (select all in row/column)
    - Submit form to update all assignments at once
    - _Requirements: 3.1-3.8_

  - [x] 10.3 Create user role assignment view
    - Create resources/views/users/roles.blade.php: display user info, current roles, available roles
    - Show role assignment form with multi-select or checkboxes
    - Display effective permissions (read-only) computed from assigned roles
    - Show direct permissions separately
    - _Requirements: 4.1-4.8, 5.1-5.6_

  - [x] 10.4 Create audit log viewer
    - Create resources/views/audit-logs/index.blade.php: display audit logs in table format
    - Add filters: date range picker, user dropdown, action type dropdown, entity type dropdown
    - Show old_value and new_value as formatted JSON
    - Implement pagination
    - _Requirements: 9.1-9.8_

  - [x] 10.5 Create shared components for permission UI
    - Create resources/views/permissions/index.blade.php: display all permissions grouped by module
    - Create resources/views/users/permissions.blade.php: manage direct user permissions
    - _Requirements: 1.1-9.8_


- [ ] 11. Integration with Existing Controllers
  - [ ] 11.1 Add authorization to CustomerController
    - Add authorize() calls or middleware to each action (index, show, create, store, edit, update, destroy)
    - Use CustomerPolicy for authorization
    - _Requirements: 6.1-6.7_

  - [ ] 11.2 Add authorization to SupplierController
    - Add authorize() calls or middleware to each action
    - Use SupplierPolicy for authorization
    - _Requirements: 6.1-6.7_

  - [ ] 11.3 Add authorization to EmployeeController
    - Add authorize() calls or middleware to each action
    - Use EmployeePolicy for authorization
    - _Requirements: 6.1-6.7_

  - [ ] 11.4 Add authorization to ProductController
    - Add authorize() calls or middleware to each action
    - Use ProductPolicy for authorization
    - _Requirements: 6.1-6.7_

  - [ ] 11.5 Add authorization to WarehouseController
    - Add authorize() calls or middleware to each action
    - Use WarehousePolicy for authorization
    - _Requirements: 6.1-6.7_

  - [ ] 11.6 Add authorization to InventoryController
    - Add authorize() calls or middleware to each action
    - Use InventoryPolicy for authorization
    - _Requirements: 6.1-6.7_

  - [ ] 11.7 Add authorization to ImportController
    - Add authorize() calls or middleware to each action
    - Use ImportPolicy for authorization (including approve action)
    - _Requirements: 6.1-6.7_

  - [ ] 11.8 Add authorization to ExportController
    - Add authorize() calls or middleware to each action
    - Use ExportPolicy for authorization (including approve action)
    - _Requirements: 6.1-6.7_

  - [ ] 11.9 Add authorization to TransferController
    - Add authorize() calls or middleware to each action
    - Use TransferPolicy for authorization
    - _Requirements: 6.1-6.7_

  - [ ] 11.10 Add authorization to DamagedGoodController
    - Add authorize() calls or middleware to each action
    - Use DamagedGoodPolicy for authorization
    - _Requirements: 6.1-6.7_

  - [ ] 11.11 Add authorization and data filtering to SaleController
    - Add authorize() calls or middleware to each action
    - Use SalePolicy for authorization
    - Implement data filtering: if user has view_own_sales but not view_all_sales, filter query to show only user's sales
    - In show() method, return 404 (not 403) if user lacks permission to view specific record
    - _Requirements: 6.1-6.7, 8.1-8.7_

  - [ ]* 11.12 Write property tests for data filtering
    - **Property 24: View Own Data Filtering** - Verify view_own_sales filters to user's records only
    - **Property 25: View All Data No Filtering** - Verify view_all_sales returns all records
    - **Property 26: Individual Record Authorization** - Verify record access requires permission + ownership
    - **Property 27: Unauthorized Record Access Response** - Verify 404 returned (not 403) for unauthorized record access
    - **Validates: Requirements 8.1-8.7**

  - [ ] 11.13 Add authorization and data filtering to QuotationController
    - Add authorize() calls or middleware to each action
    - Use QuotationPolicy for authorization (including approve action)
    - Implement data filtering similar to SaleController
    - _Requirements: 6.1-6.7, 8.1-8.7_

  - [ ] 11.14 Add authorization and data filtering to PurchaseOrderController
    - Add authorize() calls or middleware to each action
    - Use PurchaseOrderPolicy for authorization (including approve action)
    - Implement data filtering similar to SaleController
    - _Requirements: 6.1-6.7, 8.1-8.7_

  - [ ] 11.15 Add authorization to ReportController
    - Add authorize() calls or middleware to each action
    - Use ReportPolicy for authorization (view and export)
    - _Requirements: 6.1-6.7_

  - [ ] 11.16 Add authorization to SettingController
    - Add authorize() calls or middleware to each action
    - Use SettingPolicy for authorization
    - _Requirements: 6.1-6.7_


- [x] 12. Update Navigation and Sidebar
  - [x] 12.1 Update main navigation menu with permission checks
    - Wrap each menu item with @can directive to show/hide based on permissions
    - Note: Existing menu items will be protected when authorization is added to controllers in task 11
    - _Requirements: 7.1-7.7_

  - [x] 12.2 Add RBAC management section to admin menu
    - Add "Roles" menu item: @can('view_roles')
    - Add "Permissions" menu item: @can('view_permissions')
    - Add "Audit Logs" menu item: @can('view_audit_logs')
    - Group under "Access Control" or "Administration" section
    - _Requirements: 7.1-7.7_

  - [x] 12.3 Update action buttons in list views
    - Note: Action buttons will be wrapped with @can directives in views created in task 10
    - All views already include permission checks for Create, Edit, Delete, Approve, Export buttons
    - _Requirements: 7.1-7.7_


- [ ] 13. Checkpoint - Integration Complete
  - Ensure all tests pass, ask the user if questions arise.


- [ ] 14. Comprehensive Testing Suite
  - [ ]* 14.1 Write property-based tests for remaining properties
    - **Property 5: Role Name Character and Length Validation** - Test with random strings of varying lengths and character sets
    - **Property 6: Inactive Role Assignment Exclusion** - Verify inactive roles excluded from new assignments and permission calculations
    - **Property 9: Permission Seeding Idempotence** - Run seeder multiple times, verify same result
    - **Property 13: Permission Matrix Structure** - Verify matrix has correct dimensions and structure
    - **Property 16: Role Removal Permission Revocation** - Verify removing role only revokes exclusive permissions
    - **Property 17: Direct Permission Independence from Roles** - Verify role changes don't affect direct permissions and vice versa
    - **Property 28: Ownership Permission Pair Existence** - Verify view_own and view_all pairs exist for ownership modules
    - **Property 30: Audit Log Filtering** - Test with random filter combinations
    - **Property 32: Role Seeding Idempotence** - Run role seeder multiple times, verify no duplicates
    - **Property 33: Cache Key Format** - Verify cache keys match expected format
    - **Property 40: GetAllPermissions No Duplicates** - Verify no duplicate permissions in result
    - **Property 41: Gate Allows and Denies Complement** - Verify Gate::allows() = NOT Gate::denies()
    - **Property 42: API and Gate Consistency** - Verify User->can() matches Gate::allows()
    - **Validates: Requirements 1.7, 1.8, 2.5, 3.5, 4.6, 5.5, 8.7, 9.6, 10.11, 11.1, 12.5, 12.7, 12.6**

  - [ ]* 14.2 Write unit tests for edge cases
    - Test role creation with empty name (expect validation error)
    - Test role creation with name > 100 characters (expect validation error)
    - Test role deletion with assigned users (expect error)
    - Test role deletion without assigned users (expect success)
    - Test permission assignment with non-existent role (expect error)
    - Test permission assignment with non-existent permission (expect error)
    - Test duplicate permission assignment (expect idempotent behavior)
    - Test user with no roles (expect empty effective permissions)
    - Test user with inactive role (expect role permissions excluded)
    - Test cache unavailable scenario (expect fallback to database)
    - Test audit log creation for all operation types
    - _Requirements: 1.1-12.7_

  - [ ]* 14.3 Write integration tests for end-to-end workflows
    - Test complete workflow: create role → assign permissions → assign to user → user accesses protected route
    - Test workflow: user with view_own_sales can only see their sales
    - Test workflow: manager with view_all_sales can see all sales
    - Test workflow: admin modifies role permissions → all users with that role get updated permissions (cache invalidation)
    - Test workflow: user assigned direct permission → can access resource even without role
    - Test workflow: unauthorized user attempts access → 403 response → audit log created
    - Test workflow: user views record they don't own → 404 response (not 403)
    - _Requirements: 1.1-12.7_

  - [ ]* 14.4 Write performance tests
    - Benchmark permission check with cache (target: < 50ms, ideal: 10-20ms)
    - Benchmark permission check without cache (target: < 200ms)
    - Benchmark cache invalidation for role with 100 users (target: < 5 seconds)
    - Benchmark permission matrix rendering for 50 roles × 200 permissions (target: < 1 second)
    - Test concurrent permission checks (100 concurrent requests)
    - Measure cache hit rate under normal load (target: > 95%)
    - _Requirements: 6.6, 11.2_


- [ ] 15. Documentation and Developer Guide
  - [ ] 15.1 Create API documentation
    - Document all public methods in PermissionService, RoleService, CacheService, AuditService
    - Document User trait methods (can, hasRole, hasAnyRole, hasAllRoles, getAllPermissions)
    - Document middleware usage: Route::middleware('permission:view_customers')
    - Document policy usage: $this->authorize('update', $customer)
    - Document Blade directive usage: @can, @canany, @role, @hasrole
    - Include code examples for common scenarios
    - _Requirements: 1.1-12.7_

  - [ ] 15.2 Create usage guide for developers
    - How to add new permissions (add to seeder, run migration)
    - How to create new policies (extend BasePolicy, implement methods)
    - How to protect routes (middleware, authorize calls)
    - How to protect UI elements (Blade directives)
    - How to implement data filtering (view_own vs view_all pattern)
    - How to check permissions in code (User->can(), Gate::allows())
    - Best practices for permission naming
    - _Requirements: 1.1-12.7_

  - [ ] 15.3 Create migration guide for existing code
    - Identify all existing controllers that need authorization
    - Document step-by-step process to add authorization to existing controller
    - Document how to update existing views with @can directives
    - Document how to test authorization after migration
    - Provide checklist for complete migration
    - _Requirements: 6.1-7.7_

  - [ ] 15.4 Create admin user guide
    - How to create and manage roles
    - How to assign permissions to roles using permission matrix
    - How to assign roles to users
    - How to assign direct permissions to users
    - How to view audit logs and filter by criteria
    - How to troubleshoot permission issues
    - Screenshots and examples
    - _Requirements: 1.1-9.8_


- [ ] 16. Final Integration and Validation
  - [ ] 16.1 Run all migrations and seeders on fresh database
    - Verify all tables created correctly
    - Verify all permissions seeded (check count and format)
    - Verify all predefined roles created with correct permission assignments
    - _Requirements: 1.1-2.7, 10.1-10.11_

  - [ ] 16.2 Create test users with different roles
    - Create Super Admin user (all permissions)
    - Create Warehouse Manager user (warehouse permissions)
    - Create Sales Staff user (view_own_sales only)
    - Create Sales Manager user (view_all_sales)
    - Create user with direct permissions
    - Create user with inactive role
    - _Requirements: 4.1-5.6, 10.1-10.11_

  - [ ] 16.3 Manual testing of complete workflows
    - Login as each test user
    - Verify menu items show/hide correctly based on permissions
    - Verify action buttons show/hide correctly
    - Verify protected routes return 403 when accessed without permission
    - Verify data filtering works (Sales Staff sees only own sales, Manager sees all)
    - Verify permission matrix updates work
    - Verify audit logs capture all changes
    - _Requirements: 1.1-12.7_

  - [ ] 16.4 Verify cache behavior
    - Check Redis for cached permissions after user login
    - Verify cache key format: user_permissions:{userId}
    - Modify role permissions, verify cache invalidated for affected users
    - Verify system works when Redis is stopped (graceful degradation)
    - _Requirements: 11.1-11.8_

  - [ ] 16.5 Run complete test suite
    - Run all unit tests: vendor/bin/pest --filter=unit
    - Run all property-based tests: vendor/bin/pest --filter=property-based
    - Run all integration tests: vendor/bin/pest --filter=integration
    - Verify all tests pass
    - Generate coverage report (target: > 80%)
    - _Requirements: 1.1-12.7_

  - [ ] 16.6 Performance validation
    - Measure permission check latency with cache (should be < 50ms)
    - Measure permission check latency without cache (should be < 200ms)
    - Verify cache invalidation completes within 5 seconds
    - Test with 100 concurrent users
    - _Requirements: 6.6, 11.2_


- [ ] 17. Final Checkpoint - Feature Complete
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional testing tasks and can be skipped for faster MVP delivery
- Each task references specific requirements for traceability
- Property-based tests validate universal correctness properties from the design document
- Unit tests validate specific examples, edge cases, and error conditions
- Integration tests validate end-to-end workflows
- Performance tests ensure the system meets latency and throughput requirements
- The implementation follows Laravel best practices and conventions
- All database operations use transactions for consistency
- Cache failures are handled gracefully with fallback to database
- Audit logging is comprehensive but non-blocking
- The system is designed for security (fail-safe), performance (caching), and maintainability (separation of concerns)

## Testing Configuration

**Framework**: Pest PHP with pest-plugin-faker for property-based testing

**Property Test Configuration**:
- Minimum 100 iterations per property test
- Each property test references its design document property number
- Tag format: `Feature: role-based-access-control, Property {number}: {property_text}`

**Example Property Test**:
```php
use function Pest\Faker\fake;

test('Property 14: Effective Permissions Union', function () {
    // Feature: role-based-access-control, Property 14
    for ($i = 0; $i < 100; $i++) {
        $user = User::factory()->create();
        $roles = Role::factory()->count(rand(1, 5))->create();
        $rolePermissions = collect();
        
        foreach ($roles as $role) {
            $perms = Permission::factory()->count(rand(2, 10))->create();
            $role->permissions()->attach($perms->pluck('id'));
            $rolePermissions = $rolePermissions->merge($perms);
        }
        
        $user->roles()->attach($roles->pluck('id'));
        $directPerms = Permission::factory()->count(rand(0, 5))->create();
        $user->directPermissions()->attach($directPerms->pluck('id'));
        
        $expected = $rolePermissions->merge($directPerms)->unique('id')->pluck('id')->sort()->values();
        $actual = $user->getAllPermissions()->pluck('id')->sort()->values();
        
        expect($actual->toArray())->toBe($expected->toArray());
    }
})->group('property-based', 'rbac', 'permissions');
```

## Implementation Order Rationale

The tasks are ordered to ensure:
1. **Foundation First**: Database schema and seeders provide the data foundation
2. **Bottom-Up**: Models → Repositories → Services → Controllers → Views
3. **No Orphaned Code**: Each layer integrates with previous layers immediately
4. **Early Validation**: Checkpoints after major milestones ensure correctness
5. **Incremental Testing**: Tests written alongside implementation for rapid feedback
6. **Integration Last**: Existing controllers updated after core RBAC is stable
7. **Documentation Throughout**: API docs and guides created as features are built

## Success Criteria

The RBAC feature is complete when:
- All 17 task groups are completed
- All non-optional tests pass (unit, integration, performance)
- All 42 correctness properties are validated (optional property tests)
- Manual testing confirms expected behavior for all user roles
- Cache behavior is correct (with and without Redis)
- Audit logs capture all permission changes
- Documentation is complete and accurate
- Performance meets targets (< 50ms cached, < 200ms uncached, < 5s invalidation)
- Code coverage > 80%
- All existing controllers have authorization checks
- All UI elements respect permissions
