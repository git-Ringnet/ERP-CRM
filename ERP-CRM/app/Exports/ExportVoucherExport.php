<?php

namespace App\Exports;

use App\Models\Export;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Contracts\View\View;

class ExportVoucherExport implements FromView, WithColumnWidths, WithStyles
{
    protected $export;

    public function __construct(Export $export)
    {
        $this->export = $export;
    }

    public function view(): View
    {
        $export = $this->export->load(['warehouse', 'employee', 'items.product', 'project', 'customer']);
        
        return view('exports.excel-voucher', [
            'export' => $export
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // STT
            'B' => 50,  // Tên sản phẩm (rộng để wrap text)
            'C' => 15,  // Mã số
            'D' => 12,  // Đơn vị tính
            'E' => 12,  // Số lượng yêu cầu
            'F' => 12,  // Thực xuất
            'G' => 15,  // Đơn giá
            'H' => 18,  // Thành tiền
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        
        // Merge cells for header rows (company info) - rows 1-3
        $sheet->mergeCells('A1:F1'); // Company name
        $sheet->mergeCells('G1:H1'); // Mẫu số
        $sheet->mergeCells('A2:F2'); // Address line 1
        $sheet->mergeCells('G2:H2'); // Thông tư line 1
        $sheet->mergeCells('A3:F3'); // Address line 2
        $sheet->mergeCells('G3:H3'); // Thông tư line 2
        
        // Row 4: empty
        $sheet->mergeCells('A4:H4');
        
        // Row 5: PHIẾU XUẤT KHO
        $sheet->mergeCells('A5:H5');
        
        // Row 6: Ngày + Nợ
        $sheet->mergeCells('A6:F6');
        $sheet->mergeCells('G6:H6');
        
        // Row 7: Số + Có
        $sheet->mergeCells('A7:F7');
        $sheet->mergeCells('G7:H7');
        
        // Row 8: empty
        $sheet->mergeCells('A8:H8');
        
        // Rows 9-12: Info rows (Họ và tên, Địa chỉ, Lý do, Xuất tại kho)
        $sheet->mergeCells('A9:H9');
        $sheet->mergeCells('A10:H10');
        $sheet->mergeCells('A11:H11');
        $sheet->mergeCells('A12:H12');
        
        // Row 13: empty
        $sheet->mergeCells('A13:H13');
        
        // Rows 14-15: Table header
        // Row 14: Header with merges
        $sheet->mergeCells('A14:A15'); // STT
        $sheet->mergeCells('B14:B15'); // Tên sản phẩm
        $sheet->mergeCells('C14:C15'); // Mã số
        $sheet->mergeCells('D14:D15'); // Đơn vị tính
        $sheet->mergeCells('E14:F14'); // Số lượng
        $sheet->mergeCells('G14:G15'); // Đơn giá
        $sheet->mergeCells('H14:H15'); // Thành tiền
        
        // Find total row (Cộng)
        $totalRow = null;
        for ($row = 16; $row <= $lastRow; $row++) {
            $cellValue = $sheet->getCell("A{$row}")->getValue();
            if (stripos($cellValue, 'Cộng') !== false) {
                $totalRow = $row;
                break;
            }
        }
        
        // Merge cells after total row
        if ($totalRow) {
            $currentRow = $totalRow + 1;
            
            // Empty row
            if ($currentRow <= $lastRow) {
                $sheet->mergeCells("A{$currentRow}:H{$currentRow}");
                $currentRow++;
            }
            
            // Tổng số tiền
            if ($currentRow <= $lastRow) {
                $sheet->mergeCells("A{$currentRow}:H{$currentRow}");
                $currentRow++;
            }
            
            // Số chứng từ
            if ($currentRow <= $lastRow) {
                $sheet->mergeCells("A{$currentRow}:H{$currentRow}");
                $currentRow++;
            }
            
            // Empty row
            if ($currentRow <= $lastRow) {
                $sheet->mergeCells("A{$currentRow}:H{$currentRow}");
                $currentRow++;
            }
            
            // Ngày row
            if ($currentRow <= $lastRow) {
                $sheet->mergeCells("A{$currentRow}:H{$currentRow}");
                $currentRow++;
            }
            
            // Empty row
            if ($currentRow <= $lastRow) {
                $sheet->mergeCells("A{$currentRow}:H{$currentRow}");
                $currentRow++;
            }
            
            // Signature row 1
            if ($currentRow <= $lastRow) {
                $sheet->mergeCells("A{$currentRow}:B{$currentRow}"); // Người lập phiếu
                $sheet->mergeCells("C{$currentRow}:D{$currentRow}"); // Người nhận hàng
                $sheet->mergeCells("F{$currentRow}:G{$currentRow}"); // Kế toán trưởng
                $currentRow++;
            }
            
            // Signature row 2
            if ($currentRow <= $lastRow) {
                $sheet->mergeCells("A{$currentRow}:B{$currentRow}");
                $sheet->mergeCells("C{$currentRow}:D{$currentRow}");
                $sheet->mergeCells("F{$currentRow}:G{$currentRow}");
            }
        }
        
        // Apply wrap text to all cells
        $sheet->getStyle("A1:H{$lastRow}")->getAlignment()->setWrapText(true);
        
        // Vertical alignment for all cells
        $sheet->getStyle("A1:H{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        
        // Header company info (rows 1-3)
        $sheet->getStyle('A1:F3')->getFont()->setSize(11)->setBold(true);
        $sheet->getStyle('G1:H3')->getFont()->setSize(10);
        $sheet->getStyle('G1:H3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Title (PHIẾU XUẤT KHO) - row 5
        $sheet->getStyle('A5:H5')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);
        
        // Date and code rows (6-7)
        $sheet->getStyle('A6:H7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A6:H7')->getFont()->setItalic(true);
        
        // Table header styling (rows 14-15)
        $sheet->getStyle('A14:H15')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F2F2F2'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        
        // Data rows (from 16 to total row)
        if ($totalRow) {
            $dataStartRow = 16;
            $dataEndRow = $totalRow;
            
            // All data cells with borders
            $sheet->getStyle("A{$dataStartRow}:H{$dataEndRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);
            
            // Product name column - left align
            $sheet->getStyle("B{$dataStartRow}:B{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            
            // Number columns - center align
            $sheet->getStyle("A{$dataStartRow}:A{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$dataStartRow}:H{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Total row - bold
            $sheet->getStyle("A{$totalRow}:H{$totalRow}")->getFont()->setBold(true);
        }
        
        return [];
    }

    private function findRowWithText(Worksheet $sheet, string $text): ?int
    {
        $highestRow = $sheet->getHighestRow();
        
        for ($row = 1; $row <= $highestRow; $row++) {
            $cellValue = $sheet->getCell('A' . $row)->getValue();
            if (stripos($cellValue, $text) !== false) {
                return $row;
            }
            // Check other columns too
            for ($col = 'B'; $col <= 'H'; $col++) {
                $cellValue = $sheet->getCell($col . $row)->getValue();
                if (stripos($cellValue, $text) !== false) {
                    return $row;
                }
            }
        }
        
        return null;
    }
}
