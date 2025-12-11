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

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Dashboard as default page
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index']);

// Resource routes for CRUD operations
Route::resource('customers', CustomerController::class);
Route::resource('suppliers', SupplierController::class);
Route::resource('employees', EmployeeController::class);
Route::resource('products', ProductController::class);
Route::get('/products/{product}/items', [ProductController::class, 'items'])->name('products.items');

// Export routes (Requirements: 7.6, 7.7)
Route::get('/customers/export/excel', [CustomerController::class, 'export'])->name('customers.export');
Route::get('/suppliers/export/excel', [SupplierController::class, 'export'])->name('suppliers.export');
Route::get('/employees/export/excel', [EmployeeController::class, 'export'])->name('employees.export');
Route::get('/products/export/excel', [ProductController::class, 'export'])->name('products.export');

// Excel Import routes (for importing data from Excel/CSV)
Route::get('/excel-import', [ExcelImportController::class, 'index'])->name('excel-import.index');
Route::get('/excel-import/template/{type}', [ExcelImportController::class, 'template'])->name('excel-import.template');
Route::post('/excel-import/preview', [ExcelImportController::class, 'preview'])->name('excel-import.preview');
Route::post('/excel-import', [ExcelImportController::class, 'store'])->name('excel-import.store');

// Import Module Routes (Nhập kho) - Requirements: 1.3
Route::resource('imports', ImportController::class);
Route::post('/imports/{import}/approve', [ImportController::class, 'approve'])->name('imports.approve');

// Export Module Routes (Xuất kho) - Requirements: 2.3
Route::get('/exports/available-items', [ExportController::class, 'getAvailableItems'])->name('exports.available-items');
Route::resource('exports', ExportController::class);
Route::post('/exports/{export}/approve', [ExportController::class, 'approve'])->name('exports.approve');

// Transfer Module Routes (Chuyển kho) - Requirements: 3.3
Route::resource('transfers', TransferController::class);
Route::post('/transfers/{transfer}/approve', [TransferController::class, 'approve'])->name('transfers.approve');

// Warehouse Module Routes
Route::resource('warehouses', WarehouseController::class);

// Inventory Routes
Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
Route::get('/inventory/low-stock', [InventoryController::class, 'lowStock'])->name('inventory.low-stock');
Route::get('/inventory/expiring', [InventoryController::class, 'expiringSoon'])->name('inventory.expiring');
Route::get('/inventory/{inventory}', [InventoryController::class, 'show'])->name('inventory.show');



// Damaged Goods Routes
Route::resource('damaged-goods', DamagedGoodController::class);
Route::patch('/damaged-goods/{damaged_good}/status', [DamagedGoodController::class, 'updateStatus'])->name('damaged-goods.update-status');

// Report Routes
Route::get('/reports/inventory-summary', [ReportController::class, 'inventorySummary'])->name('reports.inventory-summary');
Route::get('/reports/transaction-report', [ReportController::class, 'transactionReport'])->name('reports.transaction-report');
Route::get('/reports/damaged-goods-report', [ReportController::class, 'damagedGoodsReport'])->name('reports.damaged-goods-report');

// Sales routes
Route::resource('sales', SaleController::class);
Route::get('/sales/export/excel', [SaleController::class, 'export'])->name('sales.export');
Route::get('/sales/{sale}/pdf', [SaleController::class, 'generatePdf'])->name('sales.pdf');
Route::post('/sales/{sale}/email', [SaleController::class, 'sendEmail'])->name('sales.email');
Route::post('/sales/{sale}/payment', [SaleController::class, 'recordPayment'])->name('sales.payment');

// Cost Formula routes
Route::resource('cost-formulas', CostFormulaController::class);
Route::get('/api/cost-formulas/applicable', [CostFormulaController::class, 'getApplicableFormulas'])->name('cost-formulas.applicable');
