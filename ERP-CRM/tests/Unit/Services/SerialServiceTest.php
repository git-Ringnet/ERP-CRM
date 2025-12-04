<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SerialService;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SerialServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SerialService $serialService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serialService = new SerialService();
    }

    /**
     * Test serial generation format
     * Requirements: 4.13
     */
    public function test_generate_returns_correct_format(): void
    {
        $product = Product::factory()->create([
            'serial_prefix' => 'TEST',
            'management_type' => 'serial',
            'auto_generate_serial' => true,
        ]);

        $serial = $this->serialService->generate($product);
        
        // Check format: [prefix]-[year]-[6-digit-random]
        $pattern = '/^TEST-\d{4}-\d{6}$/';
        $this->assertMatchesRegularExpression($pattern, $serial);
        
        // Check year is current year
        $year = date('Y');
        $this->assertStringContainsString("-{$year}-", $serial);
    }

    /**
     * Test serial generation with default prefix
     * Requirements: 4.13
     */
    public function test_generate_uses_default_prefix_when_not_set(): void
    {
        $product = Product::factory()->create([
            'serial_prefix' => null,
            'management_type' => 'serial',
            'auto_generate_serial' => true,
        ]);

        $serial = $this->serialService->generate($product);
        
        // Should use default prefix 'SN'
        $this->assertStringStartsWith('SN-', $serial);
    }

    /**
     * Test multiple serial generations are unique
     * Requirements: 4.13
     */
    public function test_generate_creates_unique_serials(): void
    {
        $product = Product::factory()->create([
            'serial_prefix' => 'PROD',
            'management_type' => 'serial',
            'auto_generate_serial' => true,
        ]);

        $serials = [];
        for ($i = 0; $i < 10; $i++) {
            $serials[] = $this->serialService->generate($product);
        }

        // All serials should be unique
        $uniqueSerials = array_unique($serials);
        $this->assertCount(10, $uniqueSerials);
    }

    /**
     * Test lot number generation
     */
    public function test_generate_lot_number_returns_correct_format(): void
    {
        $lotNumber = $this->serialService->generateLotNumber();
        
        // Check format: LOT-[YYYYMMDD]-[4-digit-random]
        $pattern = '/^LOT-\d{8}-\d{4}$/';
        $this->assertMatchesRegularExpression($pattern, $lotNumber);
    }

    /**
     * Test internal serial generation
     */
    public function test_generate_internal_serial_returns_correct_format(): void
    {
        $productCode = 'PROD001';
        $internalSerial = $this->serialService->generateInternalSerial($productCode);
        
        // Check format: [productCode]-[timestamp]-[3-digit-random]
        $pattern = '/^PROD001-\d+-\d{3}$/';
        $this->assertMatchesRegularExpression($pattern, $internalSerial);
    }
}
