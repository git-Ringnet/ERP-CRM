<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Vendor Team Mapping for Project Registration (Đăng ký dự án)
    |--------------------------------------------------------------------------
    |
    | Quy định phân luồng Hãng (Vendor) sang Team tiếp nhận ĐKDA.
    | Mặc định các hãng không được định nghĩa sẽ về 'pm_team'.
    | Khi có Hãng mới cần phân luồng sang PO Team hoặc Team khác, chỉ cần khai báo thêm vào mảng dưới đây.
    |
    */
    'vendor_team_mapping' => [
        'FTN' => 'po_team',        // Fortinet -> PO Team
        'FORTINET' => 'po_team',   // Fortinet -> PO Team
        'DEFAULT' => 'pm_team',    // Các Hãng khác -> PM Team
    ],
];
