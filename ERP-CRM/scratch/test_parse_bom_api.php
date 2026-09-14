<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$user = User::first();
Auth::login($user);

$postData = [
    'bom_data' => "STT\tP/N\tDescription\tQuantity\tPrice\n1\tAW210040\tHuawei AP\t3\t15000000\n2\tFG-60F\tFortiGate 60F\t2\t21000000",
    'project_id' => 1
];

$request = Request::create('/sales/parse-bom', 'POST', $postData);
$controller = app(\App\Http\Controllers\SaleController::class);

$response = $controller->parseBom($request);
echo "API response:\n";
echo json_encode($response->getData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
