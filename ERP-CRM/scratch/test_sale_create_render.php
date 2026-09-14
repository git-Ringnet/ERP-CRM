<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$user = User::first();
Auth::login($user);
\Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag());

$request = Request::create('/sales/create?project_id=1', 'GET');
$controller = app(\App\Http\Controllers\SaleController::class);

try {
    $response = $controller->create($request);
    $view = $response->render();
    echo "SUCCESS: sales.create rendered successfully! Content length: " . strlen($view) . " bytes.\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
