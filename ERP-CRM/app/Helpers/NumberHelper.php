<?php

namespace App\Helpers;

class NumberHelper
{
    /**
     * Chuyển đổi số thành chữ Tiếng Việt.
     * Ví dụ: 22,000,000 → "Hai Mươi Hai Triệu đồng chẵn."
     */
    public static function currencyToVietnameseWords(int|float $amount): string
    {
        $amount = (int) round($amount);

        if ($amount === 0) {
            return 'Không đồng';
        }

        $digits = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
        $units  = ['', 'nghìn', 'triệu', 'tỷ'];

        // Tách thành từng nhóm 3 chữ số (từ thấp → cao)
        $groups    = str_split(strrev((string) $amount), 3);
        $numGroups = count($groups);
        $result    = [];   // được xây từ CAO → THẤP

        for ($i = $numGroups - 1; $i >= 0; $i--) {
            $groupStr  = strrev($groups[$i]);
            $groupVal  = (int) $groupStr;

            if ($groupVal === 0) {
                continue;
            }

            $h = intdiv($groupVal, 100);           // hàng trăm
            $t = intdiv($groupVal % 100, 10);      // hàng chục
            $u = $groupVal % 10;                   // hàng đơn vị

            // Đã có nhóm cao hơn trong kết quả chưa?
            $hasHigher = count($result) > 0;

            $part = '';

            // ---- Hàng trăm ----
            if ($h > 0) {
                $part .= $digits[$h] . ' trăm ';
            } elseif ($hasHigher) {
                // Nhóm trung gian, trăm = 0 → thêm "không trăm"
                $part .= 'không trăm ';
            }

            // ---- Hàng chục ----
            if ($t === 0) {
                if ($u > 0 && ($h > 0 || $hasHigher)) {
                    $part .= 'lẻ ';   // 101, 1 005 000 …
                }
            } elseif ($t === 1) {
                $part .= 'mười ';
            } else {
                $part .= $digits[$t] . ' mươi ';
            }

            // ---- Hàng đơn vị ----
            if ($u === 1 && $t > 1) {
                $part .= 'mốt';       // 21, 31… (không phải 11)
            } elseif ($u === 5 && $t > 0) {
                $part .= 'lăm';       // 15, 25… (không phải 5 đứng đầu)
            } elseif ($u > 0) {
                $part .= $digits[$u];
            }

            $unitLabel = $units[min($i, count($units) - 1)];
            $result[]  = trim($part) . ($unitLabel ? ' ' . $unitLabel : '');
        }

        $words = implode(' ', $result);
        $words = mb_convert_case($words, MB_CASE_TITLE, 'UTF-8');

        return trim($words) . ' đồng chẵn.';
    }
}
