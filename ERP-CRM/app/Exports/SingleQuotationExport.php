<?php

namespace App\Exports;

use App\Models\Quotation;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Contracts\View\View;

class SingleQuotationExport implements FromView, WithColumnWidths, WithStyles, WithTitle
{
    protected Quotation $quotation;

    public function __construct(Quotation $quotation)
    {
        $this->quotation = $quotation;
    }

    public function title(): string
    {
        return 'BaoGia';
    }

    public function view(): View
    {
        return view('reports.vouchers.bao-gia', ['quotation' => $this->quotation]);
    }

    public function columnWidths(): array
    {
        $customColumns = $this->quotation->custom_columns ?? [];
        if (!is_array($customColumns)) {
            $customColumns = [];
        }
        $customColumns = array_values(array_filter($customColumns, fn($col) => !in_array($col, ['product_id', 'quantity', 'price', 'vat', 'row_total'])));

        $widths = [
            'A' => 8,   // STT
            'B' => 45,  // Tên hàng hóa, dịch vụ / Mô tả
            'C' => 12,  // Số lượng
            'D' => 18,  // Đơn giá
            'E' => 12,  // VAT (%)
        ];

        // Custom columns start at column F (which is index 5)
        $colIndex = 5;
        foreach ($customColumns as $colName) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $widths[$colLetter] = 18; // Width for custom columns
            $colIndex++;
        }

        // Last column is Thành tiền
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
        $widths[$lastColLetter] = 20;

        return $widths;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $customColumns = $this->quotation->custom_columns ?? [];
        if (!is_array($customColumns)) {
            $customColumns = [];
        }
        $customColumns = array_values(array_filter($customColumns, fn($col) => !in_array($col, ['product_id', 'quantity', 'price', 'vat', 'row_total'])));
        $totalCols = 6 + count($customColumns);
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);

        // Wrap text & vertical align top globally
        $sheet->getStyle("A1:{$lastColLetter}{$lastRow}")
            ->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_TOP);

        // Company header bold
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        // Document Title - BÁO GIÁ
        $sheet->getStyle("A5:{$lastColLetter}5")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '3498DB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle("A6:{$lastColLetter}6")->applyFromArray([
            'font'      => ['italic' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Find the product table header row
        $headerRow = null;
        $totalRow  = null;

        for ($r = 1; $r <= $lastRow; $r++) {
            $val = (string) $sheet->getCell("A{$r}")->getValue();
            if (stripos($val, 'STT') !== false) {
                $headerRow = $r;
            }
            if (stripos($val, 'TỔNG CỘNG THANH TOÁN') !== false || stripos($val, 'Tổng cộng') !== false) {
                $totalRow = $r;
                break;
            }
        }

        if ($headerRow) {
            // Header row styling
            $sheet->getStyle("A{$headerRow}:{$lastColLetter}{$headerRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '3498DB'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            $sheet->getRowDimension($headerRow)->setRowHeight(25);

            // Data rows
            $dataStart = $headerRow + 1;
            if ($totalRow) {
                // Find the last item row index by checking when the A cell has a numeric index
                $lastItemRow = $headerRow;
                for ($r = $dataStart; $r <= $lastRow; $r++) {
                    $val = $sheet->getCell("A{$r}")->getValue();
                    if (is_numeric($val)) {
                        $lastItemRow = $r;
                    }
                }

                // Apply thin borders to item rows
                $sheet->getStyle("A{$dataStart}:{$lastColLetter}{$lastItemRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);

                // Right-align money columns: D (Đơn giá) and the last column (Thành tiền)
                $sheet->getStyle("D{$dataStart}:D{$lastItemRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("{$lastColLetter}{$dataStart}:{$lastColLetter}{$lastItemRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Center STT, SL, VAT
                $sheet->getStyle("A{$dataStart}:A{$lastItemRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C{$dataStart}:C{$lastItemRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E{$dataStart}:E{$lastItemRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Apply borders and bold formatting to the total section
                $totalSectionEnd = $totalRow;
                if ($this->quotation->currency && !$this->quotation->currency->is_base) {
                    $totalSectionEnd = $totalRow + 1; // Include exchange rate conversion row
                }

                $sheet->getStyle("A" . ($lastItemRow + 1) . ":{$lastColLetter}{$totalSectionEnd}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'font'    => ['bold' => true],
                ]);
                
                // Right align money in total section
                $sheet->getStyle("{$lastColLetter}" . ($lastItemRow + 1) . ":{$lastColLetter}{$totalSectionEnd}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        }

        return [];
    }
}
