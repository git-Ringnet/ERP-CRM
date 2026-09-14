<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Project;
use App\Models\Quotation;
use App\Models\Customer;
use App\Models\User;
use App\Models\Sale;
use App\Http\Controllers\QuotationController;

$user = User::first();
\Illuminate\Support\Facades\Auth::login($user);

$project = Project::first();
$customer = $project->customer ?? Customer::first();
$contact = $customer->contacts()->first();

$quotationCode = 'BG-TEST-' . time();
$quotation = Quotation::create([
    'code' => $quotationCode,
    'title' => 'Báo giá kiểm tra liên kết dự án ' . $project->code,
    'customer_id' => $customer->id,
    'contact_id' => $contact ? $contact->id : 1,
    'customer_name' => $customer->name,
    'project_id' => $project->id,
    'created_by' => $user->id,
    'date' => date('Y-m-d'),
    'valid_until' => date('Y-m-d', strtotime('+30 days')),
    'currency_id' => 1,
    'exchange_rate' => 1,
    'discount' => 0,
    'subtotal' => 30000000,
    'vat' => 0,
    'total' => 30000000,
    'status' => 'approved',
]);

echo "Created Quotation #{$quotation->id} with project_id={$quotation->project_id}\n";

$controller = app(QuotationController::class);
$convertResponse = $controller->convertToSale($quotation);

if (session('error')) {
    echo "Convert Error: " . session('error') . "\n";
}

$quotation->refresh();
echo "Quotation Status: {$quotation->status}\n";
echo "Converted to Sale ID: " . ($quotation->converted_to_sale_id ?? 'NULL') . "\n";

if ($quotation->converted_to_sale_id) {
    $createdSale = Sale::find($quotation->converted_to_sale_id);
    echo "Converted to Sale #{$createdSale->id} ({$createdSale->code}):\n";
    echo "   - Sale type: {$createdSale->type}\n";
    echo "   - Sale project_id: {$createdSale->project_id}\n";
    echo "   - Sale project relation: " . ($createdSale->project ? $createdSale->project->code : 'NONE') . "\n";
}
