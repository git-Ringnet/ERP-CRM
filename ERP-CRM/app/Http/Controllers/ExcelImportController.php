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
    public function downloadTemplate($type)
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
     * Requirements: 3.2, 3.3, 3.6, 3.7
     */
    public function import(Request $request)
    {
        $request->validate([
            'type' => 'required|in:products,inventory',
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // Max 10MB
        ]);
        
        try {
            $file = $request->file('file');
            $type = $request->input('type');
            
            // Save file temporarily
            $path = $file->store('temp');
            $fullPath = storage_path('app/' . $path);
            
            // Process import
            $result = match($type) {
                'products' => $this->excelImportService->importProducts($fullPath),
                'inventory' => $this->excelImportService->importInventory($fullPath),
            };
            
            // Clean up temp file
            Storage::delete($path);
            
            // Return result view
            return view('import.result', [
                'type' => $type,
                'success' => $result['success'],
                'imported' => $result['imported'],
                'errors' => $result['errors'],
            ]);
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Lỗi khi import: ' . $e->getMessage());
        }
    }
}
