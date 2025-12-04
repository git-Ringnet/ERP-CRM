<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Customer;
use App\Models\Product;

class ImportService
{
    /**
     * Parse Excel file and return data as collection
     */
    public function parseExcel(UploadedFile $file): Collection
    {
        $data = Excel::toCollection(null, $file)->first();
        
        // Remove header row
        $data->shift();
        
        return $data;
    }

    /**
     * Validate import data
     * Returns [valid, invalid, duplicates]
     */
    public function validateData(Collection $data, string $type): array
    {
        $valid = [];
        $invalid = [];
        $duplicates = [];
        
        $existingCodes = $this->getExistingCodes($type);
        
        foreach ($data as $index => $row) {
            $rowData = $this->mapRowToArray($row, $type);
            
            // Check required fields
            if (!$this->hasRequiredFields($rowData, $type)) {
                $invalid[] = [
                    'row' => $index + 2, // +2 because of header and 0-index
                    'data' => $rowData,
                    'error' => 'Missing required fields'
                ];
                continue;
            }
            
            // Check duplicates
            if (in_array($rowData['code'], $existingCodes)) {
                $duplicates[] = [
                    'row' => $index + 2,
                    'data' => $rowData,
                    'error' => 'Duplicate code: ' . $rowData['code']
                ];
                continue;
            }
            
            $valid[] = $rowData;
            $existingCodes[] = $rowData['code'];
        }
        
        return [
            'valid' => $valid,
            'invalid' => $invalid,
            'duplicates' => $duplicates
        ];
    }

    /**
     * Import customers
     */
    public function importCustomers(Collection $data): array
    {
        $success = 0;
        $failed = 0;
        $skipped = 0;
        
        foreach ($data as $row) {
            try {
                Customer::create($row);
                $success++;
            } catch (\Exception $e) {
                $failed++;
            }
        }
        
        return [
            'success' => $success,
            'failed' => $failed,
            'skipped' => $skipped
        ];
    }

    /**
     * Import products
     */
    public function importProducts(Collection $data): array
    {
        $success = 0;
        $failed = 0;
        $skipped = 0;
        
        foreach ($data as $row) {
            try {
                Product::create($row);
                $success++;
            } catch (\Exception $e) {
                $failed++;
            }
        }
        
        return [
            'success' => $success,
            'failed' => $failed,
            'skipped' => $skipped
        ];
    }

    /**
     * Generate Excel template for import
     */
    public function generateTemplate(string $type): string
    {
        $headers = $this->getTemplateHeaders($type);
        
        // Create a simple CSV template
        $filename = storage_path('app/templates/' . $type . '_template.xlsx');
        
        // Ensure directory exists
        if (!file_exists(storage_path('app/templates'))) {
            mkdir(storage_path('app/templates'), 0755, true);
        }
        
        // Create Excel file with headers
        Excel::store(new \App\Exports\TemplateExport($headers), 'templates/' . $type . '_template.xlsx');
        
        return $filename;
    }

    /**
     * Get existing codes for duplicate checking
     */
    private function getExistingCodes(string $type): array
    {
        switch ($type) {
            case 'customers':
                return Customer::pluck('code')->toArray();
            case 'products':
                return Product::pluck('code')->toArray();
            default:
                return [];
        }
    }

    /**
     * Map Excel row to array based on type
     */
    private function mapRowToArray($row, string $type): array
    {
        switch ($type) {
            case 'customers':
                return [
                    'code' => $row[0] ?? '',
                    'name' => $row[1] ?? '',
                    'email' => $row[2] ?? '',
                    'phone' => $row[3] ?? '',
                    'address' => $row[4] ?? '',
                    'type' => $row[5] ?? 'normal',
                    'tax_code' => $row[6] ?? '',
                    'debt_limit' => $row[7] ?? 0,
                    'debt_days' => $row[8] ?? 0,
                ];
            case 'products':
                return [
                    'code' => $row[0] ?? '',
                    'name' => $row[1] ?? '',
                    'unit' => $row[2] ?? '',
                    'price' => $row[3] ?? 0,
                    'cost' => $row[4] ?? 0,
                    'category' => $row[5] ?? '',
                    'management_type' => $row[6] ?? 'normal',
                ];
            default:
                return [];
        }
    }

    /**
     * Check if row has required fields
     */
    private function hasRequiredFields(array $row, string $type): bool
    {
        switch ($type) {
            case 'customers':
                return !empty($row['code']) && !empty($row['name']) && 
                       !empty($row['email']) && !empty($row['phone']);
            case 'products':
                return !empty($row['code']) && !empty($row['name']) && 
                       !empty($row['unit']) && isset($row['price']) && isset($row['cost']);
            default:
                return false;
        }
    }

    /**
     * Get template headers based on type
     */
    private function getTemplateHeaders(string $type): array
    {
        switch ($type) {
            case 'customers':
                return ['Code', 'Name', 'Email', 'Phone', 'Address', 'Type', 'Tax Code', 'Debt Limit', 'Debt Days'];
            case 'products':
                return ['Code', 'Name', 'Unit', 'Price', 'Cost', 'Category', 'Management Type'];
            default:
                return [];
        }
    }
}
