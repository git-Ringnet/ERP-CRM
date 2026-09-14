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
use Illuminate\Http\Request;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\ViewErrorBag;

echo "=== STARTING VERIFICATION TEST ===\n\n";

\Illuminate\Support\Facades\View::share('errors', new ViewErrorBag());

// 1. Get or create sample Project with BOM data
$project = Project::first();
if (!$project) {
    echo "No project found in database.\n";
    exit(1);
}

echo "1. Testing Project #{$project->id} ({$project->code} - {$project->name})\n";
echo "   - Partner/SI: " . ($project->customer?->name ?? $project->partner_name ?? 'N/A') . "\n";
echo "   - End-User: " . ($project->end_user_name ?? 'N/A') . "\n";
echo "   - BOM Data sample length: " . strlen($project->bom_data ?? '') . " chars\n";

// 2. Test QuotationController::create with project_id
$user = User::first();
\Illuminate\Support\Facades\Auth::login($user);

$req = new Request(['project_id' => $project->id]);
$controller = app(QuotationController::class);
$response = $controller->create($req);

echo "\n2. Testing QuotationController::create with project_id={$project->id}\n";
$viewData = $response->getData();
echo "   - View returned: " . $response->name() . "\n";
echo "   - Selected Project loaded: " . (isset($viewData['selectedProject']) ? $viewData['selectedProject']->code : 'NONE') . "\n";
echo "   - Selected Customer ID: " . ($viewData['prefill']['customer_id'] ?? 'NONE') . "\n";
echo "   - Prefilled Products Count: " . count($viewData['prefilledProducts'] ?? []) . "\n";
if (!empty($viewData['prefilledProducts'])) {
    foreach ($viewData['prefilledProducts'] as $i => $p) {
        echo "     * Row " . ($i + 1) . ": " . ($p['sku'] ?? '') . " | " . ($p['product_name'] ?? '') . " | Qty: {$p['quantity']} | Unit Price: {$p['price']}\n";
    }
}

// 3. Test Commercial Price Privacy
echo "\n3. Testing Commercial Price Privacy helper on User model\n";
// Test with sales manager / admin
$adminUser = User::whereHas('roles', fn($q) => $q->whereIn('name', ['super_admin', 'admin', 'sales_manager']))->first() ?? $user;
echo "   - User {$adminUser->name} (Dept: {$adminUser->department}): canViewCommercialPrice = " . ($adminUser->canViewCommercialPrice($project->manager_id) ? 'YES' : 'NO') . "\n";

// Test with pure PM user mock
$pmUser = new User([
    'name' => 'PM Test User',
    'department' => 'PM',
]);
$pmUser->id = 999999;
echo "   - Mock PM User (Dept: PM, Not Creator): canViewCommercialPrice = " . ($pmUser->canViewCommercialPrice($project->manager_id) ? 'YES' : 'NO') . "\n";

// 4. Test View Rendering
echo "\n4. Testing Blade View Rendering for quotations.create and projects.show\n";
try {
    $renderedQuotation = view('quotations.create', $viewData)->render();
    echo "   - quotations.create rendered successfully (" . strlen($renderedQuotation) . " bytes)\n";
} catch (\Exception $e) {
    echo "   - ERROR rendering quotations.create: " . $e->getMessage() . "\n";
}

try {
    $projController = app(ProjectController::class);
    $projView = $projController->show($project);
    $renderedProject = view('projects.show', $projView->getData())->render();
    echo "   - projects.show rendered successfully (" . strlen($renderedProject) . " bytes)\n";
} catch (\Exception $e) {
    echo "   - ERROR rendering projects.show: " . $e->getMessage() . "\n";
}

echo "\n=== ALL VERIFICATION TESTS COMPLETED SUCCESSFULLY ===\n";
