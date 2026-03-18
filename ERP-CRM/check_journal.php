<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WarehouseJournalEntry;
use App\Models\Import;

echo "Total Imports: " . Import::count() . "\n";
echo "Pending Imports: " . Import::where('status', 'pending')->count() . "\n";
echo "Total Journal Entries: " . WarehouseJournalEntry::count() . "\n";

echo "Journal Entries for IMP00002:\n";
foreach (WarehouseJournalEntry::where('reference_code', 'IMP00002')->get() as $e) {
    echo "- Action: {$e->action}, Status: {$e->status}, Date: {$e->entry_date}\n";
}

echo "Journal Entries for IMP00003:\n";
foreach (WarehouseJournalEntry::where('reference_code', 'IMP00003')->get() as $e) {
    echo "- Action: {$e->action}, Status: {$e->status}, Date: {$e->entry_date}\n";
}

echo "All reference codes in Journal:\n";
echo implode(', ', WarehouseJournalEntry::pluck('reference_code')->toArray()) . "\n";
