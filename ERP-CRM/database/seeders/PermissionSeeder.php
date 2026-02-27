<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        // Define modules and their standard actions
        $modules = [
            'customers' => ['view', 'create', 'edit', 'delete', 'export'],
            'suppliers' => ['view', 'create', 'edit', 'delete', 'export'],
            'employees' => ['view', 'create', 'edit', 'delete', 'export'],
            'products' => ['view', 'create', 'edit', 'delete', 'export'],
            'warehouses' => ['view', 'create', 'edit', 'delete'],
            'inventory' => ['view', 'export'],
            'imports' => ['view', 'create', 'edit', 'delete', 'export'],
            'exports' => ['view', 'create', 'edit', 'delete', 'export'],
            'transfers' => ['view', 'create', 'edit', 'delete', 'export'],
            'damaged_goods' => ['view', 'create', 'edit', 'delete', 'export'],
            'sales' => ['view', 'create', 'edit', 'delete', 'export'],
            'quotations' => ['view', 'create', 'edit', 'delete', 'export'],
            'purchase_orders' => ['view', 'create', 'edit', 'delete', 'export'],
            'reports' => ['view', 'export'],
            'settings' => ['view', 'edit'],
            'roles' => ['view', 'create', 'edit', 'delete'],
            'permissions' => ['view', 'edit'],
            'audit_logs' => ['view'],
            'user_roles' => ['view', 'assign', 'revoke'],
            'user_permissions' => ['view', 'assign', 'revoke'],
        ];
        
        $permissions = [];
        
        // Generate standard permissions
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $slug = "{$action}_{$module}";
                $name = $this->translatePermissionName($action, $module);
                $description = $this->translatePermissionDescription($action, $module);
                
                $permissions[] = [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'module' => $module,
                    'action' => $action,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        
        // Add special permissions
        $specialPermissions = [
            [
                'name' => 'Duyệt Nhập kho',
                'slug' => 'approve_imports',
                'description' => 'Quyền duyệt phiếu nhập kho',
                'module' => 'imports',
                'action' => 'approve',
            ],
            [
                'name' => 'Duyệt Xuất kho',
                'slug' => 'approve_exports',
                'description' => 'Quyền duyệt phiếu xuất kho',
                'module' => 'exports',
                'action' => 'approve',
            ],
            [
                'name' => 'Duyệt Báo giá',
                'slug' => 'approve_quotations',
                'description' => 'Quyền duyệt báo giá bán hàng',
                'module' => 'quotations',
                'action' => 'approve',
            ],
            [
                'name' => 'Duyệt Đơn mua hàng',
                'slug' => 'approve_purchase_orders',
                'description' => 'Quyền duyệt đơn mua hàng',
                'module' => 'purchase_orders',
                'action' => 'approve',
            ],
            [
                'name' => 'Xem Tất cả Đơn hàng',
                'slug' => 'view_all_sales',
                'description' => 'Quyền xem tất cả đơn hàng bán',
                'module' => 'sales',
                'action' => 'view',
            ],
            [
                'name' => 'Xem Đơn hàng Của mình',
                'slug' => 'view_own_sales',
                'description' => 'Quyền chỉ xem đơn hàng bán của mình',
                'module' => 'sales',
                'action' => 'view',
            ],
        ];
        
        foreach ($specialPermissions as $permission) {
            $permissions[] = array_merge($permission, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        
        // Insert permissions (use insertOrIgnore for idempotence)
        DB::table('permissions')->insertOrIgnore($permissions);
    }
    
    /**
     * Translate permission name to Vietnamese
     */
    private function translatePermissionName(string $action, string $module): string
    {
        $actionMap = [
            'view' => 'Xem',
            'create' => 'Tạo',
            'edit' => 'Sửa',
            'delete' => 'Xóa',
            'export' => 'Xuất Excel',
            'assign' => 'Gán',
            'revoke' => 'Gỡ bỏ',
        ];
        
        $moduleMap = [
            'customers' => 'Khách hàng',
            'suppliers' => 'Nhà cung cấp',
            'employees' => 'Nhân viên',
            'products' => 'Sản phẩm',
            'warehouses' => 'Kho hàng',
            'inventory' => 'Tồn kho',
            'imports' => 'Nhập kho',
            'exports' => 'Xuất kho',
            'transfers' => 'Chuyển kho',
            'damaged_goods' => 'Hàng hư hỏng',
            'sales' => 'Đơn hàng bán',
            'quotations' => 'Báo giá',
            'purchase_orders' => 'Đơn mua hàng',
            'reports' => 'Báo cáo',
            'settings' => 'Cài đặt',
            'roles' => 'Vai trò',
            'permissions' => 'Quyền',
            'audit_logs' => 'Nhật ký Kiểm toán',
            'user_roles' => 'Vai trò Người dùng',
            'user_permissions' => 'Quyền Người dùng',
        ];
        
        $actionText = $actionMap[$action] ?? ucfirst($action);
        $moduleText = $moduleMap[$module] ?? ucfirst(str_replace('_', ' ', $module));
        
        return "{$actionText} {$moduleText}";
    }
    
    /**
     * Translate permission description to Vietnamese
     */
    private function translatePermissionDescription(string $action, string $module): string
    {
        $actionMap = [
            'view' => 'xem',
            'create' => 'tạo',
            'edit' => 'sửa',
            'delete' => 'xóa',
            'export' => 'xuất excel',
            'assign' => 'gán',
            'revoke' => 'gỡ bỏ',
        ];
        
        $moduleMap = [
            'customers' => 'khách hàng',
            'suppliers' => 'nhà cung cấp',
            'employees' => 'nhân viên',
            'products' => 'sản phẩm',
            'warehouses' => 'kho hàng',
            'inventory' => 'tồn kho',
            'imports' => 'phiếu nhập kho',
            'exports' => 'phiếu xuất kho',
            'transfers' => 'phiếu chuyển kho',
            'damaged_goods' => 'hàng hư hỏng',
            'sales' => 'đơn hàng bán',
            'quotations' => 'báo giá',
            'purchase_orders' => 'đơn mua hàng',
            'reports' => 'báo cáo',
            'settings' => 'cài đặt hệ thống',
            'roles' => 'vai trò',
            'permissions' => 'quyền',
            'audit_logs' => 'nhật ký kiểm toán',
            'user_roles' => 'vai trò người dùng',
            'user_permissions' => 'quyền người dùng',
        ];
        
        $actionText = $actionMap[$action] ?? $action;
        $moduleText = $moduleMap[$module] ?? str_replace('_', ' ', $module);
        
        return "Quyền {$actionText} {$moduleText}";
    }
}
