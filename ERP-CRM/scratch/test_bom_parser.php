<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new \App\Services\BomParserService();

$bomText1 = <<<EOT
STT	Part Number	Model / Description	Số lượng	Đơn giá	Thành tiền
1	AW210040	AirEngine 5760-51	2	15,000,000	30,000,000
2	FG-60F-BDL	FortiGate 60F Hardware plus FortiCare	1	12.500.000	12.500.000
EOT;

$results1 = $service->parse($bomText1, 1);
echo "=== TEST 1 (Tab-delimited with headers) ===\n";
print_r($results1);

$bomText2 = <<<EOT
2x FG-60F-BDL
Switch Cisco C9200L-24P-4G-E (Qty: 3, Đơn giá: 25.000.000)
AW210040 - Huawei AirEngine - 5 cái @ 14,000,000
EOT;

$results2 = $service->parse($bomText2, 2);
echo "=== TEST 2 (Freeform text) ===\n";
print_r($results2);
