<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ghi nhớ URL (kèm bộ lọc, trang) của các trang danh sách (*.index) để nút "Quay lại"
 * ở trang chi tiết trả về đúng danh sách đang lọc. Dùng cùng App\Helpers\ListUrl::back().
 */
class RememberListUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $routeName = $request->route()?->getName();
        if (
            $request->isMethod('GET')
            && $routeName
            && str_ends_with($routeName, '.index')
            && !$request->expectsJson()
            && $response->getStatusCode() === 200
            && $request->hasSession()
        ) {
            $request->session()->put('list_url.' . $routeName, $request->fullUrl());
        }

        return $response;
    }
}
