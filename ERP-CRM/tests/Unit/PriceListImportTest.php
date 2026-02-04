<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\SupplierPriceListController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PriceListImportTest extends TestCase
{
    /**
     * Test auto-detect mapping for various suppliers
     */
    public function test_auto_detect_mapping_for_various_suppliers()
    {
        $controller = new SupplierPriceListController();

        $scenarios = [
            'HP_Format' => [
                'headers' => [
                    ['index' => 0, 'name' => 'Part#'],
                    ['index' => 1, 'name' => 'Product Description'],
                    ['index' => 2, 'name' => 'List Price'],
                    ['index' => 3, 'name' => 'Category'],
                ],
                'expected' => [
                    'sku' => 0,
                    'description' => 1,
                    'price' => 2,
                    'category' => 3
                ]
            ],
            'Dell_Format' => [
                'headers' => [
                    ['index' => 0, 'name' => 'Item Number'],
                    ['index' => 1, 'name' => 'Name'],
                    ['index' => 2, 'name' => 'MSRP'],
                    ['index' => 3, 'name' => 'Class'],
                ],
                'expected' => [
                    'sku' => 0,
                    'product_name' => 1,
                    'price' => 2,
                    'category' => 3
                ]
            ],
            'Juniper_Format' => [
                'headers' => [
                    ['index' => 0, 'name' => 'Model'],
                    ['index' => 1, 'name' => 'Description'],
                    ['index' => 2, 'name' => 'Global Price List'],
                ],
                'expected' => [
                    'sku' => 0,
                    'description' => 1,
                    'price' => 2
                ]
            ],
            'Generic_Format' => [
                'headers' => [
                    ['index' => 0, 'name' => 'Code'],
                    ['index' => 1, 'name' => 'Item Name'],
                    ['index' => 2, 'name' => 'Cost'],
                    ['index' => 3, 'name' => '12 Months'],
                ],
                'expected' => [
                    'sku' => 0,
                    'product_name' => 1,
                    'price' => 2,
                    'price_1yr' => 3
                ]
            ]
        ];

        foreach ($scenarios as $name => $data) {
            $request = new Request([
                'headers' => $data['headers'],
                'supplier_type' => 'default'
            ]);

            $response = $controller->autoDetectMapping($request);
            $content = json_decode($response->getContent(), true);

            $this->assertTrue($content['success'], "Failed scenario: $name. Message: " . ($content['message'] ?? ''));
            
            foreach ($data['expected'] as $field => $expectedIndex) {
                $actual = $content['mapping'][$field] ?? null;
                $this->assertEquals(
                    $expectedIndex, 
                    $actual,
                    "Scenario $name: Field '$field' should map to $expectedIndex but got " . ($actual ?? 'null') . "\nFull Mapping: " . json_encode($content['mapping'])
                );
            }
            
            echo "Scenario $name passed.\n";
        }
    }
}
