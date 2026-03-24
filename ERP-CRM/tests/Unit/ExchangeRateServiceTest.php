<?php

namespace Tests\Unit;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExchangeRateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ExchangeRateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ExchangeRateService::class);

        // Retrieve pre-seeded currencies
        $this->vnd = Currency::where('code', 'VND')->first();
        $this->usd = Currency::where('code', 'USD')->first();
        $this->eur = Currency::where('code', 'EUR')->first();
    }

    public function test_xml_parser_extracts_transfer_sell_buy_rates()
    {
        $xmlPayload = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<ExrateList>
  <DateTime>3/20/2026 8:00 AM</DateTime>
  <Exrate CurrencyCode="USD" CurrencyName="US DOLLAR" Buy="25,200" Transfer="25,300" Sell="25,400" />
  <Exrate CurrencyCode="EUR" CurrencyName="EURO" Buy="27,100" Transfer="27,200" Sell="27,300" />
</ExrateList>
XML;

        // Using reflection to test the protected method
        $reflection = new \ReflectionClass(ExchangeRateService::class);
        $method = $reflection->getMethod('parseVcbXml');
        $method->setAccessible(true);

        $parsed = $method->invoke($this->service, $xmlPayload);

        $this->assertArrayHasKey('USD', $parsed);
        $this->assertEquals(25200, $parsed['USD']['buy']);
        $this->assertEquals(25300, $parsed['USD']['transfer']);
        $this->assertEquals(25400, $parsed['USD']['sell']);

        $this->assertArrayHasKey('EUR', $parsed);
        $this->assertEquals(27100, $parsed['EUR']['buy']);
        $this->assertEquals(27300, $parsed['EUR']['sell']);
    }

    public function test_fetch_and_store_ignores_inactive_or_nonexistent_currencies()
    {
        // Mock the HTTP facade to return custom XML
        Http::fake([
            '*' => Http::response(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<ExrateList>
  <Exrate CurrencyCode="USD" Buy="25000" Transfer="25100" Sell="25200" />
  <Exrate CurrencyCode="JPY" Buy="160" Transfer="165" Sell="170" />
</ExrateList>
XML
            , 200, ['Content-Type' => 'text/xml'])
        ]);

        // JPY is not created in the database, so it should be ignored
        
        $date = Carbon::parse('2026-03-20');
        $result = $this->service->fetchAndStore($date);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['currencies_updated']); // Only USD

        $this->assertDatabaseHas('exchange_rates', [
            'effective_date' => '2026-03-20',
            'rate' => 25100, // Prefers Transfer rate over Sell/Buy
            'source' => 'auto',
        ]);
        
        // Ensure no JPY record created
        $this->assertEquals(1, ExchangeRate::count());
    }

    public function test_fallback_copies_previous_date_when_api_fails()
    {
        // Setup a previous exchange rate
        $usd = Currency::where('code', 'USD')->first();
        ExchangeRate::create([
            'currency_id' => $usd->id,
            'rate' => 25000,
            'effective_date' => '2026-03-19',
            'source' => 'auto',
        ]);

        // Mock API failure
        Http::fake([
            '*' => Http::response('', 500)
        ]);

        $date = Carbon::parse('2026-03-20');
        $result = $this->service->fetchAndStore($date);

        $this->assertTrue($result['success']); // Fallback succeeds
        $this->assertEquals(1, $result['currencies_updated']);
        $this->assertStringContainsString('fallback', implode(' ', $result['errors']));

        $this->assertDatabaseHas('exchange_rates', [
            'currency_id' => $usd->id,
            'rate' => 25000,
            'effective_date' => '2026-03-20',
        ]);
    }
}
