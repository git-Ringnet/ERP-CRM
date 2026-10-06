<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MarketingSupplierFundTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    /**
     * Dữ liệu mẫu (mẫu chuẩn sạch UTF-8)
     */
    public function array(): array
    {
        return [
            [
                'Fortinet',
                'Quỹ Hãng Fortinet MDF Q3-2026',
                'Q3',
                2026,
                150000000,
                'Tài trợ sự kiện hội thảo an ninh mạng & workshop kỹ thuật',
            ],
            [
                'Cisco',
                'Quỹ Đồng hành Cisco Co-op Q3-2026',
                'Q3',
                2026,
                200000000,
                'Chương trình thúc đẩy giải pháp mạng doanh nghiệp',
            ],
            [
                'HPE',
                'Quỹ Hỗ trợ HPE FastForward MDF Q4-2026',
                'Q4',
                2026,
                120000000,
                'Tài trợ chi phí hội thảo Data Center Day',
            ],
            [
                'Dell',
                'Quỹ Hãng Dell Partner Marketing 2026',
                'Q3',
                2026,
                180000000,
                'Chương trình đào tạo chuyên sâu và tài liệu bán hàng',
            ],
        ];
    }

    /**
     * Tiêu đề các cột
     */
    public function headings(): array
    {
        return [
            'Tên Hãng / Nhà cung cấp (*)',
            'Tên Quỹ Hỗ Trợ (*)',
            'Quý (*)',
            'Năm (*)',
            'Tổng Tiền Quỹ Cấp (VNĐ) (*)',
            'Ghi chú / Điều kiện',
        ];
    }

    /**
     * Độ rộng các cột
     */
    public function columnWidths(): array
    {
        return [
            'A' => 28,
            'B' => 45,
            'C' => 12,
            'D' => 12,
            'E' => 28,
            'F' => 45,
        ];
    }

    /**
     * Định dạng giao diện file Excel
     */
    public function styles(Worksheet $sheet)
    {
        // Header styling (màu tím thương hiệu Module Marketing & Quỹ)
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '6B21A8'], // Purple 800
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(30);

        $lastRow = 5;
        $sheet->getStyle("A1:F{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E5E7EB'],
                ],
            ],
        ]);

        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("B2:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("C2:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("F2:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->getStyle("E2:E{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

        return [];
    }
}
