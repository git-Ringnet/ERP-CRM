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

class BomTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function array(): array
    {
        return [
            [
                '1',
                'AW210040',
                'AirEngine 5760-51 Access Point (802.11ax, 4x4:4 MIMO, Smart Antennas)',
                'Cái',
                2,
                15000000,
                12,
                'Hàng chính hãng, bảo hành 12 tháng',
            ],
            [
                '2',
                'FG-60F-BDL-950-12',
                'FortiGate-60F Hardware plus 1 Year 24x7 FortiCare and FortiGuard Unified Threat Protection (UTP)',
                'Bộ',
                1,
                12500000,
                12,
                'Bao gồm license 1 năm',
            ],
            [
                '3',
                'CAB-CAT6-UTP-305M',
                'Cáp mạng CommScope / AMP Category 6 UTP, 4-pair, 23 AWG (Cuộn 305m)',
                'Cuộn',
                3,
                2800000,
                24,
                'Dây cáp mạng lõi đồng nguyên chất',
            ],
            [
                '4',
                'SP-MOI-01',
                'Gói dịch vụ triển khai lắp đặt & cấu hình Onsite',
                'Gói',
                1,
                5000000,
                0,
                'Dịch vụ kỹ thuật',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'STT',
            'Mã Part Number / Mã sản phẩm (*)',
            'Tên / Model sản phẩm (*)',
            'Đơn vị tính',
            'Số lượng (*)',
            'Đơn giá (VNĐ)',
            'Bảo hành (tháng)',
            'Ghi chú',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 24,
            'C' => 50,
            'D' => 14,
            'E' => 12,
            'F' => 18,
            'G' => 18,
            'H' => 35,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'], // Primary blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);

        // Body border & alignments
        $lastRow = 5;
        $sheet->getStyle("A1:H{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        // Alignment for specific columns
        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F2:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("G2:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Number format for Price
        $sheet->getStyle("F2:F{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

        return [];
    }
}
