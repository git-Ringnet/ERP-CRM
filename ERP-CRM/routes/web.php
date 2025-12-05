<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryTransactionController;
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

// Export routes (Requirements: 7.6, 7.7)
Route::get('/customers/export/excel', [CustomerController::class, 'export'])->name('customers.export');
Route::get('/suppliers/export/excel', [SupplierController::class, 'export'])->name('suppliers.export');
Route::get('/employees/export/excel', [EmployeeController::class, 'export'])->name('employees.export');
Route::get('/products/export/excel', [ProductController::class, 'export'])->name('products.export');

// Import routes
Route::get('/import', [ImportController::class, 'index'])->name('import.index');
Route::get('/import/template/{type}', [ImportController::class, 'template'])->name('import.template');
Route::post('/import/preview', [ImportController::class, 'preview'])->name('import.preview');
Route::post('/import', [ImportController::class, 'store'])->name('import.store');

// Warehouse Module Routes
Route::resource('warehouses', WarehouseController::class);

// Inventory Routes
Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
Route::get('/inventory/low-stock', [InventoryController::class, 'lowStock'])->name('inventory.low-stock');
Route::get('/inventory/expiring', [InventoryController::class, 'expiringSoon'])->name('inventory.expiring');
Route::get('/inventory/{inventory}', [InventoryController::class, 'show'])->name('inventory.show');

// Inventory Transaction Routes
Route::get('/transactions', [InventoryTransactionController::class, 'index'])->name('transactions.index');
Route::get('/transactions/create', [InventoryTransactionController::class, 'create'])->name('transactions.create');
Route::get('/transactions/export/csv', [InventoryTransactionController::class, 'exportCsv'])->name('transactions.export.csv');
Route::get('/transactions/export/json', [InventoryTransactionController::class, 'exportJson'])->name('transactions.export.json');
Route::get('/transactions/import/form', [InventoryTransactionController::class, 'importForm'])->name('transactions.import.form');
Route::post('/transactions/import', [InventoryTransactionController::class, 'import'])->name('transactions.import');
Route::post('/transactions', [InventoryTransactionController::class, 'store'])->name('transactions.store');
Route::get('/transactions/{transaction}/edit', [InventoryTransactionController::class, 'edit'])->name('transactions.edit');
Route::put('/transactions/{transaction}', [InventoryTransactionController::class, 'update'])->name('transactions.update');
Route::delete('/transactions/{transaction}', [InventoryTransactionController::class, 'destroy'])->name('transactions.destroy');
Route::post('/transactions/{transaction}/approve', [InventoryTransactionController::class, 'approve'])->name('transactions.approve');
Route::get('/transactions/{transaction}', [InventoryTransactionController::class, 'show'])->name('transactions.show');

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
