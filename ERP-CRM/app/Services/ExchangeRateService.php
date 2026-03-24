<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    /**
     * URL API Vietcombank
     */
    const VCB_API_URL = 'https://portal.vietcombank.com.vn/Usercontrols/TVPortal.TyGia/pXML.aspx';

    /**
     * Fetch tỷ giá từ Vietcombank và lưu vào DB
     * Ưu tiên: Transfer rate > Sell rate > Buy rate
     */
    public function fetchAndStore(?Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();
        $results = [
            'success' => false,
            'date' => $date->toDateString(),
            'currencies_updated' => 0,
            'errors' => [],
        ];

        try {
            $rates = $this->fetchFromVietcombank();

            if (empty($rates)) {
                throw new \Exception('Không lấy được tỷ giá từ Vietcombank API. Response rỗng.');
            }

            // Đảm bảo VND (Base Currency) luôn tồn tại
            Currency::updateOrCreate(
                ['code' => 'VND'],
                [
                    'name' => 'Vietnamese Dong',
                    'name_vi' => 'Đồng Việt Nam',
                    'symbol' => '₫',
                    'decimal_places' => 0,
                    'is_base' => true,
                    'is_active' => true,
                    'sort_order' => 1,
                ]
            );

            $count = 0;
            foreach ($rates as $currencyCode => $rateData) {
                if ($currencyCode === 'VND') {
                    continue; // Skip base currency rate update
                }

                $currency = Currency::firstOrCreate(
                    ['code' => $currencyCode],
                    [
                        'name' => $rateData['name'] ?? $currencyCode,
                        'name_vi' => $rateData['name'] ?? $currencyCode,
                        'symbol' => $currencyCode,
                        'decimal_places' => 2,
                        'is_base' => false,
                        'is_active' => true,
                    ]
                );

                $transferRate = $rateData['transfer'] ?? 0;

                if ($transferRate <= 0) {
                    $results['errors'][] = "Tỷ giá không hợp lệ cho {$currencyCode}: {$transferRate}";
                    continue;
                }

                ExchangeRate::updateOrCreate(
                    [
                        'currency_id' => $currency->id,
                        'effective_date' => $date->toDateString(),
                    ],
                    [
                        'rate' => $transferRate,
                        'source' => 'auto',
                    ]
                );

                $count++;
            }

            $results['success'] = true;
            $results['currencies_updated'] = $count;

            Log::info("ExchangeRate: Cập nhật {$count} tỷ giá thành công cho ngày {$date->toDateString()}");

        } catch (\Exception $e) {
            Log::error("ExchangeRate: Lỗi fetch tỷ giá - {$e->getMessage()}");
            $results['errors'][] = $e->getMessage();

            // Fallback: copy tỷ giá từ ngày gần nhất trước đó
            $fallbackCount = $this->fallbackToPreviousRates($date);
            if ($fallbackCount > 0) {
                $results['success'] = true;
                $results['currencies_updated'] = $fallbackCount;
                $results['errors'][] = "Đã fallback sang tỷ giá ngày trước (cập nhật {$fallbackCount} tỷ giá)";
                Log::warning("ExchangeRate: Fallback - Copy {$fallbackCount} tỷ giá từ ngày trước cho {$date->toDateString()}");
            }
        }

        return $results;
    }

    /**
     * Fetch và parse XML từ Vietcombank
     * Returns: ['USD' => ['buy' => 25200, 'sell' => 25400, 'transfer' => 25300], ...]
     */
    protected function fetchFromVietcombank(): array
    {
        $response = Http::timeout(30)
            ->retry(3, 2000)
            ->get(self::VCB_API_URL);

        if (!$response->successful()) {
            throw new \Exception("Vietcombank API trả về HTTP {$response->status()}");
        }

        $xmlContent = $response->body();

        if (empty($xmlContent)) {
            throw new \Exception('Vietcombank API trả về response rỗng');
        }

        return $this->parseVcbXml($xmlContent);
    }

    /**
     * Parse XML response từ Vietcombank
     *
     * VCB XML format:
     * <ExrateList>
     *   <DateTime>...</DateTime>
     *   <Exrate CurrencyCode="USD" CurrencyName="..." Buy="25,200" Transfer="25,300" Sell="25,400"/>
     *   ...
     * </ExrateList>
     */
    protected function parseVcbXml(string $xmlContent): array
    {
        $rates = [];

        try {
            // Suppress XML warnings, handle gracefully
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlContent);

            if ($xml === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                $errorMsg = !empty($errors) ? $errors[0]->message : 'Unknown XML parse error';
                throw new \Exception("Lỗi parse XML Vietcombank: {$errorMsg}");
            }

            foreach ($xml->Exrate as $exrate) {
                $attributes = $exrate->attributes();
                $currencyCode = trim((string) $attributes['CurrencyCode']);

                if (empty($currencyCode)) {
                    continue;
                }

                $currencyName = trim((string) $attributes['CurrencyName']);
                $transferRate = $this->parseVcbRate((string) $attributes['Transfer']);

                $rates[$currencyCode] = [
                    'name' => $currencyName,
                    'transfer' => $transferRate,
                ];
            }
        } catch (\Exception $e) {
            throw $e;
        } finally {
            libxml_use_internal_errors(false);
        }

        return $rates;
    }

    /**
     * Parse giá trị tỷ giá từ chuỗi VCB (có dấu phẩy)
     * VD: "25,400" -> 25400.0, "25,400.50" -> 25400.5
     */
    protected function parseVcbRate(string $value): float
    {
        $value = trim($value);

        if (empty($value) || $value === '-') {
            return 0;
        }

        // VCB dùng dấu phẩy làm thousands separator
        $value = str_replace(',', '', $value);

        return (float) $value;
    }

    /**
     * Fallback: Copy tỷ giá từ ngày gần nhất trước đó
     * Sử dụng khi API Vietcombank bị lỗi / sập
     */
    protected function fallbackToPreviousRates(Carbon $date): int
    {
        $activeCurrencies = Currency::active()->foreign()->get();
        $count = 0;

        foreach ($activeCurrencies as $currency) {
            // Tìm tỷ giá gần nhất TRƯỚC ngày hiện tại
            $previousRate = ExchangeRate::forCurrency($currency->id)
                ->where('effective_date', '<', $date->toDateString())
                ->orderByDesc('effective_date')
                ->first();

            if (!$previousRate) {
                continue;
            }

            // Kiểm tra ngày hôm nay đã có chưa
            $existingRate = ExchangeRate::forCurrency($currency->id)
                ->forDate($date)
                ->first();

            if ($existingRate) {
                continue; // Đã có, không ghi đè
            }

            // Copy rate
            ExchangeRate::create([
                'currency_id' => $currency->id,
                'rate' => $previousRate->rate,
                'effective_date' => $date->toDateString(),
                'source' => 'auto',
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Lưu tỷ giá thủ công (Kế toán trưởng nhập tay)
     */
    public function storeManualRate(
        int $currencyId,
        float $rate,
        Carbon $date,
        ?int $createdBy = null
    ): ExchangeRate {
        return ExchangeRate::updateOrCreate(
            [
                'currency_id' => $currencyId,
                'effective_date' => $date->toDateString(),
            ],
            [
                'rate' => $rate,
                'source' => 'manual',
                'created_by' => $createdBy,
            ]
        );
    }
}
