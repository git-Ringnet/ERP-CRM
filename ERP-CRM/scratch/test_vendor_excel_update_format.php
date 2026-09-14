<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Project;
use App\Models\User;
use App\Exports\ProjectsExport;
use Carbon\Carbon;

echo "=== KIỂM THỬ ĐỊNH DẠNG CẬP NHẬT DỰ ÁN GỬI HÃNG ===\n\n";

$admin = User::where('email', 'admin@ringnet.vn')->first() ?? User::first();

$project = Project::firstOrCreate(
    ['code' => 'TEST-VENDOR-UPDATE-PRJ'],
    [
        'name' => 'Dự án kiểm thử format update gửi hãng',
        'customer_id' => 1,
        'vendor_id' => 1,
        'manager_id' => $admin->id,
        'status' => 'in_progress',
        'registration_status' => 'update_status',
        'eu_name_vi' => 'Công ty Cổ phần Thử nghiệm',
        'eu_tax_code' => '0301091700',
        'collaborate_type' => 'end_user',
        'distributor_am' => 'test@ringnet.vn | Admin',
    ]
);

// Clear old test notes & status updates
$project->notes()->delete();
$project->statusUpdates()->delete();

// 1. Giả lập Sales cập nhật định kỳ: Báo giá dự toán vào lúc 20:30 ngày 02/09/2027
$su = $project->statusUpdates()->create([
    'user_id' => $admin->id,
    'forecast_stage' => 'budget_quote',
    'support_request_note' => null,
]);
// Set exact datetime as requested: 20:30 2.9.2027
$su->created_at = Carbon::create(2027, 9, 2, 20, 30, 0);
$su->save();

// 2. Giả lập PM gửi note phản hồi vào lúc 09:15 ngày 03/09/2027
$pmNote = $project->notes()->create([
    'user_id' => $admin->id,
    'user_role' => 'pm',
    'content' => 'Đã gửi thông tin dự án cho Hãng và xin hỗ trợ giá đặc biệt',
]);
$pmNote->created_at = Carbon::create(2027, 9, 3, 9, 15, 0);
$pmNote->save();

$project->refresh();
$project->load(['notes.user', 'statusUpdates.user', 'vendorQuoteVersions.creator']);

echo "1. Danh sách timeline updates:\n";
$timeline = $project->getTimelineUpdates();
foreach ($timeline as $idx => $entry) {
    echo "   [{$idx}] " . $entry['formatted'] . "\n";
}

echo "\n2. Tổng hợp tóm tắt (Latest update):\n";
echo "   " . $project->getFormattedUpdatesSummary(false) . "\n";

echo "\n3. Toàn bộ lịch sử cập nhật (Full timeline):\n";
echo $project->getFormattedUpdatesSummary(true) . "\n";

// 4. Test xuất mảng ProjectsExport
echo "\n4. Kiểm tra dữ liệu khi xuất Excel (ProjectsExport):\n";
$export = new ProjectsExport(['project_id' => $project->id]);
$rows = $export->array();

echo "   - Số dòng export: " . count($rows) . "\n";
$firstRow = $rows[0];
echo "   - Project: " . $firstRow['project_name'] . "\n";
echo "   - Last update cell:\n";
echo "--------------------------------------------------\n";
echo $firstRow['last_update'] . "\n";
echo "--------------------------------------------------\n";

if (str_contains($firstRow['last_update'], '[20:30 2.9.2027] Sales: Đã báo giá dự toán')
    && str_contains($firstRow['last_update'], '[09:15 3.9.2027] PM: Đã gửi thông tin dự án cho Hãng')) {
    echo "=> [PASS] ĐỊNH DẠNG CẬP NHẬT DỰ ÁN HOÀN TOÀN CHÍNH XÁC!\n";
} else {
    echo "=> [FAIL] Định dạng chưa khớp yêu cầu.\n";
}

echo "\n=== HOÀN TẤT KIỂM THỬ ===\n";
