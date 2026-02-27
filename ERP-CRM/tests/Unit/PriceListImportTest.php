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
                    'product_name' => 1,
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
                    'product_name' => 1,
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
            ],
            'Bitdefender_Format' => [
                'headers' => [
                    ['index' => 0, 'name' => 'Product Name'],
                    ['index' => 1, 'name' => 'Users range'],
                    ['index' => 2, 'name' => 'PART NUMBERS (SKU)'],
                    ['index' => 3, 'name' => 'Price List/Unit 1 Year'],
                    ['index' => 4, 'name' => 'PART NUMBERS (SKU)'],
                    ['index' => 5, 'name' => 'Price List/Unit 2 Years'],
                    ['index' => 6, 'name' => 'PART NUMBERS (SKU)'],
                    ['index' => 7, 'name' => 'Price List/Unit 3 Years'],
                ],
                'expected' => [
                    'sku' => 2,
                    'product_name' => 0,
                    'price_1yr' => 3,
                    'price_2yr' => 5,
                    'price_3yr' => 7,
                    'sku_2yr' => 4,
                    'sku_3yr' => 6,
                    '_range_column' => 1,
                ],
                'not_expected_keys' => ['custom_'] // No custom columns from duplicate SKU headers
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
            
            // Check that certain key prefixes are NOT in the mapping
            if (isset($data['not_expected_keys'])) {
                foreach ($data['not_expected_keys'] as $prefix) {
                    foreach ($content['mapping'] as $key => $val) {
                        $this->assertFalse(
                            str_starts_with($key, $prefix),
                            "Scenario $name: Key '$key' should NOT be in mapping (prefix '$prefix' is excluded)\nFull Mapping: " . json_encode($content['mapping'])
                        );
                    }
                }
            }
            
            echo "Scenario $name passed.\n";
        }
    }
}
