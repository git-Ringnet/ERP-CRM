<?php

namespace App\Helpers;

class ListUrl
{
    /**
     * URL quay lại trang danh sách, giữ nguyên bộ lọc / trang người dùng đang xem
     * (do middleware RememberListUrl ghi nhớ). Chưa có thì về danh sách mặc định.
     */
    public static function back(string $indexRouteName, array $parameters = []): string
    {
        return session('list_url.' . $indexRouteName) ?: route($indexRouteName, $parameters);
    }

    /**
     * Trang chi tiết mở được từ nhiều nơi: ưu tiên trang trước đó; nếu trang trước chính là
     * trang hiện tại (vừa bấm lưu / duyệt nên trang tải lại) thì về danh sách đang lọc.
     */
    public static function previousOr(string $indexRouteName): string
    {
        $previous = url()->previous();
        $current = url()->current();

        if (!$previous || strtok($previous, '?') === $current) {
            return self::back($indexRouteName);
        }

        return $previous;
    }
}
