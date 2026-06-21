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

    public static function currencyToEnglishWords(int|float $amount): string
    {
        $amount = round($amount, 2);
        $dollars = (int) floor($amount);
        $cents = (int) round(($amount - $dollars) * 100);

        $dollarWords = str_replace('-', ' ', strtolower(self::numberToEnglishWords($dollars)));
        
        $result = $dollarWords . ' US dollars';
        if ($cents > 0) {
            $centWords = str_replace('-', ' ', strtolower(self::numberToEnglishWords($cents)));
            $result .= ' and ' . $centWords . ' cents';
        } else {
            $result .= ' only';
        }

        return ucfirst(trim($result)) . '.';
    }

    private static function numberToEnglishWords(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $ones = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
            5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
            15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'
        ];

        $tens = [
            2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
            6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
        ];

        $thousands = [
            '', 'Thousand', 'Million', 'Billion', 'Trillion'
        ];

        $result = '';
        $groups = [];
        while ($number > 0) {
            $groups[] = $number % 1000;
            $number = (int)($number / 1000);
        }

        for ($i = count($groups) - 1; $i >= 0; $i--) {
            $groupVal = $groups[$i];
            if ($groupVal === 0) {
                continue;
            }

            $groupWords = '';
            $h = (int)($groupVal / 100);
            $t = $groupVal % 100;

            if ($h > 0) {
                $groupWords .= $ones[$h] . ' Hundred ';
            }

            if ($t > 0) {
                if ($t < 20) {
                    $groupWords .= $ones[$t] . ' ';
                } else {
                    $tenVal = (int)($t / 10);
                    $oneVal = $t % 10;
                    $groupWords .= $tens[$tenVal] . ($oneVal > 0 ? '-' . $ones[$oneVal] : '') . ' ';
                }
            }

            $groupWords = trim($groupWords);
            if ($groupWords !== '') {
                $result .= ' ' . $groupWords . ($thousands[$i] !== '' ? ' ' . $thousands[$i] : '');
            }
        }

        return trim($result);
    }
}

