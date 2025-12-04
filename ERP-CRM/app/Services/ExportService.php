<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CustomersExport;
use App\Exports\SuppliersExport;
use App\Exports\EmployeesExport;
use App\Exports\ProductsExport;

/**
 * ExportService handles exporting data to Excel files
 * Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7
 */
class ExportService
{
    /**
     * Export customers to Excel
     * Requirements: 7.1, 7.2, 7.6
     * 
     * @param Collection $customers
     * @return string File path
     */
    public function exportCustomers(Collection $customers): string
    {
        $filename = 'customers-' . date('Y-m-d-His') . '.xlsx';
        $filepath = 'exports/' . $filename;
        
        Excel::store(new CustomersExport($customers), $filepath, 'local');
        
        return storage_path('app/' . $filepath);
    }

    /**
     * Export suppliers to Excel
     * Requirements: 7.1, 7.3, 7.6
     * 
     * @param Collection $suppliers
     * @return string File path
     */
    public function exportSuppliers(Collection $suppliers): string
    {
        $filename = 'suppliers-' . date('Y-m-d-His') . '.xlsx';
        $filepath = 'exports/' . $filename;
        
        Excel::store(new SuppliersExport($suppliers), $filepath, 'local');
        
        return storage_path('app/' . $filepath);
    }

    /**
     * Export employees to Excel
     * Requirements: 7.1, 7.4, 7.6
     * 
     * @param Collection $employees
     * @return string File path
     */
    public function exportEmployees(Collection $employees): string
    {
        $filename = 'employees-' . date('Y-m-d-His') . '.xlsx';
        $filepath = 'exports/' . $filename;
        
        Excel::store(new EmployeesExport($employees), $filepath, 'local');
        
        return storage_path('app/' . $filepath);
    }

    /**
     * Export products to Excel
     * Requirements: 7.1, 7.5, 7.6
     * 
     * @param Collection $products
     * @return string File path
     */
    public function exportProducts(Collection $products): string
    {
        $filename = 'products-' . date('Y-m-d-His') . '.xlsx';
        $filepath = 'exports/' . $filename;
        
        Excel::store(new ProductsExport($products), $filepath, 'local');
        
        return storage_path('app/' . $filepath);
    }
}

