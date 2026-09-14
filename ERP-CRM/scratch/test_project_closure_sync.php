<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Project;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== BẮT ĐẦU KIỂM THỬ ĐÓNG DỰ ÁN & ĐỒNG BỘ ĐƠN HÀNG ===\n\n";

$admin = User::where('email', 'admin@ringnet.vn')->first() ?? User::first();
Auth::login($admin);
echo "1. Đăng nhập người dùng: {$admin->name} ({$admin->email})\n";

// 1. Tạo hoặc lấy dự án thử nghiệm
$supplier = Supplier::first();
$customer = Customer::first();

$project = Project::firstOrCreate(
    ['code' => 'TEST-CLOSE-PRJ'],
    [
        'name' => 'Dự án đóng & đồng bộ tự động',
        'customer_id' => $customer?->id,
        'vendor_id' => $supplier?->id ?? 1,
        'manager_id' => $admin->id,
        'status' => 'in_progress',
        'registration_status' => 'update_status',
        'eu_name_vi' => 'Công ty Test EU',
        'eu_name_en' => 'Test EU Company',
        'eu_tax_code' => '0109998888',
        'collaborate_type' => 'end_user',
        'distributor_am' => 'test@ringnet.vn | Admin',
    ]
);
// Reset project status for test
$project->update([
    'registration_status' => 'update_status',
    'status' => 'in_progress',
    'po_code' => null,
    'order_value' => null,
    'order_date' => null,
    'bom_data' => null,
]);
echo "2. Chuẩn bị Dự án ID {$project->id} ({$project->code}) - Trạng thái: {$project->registration_status}\n";

// 2. Tạo hoặc lấy Đơn bán hàng (Sale) liên kết với Dự án
$product1 = Product::firstOrCreate(['code' => 'FT-TEST-01'], ['name' => 'FortiGate 60F Hardware', 'price' => 12500000, 'unit' => 'Chiếc', 'cost' => 9000000]);
$product2 = Product::firstOrCreate(['code' => 'FT-TEST-02'], ['name' => 'FortiWiFi 60F License 1Y', 'price' => 5500000, 'unit' => 'License', 'cost' => 4000000]);

$sale = Sale::firstOrCreate(
    ['code' => 'SO-TEST-CLOSE-01'],
    [
        'project_id' => $project->id,
        'customer_id' => $customer?->id ?? 1,
        'customer_name' => $customer?->name ?? 'Khách Hàng Thử Nghiệm',
        'user_id' => $admin->id,
        'date' => now(),
        'status' => 'approved',
        'subtotal' => 23500000,
        'total' => 25850000,
        'cost' => 18000000,
        'margin' => 7850000,
    ]
);

$sale->items()->delete();
$sale->items()->create([
    'product_id' => $product1->id,
    'product_name' => $product1->name,
    'project_id' => $project->id,
    'quantity' => 2,
    'price' => 12500000,
    'cost' => 9000000,
    'total' => 25000000,
]);
$sale->items()->create([
    'product_id' => $product2->id,
    'product_name' => $product2->name,
    'project_id' => $project->id,
    'quantity' => 1,
    'price' => 5500000,
    'cost' => 4000000,
    'total' => 5500000,
]);
$sale->update(['total' => 30500000]);
$sale->load('items.product');

echo "3. Chuẩn bị Đơn hàng #{$sale->code} (Tổng: " . number_format($sale->total, 0, ',', '.') . " đ, {$sale->items->count()} sản phẩm)\n";

// 3. Test Auto-Sync Closure
echo "\n4. Test: Đóng dự án với Auto-Sync (Đồng bộ PO Code, Giá trị, Ngày và BOM)\n";
$controller = app(\App\Http\Controllers\ProjectController::class);

$syncRequest = Request::create(route('projects.close', $project->id), 'POST', [
    'close_status' => 'closed_won',
    'source_mode' => 'sync_sale',
    'sale_id' => $sale->id,
    'sync_bom' => '1',
    'close_note' => 'Chốt deal thành công theo đơn ' . $sale->code,
]);

$response = $controller->closeProject($syncRequest, $project);
$project->refresh();

echo "   - Kết quả đóng dự án: {$project->registration_status} (Status: {$project->status})\n";
echo "   - PO Code đồng bộ: {$project->po_code}\n";
echo "   - Giá trị đơn hàng đồng bộ: " . number_format($project->order_value, 0, ',', '.') . " đ\n";
echo "   - Ngày đặt hàng: {$project->order_date}\n";
echo "   - BOM Data được đồng bộ:\n";
echo "--------------------------------------------------\n";
echo $project->bom_data . "\n";
echo "--------------------------------------------------\n";

if ($project->registration_status === 'closed_won' 
    && $project->po_code === $sale->code 
    && (float)$project->order_value === (float)$sale->total
    && str_contains($project->bom_data, 'FT-TEST-01')
    && str_contains($project->bom_data, 'FT-TEST-02')) {
    echo "=> [PASS] ĐỒNG BỘ ĐÓNG DỰ ÁN VỚI ĐƠN HÀNG THÀNH CÔNG!\n";
} else {
    echo "=> [FAIL] Có lỗi khi đồng bộ đóng dự án.\n";
}

// 4. Test Manual Mode Closure (Gộp nhiều hãng / nhiều đơn DA)
echo "\n5. Test: Đóng dự án theo chế độ Nhập thủ công (Gộp hãng/nhiều DA)\n";
$project->update([
    'registration_status' => 'update_status',
    'status' => 'in_progress',
]);

$manualRequest = Request::create(route('projects.close', $project->id), 'POST', [
    'close_status' => 'closed_won',
    'source_mode' => 'manual',
    'po_code' => 'PO-CUSTOM-MULTI-VENDOR-999',
    'order_value' => '88000000',
    'order_date' => '2026-09-15',
    'close_note' => 'Gộp từ 2 hãng phân phối cho hợp đồng gói thầu số 5',
]);

$controller->closeProject($manualRequest, $project);
$project->refresh();

echo "   - Kết quả đóng thủ công: {$project->registration_status} (Status: {$project->status})\n";
echo "   - PO Code thủ công: {$project->po_code}\n";
echo "   - Giá trị đơn hàng thủ công: " . number_format($project->order_value, 0, ',', '.') . " đ\n";
echo "   - Ghi chú: {$project->close_note}\n";

if ($project->po_code === 'PO-CUSTOM-MULTI-VENDOR-999' && (float)$project->order_value === 88000000.0) {
    echo "=> [PASS] ĐÓNG DỰ ÁN CHẾ ĐỘ NHẬP THỦ CÔNG THÀNH CÔNG!\n";
} else {
    echo "=> [FAIL] Lỗi đóng thủ công.\n";
}

// 5. Test Blade rendering for projects.show and sales.show
\Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag());
echo "\n6. Test: Render Blade View projects.show & sales.show\n";
try {
    // projects.show
    $projView = $controller->show($project);
    $renderedProj = $projView->render();
    echo "   - [projects.show]: Render thành công (" . strlen($renderedProj) . " bytes)\n";
    
    // sales.show
    $saleController = app(\App\Http\Controllers\SaleController::class);
    $saleView = $saleController->show($sale);
    $renderedSale = $saleView->render();
    echo "   - [sales.show]: Render thành công (" . strlen($renderedSale) . " bytes)\n";
    
    echo "=> [PASS] TẤT CẢ GIAO DIỆN BLADE RENDER HOÀN HẢO!\n";
} catch (\Throwable $e) {
    echo "=> [FAIL] Lỗi render Blade: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== HOÀN TẤT TOÀN BỘ KIỂM THỬ ===\n";
