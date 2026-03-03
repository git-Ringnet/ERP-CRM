<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get admin user
$user = App\Models\User::where('email', 'admin@example.com')->first();

if (!$user) {
    echo "Admin user not found!\n";
    exit(1);
}

echo "Testing WarrantyPolicy for user: {$user->name} (ID: {$user->id})\n";
echo "User roles: " . $user->roles->pluck('name')->implode(', ') . "\n\n";

// Get policy instance
$policy = app(App\Policies\WarrantyPolicy::class);

// Test viewAny
try {
    $canViewAny = $policy->viewAny($user);
    echo "✓ viewAny: " . ($canViewAny ? 'TRUE' : 'FALSE') . "\n";
} catch (Exception $e) {
    echo "✗ viewAny ERROR: " . $e->getMessage() . "\n";
}

// Test export
try {
    $canExport = $policy->export($user);
    echo "✓ export: " . ($canExport ? 'TRUE' : 'FALSE') . "\n";
} catch (Exception $e) {
    echo "✗ export ERROR: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";
