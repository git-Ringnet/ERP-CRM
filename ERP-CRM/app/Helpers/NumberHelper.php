<?php

namespace App\Helpers;

class NumberHelper
{
    /**
     * Chuyển đổi số thành chữ Tiếng Việt
     *
     * @param float|int $amount
     * @return string
     */
    public static function currencyToVietnameseWords($amount)
    {
        if ($amount == 0) {
            return 'Không đồng';
        }

        $digits = ["không", "một", "hai", "ba", "bốn", "năm", "sáu", "bảy", "tám", "chín"];
        $units = ["", "nghìn", "triệu", "tỷ", "triệu tỷ", "tỷ tỷ"];

        $amount_str = (string)number_format($amount, 0, '', '');
        $groups = str_split(strrev($amount_str), 3);
        $result = [];

        foreach ($groups as $i => $group) {
            $group = strrev($group);
            $group_val = (int)$group;
            if ($group_val == 0) continue;

            $group_str = "";
            $h = (int)($group / 100);
            $t = (int)(($group % 100) / 10);
            $u = $group % 10;

            // Hundred
            if ($h > 0 || count($groups) > 1) {
                $group_str .= $digits[$h] . " trăm ";
            }

            // Ten
            if ($t == 0) {
                if ($u > 0 && ($h > 0 || count($groups) > 1)) {
                    $group_str .= "lẻ ";
                }
            } elseif ($t == 1) {
                $group_str .= "mười ";
            } else {
                $group_str .= $digits[$t] . " mươi ";
            }

            // Unit
            if ($u == 1 && $t > 1) {
                $group_str .= "mốt";
            } elseif ($u == 5 && $t > 0) {
                $group_str .= "lăm";
            } elseif ($u > 0 || ($group_val == 0 && count($groups) == 1)) {
                $group_str .= $digits[$u];
            }

            $result[] = trim($group_str) . " " . $units[$i];
        }

        $res = implode(" ", array_reverse($result));
        $res = mb_convert_case($res, MB_CASE_TITLE, "UTF-8");
        return trim($res) . " đồng chẵn.";
    }
}
