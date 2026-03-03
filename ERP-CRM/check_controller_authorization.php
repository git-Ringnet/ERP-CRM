<?php

$controllersDir = __DIR__ . '/app/Http/Controllers';
$controllers = glob($controllersDir . '/*.php');

$withAuth = [];
$withoutAuth = [];

foreach ($controllers as $file) {
    $content = file_get_contents($file);
    $filename = basename($file);
    
    // Skip base Controller
    if ($filename === 'Controller.php') {
        continue;
    }
    
    // Check for authorization
    $hasAuthorizeResource = strpos($content, 'authorizeResource') !== false;
    $hasAuthorize = strpos($content, '$this->authorize(') !== false;
    $hasGate = strpos($content, 'Gate::authorize') !== false;
    $hasMiddleware = strpos($content, "middleware('permission:") !== false;
    
    if ($hasAuthorizeResource || $hasAuthorize || $hasGate || $hasMiddleware) {
        $withAuth[] = $filename;
    } else {
        $withoutAuth[] = $filename;
    }
}

echo "=== CONTROLLERS WITH AUTHORIZATION (" . count($withAuth) . ") ===\n";
sort($withAuth);
foreach ($withAuth as $controller) {
    echo "✓ $controller\n";
}

echo "\n=== CONTROLLERS WITHOUT AUTHORIZATION (" . count($withoutAuth) . ") ===\n";
sort($withoutAuth);
foreach ($withoutAuth as $controller) {
    echo "✗ $controller\n";
}

echo "\n=== SUMMARY ===\n";
echo "Total controllers: " . (count($withAuth) + count($withoutAuth)) . "\n";
echo "With authorization: " . count($withAuth) . "\n";
echo "Without authorization: " . count($withoutAuth) . "\n";
