# Design Document - Liên kết Xuất Kho với Dự Án

## Overview

Tính năng này mở rộng module xuất kho hiện tại bằng cách thêm liên kết với bảng projects. Khi tạo phiếu xuất kho, người dùng có thể chọn dự án đích, giúp theo dõi sản phẩm được phân bổ cho dự án nào.

## Architecture

### Database Changes

**Migration: add_project_id_to_inventory_transactions**
- Thêm cột `project_id` (nullable, foreign key) vào bảng `inventory_transactions`
- Index trên `project_id` để tối ưu query
- Foreign key constraint với `projects.id` (ON DELETE SET NULL)

### Components and Interfaces

#### 1. ExportController
- **Method mới**: Không cần thêm method mới
- **Method cập nhật**: 
  - `create()`: Truyền danh sách projects vào view
  - `store()`: Validate và lưu project_id
  - `index()`: Thêm filter theo project

#### 2. ProjectController  
- **Method mới**: `exportHistory($projectId)` - Xem lịch sử xuất kho của dự án
- **Method cập nhật**: `show()` - Hiển thị tổng giá trị vật tư đã xuất

#### 3. Views
- `exports/create.blade.php`: Thêm dropdown chọn dự án
- `exports/index.blade.php`: Thêm filter theo dự án, hiển thị tên dự án
- `exports/show.blade.php`: Hiển thị thông tin dự án
- `projects/show.blade.php`: Thêm tab "Vật tư đã xuất"

## Data Models

### InventoryTransaction (Updated)
```php
- id
- type (import/export/transfer)
- warehouse_id
- to_warehouse_id (nullable)
- project_id (nullable) // NEW
- employee_id
- date
- total_qty
- note
- status
- timestamps
```

### Relationships
```php
// InventoryTransaction Model
public function project()
{
    return $this->belongsTo(Project::class);
}

// Project Model  
public function exports()
{
    return $this->hasMany(InventoryTransaction::class, 'project_id')
                ->where('type', 'export');
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system.*

### Property 1: Project link preservation
*For any* export transaction with a project_id, querying that transaction should return the correct project information.
**Validates: Requirements 1.2, 1.4**

### Property 2: Filter consistency  
*For any* project filter selection, all returned export transactions should have matching project_id.
**Validates: Requirements 3.1, 3.2**

### Property 3: Null project handling
*For any* export transaction without project_id (null), the system should display it normally without errors.
**Validates: Requirements 1.3**

### Property 4: Total calculation accuracy
*For any* project, the sum of all export values should equal the total displayed in project details.
**Validates: Requirements 2.2, 4.1**

## Error Handling

1. **Invalid project_id**: Validate project exists and is active
2. **Deleted project**: Use ON DELETE SET NULL, display "Dự án đã xóa"
3. **Permission**: Check user has access to selected project

## Testing Strategy

### Unit Tests
- Test project_id validation in ExportController
- Test project relationship in models
- Test filter logic with various project selections

### Integration Tests  
- Test complete flow: create export with project → view in project details
- Test filter: select project → verify only matching exports shown
- Test null handling: create export without project → verify no errors

## Implementation Notes

1. **Backward Compatibility**: Existing exports without project_id should work normally
2. **UI/UX**: Dropdown dự án nên có search để dễ tìm khi có nhiều dự án
3. **Performance**: Index trên project_id để query nhanh
4. **Reporting**: Thêm group by project trong báo cáo xuất kho
