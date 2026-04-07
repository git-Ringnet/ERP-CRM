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
                'name' => 'Logistic Manager',
                'slug' => 'warehouse_manager',
                'description' => 'Quản lý hoạt động kho, tồn kho và duyệt phiếu nhập/xuất',
                'status' => 'active',
            ],
            [
                'name' => 'Logistic Staff',
                'slug' => 'warehouse_staff',
                'description' => 'Xử lý hoạt động kho và quản lý tồn kho',
                'status' => 'active',
            ],
            [
                'name' => 'Sales Manager',
                'slug' => 'sales_manager',
                'description' => 'Quản lý hoạt động bán hàng, khách hàng và duyệt báo giá',
                'status' => 'active',
            ],
            [
                'name' => 'Sales Staff',
                'slug' => 'sales_staff',
                'description' => 'Xử lý quan hệ khách hàng và đơn hàng bán',
                'status' => 'active',
            ],
            [
                'name' => 'PO Manager',
                'slug' => 'purchase_manager',
                'description' => 'Quản lý mua hàng và duyệt đơn mua hàng',
                'status' => 'active',
            ],
            [
                'name' => 'PO Staff',
                'slug' => 'purchase_staff',
                'description' => 'Xử lý quan hệ nhà cung cấp và đơn mua hàng',
                'status' => 'active',
            ],
            [
                'name' => 'Finance Team',
                'slug' => 'accountant',
                'description' => 'Xuất hoá đơn, theo dõi công nợ và thanh toán khách hàng',
                'status' => 'active',
            ],
            [
                'name' => 'Legal Team',
                'slug' => 'legal_team',
                'description' => 'Review hợp đồng, kiểm tra điều khoản và policy',
                'status' => 'active',
            ],
            [
                'name' => 'BOD',
                'slug' => 'director',
                'description' => 'Phê duyệt hợp đồng, xem tất cả dữ liệu và báo cáo',
                'status' => 'active',
            ],
        ];
        
        // Insert roles (use insertOrIgnore for idempotence)
        // Update or insert roles
        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $role['slug']],
                array_merge($role, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
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
        
        // ============================================================
        // SUPER ADMIN - Toàn quyền hệ thống
        // ============================================================
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
        
        // ============================================================
        // SALES TEAM - Quản lý Bán hàng
        // Quy trình: Xây dựng Database KH → Sàng lọc → Tư vấn → ĐKDA
        //            → Báo giá → Chốt BOM → Lập HĐMB → Đặt hàng → Theo dõi
        // ============================================================
        if (isset($roles['sales_manager'])) {
            // Full CRUD modules
            $salesManagerPerms = $this->getPermissionsByModules(
                $allPermissions,
                ['customers', 'sales', 'quotations', 'leads', 'opportunities', 'activities', 'projects', 
                 'customer_care_stages', 'customer_debts', 'sale_reports', 'price_lists', 'warranties',
                 'milestone_templates', 'work_schedules', 'communication_logs', 'products']
            );
            // View-only: inventory, shipping, exports, cost_formulas (theo dõi hàng hoá & giao hàng)
            $viewPerms = $this->getPermissionsByModulesAndActions($allPermissions, 
                ['inventory', 'shipping_allocations', 'exports', 'cost_formulas'], ['view']
            );
            // Purchase modules: purchase_requests, purchase_orders (view, create, edit, export)
            $purchasePerms = $this->getPermissionsByModulesAndActions($allPermissions,
                ['purchase_requests', 'purchase_orders'], ['view', 'create', 'edit', 'export']
            );
            $specialPerms = $this->getPermissionsBySlugs($allPermissions, [
                'approve_quotations', 'view_all_sales', 'view_all_quotations', 
                'view_dashboard', 'view_business_dashboard', 'export_business_reports',
                'view_all_purchase_orders', 'view_all_purchase_requests'
            ]);
            $salesManagerPerms = array_unique(array_merge($salesManagerPerms, $viewPerms, $purchasePerms, $specialPerms));
            $this->attachPermissionsToRole($roles['sales_manager'], $salesManagerPerms, $now);
        }
        
        // ============================================================
        // SALES TEAM - Nhân viên Bán hàng
        // ============================================================
        if (isset($roles['sales_staff'])) {
            $salesStaffPerms = $this->getPermissionsByModulesAndActions(
                $allPermissions,
                ['customers', 'sales', 'quotations', 'leads', 'opportunities', 'activities', 
                 'customer_care_stages', 'projects', 'communication_logs'],
                ['view', 'create', 'edit']
            );
            // View-only modules
            $viewPerms = $this->getPermissionsByModulesAndActions($allPermissions, 
                ['customer_debts', 'warranties', 'price_lists', 'work_schedules', 'products',
                 'cost_formulas', 'inventory', 'shipping_allocations', 'exports', 'sale_reports'], 
                ['view']
            );
            // Purchase modules: purchase_requests, purchase_orders (view, create, edit)
            $purchasePerms = $this->getPermissionsByModulesAndActions($allPermissions,
                ['purchase_requests', 'purchase_orders'], ['view', 'create', 'edit']
            );
            $ownPerms = $this->getPermissionsBySlugs($allPermissions, [
                'view_own_sales', 'view_own_quotations', 'view_dashboard'
            ]);
            $salesStaffPerms = array_unique(array_merge($salesStaffPerms, $viewPerms, $purchasePerms, $ownPerms));
            $this->attachPermissionsToRole($roles['sales_staff'], $salesStaffPerms, $now);
        }
        
        // ============================================================
        // LEGAL TEAM - Pháp lý (MỚI)
        // Quy trình: Review hợp đồng → Kiểm tra điều khoản & policy
        //            → Yêu cầu điều chỉnh nếu chưa hợp lý
        // ============================================================
        if (isset($roles['legal_team'])) {
            // View: sales, quotations, customers, products, approval_workflows
            $legalPerms = $this->getPermissionsByModulesAndActions($allPermissions,
                ['sales', 'quotations', 'customers', 'products', 'approval_workflows'],
                ['view']
            );
            // Edit: approval_workflows (đánh dấu trạng thái review)
            $editPerms = $this->getPermissionsByModulesAndActions($allPermissions,
                ['approval_workflows'], ['edit']
            );
            $specialPerms = $this->getPermissionsBySlugs($allPermissions, [
                'view_dashboard', 'view_all_sales', 'view_all_quotations'
            ]);
            $legalPerms = array_unique(array_merge($legalPerms, $editPerms, $specialPerms));
            $this->attachPermissionsToRole($roles['legal_team'], $legalPerms, $now);
        }
        
        // ============================================================
        // BOD - Ban Giám đốc
        // Quy trình: Phê duyệt hợp đồng cuối cùng sau Legal review
        //            + Xem tất cả báo cáo, dữ liệu tài chính
        // ============================================================
        if (isset($roles['director'])) {
            $directorPerms = $this->getPermissionsByActions($allPermissions, ['view', 'edit', 'approve', 'export']);
            $reportPerms = $this->getPermissionsByModules($allPermissions, ['reports', 'sale_reports', 'purchase_reports', 'employee_asset_reports']);
            $specialPerms = $this->getPermissionsBySlugs($allPermissions, [
                'view_all_sales', 'view_all_quotations', 'view_all_purchase_orders', 
                'view_business_dashboard', 'export_business_reports'
            ]);
            $financialPerms = $this->getPermissionsByModulesAndActions($allPermissions, 
                ['financial_transactions', 'transaction_categories', 'reconciliations', 
                 'warehouse_journal_entries', 'employee_asset_assignments'], 
                ['create', 'edit', 'delete']
            );
            $directorPerms = array_unique(array_merge($directorPerms, $reportPerms, $specialPerms, $financialPerms));
            $this->attachPermissionsToRole($roles['director'], $directorPerms, $now);
        }
        
        // ============================================================
        // LOGISTIC TEAM - Quản lý Kho
        // Quy trình: Kiểm tra hàng hoá/license → Phân loại → Báo cáo thiếu/lỗi
        // ============================================================
        if (isset($roles['warehouse_manager'])) {
            $warehouseManagerPerms = $this->getPermissionsByModules(
                $allPermissions,
                ['warehouses', 'inventory', 'imports', 'exports', 'transfers', 'damaged_goods', 'products', 'excel_imports', 'work_schedules']
            );
            $approvePerms = $this->getPermissionsBySlugs($allPermissions, [
                'approve_imports', 'approve_exports', 'view_dashboard', 'view_business_dashboard', 'export_business_reports'
            ]);
            $viewPerms = $this->getPermissionsByModulesAndActions($allPermissions, ['warehouse_journal_entries'], ['view']);
            $warehouseManagerPerms = array_unique(array_merge($warehouseManagerPerms, $approvePerms, $viewPerms));
            $this->attachPermissionsToRole($roles['warehouse_manager'], $warehouseManagerPerms, $now);
        }
        
        // ============================================================
        // LOGISTIC TEAM - Nhân viên Kho
        // ============================================================
        if (isset($roles['warehouse_staff'])) {
            $warehouseStaffPerms = array_merge(
                $this->getPermissionsByModulesAndActions($allPermissions, ['imports', 'exports', 'transfers', 'damaged_goods'], ['view', 'create', 'edit']),
                $this->getPermissionsByModulesAndActions($allPermissions, ['inventory', 'products', 'warehouses', 'work_schedules'], ['view']),
                $this->getPermissionsBySlugs($allPermissions, ['view_dashboard'])
            );
            $warehouseStaffPerms = array_unique($warehouseStaffPerms);
            $this->attachPermissionsToRole($roles['warehouse_staff'], $warehouseStaffPerms, $now);
        }
        
        // ============================================================
        // PO TEAM - Quản lý Mua hàng
        // Quy trình: Mapping PO/Partner/Sales → Nhập kho → Match thông tin order
        // ============================================================
        if (isset($roles['purchase_manager'])) {
            $purchaseManagerPerms = $this->getPermissionsByModules(
                $allPermissions,
                ['suppliers', 'purchase_orders', 'purchase_requests', 'supplier_quotations', 
                 'supplier_price_lists', 'shipping_allocations', 'purchase_reports', 'cost_formulas', 'work_schedules']
            );
            // Nhập kho theo mapping
            $importPerms = $this->getPermissionsByModulesAndActions($allPermissions,
                ['imports'], ['view', 'create', 'edit', 'export']
            );
            // View: sales, inventory, products (match thông tin order)
            $viewPerms = $this->getPermissionsByModulesAndActions($allPermissions,
                ['sales', 'inventory', 'products'], ['view']
            );
            $specialPerms = $this->getPermissionsBySlugs($allPermissions, [
                'approve_purchase_orders', 'view_all_purchase_orders', 
                'view_dashboard', 'view_business_dashboard', 'export_business_reports'
            ]);
            $purchaseManagerPerms = array_unique(array_merge($purchaseManagerPerms, $importPerms, $viewPerms, $specialPerms));
            $this->attachPermissionsToRole($roles['purchase_manager'], $purchaseManagerPerms, $now);
        }
        
        // ============================================================
        // PO TEAM - Nhân viên Mua hàng
        // ============================================================
        if (isset($roles['purchase_staff'])) {
            $purchaseStaffPerms = $this->getPermissionsByModulesAndActions(
                $allPermissions,
                ['suppliers', 'purchase_orders', 'purchase_requests', 'supplier_quotations', 
                 'supplier_price_lists', 'shipping_allocations'],
                ['view', 'create', 'edit']
            );
            // Nhập kho theo mapping
            $importPerms = $this->getPermissionsByModulesAndActions($allPermissions,
                ['imports'], ['view', 'create', 'edit']
            );
            // View: sales, inventory, products, cost_formulas, work_schedules
            $viewPerms = $this->getPermissionsByModulesAndActions($allPermissions,
                ['sales', 'inventory', 'products', 'cost_formulas', 'work_schedules'], ['view']
            );
            $ownPerms = $this->getPermissionsBySlugs($allPermissions, ['view_own_purchase_orders', 'view_dashboard']);
            $purchaseStaffPerms = array_unique(array_merge($purchaseStaffPerms, $importPerms, $viewPerms, $ownPerms));
            $this->attachPermissionsToRole($roles['purchase_staff'], $purchaseStaffPerms, $now);
        }
        
        // ============================================================
        // FINANCE TEAM - Kế toán
        // Quy trình: Xuất hoá đơn chính thức → Theo dõi công nợ & thanh toán KH
        // ============================================================
        if (isset($roles['accountant'])) {
            // View and export for all modules
            $accountantPerms = $this->getPermissionsByActions($allPermissions, ['view', 'export']);
            $specialPerms = $this->getPermissionsBySlugs($allPermissions, [
                'view_all_sales', 'view_all_quotations', 'view_all_purchase_orders'
            ]);
            // Create/edit for financial modules
            $financialPerms = $this->getPermissionsByModulesAndActions($allPermissions, 
                ['financial_transactions', 'transaction_categories', 'reconciliations', 
                 'warehouse_journal_entries', 'employee_asset_assignments'], 
                ['create', 'edit']
            );
            // Create/edit exports (xuất hoá đơn chính thức & bàn giao hàng)
            $exportPerms = $this->getPermissionsByModulesAndActions($allPermissions,
                ['exports'], ['create', 'edit']
            );
            // Record payment for customer debts (theo dõi công nợ & thanh toán)
            $debtPerms = $this->getPermissionsBySlugs($allPermissions, ['record_payment_customer_debts']);
            $accountantPerms = array_unique(array_merge($accountantPerms, $specialPerms, $financialPerms, $exportPerms, $debtPerms));
            $this->attachPermissionsToRole($roles['accountant'], $accountantPerms, $now);
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
