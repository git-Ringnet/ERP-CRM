<?php

namespace Tests\Unit;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Services\CurrencyService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CurrencyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CurrencyService::class);

        // Retrieve base currency seeded by migration
        $this->vnd = Currency::where('code', 'VND')->first();
    }

    public function test_get_base_currency_returns_correct_model()
    {
        $base = Currency::getBaseCurrency();
        $this->assertNotNull($base);
        $this->assertTrue($base->is_base);
        $this->assertEquals('VND', $base->code);
    }

    public function test_get_rate_returns_one_for_base_currency()
    {
        $base = Currency::getBaseCurrency();
        $rate = $this->service->getRate($base, now());
        $this->assertEquals(1.0, $rate);
    }

    public function test_get_rate_returns_correct_foreign_rate()
    {
        $usd = Currency::where('code', 'USD')->first();

        $date = Carbon::parse('2026-03-20');
        ExchangeRate::create([
            'currency_id' => $usd->id,
            'rate' => 25400.5,
            'effective_date' => $date->toDateString(),
            'source' => 'manual',
        ]);

        $rate = $this->service->getRate($usd, $date);
        $this->assertEquals(25400.5, $rate);
    }

    public function test_to_base_calculates_correctly()
    {
        $amountForeign = 100.50; // $100.50
        $rate = 25000;
        
        $expectedVnd = round(100.50 * 25000); // 2,512,500
        $actualVnd = $this->service->toBase($amountForeign, $rate);

        $this->assertEquals($expectedVnd, $actualVnd);
    }

    public function test_format_dual_displays_both_currencies()
    {
        $usd = Currency::where('code', 'USD')->first();

        $base = Currency::getBaseCurrency();

        $output = $this->service->formatDual(100, 2500000, $usd);
        // Expect format like: $100.00 (~ 2,500,000 ₫)
        $this->assertStringContainsString('$100.00', $output);
        $this->assertStringContainsString('2,500,000 ₫', $output);
    }
}
