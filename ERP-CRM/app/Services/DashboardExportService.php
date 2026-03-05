<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DashboardExport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

/**
 * DashboardExportService handles exporting dashboard data to multiple formats
 * Requirements: 8.1, 8.2, 8.5, 8.6, 8.7, 8.8
 */
class DashboardExportService
{
    /**
     * Export dashboard data to PDF format
     * Requirements: 8.2, 8.5, 8.6, 8.7, 8.8
     * 
     * NOTE: This method requires the barryvdh/laravel-dompdf package to be installed.
     * Install with: composer require barryvdh/laravel-dompdf
     * 
     * @param array $data Dashboard data including metrics, charts, and analysis
     * @param array $filters Time period and filter information
     * @return string File path to generated PDF
     */
    public function exportToPDF(array $data, array $filters): string
    {
        try {
            // Check if DomPDF is available
            if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                throw new \Exception('DomPDF package is not installed. Please run: composer require barryvdh/laravel-dompdf');
            }
            
            // Generate filename with timestamp
            $filename = 'dashboard-' . date('Y-m-d-His') . '.pdf';
            $filepath = 'exports/' . $filename;
            
            // Prepare data for PDF view
            $pdfData = [
                'data' => $data,
                'filters' => $filters,
                'generated_at' => now()->format('d/m/Y H:i:s'),
                'period_label' => $this->getPeriodLabel($filters),
            ];
            
            // Generate PDF using DomPDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('dashboard.export-pdf', $pdfData);
            $pdf->setPaper('a4', 'portrait');
            
            // Save to storage
            $fullPath = storage_path('app/' . $filepath);
            $pdf->save($fullPath);
            
            return $fullPath;
        } catch (\Exception $e) {
            Log::error('Dashboard PDF export failed: ' . $e->getMessage(), [
                'filters' => $filters,
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Không thể xuất báo cáo PDF. Vui lòng thử lại.');
        }
    }

    /**
     * Export dashboard data to Excel format
     * Requirements: 8.2, 8.5, 8.6, 8.7, 8.8
     * 
     * @param array $data Dashboard data including metrics, charts, and analysis
     * @param array $filters Time period and filter information
     * @return string File path to generated Excel file
     */
    public function exportToExcel(array $data, array $filters): string
    {
        try {
            // Generate filename with timestamp
            $filename = 'dashboard-' . date('Y-m-d-His') . '.xlsx';
            $filepath = 'exports/' . $filename;
            
            // Create export instance
            $export = new DashboardExport($data, $filters);
            
            // Store Excel file
            Excel::store($export, $filepath, 'local');
            
            return storage_path('app/' . $filepath);
        } catch (\Exception $e) {
            Log::error('Dashboard Excel export failed: ' . $e->getMessage(), [
                'filters' => $filters,
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Không thể xuất báo cáo Excel. Vui lòng thử lại.');
        }
    }

    /**
     * Export dashboard data to CSV format
     * Requirements: 8.2, 8.5, 8.6, 8.7
     * 
     * @param array $data Dashboard data including metrics, charts, and analysis
     * @param array $filters Time period and filter information
     * @return string File path to generated CSV file
     */
    public function exportToCSV(array $data, array $filters): string
    {
        try {
            // Generate filename with timestamp
            $filename = 'dashboard-' . date('Y-m-d-His') . '.csv';
            $filepath = 'exports/' . $filename;
            
            // Create export instance
            $export = new DashboardExport($data, $filters, 'csv');
            
            // Store CSV file
            Excel::store($export, $filepath, 'local', \Maatwebsite\Excel\Excel::CSV);
            
            return storage_path('app/' . $filepath);
        } catch (\Exception $e) {
            Log::error('Dashboard CSV export failed: ' . $e->getMessage(), [
                'filters' => $filters,
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Không thể xuất báo cáo CSV. Vui lòng thử lại.');
        }
    }

    /**
     * Get human-readable period label for display
     * 
     * @param array $filters Filter information
     * @return string Period label in Vietnamese
     */
    private function getPeriodLabel(array $filters): string
    {
        $periodType = $filters['period_type'] ?? 'custom';
        $startDate = $filters['start_date'] ?? '';
        $endDate = $filters['end_date'] ?? '';
        
        $labels = [
            'today' => 'Hôm nay',
            'week' => 'Tuần này',
            'month' => 'Tháng này',
            'quarter' => 'Quý này',
            'year' => 'Năm này',
        ];
        
        if (isset($labels[$periodType])) {
            return $labels[$periodType];
        }
        
        // Custom period
        if ($startDate && $endDate) {
            return "Từ {$startDate} đến {$endDate}";
        }
        
        return 'Tùy chỉnh';
    }
}
