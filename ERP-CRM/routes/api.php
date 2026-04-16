<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Dashboard APIs
Route::prefix('dashboard')->group(function () {
    Route::get('/summary', [\App\Http\Controllers\Api\DashboardApiController::class, 'summary']);
    Route::get('/business-activity', [\App\Http\Controllers\Api\DashboardApiController::class, 'businessActivity']);
});

// Master Data APIs
Route::apiResource('customers', \App\Http\Controllers\Api\CustomerApiController::class);
Route::apiResource('suppliers', \App\Http\Controllers\Api\SupplierApiController::class);
Route::apiResource('employees', \App\Http\Controllers\Api\EmployeeApiController::class);
Route::get('products/search', [\App\Http\Controllers\ProductController::class, 'apiSearch'])->name('api.products.search');
Route::apiResource('products', \App\Http\Controllers\Api\ProductApiController::class);

// Inventory APIs
Route::apiResource('warehouses', \App\Http\Controllers\Api\WarehouseApiController::class);
Route::get('inventory/stats', [\App\Http\Controllers\Api\InventoryApiController::class, 'stats']);
Route::apiResource('inventory', \App\Http\Controllers\Api\InventoryApiController::class)->only(['index', 'show']);

Route::post('imports/{import}/approve', [\App\Http\Controllers\Api\ImportApiController::class, 'approve']);
Route::post('imports/{import}/reject', [\App\Http\Controllers\Api\ImportApiController::class, 'reject']);
Route::apiResource('imports', \App\Http\Controllers\Api\ImportApiController::class);

Route::get('exports/available-items', [\App\Http\Controllers\Api\ExportApiController::class, 'getAvailableItems']);
Route::post('exports/{export}/approve', [\App\Http\Controllers\Api\ExportApiController::class, 'approve']);
Route::apiResource('exports', \App\Http\Controllers\Api\ExportApiController::class);

Route::post('transfers/{transfer}/approve', [\App\Http\Controllers\Api\TransferApiController::class, 'approve']);
Route::apiResource('transfers', \App\Http\Controllers\Api\TransferApiController::class);

Route::post('damaged-goods/{damagedGood}/status', [\App\Http\Controllers\Api\DamagedGoodApiController::class, 'updateStatus']);
Route::apiResource('damaged-goods', \App\Http\Controllers\Api\DamagedGoodApiController::class);

// Sales APIs
Route::post('leads/{lead}/convert', [\App\Http\Controllers\Api\LeadApiController::class, 'convert']);
Route::apiResource('leads', \App\Http\Controllers\Api\LeadApiController::class);

Route::post('opportunities/{opportunity}/stage', [\App\Http\Controllers\Api\OpportunityApiController::class, 'updateStage']);
Route::apiResource('opportunities', \App\Http\Controllers\Api\OpportunityApiController::class);

Route::post('quotations/{quotation}/convert', [\App\Http\Controllers\Api\QuotationApiController::class, 'convertToSale']);
Route::apiResource('quotations', \App\Http\Controllers\Api\QuotationApiController::class);

Route::post('sales/{sale}/status', [\App\Http\Controllers\Api\SaleApiController::class, 'updateStatus']);
Route::apiResource('sales', \App\Http\Controllers\Api\SaleApiController::class);

Route::get('projects/{project}/stats', [\App\Http\Controllers\Api\ProjectApiController::class, 'stats']);
Route::apiResource('projects', \App\Http\Controllers\Api\ProjectApiController::class);

Route::get('customer-debts/aging-report', [\App\Http\Controllers\Api\CustomerDebtApiController::class, 'agingReport']);
Route::post('customer-debts/{sale}/payment', [\App\Http\Controllers\Api\CustomerDebtApiController::class, 'recordPayment']);
Route::apiResource('customer-debts', \App\Http\Controllers\Api\CustomerDebtApiController::class);

Route::post('marketing-events/{marketingEvent}/approve', [\App\Http\Controllers\Api\MarketingEventApiController::class, 'submitApproval']);
Route::post('marketing-events/{marketingEvent}/customers', [\App\Http\Controllers\Api\MarketingEventApiController::class, 'addCustomers']);
Route::apiResource('marketing-events', \App\Http\Controllers\Api\MarketingEventApiController::class);

