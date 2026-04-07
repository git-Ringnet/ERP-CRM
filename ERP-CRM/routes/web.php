<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\ExcelImportController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\DamagedGoodController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\CostFormulaController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\CustomerDebtController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ApprovalWorkflowController;
use App\Http\Controllers\PriceListController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\SupplierQuotationController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\WarrantyController;
use App\Http\Controllers\ShippingAllocationController;
use App\Http\Controllers\PurchaseReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\FinancialTransactionController;
use App\Http\Controllers\BusinessReportController;
use App\Http\Controllers\BusinessDashboardController;
use App\Http\Controllers\WorkLocationController;
use App\Http\Controllers\SalaryComponentController;
use App\Http\Controllers\EmployeeSalaryComponentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PayrollController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// All routes require authentication
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Business Activity Dashboard
    Route::get('/dashboard/business-activity', [BusinessDashboardController::class, 'index'])->name('dashboard.business-activity');
    Route::post('/dashboard/business-activity/export', [BusinessDashboardController::class, 'export'])->name('dashboard.business-activity.export');
    Route::post('/dashboard/business-activity/refresh', [BusinessDashboardController::class, 'refresh'])->name('dashboard.business-activity.refresh');

    // Resource routes for CRUD operations
    Route::resource('customers', CustomerController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('employees', EmployeeController::class);
    Route::resource('products', ProductController::class);
    Route::get('/products/{product}/items', [ProductController::class, 'items'])->name('products.items');

    // Export routes
    Route::get('/customers/export/excel', [CustomerController::class, 'export'])->name('customers.export');
    Route::get('/customers/import/template', [CustomerController::class, 'importTemplate'])->name('customers.import.template');
    Route::post('/customers/import', [CustomerController::class, 'import'])->name('customers.import');
    Route::get('/suppliers/export/excel', [SupplierController::class, 'export'])->name('suppliers.export');
    Route::get('/suppliers/import/template', [SupplierController::class, 'importTemplate'])->name('suppliers.import.template');
    Route::post('/suppliers/import', [SupplierController::class, 'import'])->name('suppliers.import');
    Route::get('/employees/export/excel', [EmployeeController::class, 'export'])->name('employees.export');
    Route::get('/employees/import/template', [EmployeeController::class, 'importTemplate'])->name('employees.import.template');
    Route::post('/employees/import', [EmployeeController::class, 'import'])->name('employees.import');
    Route::post('/employees/{employee}/toggle-lock', [EmployeeController::class, 'toggleLock'])->name('employees.toggle-lock');
    Route::get('/products/export/excel', [ProductController::class, 'export'])->name('products.export');
    Route::get('/products/import/template', [ProductController::class, 'importTemplate'])->name('products.import.template');
    Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');

    // Excel Import routes
    Route::get('/excel-import/template/{type}', [ExcelImportController::class, 'template'])->name('excel-import.template');
    Route::post('/excel-import', [ExcelImportController::class, 'store'])->name('excel-import.store');

    // Import Module Routes (Nhập kho)
    Route::get('/imports/export-misa', [ImportController::class, 'exportMisa'])->name('imports.export-misa');
    Route::get('/imports/export', [ImportController::class, 'export'])->name('imports.export');
    Route::resource('imports', ImportController::class);
    Route::post('/imports/{import}/approve', [ImportController::class, 'approve'])->name('imports.approve');
    Route::post('/imports/{import}/reject', [ImportController::class, 'reject'])->name('imports.reject');
    Route::get('/imports/{import}/export-misa', [ImportController::class, 'exportMisaSingle'])->name('imports.export-misa-single');
    Route::get('/imports/{import}/print', [ImportController::class, 'print'])->name('imports.print');

    // Export Module Routes (Xuất kho)
    Route::get('/exports/available-items', [ExportController::class, 'getAvailableItems'])->name('exports.available-items');
    Route::get('/exports/export-misa', [ExportController::class, 'exportMisa'])->name('exports.export-misa');
    Route::get('/exports/export', [ExportController::class, 'exportToExcel'])->name('exports.export');
    Route::resource('exports', ExportController::class);
    Route::post('/exports/{export}/approve', [ExportController::class, 'approve'])->name('exports.approve');
    Route::post('/exports/{export}/reject', [ExportController::class, 'reject'])->name('exports.reject');
    Route::get('/exports/{export}/export-misa-single', [ExportController::class, 'exportMisaSingle'])->name('exports.export-misa-single');
    Route::get('/exports/{export}/print', [ExportController::class, 'print'])->name('exports.print');
    Route::get('/exports/{export}/export-excel', [ExportController::class, 'exportVoucherToExcel'])->name('exports.export-excel');

    // Transfer Module Routes (Chuyển kho)
    Route::get('/transfers/available-items', [TransferController::class, 'getAvailableItems'])->name('transfers.available-items');
    Route::resource('transfers', TransferController::class);
    Route::post('/transfers/{transfer}/approve', [TransferController::class, 'approve'])->name('transfers.approve');
    Route::post('/transfers/{transfer}/reject', [TransferController::class, 'reject'])->name('transfers.reject');
    Route::get('/transfers-export', [TransferController::class, 'exportToExcel'])->name('transfers.export');

    // Warehouse Module Routes
    Route::resource('warehouses', WarehouseController::class);
    Route::get('/warehouses-export', [WarehouseController::class, 'export'])->name('warehouses.export');

    // Inventory Routes
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/low-stock', [InventoryController::class, 'lowStock'])->name('inventory.low-stock');
    Route::get('/inventory/expiring', [InventoryController::class, 'expiringSoon'])->name('inventory.expiring');
    Route::get('/inventory-export', [InventoryController::class, 'export'])->name('inventory.export');
    Route::get('/inventory/{inventory}', [InventoryController::class, 'show'])->name('inventory.show');

    // Damaged Goods Routes
    Route::resource('damaged-goods', DamagedGoodController::class);
    Route::get('/api/damaged-goods/items', [DamagedGoodController::class, 'getProductItems'])->name('damaged-goods.items');
    Route::patch('/damaged-goods/{damaged_good}/status', [DamagedGoodController::class, 'updateStatus'])->name('damaged-goods.update-status');
    Route::get('/damaged-goods-export', [DamagedGoodController::class, 'export'])->name('damaged-goods.export');

    // Report Routes
    Route::get('/reports/inventory-summary', [ReportController::class, 'inventorySummary'])->name('reports.inventory-summary');
    Route::get('/reports/inventory-summary/export', [ReportController::class, 'exportInventorySummary'])->name('reports.inventory-summary.export');
    Route::get('/reports/transaction-report', [ReportController::class, 'transactionReport'])->name('reports.transaction-report');
    Route::get('/reports/transaction-report/export', [ReportController::class, 'exportTransactionReport'])->name('reports.transaction-report.export');
    Route::get('/reports/damaged-goods-report', [ReportController::class, 'damagedGoodsReport'])->name('reports.damaged-goods-report');
    Route::get('/reports/damaged-goods-report/export', [ReportController::class, 'exportDamagedGoodsReport'])->name('reports.damaged-goods-report.export');

    // Warranty Tracking Routes (Theo dõi bảo hành)
    Route::get('/warranties', [WarrantyController::class, 'index'])->name('warranties.index');
    Route::get('/warranties/expiring', [WarrantyController::class, 'expiring'])->name('warranties.expiring');
    Route::get('/warranties/report', [WarrantyController::class, 'report'])->name('warranties.report');
    Route::get('/warranties/export', [WarrantyController::class, 'export'])->name('warranties.export');
    Route::get('/warranties/{saleItem}', [WarrantyController::class, 'show'])->name('warranties.show');

    // Sales routes
    Route::resource('sales', SaleController::class);
    Route::get('/sales/export/excel', [SaleController::class, 'export'])->name('sales.export');
    Route::get('/sales/{sale}/pdf', [SaleController::class, 'generatePdf'])->name('sales.pdf');
    Route::post('/sales/{sale}/email', [SaleController::class, 'sendEmail'])->name('sales.email');
    Route::post('/sales/bulk-email', [SaleController::class, 'sendBulkEmail'])->name('sales.bulkEmail');
    Route::post('/sales/{sale}/payment', [SaleController::class, 'recordPayment'])->name('sales.payment');
    Route::patch('/sales/{sale}/status', [SaleController::class, 'updateStatus'])->name('sales.updateStatus');

    // Cost Formula routes
    Route::resource('cost-formulas', CostFormulaController::class);
    Route::post('/api/cost-formulas/calculate', [CostFormulaController::class, 'calculateForSale'])->name('cost-formulas.calculate');

    // Settings routes
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings/email', [SettingController::class, 'updateEmail'])->name('settings.email.update');
    Route::post('/settings/email/test', [SettingController::class, 'testEmail'])->name('settings.email.test');
    Route::post('/settings/company', [SettingController::class, 'updateCompany'])->name('settings.company.update');

    // Customer Debt Management routes
    Route::get('/customer-debts', [CustomerDebtController::class, 'index'])->name('customer-debts.index');
    Route::get('/customer-debts/export', [CustomerDebtController::class, 'export'])->name('customer-debts.export');
    Route::get('/customer-debts/aging-report', [CustomerDebtController::class, 'agingReport'])->name('customer-debts.aging-report');
    Route::get('/customer-debts/aging-report/export', [CustomerDebtController::class, 'exportAgingReport'])->name('customer-debts.aging-report.export');
    Route::get('/customer-debts/{customer}', [CustomerDebtController::class, 'show'])->name('customer-debts.show');
    Route::post('/customer-debts/payment/{sale}', [CustomerDebtController::class, 'recordPayment'])->name('customer-debts.record-payment');
    Route::delete('/customer-debts/payment/{payment}', [CustomerDebtController::class, 'deletePayment'])->name('customer-debts.delete-payment');
    Route::get('/customer-debts/{customer}/statement', [CustomerDebtController::class, 'statement'])->name('customer-debts.statement');
    Route::get('/customer-debts/{customer}/statement/export', [CustomerDebtController::class, 'exportStatement'])->name('customer-debts.export-statement');

    // Supplier Debt Management routes (Công nợ NCC)
    Route::get('/supplier-debts', [\App\Http\Controllers\SupplierDebtController::class, 'index'])->name('supplier-debts.index');
    Route::get('/supplier-debts/aging', [\App\Http\Controllers\SupplierDebtController::class, 'agingReport'])->name('supplier-debts.aging');
    Route::get('/supplier-debts/export', [\App\Http\Controllers\SupplierDebtController::class, 'export'])->name('supplier-debts.export');
    Route::get('/supplier-debts/{supplier}', [\App\Http\Controllers\SupplierDebtController::class, 'show'])->name('supplier-debts.show');
    Route::get('/supplier-debts/{supplier}/statement', [\App\Http\Controllers\SupplierDebtController::class, 'statement'])->name('supplier-debts.statement');
    Route::get('/supplier-debts/{supplier}/statement/export', [\App\Http\Controllers\SupplierDebtController::class, 'exportStatement'])->name('supplier-debts.export-statement');
    Route::post('/supplier-debts/{purchaseOrder}/payment', [\App\Http\Controllers\SupplierDebtController::class, 'recordPayment'])->name('supplier-debts.record-payment');
    Route::delete('/supplier-debts/payment/{payment}', [\App\Http\Controllers\SupplierDebtController::class, 'deletePayment'])->name('supplier-debts.delete-payment');

    // Sale-to-Export sync route
    Route::get('/sales/{sale}/export', [SaleController::class, 'getExport'])->name('sales.export.link');

    // Quotation routes (Báo giá)
    Route::get('/quotations/export/excel', [QuotationController::class, 'export'])->name('quotations.export');
    Route::resource('quotations', QuotationController::class);
    Route::post('/quotations/{quotation}/submit', [QuotationController::class, 'submitForApproval'])->name('quotations.submit');
    Route::post('/quotations/{quotation}/approve', [QuotationController::class, 'approve'])->name('quotations.approve');
    Route::post('/quotations/{quotation}/reject', [QuotationController::class, 'reject'])->name('quotations.reject');
    Route::post('/quotations/{quotation}/send', [QuotationController::class, 'markAsSent'])->name('quotations.send');
    Route::post('/quotations/{quotation}/response', [QuotationController::class, 'customerResponse'])->name('quotations.response');
    Route::post('/quotations/{quotation}/delegate', [QuotationController::class, 'delegate'])->name('quotations.delegate');
    Route::post('/quotations/{quotation}/convert', [QuotationController::class, 'convertToSale'])->name('quotations.convert');
    Route::get('/quotations/{quotation}/print', [QuotationController::class, 'print'])->name('quotations.print');

    // Approval Workflow routes
    Route::resource('approval-workflows', ApprovalWorkflowController::class);
    Route::post('/approval-workflows/{approvalWorkflow}/toggle', [ApprovalWorkflowController::class, 'toggle'])->name('approval-workflows.toggle');

    // Price List routes
    Route::get('/price-lists/export/excel', [PriceListController::class, 'export'])->name('price-lists.export');
    Route::resource('price-lists', PriceListController::class);
    Route::post('/price-lists/{priceList}/toggle', [PriceListController::class, 'toggle'])->name('price-lists.toggle');
    Route::get('/api/price-lists/for-customer/{customer}', [PriceListController::class, 'getForCustomer'])->name('price-lists.for-customer');

    // Project routes
    Route::get('/projects/report', [ProjectController::class, 'report'])->name('projects.report');
    Route::get('/projects/export/excel', [ProjectController::class, 'export'])->name('projects.export');
    Route::get('/api/projects', [ProjectController::class, 'getList'])->name('projects.list');
    Route::resource('projects', ProjectController::class);


    // Purchase Request routes (Yêu cầu báo giá NCC)
    Route::resource('purchase-requests', PurchaseRequestController::class);
    Route::post('/purchase-requests/{purchaseRequest}/send', [PurchaseRequestController::class, 'send'])->name('purchase-requests.send');
    Route::post('/purchase-requests/{purchaseRequest}/cancel', [PurchaseRequestController::class, 'cancel'])->name('purchase-requests.cancel');

    // Supplier Quotation routes (Báo giá từ NCC)
    Route::get('/supplier-quotations/import', [SupplierQuotationController::class, 'showImportForm'])->name('supplier-quotations.import');
    Route::post('/supplier-quotations/analyze', [SupplierQuotationController::class, 'analyzeFile'])->name('supplier-quotations.analyze');
    Route::post('/supplier-quotations/sheet-data', [SupplierQuotationController::class, 'getSheetData'])->name('supplier-quotations.sheet-data');
    Route::post('/supplier-quotations/auto-detect', [SupplierQuotationController::class, 'autoDetectMapping'])->name('supplier-quotations.auto-detect');
    Route::post('/supplier-quotations/do-import', [SupplierQuotationController::class, 'import'])->name('supplier-quotations.do-import');
    Route::resource('supplier-quotations', SupplierQuotationController::class);
    Route::post('/supplier-quotations/{supplierQuotation}/select', [SupplierQuotationController::class, 'select'])->name('supplier-quotations.select');
    Route::post('/supplier-quotations/{supplierQuotation}/reject', [SupplierQuotationController::class, 'reject'])->name('supplier-quotations.reject');
    Route::get('/supplier-quotations-compare', [SupplierQuotationController::class, 'compare'])->name('supplier-quotations.compare');

    // Purchase Order routes (Đơn mua hàng)
    Route::get('/purchase-orders/export/excel', [PurchaseOrderController::class, 'export'])->name('purchase-orders.export');
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::post('/purchase-orders/{purchaseOrder}/submit-approval', [PurchaseOrderController::class, 'submitApproval'])->name('purchase-orders.submit-approval');
    Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('/purchase-orders/{purchaseOrder}/reject', [PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
    Route::post('/purchase-orders/{purchaseOrder}/send', [PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
    Route::post('/purchase-orders/{purchaseOrder}/confirm', [PurchaseOrderController::class, 'confirmBySupplier'])->name('purchase-orders.confirm');
    Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::get('/purchase-orders/{purchaseOrder}/print', [PurchaseOrderController::class, 'print'])->name('purchase-orders.print');
    Route::get('/purchase-orders/{purchaseOrder}/import', [PurchaseOrderController::class, 'getImport'])->name('purchase-orders.import.link');

    // Shipping Allocation routes (Phân bổ chi phí vận chuyển)
    Route::resource('shipping-allocations', ShippingAllocationController::class);
    Route::post('/shipping-allocations/{shippingAllocation}/approve', [ShippingAllocationController::class, 'approve'])->name('shipping-allocations.approve');
    Route::post('/shipping-allocations/{shippingAllocation}/complete', [ShippingAllocationController::class, 'complete'])->name('shipping-allocations.complete');

    // Business Overview Report (Báo cáo tổng hợp kinh doanh)
    Route::get('/reports/business-overview', [\App\Http\Controllers\BusinessReportController::class, 'index'])->name('reports.business-overview');

    // Purchase Reports routes (Báo cáo mua hàng nâng cao)
    Route::get('/purchase-reports', [PurchaseReportController::class, 'index'])->name('purchase-reports.index');
    Route::get('/purchase-reports/export', [PurchaseReportController::class, 'export'])->name('purchase-reports.export');

    // Sales Reports routes (Báo cáo bán hàng)
    Route::get('/sale-reports', [\App\Http\Controllers\SaleReportController::class, 'index'])->name('sale-reports.index');
    Route::get('/sale-reports/export', [\App\Http\Controllers\SaleReportController::class, 'export'])->name('sale-reports.export');

    // Supplier Price List routes (Bảng giá nhà cung cấp - Import Excel)
    Route::get('/supplier-price-lists', [\App\Http\Controllers\SupplierPriceListController::class, 'index'])->name('supplier-price-lists.index');
    Route::get('/supplier-price-lists/import', [\App\Http\Controllers\SupplierPriceListController::class, 'showImportForm'])->name('supplier-price-lists.import');
    Route::post('/supplier-price-lists/analyze', [\App\Http\Controllers\SupplierPriceListController::class, 'analyzeFile'])->name('supplier-price-lists.analyze');
    Route::post('/supplier-price-lists/sheet-data', [\App\Http\Controllers\SupplierPriceListController::class, 'getSheetData'])->name('supplier-price-lists.sheet-data');
    Route::post('/supplier-price-lists/auto-detect', [\App\Http\Controllers\SupplierPriceListController::class, 'autoDetectMapping'])->name('supplier-price-lists.auto-detect');
    Route::post('/supplier-price-lists/do-import', [\App\Http\Controllers\SupplierPriceListController::class, 'import'])->name('supplier-price-lists.do-import');
    Route::get('/supplier-price-lists/{supplierPriceList}/edit', [\App\Http\Controllers\SupplierPriceListController::class, 'edit'])->name('supplier-price-lists.edit');
    Route::put('/supplier-price-lists/{supplierPriceList}', [\App\Http\Controllers\SupplierPriceListController::class, 'update'])->name('supplier-price-lists.update');
    Route::get('/supplier-price-lists/{supplierPriceList}', [\App\Http\Controllers\SupplierPriceListController::class, 'show'])->name('supplier-price-lists.show');
    Route::post('/supplier-price-lists/{supplierPriceList}/toggle', [\App\Http\Controllers\SupplierPriceListController::class, 'toggle'])->name('supplier-price-lists.toggle');
    Route::delete('/supplier-price-lists/{supplierPriceList}', [\App\Http\Controllers\SupplierPriceListController::class, 'destroy'])->name('supplier-price-lists.destroy');
    Route::get('/api/supplier-price-lists/search', [\App\Http\Controllers\SupplierPriceListController::class, 'searchItems'])->name('supplier-price-lists.search');
    Route::post('/supplier-price-lists/{supplierPriceList}/apply-prices', [\App\Http\Controllers\SupplierPriceListController::class, 'applyPrices'])->name('supplier-price-lists.apply-prices');
    Route::get('/supplier-price-lists/{supplierPriceList}/preview-apply', [\App\Http\Controllers\SupplierPriceListController::class, 'previewApplyPrices'])->name('supplier-price-lists.preview-apply');
    Route::post('/supplier-price-lists/{supplierPriceList}/update-pricing-config', [\App\Http\Controllers\SupplierPriceListController::class, 'updatePricingConfig'])->name('supplier-price-lists.update-pricing-config');
    Route::post('/supplier-price-lists/sync-to-products', [\App\Http\Controllers\SupplierPriceListController::class, 'syncToProducts'])->name('supplier-price-lists.sync-to-products');
    Route::post('/supplier-price-lists/{supplierPriceList}/update-primary-column', [\App\Http\Controllers\SupplierPriceListController::class, 'updatePrimaryColumn'])->name('supplier-price-lists.update-primary-column');

    // Quotation enhanced methods
    Route::get('/api/quotations/search-catalog', [\App\Http\Controllers\QuotationController::class, 'searchCatalog'])->name('quotations.search-catalog');

    // Notification routes (Thông báo)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');

    // Leads & Opportunities (CRM)
    Route::resource('leads', \App\Http\Controllers\LeadController::class);
    Route::post('/leads/{lead}/convert', [\App\Http\Controllers\LeadController::class, 'convert'])->name('leads.convert');

    Route::resource('opportunities', \App\Http\Controllers\OpportunityController::class);
    Route::post('/opportunities/{opportunity}/update-stage', [\App\Http\Controllers\OpportunityController::class, 'updateStage'])->name('opportunities.update-stage');

    // Activities (Tasks/CRM)
    Route::resource('activities', \App\Http\Controllers\ActivityController::class);

    // Financial Transactions (Thu Chi)
    Route::get('/financial-transactions/categories', [FinancialTransactionController::class, 'categories'])->name('financial-transactions.categories');
    Route::post('/financial-transactions/categories', [FinancialTransactionController::class, 'storeCategory'])->name('financial-transactions.categories.store');
    Route::put('/financial-transactions/categories/{category}', [FinancialTransactionController::class, 'updateCategory'])->name('financial-transactions.categories.update');
    Route::delete('/financial-transactions/categories/{category}', [FinancialTransactionController::class, 'destroyCategory'])->name('financial-transactions.categories.destroy');
    Route::get('/financial-transactions/{transaction}/print', [FinancialTransactionController::class, 'print'])->name('financial-transactions.print');
    Route::get('/financial-transactions/export-misa', [FinancialTransactionController::class, 'exportMisa'])->name('financial-transactions.export-misa');
    Route::resource('financial-transactions', FinancialTransactionController::class);

    // Cash Flow Report (Báo cáo Dòng tiền)
    Route::get('/cash-flow-report', [\App\Http\Controllers\CashFlowReportController::class, 'index'])->name('cash-flow-report.index');
    Route::get('/cash-flow-report/export', [\App\Http\Controllers\CashFlowReportController::class, 'export'])->name('cash-flow-report.export');
    Route::get('/cash-flow-report/config', [\App\Http\Controllers\CashFlowReportController::class, 'config'])->name('cash-flow-report.config');
    Route::post('/cash-flow-report/config', [\App\Http\Controllers\CashFlowReportController::class, 'storeConfig'])->name('cash-flow-report.config.store');
    Route::delete('/cash-flow-report/config/{configItem}', [\App\Http\Controllers\CashFlowReportController::class, 'destroyConfig'])->name('cash-flow-report.config.destroy');

    // Warehouse Accounting Journal (Nhật ký kế toán kho)
    Route::get('/accounting/journal', [\App\Http\Controllers\AccountingJournalController::class, 'index'])->name('accounting.journal.index');

    // Work Schedule routes
    Route::get('/work-schedules/get-events', [\App\Http\Controllers\WorkScheduleController::class, 'getEvents'])->name('work-schedules.events');
    Route::resource('work-schedules', \App\Http\Controllers\WorkScheduleController::class);

    // Activity Log routes (Nhật ký hoạt động)
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/users/{user}/activity-logs', [ActivityLogController::class, 'userLogs'])->name('users.activity-logs');

    // Role Management routes (Quản lý vai trò)
    Route::resource('roles', RoleController::class);

    // Permission Management routes (Quản lý quyền)
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::get('/permissions/matrix', [PermissionController::class, 'matrix'])->name('permissions.matrix');
    Route::post('/permissions/matrix', [PermissionController::class, 'updateMatrix'])->name('permissions.matrix.update');

    // User Management routes (Quản lý người dùng)
    Route::get('/users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');

    // User Role Assignment routes (Gán vai trò cho người dùng)
    Route::get('/users/{user}/roles', [\App\Http\Controllers\UserRoleController::class, 'show'])->name('users.roles.show');
    Route::post('/users/{user}/roles', [\App\Http\Controllers\UserRoleController::class, 'assign'])->name('users.roles.assign');
    Route::delete('/users/{user}/roles/{role}', [\App\Http\Controllers\UserRoleController::class, 'revoke'])->name('users.roles.revoke');
    Route::put('/users/{user}/roles', [\App\Http\Controllers\UserRoleController::class, 'sync'])->name('users.roles.sync');

    // User Permission Assignment routes (Gán quyền trực tiếp cho người dùng)
    Route::get('/users/{user}/permissions', [\App\Http\Controllers\UserPermissionController::class, 'show'])->name('users.permissions.show');
    Route::post('/users/{user}/permissions', [\App\Http\Controllers\UserPermissionController::class, 'assign'])->name('users.permissions.assign');
    Route::delete('/users/{user}/permissions/{permission}', [\App\Http\Controllers\UserPermissionController::class, 'revoke'])->name('users.permissions.revoke');

    // Audit Log routes (Nhật ký kiểm toán)
    Route::get('/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('audit-logs.index');

    // Customer Care Stages routes (Theo dõi tiến độ chăm sóc khách hàng)
    Route::get('/customer-care-stages/dashboard', [\App\Http\Controllers\CustomerCareStageController::class, 'dashboard'])->name('customer-care-stages.dashboard');
    Route::resource('customer-care-stages', \App\Http\Controllers\CustomerCareStageController::class);
    Route::post('/customer-care-stages/{customerCareStage}/update-progress', [\App\Http\Controllers\CustomerCareStageController::class, 'updateProgress'])->name('customer-care-stages.update-progress');
    Route::post('/customer-care-stages/{customerCareStage}/assign', [\App\Http\Controllers\CustomerCareStageController::class, 'assignUser'])->name('customer-care-stages.assign');
    Route::post('/customer-care-stages/{customerCareStage}/complete-action', [\App\Http\Controllers\CustomerCareStageController::class, 'completeAction'])->name('customer-care-stages.complete-action');
    Route::post('/customer-care-stages/{customerCareStage}/snooze-action', [\App\Http\Controllers\CustomerCareStageController::class, 'snoozeAction'])->name('customer-care-stages.snooze-action');
    
    // Care Milestones routes
    Route::post('/customer-care-stages/{customerCareStage}/milestones', [\App\Http\Controllers\CareMilestoneController::class, 'store'])->name('care-milestones.store');
    Route::put('/care-milestones/{careMilestone}', [\App\Http\Controllers\CareMilestoneController::class, 'update'])->name('care-milestones.update');
    Route::delete('/care-milestones/{careMilestone}', [\App\Http\Controllers\CareMilestoneController::class, 'destroy'])->name('care-milestones.destroy');
    Route::post('/care-milestones/{careMilestone}/toggle-complete', [\App\Http\Controllers\CareMilestoneController::class, 'toggleComplete'])->name('care-milestones.toggle-complete');

    // Communication Logs
    Route::post('/customer-care-stages/{stage}/communications', [\App\Http\Controllers\CommunicationLogController::class, 'store'])->name('communications.store');
    Route::put('/communications/{log}', [\App\Http\Controllers\CommunicationLogController::class, 'update'])->name('communications.update');
    Route::delete('/communications/{log}', [\App\Http\Controllers\CommunicationLogController::class, 'destroy'])->name('communications.destroy');

    // Milestone Templates
    Route::resource('milestone-templates', \App\Http\Controllers\MilestoneTemplateController::class);
    Route::post('/milestone-templates/{template}/apply/{stage}', [\App\Http\Controllers\MilestoneTemplateController::class, 'apply'])->name('milestone-templates.apply');

    // Reminders
    Route::get('reminders', [\App\Http\Controllers\ReminderController::class, 'index'])->name('reminders.index');
    Route::post('reminders', [\App\Http\Controllers\ReminderController::class, 'store'])->name('reminders.store');
    Route::put('reminders/{reminder}', [\App\Http\Controllers\ReminderController::class, 'update'])->name('reminders.update');
    Route::delete('reminders/{reminder}', [\App\Http\Controllers\ReminderController::class, 'destroy'])->name('reminders.destroy');
    Route::post('/reminders/{reminder}/snooze', [\App\Http\Controllers\ReminderController::class, 'snooze'])->name('reminders.snooze');

    // Customer AJAX API
    Route::get('/api/customers/{customer}/details', [\App\Http\Controllers\CustomerCareStageController::class, 'getCustomerDetails'])->name('customers.details');

    // Business Reports
    Route::get('/reports/business-overview', [BusinessReportController::class, 'index'])->name('reports.business-overview');
    Route::get('/reports/balance-sheet', [BusinessReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
    Route::get('/reports/balance-sheet/export', [BusinessReportController::class, 'exportBalanceSheet'])->name('reports.balance-sheet.export');
    Route::get('/reports/detailed-pnl', [BusinessReportController::class, 'detailedPnL'])->name('reports.detailed-pnl');
    Route::get('/reports/misa-margin', [BusinessReportController::class, 'misaMargin'])->name('reports.misa-margin');
    Route::get('/reports/export-misa-margin', [BusinessReportController::class, 'exportMisaMargin'])->name('reports.export-misa-margin');

    // Reconciliation (Đối soát giữa các Module)
    Route::get('/reconciliation', [\App\Http\Controllers\ReconciliationController::class, 'index'])->name('reconciliation.index');
    Route::get('/reconciliation/sale-export', [\App\Http\Controllers\ReconciliationController::class, 'saleExport'])->name('reconciliation.sale-export');
    Route::get('/reconciliation/purchase-import', [\App\Http\Controllers\ReconciliationController::class, 'purchaseImport'])->name('reconciliation.purchase-import');
    Route::get('/reconciliation/inventory', [\App\Http\Controllers\ReconciliationController::class, 'inventory'])->name('reconciliation.inventory');
    Route::get('/reconciliation/debt-payment', [\App\Http\Controllers\ReconciliationController::class, 'debtPayment'])->name('reconciliation.debt-payment');

    // =========================================================================
    // Employee Asset Management — Quản lý Tài sản / Công cụ Dụng cụ Nội bộ
    // =========================================================================
    Route::get('/employee-assets-export', [\App\Http\Controllers\EmployeeAssetController::class, 'export'])->name('employee-assets.export');
    Route::resource('employee-assets', \App\Http\Controllers\EmployeeAssetController::class);

    Route::resource('employee-asset-assignments', \App\Http\Controllers\EmployeeAssetAssignmentController::class)
        ->only(['index', 'create', 'store', 'show']);
    Route::post('/employee-asset-assignments/{employeeAssetAssignment}/return', [\App\Http\Controllers\EmployeeAssetAssignmentController::class, 'returnAsset'])
        ->name('employee-asset-assignments.return');

    Route::get('/employee-asset-reports', [\App\Http\Controllers\EmployeeAssetReportController::class, 'index'])->name('employee-asset-reports.index');
    Route::get('/employee-asset-reports/export', [\App\Http\Controllers\EmployeeAssetReportController::class, 'export'])->name('employee-asset-reports.export');

    // =========================================================================
    // Skill Management — Quản lý Kỹ năng / Skillset
    // =========================================================================
    Route::resource('skills', \App\Http\Controllers\SkillController::class);
    Route::get('/skills/{skill}/employees', [\App\Http\Controllers\SkillController::class, 'employees'])->name('skills.employees');
    Route::put('/skills/{skill}/employees', [\App\Http\Controllers\SkillController::class, 'updateEmployees'])->name('skills.employees.update');
    Route::delete('/skill-categories/{category}', [\App\Http\Controllers\SkillController::class, 'destroyCategory'])->name('skill-categories.destroy');

    // Employee Skillset (Đánh giá năng lực nhân viên)
    Route::get('/employees/{employee}/skills', [\App\Http\Controllers\EmployeeSkillController::class, 'show'])->name('employee-skills.show');
    Route::get('/employees/{employee}/skills/edit', [\App\Http\Controllers\EmployeeSkillController::class, 'edit'])->name('employee-skills.edit');
    Route::put('/employees/{employee}/skills', [\App\Http\Controllers\EmployeeSkillController::class, 'update'])->name('employee-skills.update');

    // =========================================================================
    // Department KPI - Ghi nhận KPI Bộ phận
    // =========================================================================
    Route::get('/api/department-kpi-criteria', [\App\Http\Controllers\DepartmentKpiCriterionController::class, 'getByDepartment'])->name('api.department-kpi-criteria');
    Route::resource('department-kpi-criteria', \App\Http\Controllers\DepartmentKpiCriterionController::class);
    Route::resource('department-kpis', \App\Http\Controllers\DepartmentKpiController::class);
    // =========================================================================
    // Employee Sale Revenue - Ghi nhận doanh số nhân viên kinh doanh
    // =========================================================================
    Route::get('/employee-sales-revenues/get-suggested', [\App\Http\Controllers\EmployeeSaleRevenueController::class, 'getSuggestedRevenue'])->name('employee-sales-revenues.suggested');
    Route::resource('employee-sales-revenues', \App\Http\Controllers\EmployeeSaleRevenueController::class);

    // =========================================================================
    // Payroll & Attendance - Lương và chấm công
    // =========================================================================
    Route::resource('work-locations', WorkLocationController::class);
    Route::resource('salary-components', SalaryComponentController::class);
    Route::get('employees/{employee}/salary-setup', [EmployeeSalaryComponentController::class, 'edit'])->name('employees.salary-setup');
    Route::put('employees/{employee}/salary-setup', [EmployeeSalaryComponentController::class, 'update'])->name('employees.salary-setup.update');
    
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.check-in');
    Route::post('attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.check-out');
    Route::post('/attendance/resolve-location', [AttendanceController::class, 'resolveLocation'])->name('attendance.resolve-location');
    Route::get('attendances/manage', [AttendanceController::class, 'manage'])->name('attendance.manage');
    
    Route::resource('payrolls', PayrollController::class);
    Route::patch('payrolls/{payroll}/status', [PayrollController::class, 'updateStatus'])->name('payrolls.updateStatus');

    // =========================================================================
    // Multi-Currency Management — Quản lý Đa tiền tệ
    // =========================================================================
    Route::get('/currencies', [\App\Http\Controllers\CurrencyController::class, 'index'])->name('currencies.index');
    Route::put('/currencies/{currency}', [\App\Http\Controllers\CurrencyController::class, 'update'])->name('currencies.update');
    Route::post('/currencies/{currency}/toggle', [\App\Http\Controllers\CurrencyController::class, 'toggle'])->name('currencies.toggle');

    // Exchange Rates (Tỷ giá hối đoái)
    Route::get('/exchange-rates', [\App\Http\Controllers\ExchangeRateController::class, 'index'])->name('exchange-rates.index');
    Route::post('/exchange-rates', [\App\Http\Controllers\ExchangeRateController::class, 'store'])->name('exchange-rates.store');
    Route::put('/exchange-rates/{exchangeRate}', [\App\Http\Controllers\ExchangeRateController::class, 'update'])->name('exchange-rates.update');
    Route::post('/exchange-rates/fetch-today', [\App\Http\Controllers\ExchangeRateController::class, 'fetchToday'])->name('exchange-rates.fetch-today');

    // API: Lấy tỷ giá cho AJAX (dùng trong form tạo đơn hàng)
    Route::get('/api/exchange-rate', [\App\Http\Controllers\ExchangeRateController::class, 'getRate'])->name('api.exchange-rate');
});

// Auth routes (login, logout, etc.)
require __DIR__ . '/auth.php';
