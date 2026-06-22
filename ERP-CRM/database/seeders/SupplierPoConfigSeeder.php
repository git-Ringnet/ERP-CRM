<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SupplierPoConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // 1. Ensure missing suppliers are created
        $missingSuppliers = [
            [
                'code' => 'CO001',
                'name' => 'ClearOne',
                'email' => 'clearone@example.com',
                'phone' => '(801) 9757200',
            ],
            [
                'code' => 'IFT001',
                'name' => 'Infortrend',
                'email' => 'infortrend@example.com',
                'phone' => '886-2-22260126',
            ],
        ];

        foreach ($missingSuppliers as $supplier) {
            DB::table('suppliers')->updateOrInsert(
                ['code' => $supplier['code']],
                array_merge($supplier, [
                    'created_at' => $now,
                    'updated_at' => $now,
                    'payment_terms' => 30,
                    'base_discount' => 0,
                    'volume_discount' => 0,
                    'volume_threshold' => 0,
                    'early_payment_discount' => 0,
                    'early_payment_days' => 7,
                    'special_discount' => 0,
                ])
            );
        }

        // Map brand names/codes to the supplier IDs in the DB
        $supplierConfigs = [
            // ARRAY NETWORKS, INC
            'AN001' => [
                'template_type' => 'sale_contract',
                'seller_name' => 'ARRAY NETWORKS, INC',
                'seller_address_line1' => '1371 Mc Carthy Blvd, Milpitas, CA 95035, USA.',
                'seller_address_line2' => '',
                'seller_tel' => '(408) 240-8700',
                'seller_fax' => '',
                'seller_contact' => 'Srinivas Vege - svege@arraynetworks.net',
                'seller_beneficiary' => 'ARRAY NETWORKS, INC',
                'seller_beneficiary_address' => '1371 Mc Carthy Blvd, Milpitas, CA 95035, USA',
                'seller_bank_name' => 'Cathay Bank',
                'seller_bank_account' => '12016020 (main checking account)',
                'seller_bank_address_line1' => '10480 S. De Anza Blvd. Cupertino, CA 95014, U.S.A.',
                'seller_bank_address_line2' => '',
                'seller_bank_aba' => '122203950',
                'seller_swift_code' => 'CATHUS6L',
                'port_loading' => 'USA',
                'port_discharge' => 'HOCHIMINH CITY, VIETNAM',
            ],
            // CÔNG TY TNHH PHÁT TRIỂN B (VIỆT NAM)
            'PTB001' => [
                'template_type' => 'sale_contract',
                'seller_name' => 'CÔNG TY TNHH PHÁT TRIỂN B (VIỆT NAM)',
                'seller_address_line1' => 'Room 901, Kim Anh Building, 78 Duy Tan Street,',
                'seller_address_line2' => 'Dich Vong Hau ward, Cau Giay District, Hanoi City, Vietnam',
                'seller_tel' => '886-2-2641-2000',
                'seller_fax' => '886-2-2641-0555',
                'seller_contact' => '',
                'seller_beneficiary' => 'CÔNG TY TNHH PHÁT TRIỂN B (VIỆT NAM)',
                'seller_beneficiary_address' => 'Room 901, Kim Anh Building, 78 Duy Tan Street, Dich Vong Hau ward, Cau Giay District, Hanoi City, Vietnam',
                'seller_bank_name' => 'Standard Chartered Việt Nam, Chi nhánh Hà Nội',
                'seller_bank_account' => '66172064355',
                'seller_bank_address_line1' => '',
                'seller_bank_address_line2' => '',
                'seller_bank_aba' => '',
                'seller_swift_code' => '',
                'port_loading' => 'VIETNAM',
                'port_discharge' => 'HOCHIMINH CITY, VIETNAM',
            ],
            // CLEARONE INC
            'CO001' => [
                'template_type' => 'sale_contract',
                'seller_name' => 'CLEARONE INC',
                'seller_address_line1' => '5225 WILEY POST WAY SUITE 500 SALT LAKE CITY, UT 84116, USA',
                'seller_address_line2' => '',
                'seller_tel' => '(801) 9757200',
                'seller_fax' => '(801) 9750087',
                'seller_contact' => 'Srinivas Vege - svege@arraynetworks.net',
                'seller_beneficiary' => 'CLEARONE INC',
                'seller_beneficiary_address' => '5225 WILEY POST WAY SUITE 500 SALT LAKE CITY, UT 84116, USA',
                'seller_bank_name' => 'U.S. Bank, N.A',
                'seller_bank_account' => '153195059370',
                'seller_bank_address_line1' => '170 South Main Street, 6th Floor, Salt Lake City, Utah 84101, USA',
                'seller_bank_address_line2' => 'Beneficiary Account Name: CLEARONE INCORPORATED',
                'seller_bank_aba' => '124302150',
                'seller_swift_code' => 'USBKUS44IMT',
                'port_loading' => 'USA',
                'port_discharge' => 'HOCHIMINH CITY, VIETNAM',
            ],
            // Aditya Infotech Limited
            'AIL001' => [
                'template_type' => 'sale_contract',
                'seller_name' => 'Aditya Infotech Limited',
                'seller_address_line1' => 'A-12, Sector 4, NOIDA - 201301, (Delhi-NCR) India',
                'seller_address_line2' => '',
                'seller_tel' => '00 91-120-4555666',
                'seller_fax' => '',
                'seller_contact' => 'Mr. Pranay Sharma – Assistant General Manager (International Business)',
                'seller_beneficiary' => 'Aditya Infotech Limited',
                'seller_beneficiary_address' => 'A-12, Sector 4, NOIDA - 201301, (Delhi-NCR) India',
                'seller_bank_name' => 'Tamilnad Mercantile Bank Ltd',
                'seller_bank_account' => '100700150410474',
                'seller_bank_address_line1' => '384-390, First Floor, Loke Nath Building, (Opp. To Post Office),',
                'seller_bank_address_line2' => 'Chandni Chowk, Delhi - 110006 (INDIA)',
                'seller_bank_aba' => '',
                'seller_swift_code' => 'TMBLINBBCHE',
                'port_loading' => 'INDIA',
                'port_discharge' => 'HOCHIMINH CITY, VIETNAM',
            ],
            // GROUP-IB GLOBAL PRIVATE LIMITED -> Group-IB (GIB001)
            'GIB001' => [
                'template_type' => 'sale_contract',
                'seller_name' => 'GROUP-IB GLOBAL PRIVATE LIMITED',
                'seller_address_line1' => '108 Robinson Road, #07-01, Singapore 068900',
                'seller_address_line2' => '',
                'seller_tel' => '+65 3159 3798',
                'seller_fax' => '',
                'seller_contact' => 'NATASHA HERVYNA - Sales Coordinator | Group-IB | +6594783655',
                'seller_beneficiary' => 'GROUP-IB GLOBAL PRIVATE LIMITED',
                'seller_beneficiary_address' => '108 Robinson Road, #07-01, Singapore 068900',
                'seller_bank_name' => 'Citibank, N.A., Singapore Branch Singapore',
                'seller_bank_account' => '0119450012',
                'seller_bank_address_line1' => '5 CHANGI BUSINESS PARK CRESCENT, LEVEL 5, SINGAPORE 486027',
                'seller_bank_address_line2' => '',
                'seller_bank_aba' => '',
                'seller_swift_code' => 'CITISGSGXXX(Giro)',
                'port_loading' => 'SINGAPORE',
                'port_discharge' => 'HOCHIMINH CITY, VIETNAM',
            ],
            // Infortrend Technology Inc. -> Infortrend (IFT001)
            'IFT001' => [
                'template_type' => 'sale_contract',
                'seller_name' => 'Infortrend Technology Inc.',
                'seller_address_line1' => '8F., No. 102, Sec. 3, Jhongshan Rd., Jhonghe Dist.,',
                'seller_address_line2' => 'New Taipei City 23544, Taiwan',
                'seller_tel' => '886-2-22260126',
                'seller_fax' => '886-2-22260020',
                'seller_contact' => '',
                'seller_beneficiary' => 'Infortrend Technology Inc.',
                'seller_beneficiary_address' => '8F., No. 102, Sec. 3, Jhongshan Rd., Jhonghe Dist., New Taipei City 23544, Taiwan',
                'seller_bank_name' => 'Taiwan Business Bank, Pan-Chiao Branch',
                'seller_bank_account' => '14050006019',
                'seller_bank_address_line1' => '2-1, Ming-Te St. Pan-Chiao Dist., New Taipei City, Taiwan.',
                'seller_bank_address_line2' => '',
                'seller_bank_aba' => '',
                'seller_swift_code' => 'MBBTTWTP140',
                'port_loading' => 'TAIWAN',
                'port_discharge' => 'HOCHIMINH CITY, VIETNAM',
            ],
            // PERLE SYSTEMS LIMITED -> Perle (PL001)
            'PL001' => [
                'template_type' => 'sale_contract',
                'seller_name' => 'PERLE SYSTEMS LIMITED',
                'seller_address_line1' => '60 Renfrew Drive, Suite 100, Markham, Ontario, Canada L3R 0E1',
                'seller_address_line2' => '',
                'seller_tel' => '+ 1 905 946 5026',
                'seller_fax' => '+ 1 905 944 2107',
                'seller_contact' => '',
                'seller_beneficiary' => 'PERLE SYSTEMS LIMITED',
                'seller_beneficiary_address' => '60 Renfrew Drive, Suite 100, Markham, Ontario, Canada L3R 0E1',
                'seller_bank_name' => 'Royal Bank of Canada (RBC PSL) +01-905-764-4755',
                'seller_bank_account' => 'Perle Systems Ltd. - USD Account: 401-135-9',
                'seller_bank_address_line1' => '260 East Beaver Creek Road, Richmond Hill, Ontario, L4B 3M3 CANADA',
                'seller_bank_address_line2' => 'Transit: 06032, Bank: 003 (ROYAL BANK OF CANADA)',
                'seller_bank_aba' => '021-000-021',
                'seller_swift_code' => 'ROYCCAT2',
                'port_loading' => 'CANADA',
                'port_discharge' => 'HOCHIMINH CITY, VIETNAM',
            ],
            // QNAP SYSTEMS INC -> Qnap (QN001)
            'QN001' => [
                'template_type' => 'sale_contract',
                'seller_name' => 'QNAP SYSTEMS INC',
                'seller_address_line1' => '2F., No. 22, Zhongxing Rd, Xizhi Dist., New Taipei City 221, Taiwan, R.O.C',
                'seller_address_line2' => '',
                'seller_tel' => '886-2-2641-2000',
                'seller_fax' => '886-2-2641-0555',
                'seller_contact' => '',
                'seller_beneficiary' => 'QNAP SYSTEMS INC',
                'seller_beneficiary_address' => '2F., No. 22, Zhongxing Rd, Xizhi Dist., New Taipei City 221, Taiwan, R.O.C',
                'seller_bank_name' => 'Mega International Commercial Bank CO., Ltd (Sung Nan Branch)',
                'seller_bank_account' => '042-53-02188-0',
                'seller_bank_address_line1' => 'No.234, Sec.5, Nanking E. Rd., Taipei 10570, Taiwan;',
                'seller_bank_address_line2' => '',
                'seller_bank_aba' => '',
                'seller_swift_code' => 'ICBCTWTP042',
                'port_loading' => 'TAIWAN',
                'port_discharge' => 'HOCHIMINH CITY, VIETNAM',
            ],
            // SECUI CORPORATION -> Secui (SC001)
            'SC001' => [
                'template_type' => 'sale_contract',
                'seller_name' => 'SECUI CORPORATION',
                'seller_address_line1' => '3-6F Jongno Tower, Jong-Ro 51, Jongno-Gu, Seoul, Korea',
                'seller_address_line2' => '',
                'seller_tel' => '+82 2 3783 6600',
                'seller_fax' => '+82 2 3783 6499',
                'seller_contact' => 'JIMI KIM - Overseas Business Group',
                'seller_beneficiary' => 'SECUI CORPORATION',
                'seller_beneficiary_address' => '3-6F Jongno Tower, Jong-Ro 51, Jongno-Gu, Seoul, Korea',
                'seller_bank_name' => 'SHINHAN BANK',
                'seller_bank_account' => '180-001-429554',
                'seller_bank_address_line1' => '4, Seocho-Daero 74-Gil, Seocho-Gu, Seoul, Korea 06620',
                'seller_bank_address_line2' => '',
                'seller_bank_aba' => '',
                'seller_swift_code' => 'SHBKKRSE',
                'port_loading' => 'KOREA',
                'port_discharge' => 'HOCHIMINH CITY, VIETNAM',
            ],
            // ZYXEL NETWORKS CORPORATION -> Zyxel Network (ZN001)
            'ZN001' => [
                'template_type' => 'sale_contract',
                'seller_name' => 'ZYXEL NETWORKS CORPORATION',
                'seller_address_line1' => '11F., NO 225, SEC 3, BEIXIN RD, XINDIAN DIST, NEW TAIPEI CITY 231 Taiwan.',
                'seller_address_line2' => '',
                'seller_tel' => '+ 886 3578 3942',
                'seller_fax' => '+ 886 3578 0103',
                'seller_contact' => '',
                'seller_beneficiary' => 'ZYXEL NETWORKS CORPORATION',
                'seller_beneficiary_address' => 'No.2 Industry East RD. IX, Hsinchu Science Park, Hsinchu 30075, Taiwan, R.O.C',
                'seller_bank_name' => 'Mega International Commercial Bank Co., Ltd (Hsin-Ann Branch)',
                'seller_bank_account' => '020-53-18058-1',
                'seller_bank_address_line1' => 'No 1, Hsin An Road, Hsinchu Science Based Industrial Park Hsinchu City, Taiwan',
                'seller_bank_address_line2' => '-Intermediary Bank: WELLS FARGO BANK<br>-Bank address: 30 HUDSON YARDS, FLOOR 63, NEW YORK, United States<br>-BANK SWIFT CODE: PNBPUS3NNYC ; ABA NO : 026005092',
                'seller_bank_aba' => '',
                'seller_swift_code' => 'ICBCTWTP020',
                'port_loading' => 'TAIWAN',
                'port_discharge' => 'HOCHIMINH CITY, VIETNAM',
            ],
        ];

        foreach ($supplierConfigs as $code => $config) {
            // Find supplier by code
            $supplier = DB::table('suppliers')->where('code', $code)->first();
            if ($supplier) {
                DB::table('supplier_po_configs')->updateOrInsert(
                    ['supplier_id' => $supplier->id],
                    array_merge($config, [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                );
            }
        }
    }
}
