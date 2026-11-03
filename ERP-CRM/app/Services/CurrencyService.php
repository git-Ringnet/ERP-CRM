<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CurrencyService
{
    /**
     * Lấy tỷ giá cho 1 loại tiền tệ tại 1 ngày
     * Nếu là VND (base currency), luôn trả về 1.0
     */
    public function getRate(Currency $currency, Carbon $date): float
    {
        if ($currency->isBase()) {
            return 1.0;
        }

        return ExchangeRate::getRateValue($currency->id, $date);
    }

    /**
     * Lấy tỷ giá bằng currency_id
     */
    public function getRateById(int $currencyId, Carbon $date): float
    {
        $currency = Currency::find($currencyId);
        if (!$currency || $currency->isBase()) {
            return 1.0;
        }

        return ExchangeRate::getRateValue($currencyId, $date);
    }

    /**
     * Quy đổi từ ngoại tệ sang VND
     * VD: $1,000 USD * 25,400 = 25,400,000 VND
     */
    public function toBase(float $foreignAmount, float $rate): float
    {
        $a = self::toDecimalString($foreignAmount);
        $b = self::toDecimalString($rate);
        // Server không bật extension bcmath → bcmul() sẽ gây lỗi 500, dùng phép nhân thường
        if (!function_exists('bcmul')) {
            return round($foreignAmount * $rate, 2);
        }
        return round((float) bcmul($a, $b, 6), 2);
    }

    /**
     * Chuyển float sang chuỗi số thập phân hợp lệ cho bcmath (tránh dạng 1.0E-5 / 1.0E+15 gây ValueError)
     */
    private static function toDecimalString(float $value): string
    {
        $str = (string) $value;
        if (stripos($str, 'e') === false) {
            return $str;
        }
        $str = rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');
        return $str === '' || $str === '-' ? '0' : $str;
    }

    /**
     * Quy đổi từ VND sang ngoại tệ
     * VD: 25,400,000 VND / 25,400 = $1,000 USD
     */
    public function fromBase(float $baseAmount, float $rate): float
    {
        if ($rate == 0) {
            return 0;
        }

        if (!function_exists('bcdiv')) {
            return round($baseAmount / $rate, 4);
        }
        return round((float) bcdiv(self::toDecimalString($baseAmount), self::toDecimalString($rate), 6), 4);
    }

    /**
     * Quy đổi giữa 2 loại tiền tệ tại 1 ngày
     */
    public function convert(float $amount, Currency $from, Currency $to, Carbon $date): float
    {
        if ($from->id === $to->id) {
            return $amount;
        }

        $fromRate = $this->getRate($from, $date);
        $toRate = $this->getRate($to, $date);

        // Chuyển sang VND trước, rồi chuyển sang tiền tệ đích
        $baseAmount = $this->toBase($amount, $fromRate);

        if ($to->isBase()) {
            return $baseAmount;
        }

        return $this->fromBase($baseAmount, $toRate);
    }

    /**
     * Format hiển thị song song: "$1,000.00 (~ 25,400,000 ₫)"
     * Chỉ hiển thị dual khi tiền tệ khác VND
     */
    public function formatDual(float $foreignAmount, float $baseAmount, Currency $currency): string
    {
        if ($currency->isBase()) {
            return $currency->format($foreignAmount);
        }

        $baseCurrency = Currency::getBaseCurrency();
        $foreignFormatted = $currency->format($foreignAmount);
        $baseFormatted = $baseCurrency ? $baseCurrency->format($baseAmount) : number_format($baseAmount, 0, '', ',') . ' ₫';

        return "{$foreignFormatted} (~ {$baseFormatted})";
    }

    /**
     * Lấy danh sách tiền tệ đang hoạt động, VND đứng đầu
     */
    public function getActiveCurrencies(): Collection
    {
        return Currency::active()
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
    }

    /**
     * Lấy tất cả các loại tiền tệ
     */
    public function getAllCurrencies(): Collection
    {
        return Currency::orderBy('sort_order')
            ->orderBy('code')
            ->get();
    }

    /**
     * Kiểm tra có phải giao dịch ngoại tệ không
     */
    public function isForeignTransaction(?int $currencyId): bool
    {
        if (!$currencyId) {
            return false;
        }

        $currency = Currency::find($currencyId);
        return $currency && $currency->isForeign();
    }

    /**
     * Tính chênh lệch tỷ giá (realized gain/loss)
     * Dương = Lãi tỷ giá (exchange_gain)
     * Âm = Lỗ tỷ giá (exchange_loss)
     *
     * VD: Bán $1,000 tỷ giá 24,000 = 24,000,000 VND
     *     Thu $1,000 tỷ giá 25,000 = 25,000,000 VND
     *     → Chênh lệch = +1,000,000 VND (Lãi tỷ giá)
     */
    public function calculateExchangeDifference(
        float $foreignAmount,
        float $originalRate,
        float $paymentRate
    ): float {
        $originalBase = $this->toBase($foreignAmount, $originalRate);
        $paymentBase = $this->toBase($foreignAmount, $paymentRate);

        return round($paymentBase - $originalBase, 2);
    }
}
