<?php

namespace App\Http\Middleware;

use Illuminate\Contracts\Session\Session;
use Illuminate\Session\Middleware\StartSession;
use Symfony\Component\HttpFoundation\Response;

/**
 * Session chỉ-đọc cho các endpoint polling chạy ngầm (chuông thông báo, sidebar badges).
 *
 * Vấn đề: StartSession mặc định luôn ghi lại session và gửi lại cookie session ở MỌI request.
 * Nếu một request polling bắt đầu trước khi người dùng đăng xuất / đổi tài khoản ở tab khác
 * và kết thúc sau đó, nó sẽ tạo lại session cũ (vẫn đăng nhập) và ghi đè cookie của trình duyệt
 * về session cũ → CSRF token lệch → "419 Page Expired", đăng xuất không được.
 *
 * Middleware này vẫn đọc session (để xác thực người dùng) nhưng không lưu lại và không gửi cookie.
 */
class StartReadOnlySession extends StartSession
{
    protected function addCookieToResponse(Response $response, Session $session)
    {
        // Không gửi lại cookie session
    }

    protected function saveSession($request)
    {
        // Không ghi lại session
    }

    protected function storeCurrentUrl($request, $session)
    {
        // Không cập nhật previous URL
    }
}
