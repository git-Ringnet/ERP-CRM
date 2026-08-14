<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // Update existing exports linked to completed invoice requests that got stuck
            $officialInvoiceRequests = \App\Models\InvoiceRequest::where('status', 'official_issued')->get();
            foreach ($officialInvoiceRequests as $req) {
                if ($req->export_id) {
                    $export = \App\Models\Export::find($req->export_id);
                    if ($export && $export->status === 'pending_invoice') {
                        $export->update(['status' => 'pending']);
                    }
                } else {
                    $linkedExports = \App\Models\Export::where('reference_type', 'sale')
                        ->where('reference_id', $req->sale_id)
                        ->where('status', 'pending_invoice')
                        ->get();
                    foreach ($linkedExports as $le) {
                        $le->update(['status' => 'pending']);
                    }
                }
            }
        } catch (\Exception $e) {
            // Silence if tables don't exist yet in tests
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed for data fix
    }
};
