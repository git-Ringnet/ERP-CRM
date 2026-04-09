<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Contracts\View\View;

class SaleInvoiceExport implements FromView, WithColumnWidths, WithStyles, WithTitle
{
    protected Sale $sale;

    public function __construct(Sale $sale)
    {
        $this->sale = $sale;
    }

    public function title(): string
    {
        return 'HoaDon';
    }

    public function view(): View
    {
        $sale = $this->sale->load(['items.product', 'customer', 'expenses', 'project', 'currency']);

        return view('reports.vouchers.hoa-don-ban-hang', ['sale' => $sale]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // STT
            'B' => 42,  // Tên hàng hóa
            'C' => 12,  // ĐVT
            'D' => 12,  // Số lượng
            'E' => 20,  // Đơn giá
            'F' => 22,  // Thành tiền
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        // Wrap text & vertical align top globally
        $sheet->getStyle("A1:F{$lastRow}")
            ->getAlignment()
            ->setWrapText(false)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Row 1: Company name — bold, larger font
        $sheet->getStyle('A1:F1')->getFont()->setBold(true)->setSize(12);

        // Row 5: HÓA ĐƠN BÁN HÀNG — big bold centered
        $sheet->getStyle('A5:F5')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Row 6-7: mẫu số, số/ngày — italic centered
        $sheet->getStyle('A6:F7')->applyFromArray([
            'font'      => ['italic' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Find the product table header row (row 13 in our single-table structure)
        // Header row = first row that has "STT" in column A
        $headerRow = null;
        $totalRow  = null;

        for ($r = 1; $r <= $lastRow; $r++) {
            $val = (string) $sheet->getCell("A{$r}")->getValue();
            if (stripos($val, 'STT') !== false) {
                $headerRow = $r;
            }
            if (stripos($val, 'TỔNG CỘNG') !== false || stripos($val, 'Tổng cộng') !== false) {
                $totalRow = $r;
                break;
            }
        }

        if ($headerRow) {
            // Header row styling
            $sheet->getStyle("A{$headerRow}:F{$headerRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F2F2F2'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            $sheet->getRowDimension($headerRow)->setRowHeight(22);

            // Data rows
            $dataStart = $headerRow + 1;
            if ($totalRow) {
                $sheet->getStyle("A{$dataStart}:F{$totalRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'font'    => ['size' => 11],
                ]);
                // Right-align money columns
                $sheet->getStyle("E{$dataStart}:F{$totalRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                // Center STT, ĐVT, SL
                $sheet->getStyle("A{$dataStart}:A{$totalRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C{$dataStart}:D{$totalRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Bold total row
                $sheet->getStyle("A{$totalRow}:F{$totalRow}")->getFont()->setBold(true);
            }
        }

        return [];
    }
}
