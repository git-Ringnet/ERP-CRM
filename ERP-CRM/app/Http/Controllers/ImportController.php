<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ImportService;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    protected $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Display import page
     * GET /import
     */
    public function index()
    {
        return view('import.index');
    }

    /**
     * Download template file
     * GET /import/template/{type}
     */
    public function template(string $type)
    {
        // Validate type
        if (!in_array($type, ['customers', 'products'])) {
            abort(404);
        }

        $filename = $this->importService->generateTemplate($type);
        
        return response()->download($filename, $type . '_template.xlsx');
    }

    /**
     * Preview import data
     * POST /import/preview
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'type' => 'required|in:customers,products'
        ]);

        try {
            // Store file temporarily
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('temp', $filename);
            
            $data = $this->importService->parseExcel($file);
            $validation = $this->importService->validateData($data, $request->type);

            return view('import.index', [
                'preview' => true,
                'type' => $request->type,
                'validation' => $validation,
                'tempFile' => $path
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Error processing file: ' . $e->getMessage());
        }
    }

    /**
     * Execute import
     * POST /import
     */
    public function store(Request $request)
    {
        $request->validate([
            'tempFile' => 'required|string',
            'type' => 'required|in:customers,products'
        ]);

        try {
            // Get the temporary file
            $filePath = storage_path('app/' . $request->tempFile);
            
            if (!file_exists($filePath)) {
                return back()->with('error', 'Temporary file not found. Please upload again.');
            }
            
            // Create UploadedFile instance from stored file
            $file = new \Illuminate\Http\UploadedFile($filePath, basename($filePath), null, null, true);
            
            $data = $this->importService->parseExcel($file);
            $validation = $this->importService->validateData($data, $request->type);

            // Import only valid data
            $validData = collect($validation['valid']);
            
            if ($validData->isEmpty()) {
                // Clean up temp file
                Storage::delete($request->tempFile);
                return back()->with('error', 'No valid data to import');
            }

            // Execute import based on type
            if ($request->type === 'customers') {
                $result = $this->importService->importCustomers($validData);
            } else {
                $result = $this->importService->importProducts($validData);
            }

            // Calculate summary
            $summary = [
                'total' => count($data),
                'success' => $result['success'],
                'failed' => $result['failed'] + count($validation['invalid']),
                'skipped' => count($validation['duplicates'])
            ];

            // Clean up temp file
            Storage::delete($request->tempFile);

            return view('import.index', [
                'imported' => true,
                'type' => $request->type,
                'summary' => $summary,
                'validation' => $validation
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Error importing data: ' . $e->getMessage());
        }
    }
}
