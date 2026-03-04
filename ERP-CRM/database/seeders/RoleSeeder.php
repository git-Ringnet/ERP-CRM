<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        // Define predefined roles
        $roles = [
            [
                'name' => 'Quản trị viên',
                'slug' => 'super_admin',
                'description' => 'Toàn quyền truy cập hệ thống với tất cả quyền',
                'status' => 'active',
            ],
            [
                'name' => 'Quản lý Kho',
                'slug' => 'warehouse_manager',
                'description' => 'Quản lý hoạt động kho, tồn kho và duyệt phiếu nhập/xuất',
                'status' => 'active',
            ],
            [
                'name' => 'Nhân viên Kho',
                'slug' => 'warehouse_staff',
                'description' => 'Xử lý hoạt động kho và quản lý tồn kho',
                'status' => 'active',
            ],
            [
                'name' => 'Quản lý Bán hàng',
                'slug' => 'sales_manager',
                'description' => 'Quản lý hoạt động bán hàng, khách hàng và duyệt báo giá',
                'status' => 'active',
            ],
            [
                'name' => 'Nhân viên Bán hàng',
                'slug' => 'sales_staff',
                'description' => 'Xử lý quan hệ khách hàng và đơn hàng bán',
                'status' => 'active',
            ],
            [
                'name' => 'Quản lý Mua hàng',
                'slug' => 'purchase_manager',
                'description' => 'Quản lý mua hàng và duyệt đơn mua hàng',
                'status' => 'active',
            ],
            [
                'name' => 'Nhân viên Mua hàng',
                'slug' => 'purchase_staff',
                'description' => 'Xử lý quan hệ nhà cung cấp và đơn mua hàng',
                'status' => 'active',
            ],
            [
                'name' => 'Kế toán',
                'slug' => 'accountant',
                'description' => 'Xem và xuất dữ liệu tài chính và báo cáo',
                'status' => 'active',
            ],
            [
                'name' => 'Giám đốc',
                'slug' => 'director',
                'description' => 'Xem tất cả dữ liệu và duyệt các hoạt động quan trọng',
                'status' => 'active',
            ],
        ];
        
        // Insert roles (use insertOrIgnore for idempotence)
        foreach ($roles as $role) {
            DB::table('roles')->insertOrIgnore(array_merge($role, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
        
        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }
    
    /**
     * Assign permissions to predefined roles
     */
    private function assignPermissionsToRoles(): void
    {
        $now = Carbon::now();
        
        // Get all permissions
        $allPermissions = DB::table('permissions')->pluck('id', 'slug')->toArray();
        
        // Get all roles
        $roles = DB::table('roles')->pluck('id', 'slug')->toArray();
        
        // XÓA TẤT CẢ QUYỀN CŨ TRƯỚC KHI GÁN MỚI
        DB::table('role_permissions')->truncate();
        
        // Super Admin - all permissions
        if (isset($roles['super_admin'])) {
            $superAdminPermissions = [];
            foreach ($allPermissions as $slug => $permissionId) {
                $superAdminPermissions[] = [
                    'role_id' => $roles['super_admin'],
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                ];
            }
            DB::table('role_permissions')->insert($superAdminPermissions);
        }
        
        // Warehouse Manager - all warehouse, inventory, imports, exports, transfers, damaged_goods permissions
        if (isset($roles['warehouse_manager'])) {
            $warehouseManagerPerms = $this->getPermissionsByModules(
                $allPermissions,
                ['warehouses', 'inventory', 'imports', 'exports', 'transfers', 'damaged_goods', 'products', 'excel_imports', 'work_schedules']
            );
            // Add approve permissions and dashboard
            $approvePerms = $this->getPermissionsBySlugs($allPermissions, ['approve_imports', 'approve_exports', 'view_dashboard']);
            $warehouseManagerPerms = array_unique(array_merge($warehouseManagerPerms, $approvePerms));
            $this->attachPermissionsToRole($roles['warehouse_manager'], $warehouseManagerPerms, $now);
        }
        
        // Warehouse Staff - view, create, edit for imports, exports, transfers, and view for inventory
        if (isset($roles['warehouse_staff'])) {
            $warehouseStaffPerms = array_merge(
                $this->getPermissionsByModulesAndActions($allPermissions, ['imports', 'exports', 'transfers', 'damaged_goods'], ['view', 'create', 'edit']),
                $this->getPermissionsByModulesAndActions($allPermissions, ['inventory', 'products', 'warehouses', 'work_schedules'], ['view']),
                $this->getPermissionsBySlugs($allPermissions, ['view_dashboard'])
            );
            $warehouseStaffPerms = array_unique($warehouseStaffPerms);
            $this->attachPermissionsToRole($roles['warehouse_staff'], $warehouseStaffPerms, $now);
        }
        
        // Sales Manager - all customers, sales, quotations, leads, opportunities, activities, projects permissions
        if (isset($roles['sales_manager'])) {
            $salesManagerPerms = $this->getPermissionsByModules(
                $allPermissions,
                ['customers', 'sales', 'quotations', 'leads', 'opportunities', 'activities', 'projects', 
                 'customer_care_stages', 'customer_debts', 'sale_reports', 'price_lists', 'warranties',
                 'milestone_templates', 'work_schedules']
            );
            // Add approve_quotations, view_all_sales, view_all_quotations, and dashboard
            $specialPerms = $this->getPermissionsBySlugs($allPermissions, ['approve_quotations', 'view_all_sales', 'view_all_quotations', 'view_dashboard']);
            $salesManagerPerms = array_unique(array_merge($salesManagerPerms, $specialPerms));
            $this->attachPermissionsToRole($roles['sales_manager'], $salesManagerPerms, $now);
        }
        
        // Sales Staff - view, create, edit for customers, sales, quotations, leads, opportunities, activities
        if (isset($roles['sales_staff'])) {
            $salesStaffPerms = $this->getPermissionsByModulesAndActions(
                $allPermissions,
                ['customers', 'sales', 'quotations', 'leads', 'opportunities', 'activities', 
                 'customer_care_stages', 'projects'],
                ['view', 'create', 'edit']
            );
            // Add view_own_sales, view_own_quotations, and dashboard
            $ownPerms = $this->getPermissionsBySlugs($allPermissions, ['view_own_sales', 'view_own_quotations', 'view_dashboard']);
            $viewPerms = $this->getPermissionsByModulesAndActions($allPermissions, ['customer_debts', 'warranties', 'price_lists', 'work_schedules'], ['view']);
            $salesStaffPerms = array_unique(array_merge($salesStaffPerms, $ownPerms, $viewPerms));
            $this->attachPermissionsToRole($roles['sales_staff'], $salesStaffPerms, $now);
        }
        
        // Purchase Manager - all suppliers, purchase_orders, purchase_requests, supplier_quotations permissions
        if (isset($roles['purchase_manager'])) {
            $purchaseManagerPerms = $this->getPermissionsByModules(
                $allPermissions,
                ['suppliers', 'purchase_orders', 'purchase_requests', 'supplier_quotations', 
                 'supplier_price_lists', 'shipping_allocations', 'purchase_reports', 'cost_formulas', 'work_schedules']
            );
            // Add approve_purchase_orders, view_all_purchase_orders, and dashboard
            $specialPerms = $this->getPermissionsBySlugs($allPermissions, ['approve_purchase_orders', 'view_all_purchase_orders', 'view_dashboard']);
            $purchaseManagerPerms = array_unique(array_merge($purchaseManagerPerms, $specialPerms));
            $this->attachPermissionsToRole($roles['purchase_manager'], $purchaseManagerPerms, $now);
        }
        
        // Purchase Staff - view, create, edit for suppliers, purchase_orders, purchase_requests, supplier_quotations
        if (isset($roles['purchase_staff'])) {
            $purchaseStaffPerms = $this->getPermissionsByModulesAndActions(
                $allPermissions,
                ['suppliers', 'purchase_orders', 'purchase_requests', 'supplier_quotations', 
                 'supplier_price_lists', 'shipping_allocations'],
                ['view', 'create', 'edit']
            );
            // Add view_own_purchase_orders and dashboard
            $ownPerms = $this->getPermissionsBySlugs($allPermissions, ['view_own_purchase_orders', 'view_dashboard']);
            $viewPerms = $this->getPermissionsByModulesAndActions($allPermissions, ['cost_formulas', 'work_schedules'], ['view']);
            $purchaseStaffPerms = array_unique(array_merge($purchaseStaffPerms, $ownPerms, $viewPerms));
            $this->attachPermissionsToRole($roles['purchase_staff'], $purchaseStaffPerms, $now);
        }
        
        // Accountant - view and export for all modules, plus view all sales/purchases
        if (isset($roles['accountant'])) {
            $accountantPerms = $this->getPermissionsByActions($allPermissions, ['view', 'export']);
            $specialPerms = $this->getPermissionsBySlugs($allPermissions, ['view_all_sales', 'view_all_quotations', 'view_all_purchase_orders']);
            $accountantPerms = array_unique(array_merge($accountantPerms, $specialPerms));
            $this->attachPermissionsToRole($roles['accountant'], $accountantPerms, $now);
        }
        
        // Director - view, approve, export for all modules, and all report permissions
        if (isset($roles['director'])) {
            $directorPerms = $this->getPermissionsByActions($allPermissions, ['view', 'approve', 'export']);
            $reportPerms = $this->getPermissionsByModules($allPermissions, ['reports', 'sale_reports', 'purchase_reports']);
            $specialPerms = $this->getPermissionsBySlugs($allPermissions, ['view_all_sales', 'view_all_quotations', 'view_all_purchase_orders']);
            $directorPerms = array_unique(array_merge($directorPerms, $reportPerms, $specialPerms));
            $this->attachPermissionsToRole($roles['director'], $directorPerms, $now);
        }
    }
    
    /**
     * Get permission IDs by modules
     */
    private function getPermissionsByModules(array $allPermissions, array $modules): array
    {
        $permissions = DB::table('permissions')
            ->whereIn('module', $modules)
            ->pluck('id')
            ->toArray();
        
        return $permissions;
    }
    
    /**
     * Get permission IDs by actions
     */
    private function getPermissionsByActions(array $allPermissions, array $actions): array
    {
        $permissions = DB::table('permissions')
            ->whereIn('action', $actions)
            ->pluck('id')
            ->toArray();
        
        return $permissions;
    }
    
    /**
     * Get permission IDs by modules and actions
     */
    private function getPermissionsByModulesAndActions(array $allPermissions, array $modules, array $actions): array
    {
        $permissions = DB::table('permissions')
            ->whereIn('module', $modules)
            ->whereIn('action', $actions)
            ->pluck('id')
            ->toArray();
        
        return $permissions;
    }
    
    /**
     * Get permission IDs by slugs
     */
    private function getPermissionsBySlugs(array $allPermissions, array $slugs): array
    {
        $permissions = [];
        foreach ($slugs as $slug) {
            if (isset($allPermissions[$slug])) {
                $permissions[] = $allPermissions[$slug];
            }
        }
        return $permissions;
    }
    
    /**
     * Attach permissions to role
     */
    private function attachPermissionsToRole(int $roleId, array $permissionIds, Carbon $now): void
    {
        $rolePermissions = [];
        foreach ($permissionIds as $permissionId) {
            $rolePermissions[] = [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => $now,
            ];
        }
        
        if (!empty($rolePermissions)) {
            DB::table('role_permissions')->insert($rolePermissions);
        }
    }
}