Route::post('cost-formulas/calculate', [\App\Http\Controllers\Api\CostFormulaApiController::class, 'calculateForSale']);
Route::apiResource('cost-formulas', \App\Http\Controllers\Api\CostFormulaApiController::class);

Route::prefix('sale-reports')->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\Api\SaleReportApiController::class, 'dashboard']);
    Route::get('customers', [\App\Http\Controllers\Api\SaleReportApiController::class, 'customerReport']);
    Route::get('products', [\App\Http\Controllers\Api\SaleReportApiController::class, 'productReport']);
});

// Purchase APIs
Route::apiResource('supplier-price-lists', \App\Http\Controllers\Api\SupplierPriceListApiController::class);

Route::post('purchase-requests/{purchase_request}/send', [\App\Http\Controllers\Api\PurchaseRequestApiController::class, 'send']);
Route::post('purchase-requests/{purchase_request}/cancel', [\App\Http\Controllers\Api\PurchaseRequestApiController::class, 'cancel']);
Route::apiResource('purchase-requests', \App\Http\Controllers\Api\PurchaseRequestApiController::class);

Route::post('supplier-quotations/{supplier_quotation}/select', [\App\Http\Controllers\Api\SupplierQuotationApiController::class, 'select']);
Route::post('supplier-quotations/{supplier_quotation}/reject', [\App\Http\Controllers\Api\SupplierQuotationApiController::class, 'reject']);
Route::apiResource('supplier-quotations', \App\Http\Controllers\Api\SupplierQuotationApiController::class);

Route::post('purchase-orders/{purchase_order}/submit-approval', [\App\Http\Controllers\Api\PurchaseOrderApiController::class, 'submitApproval']);
Route::post('purchase-orders/{purchase_order}/approve', [\App\Http\Controllers\Api\PurchaseOrderApiController::class, 'approve']);
Route::post('purchase-orders/{purchase_order}/send', [\App\Http\Controllers\Api\PurchaseOrderApiController::class, 'send']);
Route::post('purchase-orders/{purchase_order}/receive', [\App\Http\Controllers\Api\PurchaseOrderApiController::class, 'receive']);
Route::post('purchase-orders/{purchase_order}/cancel', [\App\Http\Controllers\Api\PurchaseOrderApiController::class, 'cancel']);
Route::apiResource('purchase-orders', \App\Http\Controllers\Api\PurchaseOrderApiController::class);

Route::post('shipping-allocations/{shipping_allocation}/approve', [\App\Http\Controllers\Api\ShippingAllocationApiController::class, 'approve']);
Route::post('shipping-allocations/{shipping_allocation}/complete', [\App\Http\Controllers\Api\ShippingAllocationApiController::class, 'complete']);
Route::apiResource('shipping-allocations', \App\Http\Controllers\Api\ShippingAllocationApiController::class);

Route::get('supplier-debts', [\App\Http\Controllers\Api\SupplierDebtApiController::class, 'index']);
Route::get('supplier-debts/{supplier}', [\App\Http\Controllers\Api\SupplierDebtApiController::class, 'show']);
Route::post('supplier-debts/payments', [\App\Http\Controllers\Api\SupplierDebtApiController::class, 'storePayment']);
Route::get('supplier-debts/{supplier}/statement', [\App\Http\Controllers\Api\SupplierDebtApiController::class, 'statement']);

Route::get('purchase-reports/dashboard', [\App\Http\Controllers\Api\PurchaseReportApiController::class, 'dashboard']);
Route::get('purchase-reports/suppliers', [\App\Http\Controllers\Api\PurchaseReportApiController::class, 'supplierReport']);
Route::get('purchase-reports/products', [\App\Http\Controllers\Api\PurchaseReportApiController::class, 'productReport']);
Route::get('purchase-reports/monthly', [\App\Http\Controllers\Api\PurchaseReportApiController::class, 'monthlyTrends']);
