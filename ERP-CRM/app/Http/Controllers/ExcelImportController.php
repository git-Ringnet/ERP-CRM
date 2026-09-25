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
        $this->authorize('viewAny', \App\Models\ExcelImport::class);
        
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
                'update_serials' => $this->excelImportService->generateUpdateSerialTemplate(),
                default => throw new \Exception('Invalid template type'),
            };
            
            $filename = match($type) {
                'update_serials' => 'mau_cap_nhat_serial_' . date('Y-m-d') . '.xlsx',
                default => $type . '_template_' . date('Y-m-d') . '.xlsx',
            };
            
            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi khi tạo template: ' . $e->getMessage());
        }
    }

    /**
     * Import data from Excel file
     * Updated: Support Products import, Inventory import, and Serial updates for existing in-stock items
     */
    public function store(Request $request)
    {
        $this->authorize('create', \App\Models\ExcelImport::class);
        
        $request->validate([
            'type' => 'required|in:products,inventory,update_serials',
            'warehouse_id' => 'nullable|exists:warehouses,id', // Optional fallback warehouse
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // Max 10MB
        ]);
        
        try {
            $file = $request->file('file');
            $warehouseId = $request->input('warehouse_id'); // Optional fallback
            $type = $request->input('type');
            
            // Save file temporarily
            $path = $file->store('temp');
            $fullPath = storage_path('app/' . $path);
            
            // Process import based on type
            if ($type === 'update_serials') {
                $result = $this->excelImportService->importUpdateSerials($fullPath, $warehouseId);
            } elseif ($type === 'inventory') {
                $result = $this->excelImportService->importInventory($fullPath, $warehouseId);
            } else {
                $result = $this->excelImportService->importProducts($fullPath, $warehouseId);
            }
            
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
