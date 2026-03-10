<?php

namespace App\Http\Controllers;

use App\Models\EmployeeAsset;
use App\Models\EmployeeAssetAssignment;
use App\Models\User;
use Illuminate\Http\Request;

class EmployeeAssetReportController extends Controller
{
    /**
     * Báo cáo tổng hợp tài sản nội bộ.
     */
    public function index(Request $request)
    {
        // Auto-mark overdue
        EmployeeAssetAssignment::where('status', 'active')
            ->whereNotNull('expected_return_date')
            ->where('expected_return_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        // Summary cards
        $totalAssets     = EmployeeAsset::count();
        $totalAvailable  = EmployeeAsset::where('status', 'available')->count();
        $totalAssigned   = EmployeeAsset::where('status', 'assigned')->count();
        $totalMaintenance= EmployeeAsset::where('status', 'maintenance')->count();
        $totalDisposed   = EmployeeAsset::where('status', 'disposed')->count();
        $totalOverdue    = EmployeeAssetAssignment::where('status', 'overdue')->count();

        // Tài sản theo danh mục
        $byCategory = EmployeeAsset::selectRaw('category, count(*) as total, sum(quantity_total) as qty_total, sum(quantity_available) as qty_available')
            ->groupBy('category')
            ->orderBy('total', 'desc')
            ->get();

        // Nhân viên đang giữ tài sản
        $byEmployee = EmployeeAssetAssignment::with(['employee', 'asset'])
            ->where('status', '!=', 'returned')
            ->orderBy('assigned_date', 'desc')
            ->get()
            ->groupBy('user_id');

        // Sắp hết bảo hành (trong 90 ngày)
        $expiringWarranty = EmployeeAsset::whereNotNull('warranty_expiry')
            ->whereBetween('warranty_expiry', [now()->toDateString(), now()->addDays(90)->toDateString()])
            ->orderBy('warranty_expiry')
            ->get();

        return view('employee-asset-reports.index', compact(
            'totalAssets', 'totalAvailable', 'totalAssigned',
            'totalMaintenance', 'totalDisposed', 'totalOverdue',
            'byCategory', 'byEmployee', 'expiringWarranty'
        ));
    }

    /**
     * Xuất báo cáo ra Excel.
     */
    public function export(Request $request)
    {
        $assignments = EmployeeAssetAssignment::with(['asset', 'employee', 'assignedByUser'])
            ->orderBy('assigned_date', 'desc')
            ->get();

        $filename = 'bao-cao-tai-san-' . now()->format('Y-m-d') . '.xlsx';

        return \Response::streamDownload(function () use ($assignments) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $headers = ['Mã tài sản', 'Tên tài sản', 'Danh mục', 'Serial', 'Nhân viên', 'Người cấp', 'Ng. cấp', 'SL', 'Ngày dự kiến hoàn trả', 'Ngày hoàn trả', 'Tình trạng giao', 'Tình trạng nhận', 'Trạng thái', 'Ghi chú'];
            foreach ($headers as $i => $h) {
                $sheet->setCellValueByColumnAndRow($i + 1, 1, $h);
            }

            $row = 2;
            foreach ($assignments as $a) {
                $sheet->setCellValueByColumnAndRow(1, $row, optional($a->asset)->asset_code);
                $sheet->setCellValueByColumnAndRow(2, $row, optional($a->asset)->name);
                $sheet->setCellValueByColumnAndRow(3, $row, optional($a->asset)->category);
                $sheet->setCellValueByColumnAndRow(4, $row, optional($a->asset)->serial_number ?? '—');
                $sheet->setCellValueByColumnAndRow(5, $row, optional($a->employee)->name);
                $sheet->setCellValueByColumnAndRow(6, $row, optional($a->assignedByUser)->name);
                $sheet->setCellValueByColumnAndRow(7, $row, $a->assigned_date?->format('d/m/Y'));
                $sheet->setCellValueByColumnAndRow(8, $row, $a->quantity);
                $sheet->setCellValueByColumnAndRow(9, $row, $a->expected_return_date?->format('d/m/Y') ?? '—');
                $sheet->setCellValueByColumnAndRow(10, $row, $a->returned_date?->format('d/m/Y') ?? '—');
                $sheet->setCellValueByColumnAndRow(11, $row, $a->condition_label);
                $sheet->setCellValueByColumnAndRow(12, $row, $a->condition_return_label);
                $sheet->setCellValueByColumnAndRow(13, $row, $a->status_label);
                $sheet->setCellValueByColumnAndRow(14, $row, $a->return_note ?? '');
                $row++;
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
