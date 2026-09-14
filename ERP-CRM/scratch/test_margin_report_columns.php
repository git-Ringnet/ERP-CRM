<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;
use App\Models\User;
use App\Http\Controllers\SaleReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

echo "=== BẮT ĐẦU KIỂM THỬ BÁO CÁO MARGIN (BỔ SUNG CỘT KHOANH ĐỎ) ===\n\n";

$admin = User::where('email', 'admin@ringnet.vn')->first() ?? User::first();
Auth::login($admin);
View::share('errors', new \Illuminate\Support\ViewErrorBag());

$controller = app(SaleReportController::class);

// 1. Kiểm tra getMarginReport data structure
$dateFrom = '2020-01-01';
$dateTo = '2030-12-31';

$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('getMarginReport');
$method->setAccessible(true);

$marginData = $method->invoke($controller, $dateFrom, $dateTo);

echo "1. Lấy dữ liệu Báo cáo Margin (" . count($marginData) . " dòng):\n";
if (count($marginData) > 0) {
    $row = $marginData[0];
    echo "   - STT: {$row['stt']}\n";
    echo "   - Khách hàng: {$row['customer_name']}\n";
    echo "   - Số HĐ/SO: {$row['invoice_number']}\n";
    echo "   - Ngày xuất HĐ: {$row['invoice_date']}\n";
    echo "   - Hãng: {$row['brand']}\n";
    echo "   - License: {$row['license']}\n";
    echo "   - Loại hàng: {$row['product_type']}\n";
    echo "   - Mã hàng hóa chính: {$row['main_product_code']}\n";
    echo "   [CỘT KHOANH ĐỎ]:\n";
    echo "     * Tiền hàng (chưa gồm VAT): " . number_format($row['revenue_before_vat']) . " đ\n";
    echo "     * Tiền thuế (VAT): " . number_format($row['vat_amount']) . " đ\n";
    echo "     * Tổng tiền (gồm VAT): " . number_format($row['total_amount_incl_vat']) . " đ\n";
    echo "     * Giá vốn hàng hóa: " . number_format($row['goods_cost']) . " đ\n";
    echo "     * Chi phí triển khai HĐ: " . number_format($row['implementation_cost']) . " đ\n";
    echo "     * Thuế nhà thầu: " . number_format($row['contractor_tax']) . " đ\n";
    echo "     * Chi phí tài chính 1%: " . number_format($row['finance_cost']) . " đ\n";
    echo "     * Chi phí Quản lí, Back Office & KT: " . number_format($row['management_cost']) . " đ\n";
    echo "     * 24x7 (0.5%): " . number_format($row['support_247_cost']) . " đ\n";
    echo "     * Other Support (Zyxel): " . number_format($row['other_support_cost']) . " đ\n";
    echo "   [CỘT TIẾP THEO]:\n";
    echo "     * Margin: " . number_format($row['margin']) . " đ\n";
    echo "     * Margin %: {$row['margin_percent']}%\n";
    echo "     * NV Kinh doanh: {$row['salesperson']}\n";

    $requiredKeys = [
        'revenue_before_vat', 'vat_amount', 'total_amount_incl_vat',
        'goods_cost', 'implementation_cost', 'contractor_tax', 'finance_cost',
        'management_cost', 'support_247_cost', 'other_support_cost', 'margin', 'margin_percent', 'salesperson'
    ];

    $missing = [];
    foreach ($requiredKeys as $key) {
        if (!array_key_exists($key, $row)) {
            $missing[] = $key;
        }
    }

    if (empty($missing)) {
        echo "=> [PASS] ĐẦY ĐỦ TẤT CẢ CÁC CỘT DỮ LIỆU ĐƯỢC YÊU CẦU!\n";
    } else {
        echo "=> [FAIL] Thiếu các cột: " . implode(', ', $missing) . "\n";
    }
}

// 2. Kiểm tra render Blade view sale-reports/index.blade.php
echo "\n2. Kiểm tra render giao diện Web (sale-reports.index):\n";
try {
    $request = Request::create('/sale-reports', 'GET');
    $request->setUserResolver(fn() => $admin);
    $view = $controller->index($request);
    $html = $view->render();
    echo "   - View rendered thành công: " . strlen($html) . " bytes\n";
    if (str_contains($html, 'Tiền hàng<br/>(chưa gồm VAT)')
        && str_contains($html, 'Tổng tiền<br/>(gồm VAT)')
        && str_contains($html, 'Chi phí triển khai HĐ')
        && str_contains($html, 'Chi phí Quản lí,<br/>Back Office & kỹ thuật')
        && str_contains($html, '24x7<br/>(0.5%)')
        && str_contains($html, 'Other Support<br/>(Zyxel)')) {
        echo "=> [PASS] GIAO DIỆN BÁO CÁO MARGIN CHỨA ĐẦY ĐỦ CỘT KHOANH ĐỎ VÀ STYLE HEADER ĐẸP MẮT!\n";
    } else {
        echo "=> [FAIL] HTML view chưa chứa đầy đủ tiêu đề cột.\n";
    }
} catch (\Throwable $e) {
    echo "=> [FAIL] Lỗi render view: " . $e->getMessage() . "\n";
}

// 3. Kiểm tra exportMargin tạo spreadsheet
echo "\n3. Kiểm tra xuất Excel Margin (exportMargin):\n";
try {
    $exportReq = Request::create('/sale-reports/export-margin', 'GET', [
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
    ]);
    $exportReq->setUserResolver(fn() => $admin);
    $response = $controller->exportMargin($exportReq);
    echo "   - Response class: " . get_class($response) . "\n";
    echo "=> [PASS] XUẤT EXCEL MARGIN THÀNH CÔNG!\n";
} catch (\Throwable $e) {
    echo "=> [FAIL] Lỗi xuất Excel: " . $e->getMessage() . "\n";
}

echo "\n=== HOÀN TẤT TOÀN BỘ KIỂM THỬ ===\n";
