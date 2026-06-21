<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoCompanyConfig extends Model
{
    use HasFactory;

    protected $table = 'po_company_config';

    protected $fillable = [
        'buyer_name',
        'buyer_address_line1',
        'buyer_address_line2',
        'buyer_tel',
        'buyer_fax',
        'buyer_contact',
        'buyer_bank_account',
        'buyer_bank_name',
        'buyer_bank_address_line1',
        'buyer_bank_address_line2',
        'buyer_swift_code',
        'ship_to_name',
        'ship_to_address_line1',
        'ship_to_address_line2',
        'ship_to_attn',
        'invoice_to_name',
        'invoice_to_address_line1',
        'invoice_to_address_line2',
        'invoice_to_attn',
        'company_full_name',
        'hcmc_address',
        'hanoi_address',
        'website',
        'email',
        'phone',
        'header_logo_path',
        'header_banner_path',
        'signer_name',
        'signer_title',
    ];

    public static function getConfig(): self
    {
        return static::firstOrCreate([], [
            'buyer_name' => 'TECH HORIZON CORP',
            'buyer_address_line1' => 'No. 22, Street No. 9, Trung Son Residential Area, Hamlet 49,',
            'buyer_address_line2' => 'Binh Hung Commune, Binh Chanh Dist, Ho Chi Minh City, VietNam',
            'buyer_tel' => '(+848) 5431 6046',
            'buyer_fax' => '(+848) 5431 6047',
            'buyer_contact' => 'Mr. TRAN QUOC TRUNG - Product Director',
            'buyer_bank_account' => '2681100012008',
            'buyer_bank_name' => 'Military Commercial JS Bank (MBBank), Binh Chanh Branch',
            'buyer_bank_address_line1' => '207-209, Street 9A, Hamlet 48, Trung Son area,',
            'buyer_bank_address_line2' => 'Binh Hung commune, Hochiminh city, Vietnam',
            'buyer_swift_code' => 'MSCBVNVX',
            'ship_to_name' => 'TECH HORIZON CORP',
            'ship_to_address_line1' => 'No. 22, Street No. 9, Trung Son Residential Area, Hamlet 49,',
            'ship_to_address_line2' => 'Binh Hung Commune, Binh Chanh Dist, Ho Chi Minh City, VietNam',
            'ship_to_attn' => 'Mr. TRAN QUOC TRUNG - Mail: trung.tran@techhorizonvn.com',
            'invoice_to_name' => 'TECH HORIZON CORP',
            'invoice_to_address_line1' => 'No. 22, Street No. 9, Trung Son Residential Area, Hamlet 49,',
            'invoice_to_address_line2' => 'Binh Hung Commune, Binh Chanh Dist, Ho Chi Minh City, VietNam',
            'invoice_to_attn' => 'Tax code: 0303746883',
            'company_full_name' => 'TECH HORIZON CORP',
            'hcmc_address' => 'No. 22, Street No. 9, Trung Son Area, Hamlet 49, Binh Hung Commune, Binh Chanh District, HCMC, Vietnam',
            'hanoi_address' => '25th Floor, Tower B1, Roman Plaza, To Huu Street, Dai Mo Ward, Nam Tu Liem District, Hanoi, Vietnam',
            'website' => 'www.techhorizonvn.com',
            'email' => 'info@techhorizonvn.com',
            'phone' => '(+84) 28 5431 6046',
            'signer_name' => 'TRAN QUOC TRUNG',
            'signer_title' => 'Product Director',
        ]);
    }
}
