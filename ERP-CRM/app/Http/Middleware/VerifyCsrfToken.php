<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Đăng xuất không cần chặn CSRF: tab cũ (token đã đổi sau khi đăng nhập/đăng xuất
        // ở tab khác) bấm Đăng xuất sẽ bị "419 Page Expired". Rủi ro CSRF logout chỉ là
        // bị đăng xuất, không lộ dữ liệu.
        'logout',
    ];
}
