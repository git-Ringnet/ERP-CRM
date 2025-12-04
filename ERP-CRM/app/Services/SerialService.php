<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * SerialService handles serial number generation for products
 * Requirements: 4.13
 */
class SerialService
{
    /**
     * Generate a unique serial number for a product
     * Format: [prefix]-[year]-[6-digit-random]
     * Requirements: 4.13
     * 
     * @param Product $product
     * @return string Generated serial number
     */
    public function generate(Product $product): string
    {
        $prefix = $product->serial_prefix ?? 'SN';
        $year = date('Y');
        
        // Generate unique serial by checking against existing serials
        $maxAttempts = 100;
        $attempt = 0;
        
        do {
            // Generate 6-digit random number
            $randomDigits = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $serial = "{$prefix}-{$year}-{$randomDigits}";
            
            // Check if this serial already exists in the database
            // This assumes there's a serials table or similar tracking mechanism
            // For now, we'll check against a hypothetical serials table
            $exists = $this->serialExists($serial);
            
            $attempt++;
            
            if ($attempt >= $maxAttempts) {
                throw new \RuntimeException('Unable to generate unique serial number after ' . $maxAttempts . ' attempts');
            }
        } while ($exists);
        
        return $serial;
    }
    
    /**
     * Check if a serial number already exists
     * 
     * @param string $serial
     * @return bool
     */
    protected function serialExists(string $serial): bool
    {
        // Check if serials table exists
        // For now, we'll return false as the serials table might not exist yet
        // This will be implemented when inventory tracking is added
        try {
            return DB::table('serials')->where('serial_number', $serial)->exists();
        } catch (\Exception $e) {
            // If table doesn't exist, assume serial is unique
            return false;
        }
    }
    
    /**
     * Generate a lot number
     * Format: LOT-[year][month][day]-[4-digit-random]
     * 
     * @return string Generated lot number
     */
    public function generateLotNumber(): string
    {
        $date = date('Ymd');
        $randomDigits = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        
        return "LOT-{$date}-{$randomDigits}";
    }
    
    /**
     * Generate an internal serial number
     * Format: [productCode]-[timestamp]-[3-digit-random]
     * 
     * @param string $productCode
     * @return string Generated internal serial
     */
    public function generateInternalSerial(string $productCode): string
    {
        $timestamp = time();
        $randomDigits = str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
        
        return "{$productCode}-{$timestamp}-{$randomDigits}";
    }
}
