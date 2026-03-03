<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== KIỂM TRA MODULES THIẾU ===\n\n";

// Danh sách các controller đã được bảo vệ (từ việc thêm authorize)
$protectedControllers = [
    'CustomerController' => 'customers',
    'SupplierController' => 'suppliers',
    'EmployeeController' => 'employees',
    'ProductController' => 'products',
    'WarehouseController' => 'warehouses',
    'InventoryController' => 'inventory',
    'ImportController' => 'imports',
    'ExportController' => 'exports',
    'TransferController' => 'transfers',
    'DamagedGoodController' => 'damaged_goods',
    'SaleController' => 'sales',
    'QuotationController' => 'quotations',
    'LeadController' => 'leads',
    'OpportunityController' => 'opportunities',
    'ActivityController' => 'activities',
    'ProjectController' => 'projects',
    'WorkScheduleController' => 'work_schedules',
    'PurchaseRequestController' => 'purchase_requests',
    'PurchaseOrderController' => 'purchase_orders',
    'CostFormulaController' => 'cost_formulas',
    'SupplierQuotationController' => 'supplier_quotations',
    'ShippingAllocationController' => 'shipping_allocations',
    'ApprovalWorkflowController' => 'approval_workflows',
    'ActivityLogController' => 'activity_logs',
    'CustomerDebtController' => 'customer_debts',
    'CustomerCareStageController' => 'customer_care_stages',
    'SaleReportController' => 'sale_reports',
    'PurchaseReportController' => 'purchase_reports',
    'PriceListController' => 'price_lists',
    'SupplierPriceListController' => 'supplier_price_lists',
    'MilestoneTemplateController' => 'milestone_templates',
    'WarrantyController' => 'warranties',
    'CareMilestoneController' => 'care_milestones', // Thuộc customer_care_stages
    'CommunicationLogController' => 'communication_logs', // Thuộc customer_care_stages
    'ExcelImportController' => 'excel_imports',
    'ReminderController' => 'reminders',
    'ProfileController' => 'profiles',
    'DashboardController' => 'dashboard',
    'NotificationController' => 'notifications',
];

// Lấy danh sách modules hiện có trong database
$existingModules = DB::table('permissions')
    ->select('module')
    ->distinct()
    ->pluck('module')
    ->toArray();

echo "Modules hiện có trong database (" . count($existingModules) . "):\n";
sort($existingModules);
foreach ($existingModules as $module) {
    echo "  - $module\n";
}

echo "\n";

// Tìm modules thiếu
$missingModules = [];
foreach ($protectedControllers as $controller => $module) {
    if (!in_array($module, $existingModules)) {
        $missingModules[] = $module;
    }
}

if (empty($missingModules)) {
    echo "✅ TẤT CẢ MODULES ĐÃ CÓ PERMISSIONS!\n";
} else {
    echo "❌ CÁC MODULES THIẾU PERMISSIONS (" . count($missingModules) . "):\n";
    foreach ($missingModules as $module) {
        echo "  - $module\n";
    }
}

echo "\n";
echo "Tổng số controllers được bảo vệ: " . count($protectedControllers) . "\n";
echo "Tổng số modules có permissions: " . count($existingModules) . "\n";
