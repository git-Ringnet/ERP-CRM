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

class OrderRequestSerialTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function array(): array
    {
        return [
            [
                '1',
                'FG-100F',
                'FGT100FT18000001',
                '2028-12-31',
                'Thiết bị tường lửa chính',
            ],
            [
                '2',
                'FG-100F',
                'FGT100FT18000002',
                '2028-12-31',
                'Thiết bị tường lửa dự phòng HA',
            ],
            [
                '3',
                'FC-10-F100F-950-02-12',
                'FGT100FT18000001',
                '2027-10-31',
                'License UTP gia hạn 1 năm cho SN 1',
            ],
            [
                '4',
                'FC-10-F100F-950-02-12',
                'FGT100FT18000002',
                '2027-10-31',
                'License UTP gia hạn 1 năm cho SN 2',
            ],
            [
                '5',
                'FS-124F',
                'S124FN1900001234',
                '',
                'Switch truy nhập',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'STT',
            'Part Number / P/N (*)',
            'Serial Number (S/N) (*)',
            'Hạn dùng (YYYY-MM-DD)',
            'Ghi chú',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 28,
            'C' => 30,
            'D' => 24,
            'H' => 35,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669'], // Emerald green
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);

        $lastRow = 6;
        $sheet->getStyle("A1:E{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B2:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("D2:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }
}
