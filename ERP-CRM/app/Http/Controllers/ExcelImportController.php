<?php

namespace App\Http\Controllers;

use App\Services\ExcelImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExcelImportController extends Controller
{
    protected $excelImportService;

    public function __construct(ExcelImportService $excelImportService)
    {
        $this->excelImportService = $excelImportService;
    }

    /**
     * Display import interface
     * Requirements: 3.1
     */
    public function index()
    {
        return view('import.index');
    }

    /**
     * Download Excel template
     * Requirements: 3.1, 6.1, 6.2
     */
    public function template($type)
    {
        try {
            $tempFile = match($type) {
                'products' => $this->excelImportService->generateProductTemplate(),
                'inventory' => $this->excelImportService->generateInventoryTemplate(),
                default => throw new \Exception('Invalid template type'),
            };
            
            $filename = $type . '_template_' . date('Y-m-d') . '.xlsx';
            
            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi khi tạo template: ' . $e->getMessage());
        }
    }

    /**
     * Import data from Excel file
     * Updated: Import sản phẩm vào kho (tạo product + product_items + tính tồn kho)
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:products',
            'warehouse_id' => 'required|exists:warehouses,id',
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // Max 10MB
        ]);
        
        try {
            $file = $request->file('file');
            $warehouseId = $request->input('warehouse_id');
            
            // Save file temporarily
            $path = $file->store('temp');
            $fullPath = storage_path('app/' . $path);
            
            // Process import
            $result = $this->excelImportService->importProducts($fullPath, $warehouseId);
            
            // Clean up temp file
            Storage::delete($path);
            
            if ($result['success']) {
                return redirect()->route('imports.index')
                    ->with('success', $result['message']);
            } else {
                return redirect()->route('imports.index')
                    ->with('error', 'Import thất bại: ' . implode(', ', $result['errors']));
            }
            
        } catch (\Exception $e) {
            return redirect()->route('imports.index')
                ->with('error', 'Lỗi khi import: ' . $e->getMessage());
        }
    }
    
    /**
     * Preview import data (optional)
     */
    public function preview(Request $request)
    {
        return $this->store($request);
    }
}
