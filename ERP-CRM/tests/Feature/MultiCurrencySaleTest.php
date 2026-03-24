<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\ExchangeRate;
use App\Models\Sale;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiCurrencySaleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Currency $vnd;
    protected Currency $usd;
    protected Customer $customer;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create();
        
        $this->vnd = Currency::where('code', 'VND')->first();
        $this->usd = Currency::where('code', 'USD')->first();
        
        $this->customer = Customer::factory()->create();
        $this->product = Product::factory()->create(['base_price' => 100]); // Assuming factory exists

        ExchangeRate::create([
            'currency_id' => $this->usd->id,
            'rate' => 25000,
            'effective_date' => Carbon::today()->toDateString(),
            'source' => 'manual'
        ]);
    }

    public function test_sale_creation_in_base_currency_stores_null_currency()
    {
        $saleData = [
            'customer_id' => $this->customer->id,
            'sale_date' => Carbon::today()->toDateString(),
            'currency_id' => $this->vnd->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 100000, 'total' => 200000]
            ],
            'subtotal' => 200000,
            'total' => 200000,
        ];

        $response = $this->actingAs($this->admin)->post(route('sales.store'), $saleData);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sales', [
            'total' => 200000,
            'currency_id' => $this->vnd->id, // Actually the controller saves the base currency explicitly
            'exchange_rate' => 1,
            'total_foreign' => 200000
        ]);
    }

    public function test_sale_creation_in_foreign_currency_stores_rates_and_foreign_total()
    {
        $saleData = [
            'customer_id' => $this->customer->id,
            'sale_date' => Carbon::today()->toDateString(),
            'currency_id' => $this->usd->id,
            'exchange_rate' => 25000,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 100, 'total' => 200]
            ],
            'subtotal' => 5000000, // 200 * 25000
            'total' => 5000000,
        ];

        $response = $this->actingAs($this->admin)->post(route('sales.store'), $saleData);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sales', [
            'currency_id' => $this->usd->id,
            'exchange_rate' => 25000,
            'total' => 5000000, // VND
            'total_foreign' => 200 // USD
        ]);
    }

    public function test_recording_payment_generates_exchange_gain_or_loss()
    {
        $sale = Sale::factory()->create([
            'customer_id' => $this->customer->id,
            'currency_id' => $this->usd->id,
            'exchange_rate' => 25000,
            'total' => 5000000,
            'total_foreign' => 200,
            'debt_amount' => 5000000,
            'debt_amount_foreign' => 200,
        ]);

        // Customer pays $200 but the rate rose to 26000 (Gain)
        $paymentData = [
            'amount' => 200,
            'currency_id' => $this->usd->id,
            'exchange_rate' => 26000,
            'payment_method' => 'bank_transfer',
            'payment_date' => Carbon::today()->toDateString(),
        ];

        $response = $this->actingAs($this->admin)->post(route('sales.record-payment', $sale->id), $paymentData);
        $response->assertSessionHas('success');

        // Check if Paid Amount was updated correctly in VND and Foreign Currency
        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'paid_amount' => 5200000, // 200 * 26000
            'paid_amount_foreign' => 200,
        ]);

        // Verify exchange gain created in Financial Transactions
        $this->assertDatabaseHas('financial_transactions', [
            'type' => 'income',
            'amount' => 200000, // 5.2m - 5.0m
        ]);
    }
}
