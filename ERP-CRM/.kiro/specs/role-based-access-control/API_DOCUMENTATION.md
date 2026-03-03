# Tài liệu API - Hệ thống Phân quyền RBAC

## Tổng quan

Tài liệu này mô tả chi tiết các API công khai của hệ thống Role-Based Access Control (RBAC) trong Mini ERP Laravel. Hệ thống cung cấp các service, phương thức trên User model, middleware, policy, và Blade directive để quản lý và kiểm tra quyền truy cập.

## Mục lục

1. [Service Layer APIs](#service-layer-apis)
   - [PermissionService](#permissionservice)
   - [RoleService](#roleservice)
   - [CacheService](#cacheservice)
   - [AuditService](#auditservice)
2. [User Model Methods](#user-model-methods)
3. [Middleware Usage](#middleware-usage)
4. [Policy Usage](#policy-usage)
5. [Blade Directives](#blade-directives)
6. [Common Scenarios](#common-scenarios)

---

## Service Layer APIs

### PermissionService

Service chính để tính toán và kiểm tra quyền của người dùng.

#### `getUserPermissions(int $userId): Collection`

Lấy tất cả quyền hiệu lực của người dùng (có cache).

**Parameters:**
- `$userId` (int): ID của người dùng

**Returns:** `Collection` - Collection các Permission model

**Example:**
```php
$permissionService = app(\App\Services\PermissionService::class);
$permissions = $permissionService->getUserPermissions(auth()->id());
```

#### `checkPermission(int $userId, string $permission): bool`

Kiểm tra xem người dùng có quyền cụ thể hay không.

**Parameters:**
- `$userId` (int): ID của người dùng
- `$permission` (string): Slug của quyền cần kiểm tra (ví dụ: 'view_customers')

**Returns:** `bool` - `true` nếu có quyền, `false` nếu không

**Example:**
```php
$permissionService = app(\App\Services\PermissionService::class);
if ($permissionService->checkPermission(auth()->id(), 'create_sales')) {
    // Người dùng có quyền tạo đơn hàng
}
```

#### `computeEffectivePermissions(int $userId): Collection`

Tính toán quyền hiệu lực của người dùng (không dùng cache).

**Parameters:**
- `$userId` (int): ID của người dùng

**Returns:** `Collection` - Collection các Permission model (hợp của quyền từ vai trò và quyền trực tiếp)

**Example:**
```php
$permissionService = app(\App\Services\PermissionService::class);
$permissions = $permissionService->computeEffectivePermissions($userId);
```

#### `invalidateUserCache(int $userId): void`

Xóa cache quyền của một người dùng.

**Parameters:**
- `$userId` (int): ID của người dùng

**Returns:** `void`

**Example:**
```php
$permissionService = app(\App\Services\PermissionService::class);
$permissionService->invalidateUserCache(auth()->id());
```

#### `invalidateRoleUsersCache(int $roleId): void`

Xóa cache quyền của tất cả người dùng có vai trò cụ thể.

**Parameters:**
- `$roleId` (int): ID của vai trò

**Returns:** `void`

**Example:**
```php
$permissionService = app(\App\Services\PermissionService::class);
$permissionService->invalidateRoleUsersCache($roleId);
```

---

### RoleService

Service quản lý vai trò và gán vai trò cho người dùng.

#### `createRole(array $data): Role`

Tạo vai trò mới.

**Parameters:**
- `$data` (array): Dữ liệu vai trò
  - `name` (string, required): Tên vai trò
  - `slug` (string, required): Slug vai trò (phải unique)
  - `description` (string, optional): Mô tả vai trò
  - `status` (string, optional): Trạng thái ('active' hoặc 'inactive')

**Returns:** `Role` - Vai trò vừa tạo

**Throws:** `ValidationException` nếu slug đã tồn tại

**Example:**
```php
$roleService = app(\App\Services\RoleService::class);
$role = $roleService->createRole([
    'name' => 'Quản lý kho',
    'slug' => 'warehouse-manager',
    'description' => 'Quản lý toàn bộ hoạt động kho',
    'status' => 'active',
]);
```

#### `updateRole(int $roleId, array $data): Role`

Cập nhật vai trò hiện có.

**Parameters:**
- `$roleId` (int): ID của vai trò cần cập nhật
- `$data` (array): Dữ liệu cần cập nhật (chỉ các trường được chỉ định sẽ được cập nhật)

**Returns:** `Role` - Vai trò đã cập nhật

**Throws:** 
- `ModelNotFoundException` nếu vai trò không tồn tại
- `ValidationException` nếu slug mới đã tồn tại

**Example:**
```php
$roleService = app(\App\Services\RoleService::class);
$role = $roleService->updateRole($roleId, [
    'description' => 'Mô tả mới',
    'status' => 'inactive',
]);
```

#### `deleteRole(int $roleId): bool`

Xóa vai trò.

**Parameters:**
- `$roleId` (int): ID của vai trò cần xóa

**Returns:** `bool` - `true` nếu xóa thành công

**Throws:** 
- `ModelNotFoundException` nếu vai trò không tồn tại
- `ValidationException` nếu vai trò đang được gán cho người dùng

**Example:**
```php
$roleService = app(\App\Services\RoleService::class);
$roleService->deleteRole($roleId);
```

#### `assignRoleToUser(int $userId, int $roleId): void`

Gán vai trò cho người dùng.

**Parameters:**
- `$userId` (int): ID của người dùng
- `$roleId` (int): ID của vai trò

**Returns:** `void`

**Throws:** `ModelNotFoundException` nếu người dùng hoặc vai trò không tồn tại

**Example:**
```php
$roleService = app(\App\Services\RoleService::class);
$roleService->assignRoleToUser($userId, $roleId);
```

#### `removeRoleFromUser(int $userId, int $roleId): void`

Gỡ vai trò khỏi người dùng.

**Parameters:**
- `$userId` (int): ID của người dùng
- `$roleId` (int): ID của vai trò

**Returns:** `void`

**Throws:** `ModelNotFoundException` nếu người dùng hoặc vai trò không tồn tại

**Example:**
```php
$roleService = app(\App\Services\RoleService::class);
$roleService->removeRoleFromUser($userId, $roleId);
```

#### `syncUserRoles(int $userId, array $roleIds): void`

Đồng bộ vai trò của người dùng (thay thế tất cả vai trò hiện tại).

**Parameters:**
- `$userId` (int): ID của người dùng
- `$roleIds` (array): Mảng các ID vai trò

**Returns:** `void`

**Throws:** `ModelNotFoundException` nếu người dùng không tồn tại

**Example:**
```php
$roleService = app(\App\Services\RoleService::class);
$roleService->syncUserRoles($userId, [1, 3, 5]);
```

#### `assignPermissionsToRole(int $roleId, array $permissionIds): void`

Gán quyền cho vai trò (đồng bộ - thay thế tất cả quyền hiện tại).

**Parameters:**
- `$roleId` (int): ID của vai trò
- `$permissionIds` (array): Mảng các ID quyền

**Returns:** `void`

**Throws:** `ModelNotFoundException` nếu vai trò không tồn tại

**Example:**
```php
$roleService = app(\App\Services\RoleService::class);
$roleService->assignPermissionsToRole($roleId, [1, 2, 3, 4, 5]);
```

---

### CacheService

Service quản lý cache quyền với Redis.

#### `remember(string $key, int $ttl, Closure $callback): mixed`

Lấy giá trị từ cache hoặc thực thi callback và lưu kết quả.

**Parameters:**
- `$key` (string): Khóa cache
- `$ttl` (int): Thời gian sống (giây)
- `$callback` (Closure): Hàm thực thi nếu cache miss

**Returns:** `mixed` - Giá trị từ cache hoặc kết quả callback

**Example:**
```php
$cacheService = app(\App\Services\CacheService::class);
$data = $cacheService->remember('my_key', 3600, function() {
    return expensive_computation();
});
```

#### `forget(string $key): bool`

Xóa một mục khỏi cache.

**Parameters:**
- `$key` (string): Khóa cache

**Returns:** `bool` - `true` nếu xóa thành công

**Example:**
```php
$cacheService = app(\App\Services\CacheService::class);
$cacheService->forget('user_permissions:123');
```

#### `forgetMany(array $keys): void`

Xóa nhiều mục khỏi cache.

**Parameters:**
- `$keys` (array): Mảng các khóa cache

**Returns:** `void`

**Example:**
```php
$cacheService = app(\App\Services\CacheService::class);
$cacheService->forgetMany(['key1', 'key2', 'key3']);
```

#### `flush(): bool`

Xóa toàn bộ cache.

**Parameters:** Không có

**Returns:** `bool` - `true` nếu xóa thành công

**Example:**
```php
$cacheService = app(\App\Services\CacheService::class);
$cacheService->flush();
```

---

### AuditService

Service ghi log các thay đổi về phân quyền.

#### `log(string $actionType, string $entityType, int $entityId, array $data): void`

Ghi log audit chung.

**Parameters:**
- `$actionType` (string): Loại hành động ('created', 'updated', 'deleted', 'assigned', 'removed')
- `$entityType` (string): Loại entity ('role', 'permission', 'user_role', 'user_permission')
- `$entityId` (int): ID của entity
- `$data` (array): Dữ liệu bổ sung
  - `old_value` (array, optional): Giá trị cũ
  - `new_value` (array, optional): Giá trị mới

**Returns:** `void`

**Example:**
```php
$auditService = app(\App\Services\AuditService::class);
$auditService->log('updated', 'role', $roleId, [
    'old_value' => ['status' => 'active'],
    'new_value' => ['status' => 'inactive'],
]);
```

#### `logRoleCreated(Role $role, int $actorId): void`

Ghi log tạo vai trò.

**Parameters:**
- `$role` (Role): Vai trò vừa tạo
- `$actorId` (int): ID người dùng thực hiện

**Returns:** `void`

#### `logRoleUpdated(Role $role, array $changes, int $actorId): void`

Ghi log cập nhật vai trò.

**Parameters:**
- `$role` (Role): Vai trò đã cập nhật
- `$changes` (array): Mảng thay đổi (field => [old, new])
- `$actorId` (int): ID người dùng thực hiện

**Returns:** `void`

#### `logRoleDeleted(int $roleId, int $actorId): void`

Ghi log xóa vai trò.

**Parameters:**
- `$roleId` (int): ID vai trò đã xóa
- `$actorId` (int): ID người dùng thực hiện

**Returns:** `void`

#### `logRoleAssignment(int $userId, int $roleId, int $actorId): void`

Ghi log gán vai trò cho người dùng.

**Parameters:**
- `$userId` (int): ID người dùng nhận vai trò
- `$roleId` (int): ID vai trò được gán
- `$actorId` (int): ID người dùng thực hiện

**Returns:** `void`

#### `logPermissionAssignment(int $roleId, array $permissionIds, int $actorId): void`

Ghi log gán quyền cho vai trò.

**Parameters:**
- `$roleId` (int): ID vai trò nhận quyền
- `$permissionIds` (array): Mảng ID quyền được gán
- `$actorId` (int): ID người dùng thực hiện

**Returns:** `void`

#### `logUnauthorizedAccess(int $userId, string $resource): void`

Ghi log truy cập trái phép.

**Parameters:**
- `$userId` (int): ID người dùng cố gắng truy cập
- `$resource` (string): Tài nguyên được truy cập

**Returns:** `void`

---

## User Model Methods

Các phương thức có sẵn trên User model thông qua trait `HasRoles` và `HasPermissions`.

### Phương thức kiểm tra quyền

#### `can(string $permission): bool`

Kiểm tra xem người dùng có quyền cụ thể hay không.

**Parameters:**
- `$permission` (string): Slug của quyền

**Returns:** `bool` - `true` nếu có quyền

**Example:**
```php
if (auth()->user()->can('view_customers')) {
    // Người dùng có quyền xem khách hàng
}
```

#### `getAllPermissions(): Collection`

Lấy tất cả quyền hiệu lực của người dùng.

**Parameters:** Không có

**Returns:** `Collection` - Collection các Permission model

**Example:**
```php
$permissions = auth()->user()->getAllPermissions();
foreach ($permissions as $permission) {
    echo $permission->name;
}
```

#### `getEffectivePermissions(): Collection`

Lấy quyền hiệu lực của người dùng (alias của `getAllPermissions()`).

**Parameters:** Không có

**Returns:** `Collection` - Collection các Permission model

**Example:**
```php
$permissions = auth()->user()->getEffectivePermissions();
```

### Phương thức kiểm tra vai trò

#### `hasRole(string $roleName): bool`

Kiểm tra xem người dùng có vai trò cụ thể hay không.

**Parameters:**
- `$roleName` (string): Slug của vai trò

**Returns:** `bool` - `true` nếu có vai trò

**Example:**
```php
if (auth()->user()->hasRole('admin')) {
    // Người dùng có vai trò admin
}
```

#### `hasAnyRole(array $roleNames): bool`

Kiểm tra xem người dùng có bất kỳ vai trò nào trong danh sách hay không.

**Parameters:**
- `$roleNames` (array): Mảng slug các vai trò

**Returns:** `bool` - `true` nếu có ít nhất một vai trò

**Example:**
```php
if (auth()->user()->hasAnyRole(['admin', 'manager'])) {
    // Người dùng có vai trò admin hoặc manager
}
```

#### `hasAllRoles(array $roleNames): bool`

Kiểm tra xem người dùng có tất cả các vai trò trong danh sách hay không.

**Parameters:**
- `$roleNames` (array): Mảng slug các vai trò

**Returns:** `bool` - `true` nếu có tất cả vai trò

**Example:**
```php
if (auth()->user()->hasAllRoles(['manager', 'supervisor'])) {
    // Người dùng có cả vai trò manager và supervisor
}
```

### Phương thức quản lý vai trò

#### `assignRole(string|Role $role): void`

Gán vai trò cho người dùng.

**Parameters:**
- `$role` (string|Role): Slug vai trò hoặc Role model

**Returns:** `void`

**Example:**
```php
auth()->user()->assignRole('warehouse-manager');
// hoặc
auth()->user()->assignRole($roleModel);
```

#### `removeRole(string|Role $role): void`

Gỡ vai trò khỏi người dùng.

**Parameters:**
- `$role` (string|Role): Slug vai trò hoặc Role model

**Returns:** `void`

**Example:**
```php
auth()->user()->removeRole('warehouse-staff');
```

### Relationships

#### `roles(): BelongsToMany`

Lấy relationship với các vai trò.

**Returns:** `BelongsToMany`

**Example:**
```php
$roles = auth()->user()->roles;
foreach ($roles as $role) {
    echo $role->name;
}
```

#### `directPermissions(): BelongsToMany`

Lấy relationship với các quyền trực tiếp.

**Returns:** `BelongsToMany`

**Example:**
```php
$directPermissions = auth()->user()->directPermissions;
```

---

## Middleware Usage

### CheckPermission Middleware

Middleware để bảo vệ route yêu cầu quyền cụ thể.

#### Cú pháp

```php
Route::middleware('permission:permission_slug')->group(function () {
    // Routes được bảo vệ
});
```

#### Ví dụ đơn giản

```php
// Bảo vệ một route
Route::get('/customers', [CustomerController::class, 'index'])
    ->middleware('permission:view_customers');

// Bảo vệ nhiều route
Route::middleware('permission:view_sales')->group(function () {
    Route::get('/sales', [SaleController::class, 'index']);
    Route::get('/sales/{id}', [SaleController::class, 'show']);
});
```

#### Ví dụ với nhiều quyền

```php
// Yêu cầu quyền tạo khách hàng
Route::post('/customers', [CustomerController::class, 'store'])
    ->middleware('permission:create_customers');

// Yêu cầu quyền chỉnh sửa khách hàng
Route::put('/customers/{id}', [CustomerController::class, 'update'])
    ->middleware('permission:edit_customers');

// Yêu cầu quyền xóa khách hàng
Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])
    ->middleware('permission:delete_customers');
```

#### Ví dụ với resource controller

```php
Route::resource('warehouses', WarehouseController::class)
    ->middleware([
        'index' => 'permission:view_warehouses',
        'show' => 'permission:view_warehouses',
        'create' => 'permission:create_warehouses',
        'store' => 'permission:create_warehouses',
        'edit' => 'permission:edit_warehouses',
        'update' => 'permission:edit_warehouses',
        'destroy' => 'permission:delete_warehouses',
    ]);
```

#### Xử lý lỗi

Khi người dùng không có quyền, middleware sẽ:
- Trả về HTTP status code 403
- Hiển thị thông báo lỗi "Unauthorized action."
- Ghi log truy cập trái phép vào audit log

---

## Policy Usage

### Sử dụng Policy trong Controller

Policy cung cấp cách tổ chức logic phân quyền theo từng model.

#### Cú pháp cơ bản

```php
$this->authorize('action', $model);
```

#### Các phương thức Policy chuẩn

Mỗi Policy có các phương thức sau:
- `viewAny(User $user)`: Xem danh sách
- `view(User $user, Model $model)`: Xem chi tiết
- `create(User $user)`: Tạo mới
- `update(User $user, Model $model)`: Cập nhật
- `delete(User $user, Model $model)`: Xóa
- `approve(User $user, Model $model)`: Phê duyệt (nếu có)
- `export(User $user)`: Xuất dữ liệu (nếu có)

#### Ví dụ trong Controller

```php
class CustomerController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Customer::class);
        
        $customers = Customer::paginate(15);
        return view('customers.index', compact('customers'));
    }
    
    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);
        
        return view('customers.show', compact('customer'));
    }
    
    public function create()
    {
        $this->authorize('create', Customer::class);
        
        return view('customers.create');
    }
    
    public function store(Request $request)
    {
        $this->authorize('create', Customer::class);
        
        $customer = Customer::create($request->validated());
        return redirect()->route('customers.show', $customer);
    }
    
    public function edit(Customer $customer)
    {
        $this->authorize('update', $customer);
        
        return view('customers.edit', compact('customer'));
    }
    
    public function update(Request $request, Customer $customer)
    {
        $this->authorize('update', $customer);
        
        $customer->update($request->validated());
        return redirect()->route('customers.show', $customer);
    }
    
    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);
        
        $customer->delete();
        return redirect()->route('customers.index');
    }
}
```

#### Ví dụ với phương thức approve

```php
class ImportController extends Controller
{
    public function approve(Import $import)
    {
        $this->authorize('approve', $import);
        
        $import->update(['status' => 'approved']);
        return redirect()->back()->with('success', 'Đã phê duyệt phiếu nhập');
    }
}
```

#### Sử dụng Gate facade

```php
use Illuminate\Support\Facades\Gate;

// Kiểm tra quyền
if (Gate::allows('update', $customer)) {
    // Người dùng có quyền cập nhật
}

if (Gate::denies('delete', $customer)) {
    // Người dùng không có quyền xóa
}

// Ném exception nếu không có quyền
Gate::authorize('update', $customer);
```

#### Kiểm tra quyền trước khi thực hiện action

```php
public function bulkDelete(Request $request)
{
    $this->authorize('delete', Customer::class);
    
    $ids = $request->input('ids');
    Customer::whereIn('id', $ids)->delete();
    
    return redirect()->back()->with('success', 'Đã xóa khách hàng');
}
```

---

## Blade Directives

### @can Directive

Hiển thị nội dung nếu người dùng có quyền cụ thể.

#### Cú pháp

```blade
@can('permission_slug')
    <!-- Nội dung chỉ hiển thị khi có quyền -->
@endcan
```

#### Ví dụ

```blade
@can('create_customers')
    <a href="{{ route('customers.create') }}" class="btn btn-primary">
        Thêm khách hàng
    </a>
@endcan

@can('edit_customers')
    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-warning">
        Chỉnh sửa
    </a>
@endcan

@can('delete_customers')
    <form action="{{ route('customers.destroy', $customer) }}" method="POST" style="display: inline;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Xóa</button>
    </form>
@endcan
```

#### Với @else

```blade
@can('view_all_sales')
    <p>Bạn có thể xem tất cả đơn hàng</p>
@else
    <p>Bạn chỉ có thể xem đơn hàng của mình</p>
@endcan
```

### @canany Directive

Hiển thị nội dung nếu người dùng có ít nhất một trong các quyền.

#### Cú pháp

```blade
@canany(['permission1', 'permission2', 'permission3'])
    <!-- Nội dung hiển thị nếu có ít nhất một quyền -->
@endcanany
```

#### Ví dụ

```blade
@canany(['edit_customers', 'delete_customers'])
    <div class="action-buttons">
        @can('edit_customers')
            <button class="btn btn-warning">Sửa</button>
        @endcan
        
        @can('delete_customers')
            <button class="btn btn-danger">Xóa</button>
        @endcan
    </div>
@endcanany
```

### @role Directive

Hiển thị nội dung nếu người dùng có vai trò cụ thể.

#### Cú pháp

```blade
@role('role_slug')
    <!-- Nội dung chỉ hiển thị cho vai trò này -->
@endrole
```

#### Ví dụ

```blade
@role('admin')
    <a href="{{ route('admin.dashboard') }}" class="nav-link">
        Quản trị hệ thống
    </a>
@endrole

@role('warehouse-manager')
    <div class="manager-tools">
        <h3>Công cụ quản lý kho</h3>
        <!-- Các công cụ dành cho quản lý kho -->
    </div>
@endrole
```

### @hasrole Directive

Tương tự `@role`, kiểm tra vai trò của người dùng.

#### Cú pháp

```blade
@hasrole('role_slug')
    <!-- Nội dung chỉ hiển thị cho vai trò này -->
@endhasrole
```

#### Ví dụ

```blade
@hasrole('sales-manager')
    <li class="nav-item">
        <a href="{{ route('reports.sales') }}">Báo cáo bán hàng</a>
    </li>
@endhasrole
```

### Kết hợp nhiều directive

```blade
<div class="customer-actions">
    @canany(['view_customers', 'create_customers', 'edit_customers'])
        <div class="btn-group">
            @can('view_customers')
                <a href="{{ route('customers.index') }}" class="btn btn-info">
                    Danh sách
                </a>
            @endcan
            
            @can('create_customers')
                <a href="{{ route('customers.create') }}" class="btn btn-success">
                    Thêm mới
                </a>
            @endcan
        </div>
    @endcanany
</div>

@role('admin')
    <div class="admin-panel">
        <h4>Bảng điều khiển Admin</h4>
        @can('view_audit_logs')
            <a href="{{ route('audit-logs.index') }}">Xem audit logs</a>
        @endcan
    </div>
@endrole
```

---

## Common Scenarios

### Scenario 1: Kiểm tra quyền trong Controller

```php
class SaleController extends Controller
{
    public function index()
    {
        // Kiểm tra quyền xem đơn hàng
        $this->authorize('viewAny', Sale::class);
        
        // Lọc dữ liệu dựa trên quyền
        $query = Sale::query();
        
        if (auth()->user()->can('view_all_sales')) {
            // Xem tất cả đơn hàng
            $sales = $query->paginate(15);
        } else if (auth()->user()->can('view_own_sales')) {
            // Chỉ xem đơn hàng của mình
            $sales = $query->where('salesperson_id', auth()->id())->paginate(15);
        } else {
            abort(403, 'Bạn không có quyền xem đơn hàng');
        }
        
        return view('sales.index', compact('sales'));
    }
    
    public function show(Sale $sale)
    {
        // Kiểm tra quyền xem chi tiết
        $this->authorize('view', $sale);
        
        // Nếu chỉ có quyền xem đơn hàng của mình
        if (!auth()->user()->can('view_all_sales')) {
            if ($sale->salesperson_id !== auth()->id()) {
                // Trả về 404 thay vì 403 để không tiết lộ thông tin
                abort(404);
            }
        }
        
        return view('sales.show', compact('sale'));
    }
}
```

### Scenario 2: Bảo vệ Routes

```php
// routes/web.php

Route::middleware(['auth'])->group(function () {
    
    // Routes khách hàng
    Route::middleware('permission:view_customers')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index']);
        Route::get('/customers/{customer}', [CustomerController::class, 'show']);
    });
    
    Route::post('/customers', [CustomerController::class, 'store'])
        ->middleware('permission:create_customers');
    
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])
        ->middleware('permission:edit_customers');
    
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])
        ->middleware('permission:delete_customers');
    
    // Routes kho hàng với nhiều quyền
    Route::prefix('warehouses')->group(function () {
        Route::get('/', [WarehouseController::class, 'index'])
            ->middleware('permission:view_warehouses');
        
        Route::get('/create', [WarehouseController::class, 'create'])
            ->middleware('permission:create_warehouses');
        
        Route::post('/', [WarehouseController::class, 'store'])
            ->middleware('permission:create_warehouses');
        
        Route::get('/{warehouse}/edit', [WarehouseController::class, 'edit'])
            ->middleware('permission:edit_warehouses');
        
        Route::put('/{warehouse}', [WarehouseController::class, 'update'])
            ->middleware('permission:edit_warehouses');
        
        Route::delete('/{warehouse}', [WarehouseController::class, 'destroy'])
            ->middleware('permission:delete_warehouses');
    });
    
    // Routes phê duyệt
    Route::post('/imports/{import}/approve', [ImportController::class, 'approve'])
        ->middleware('permission:approve_imports');
    
    Route::post('/exports/{export}/approve', [ExportController::class, 'approve'])
        ->middleware('permission:approve_exports');
});
```

### Scenario 3: Hiển thị UI có điều kiện

```blade
<!-- resources/views/customers/index.blade.php -->

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Danh sách khách hàng</h1>
        
        @can('create_customers')
            <a href="{{ route('customers.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Thêm khách hàng
            </a>
        @endcan
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th>Mã KH</th>
                <th>Tên</th>
                <th>Email</th>
                <th>Điện thoại</th>
                @canany(['edit_customers', 'delete_customers'])
                    <th>Thao tác</th>
                @endcanany
            </tr>
        </thead>
        <tbody>
            @foreach($customers as $customer)
                <tr>
                    <td>{{ $customer->code }}</td>
                    <td>
                        @can('view_customers')
                            <a href="{{ route('customers.show', $customer) }}">
                                {{ $customer->name }}
                            </a>
                        @else
                            {{ $customer->name }}
                        @endcan
                    </td>
                    <td>{{ $customer->email }}</td>
                    <td>{{ $customer->phone }}</td>
                    
                    @canany(['edit_customers', 'delete_customers'])
                        <td>
                            @can('edit_customers')
                                <a href="{{ route('customers.edit', $customer) }}" 
                                   class="btn btn-sm btn-warning">
                                    Sửa
                                </a>
                            @endcan
                            
                            @can('delete_customers')
                                <form action="{{ route('customers.destroy', $customer) }}" 
                                      method="POST" 
                                      style="display: inline;"
                                      onsubmit="return confirm('Bạn có chắc muốn xóa?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        Xóa
                                    </button>
                                </form>
                            @endcan
                        </td>
                    @endcanany
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
```
