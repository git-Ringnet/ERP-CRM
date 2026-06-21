<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPoConfig extends Model
{
    use HasFactory;

    protected $table = 'supplier_po_configs';

    protected $fillable = [
        'supplier_id',
        'template_type',
        'seller_name',
        'seller_address_line1',
        'seller_address_line2',
        'seller_tel',
        'seller_fax',
        'seller_contact',
        'seller_beneficiary',
        'seller_beneficiary_address',
        'seller_bank_name',
        'seller_bank_account',
        'seller_bank_address_line1',
        'seller_bank_address_line2',
        'seller_bank_aba',
        'seller_swift_code',
        'port_loading',
        'port_discharge',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
