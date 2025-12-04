<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ImportController;

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
