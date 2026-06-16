<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Update contacts (customer contacts)
$contacts = DB::table('contacts')->get();
$countContacts = 0;
foreach ($contacts as $contact) {
    if ($contact->first_name && $contact->last_name) {
        $oldName = trim($contact->first_name . ' ' . $contact->last_name);
        $newName = trim($contact->last_name . ' ' . $contact->first_name);
        // Check if name is currently first + last
        if ($contact->name === $oldName) {
            DB::table('contacts')->where('id', $contact->id)->update([
                'name' => $newName,
                'updated_at' => now(),
            ]);
            $countContacts++;
        }
    }
}

// Update supplier_contacts
$supplierContacts = DB::table('supplier_contacts')->get();
$countSuppliers = 0;
foreach ($supplierContacts as $contact) {
    if ($contact->first_name && $contact->last_name) {
        $oldName = trim($contact->first_name . ' ' . $contact->last_name);
        $newName = trim($contact->last_name . ' ' . $contact->first_name);
        // Check if name is currently first + last
        if ($contact->name === $oldName) {
            DB::table('supplier_contacts')->where('id', $contact->id)->update([
                'name' => $newName,
                'updated_at' => now(),
                'updated_at' => now(),
            ]);
            $countSuppliers++;
        }
    }
}

echo "Updated {$countContacts} customer contacts and {$countSuppliers} supplier contacts.\n";
