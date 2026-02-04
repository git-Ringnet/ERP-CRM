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
    Route::resource('imports', ImportController::class);
    Route::post('/imports/{import}/approve', [ImportController::class, 'approve'])->name('imports.approve');
    Route::post('/imports/{import}/reject', [ImportController::class, 'reject'])->name('imports.reject');
    Route::get('/imports-export', [ImportController::class, 'export'])->name('imports.export');

    // Export Module Routes (Xuất kho)
    Route::get('/exports/available-items', [ExportController::class, 'getAvailableItems'])->name('exports.available-items');
    Route::resource('exports', ExportController::class);
    Route::post('/exports/{export}/approve', [ExportController::class, 'approve'])->name('exports.approve');
    Route::post('/exports/{export}/reject', [ExportController::class, 'reject'])->name('exports.reject');
    Route::get('/exports-export', [ExportController::class, 'exportToExcel'])->name('exports.export');

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

    // Customer Debt Management routes
    Route::get('/customer-debts', [CustomerDebtController::class, 'index'])->name('customer-debts.index');
    Route::get('/customer-debts/export', [CustomerDebtController::class, 'export'])->name('customer-debts.export');
    Route::get('/customer-debts/aging-report', [CustomerDebtController::class, 'agingReport'])->name('customer-debts.aging-report');
    Route::get('/customer-debts/aging-report/export', [CustomerDebtController::class, 'exportAgingReport'])->name('customer-debts.aging-report.export');
    Route::get('/customer-debts/{customer}', [CustomerDebtController::class, 'show'])->name('customer-debts.show');
    Route::post('/customer-debts/payment/{sale}', [CustomerDebtController::class, 'recordPayment'])->name('customer-debts.record-payment');
    Route::delete('/customer-debts/payment/{payment}', [CustomerDebtController::class, 'deletePayment'])->name('customer-debts.delete-payment');

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

    // Purchase Reports routes (Báo cáo mua hàng nâng cao)
    Route::get('/purchase-reports', [PurchaseReportController::class, 'index'])->name('purchase-reports.index');
    Route::get('/purchase-reports/export', [PurchaseReportController::class, 'export'])->name('purchase-reports.export');

    // Supplier Price List routes (Bảng giá nhà cung cấp - Import Excel)
    Route::get('/supplier-price-lists', [\App\Http\Controllers\SupplierPriceListController::class, 'index'])->name('supplier-price-lists.index');
    Route::get('/supplier-price-lists/import', [\App\Http\Controllers\SupplierPriceListController::class, 'showImportForm'])->name('supplier-price-lists.import');
    Route::post('/supplier-price-lists/analyze', [\App\Http\Controllers\SupplierPriceListController::class, 'analyzeFile'])->name('supplier-price-lists.analyze');
    Route::post('/supplier-price-lists/sheet-data', [\App\Http\Controllers\SupplierPriceListController::class, 'getSheetData'])->name('supplier-price-lists.sheet-data');
    Route::post('/supplier-price-lists/auto-detect', [\App\Http\Controllers\SupplierPriceListController::class, 'autoDetectMapping'])->name('supplier-price-lists.auto-detect');
    Route::post('/supplier-price-lists/do-import', [\App\Http\Controllers\SupplierPriceListController::class, 'import'])->name('supplier-price-lists.do-import');
    Route::get('/supplier-price-lists/{supplierPriceList}', [\App\Http\Controllers\SupplierPriceListController::class, 'show'])->name('supplier-price-lists.show');
    Route::post('/supplier-price-lists/{supplierPriceList}/toggle', [\App\Http\Controllers\SupplierPriceListController::class, 'toggle'])->name('supplier-price-lists.toggle');
    Route::delete('/supplier-price-lists/{supplierPriceList}', [\App\Http\Controllers\SupplierPriceListController::class, 'destroy'])->name('supplier-price-lists.destroy');
    Route::get('/api/supplier-price-lists/search', [\App\Http\Controllers\SupplierPriceListController::class, 'searchItems'])->name('supplier-price-lists.search');
    Route::post('/supplier-price-lists/{supplierPriceList}/apply-prices', [\App\Http\Controllers\SupplierPriceListController::class, 'applyPrices'])->name('supplier-price-lists.apply-prices');
    Route::get('/supplier-price-lists/{supplierPriceList}/preview-apply', [\App\Http\Controllers\SupplierPriceListController::class, 'previewApplyPrices'])->name('supplier-price-lists.preview-apply');
    Route::post('/supplier-price-lists/{supplierPriceList}/update-pricing-config', [\App\Http\Controllers\SupplierPriceListController::class, 'updatePricingConfig'])->name('supplier-price-lists.update-pricing-config');

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

    // Work Schedule routes
    Route::get('/work-schedules/get-events', [\App\Http\Controllers\WorkScheduleController::class, 'getEvents'])->name('work-schedules.events');
    Route::resource('work-schedules', \App\Http\Controllers\WorkScheduleController::class);
});

// Auth routes (login, logout, etc.)
require __DIR__ . '/auth.php';
