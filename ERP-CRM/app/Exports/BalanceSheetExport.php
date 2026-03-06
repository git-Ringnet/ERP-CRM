<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class BalanceSheetExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $reportData;
    protected $date;

    public function __construct(array $reportData, $date)
    {
        $this->reportData = $reportData;
        $this->date = $date;
    }

    public function title(): string
    {
        return 'Bang Can doi ke toan';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 60, // TÀI SẢN / NGUỒN VỐN
            'B' => 10, // Mã số
            'C' => 15, // Thuyết minh
            'D' => 20, // Số cuối năm
            'E' => 20, // Số đầu năm
        ];
    }

    public function headings(): array
    {
        $d = \Carbon\Carbon::parse($this->date);
        return [
            ['BẢNG CÂN ĐỐI KẾ TOÁN'],
            ['Tại ngày ' . $d->format('d/m/Y')],
            ['(Mẫu số B 01 - DN)'],
            [],
            ['TÀI SẢN', 'Mã số', 'Thuyết minh', 'Số cuối năm', 'Số đầu năm'],
            ['1', '2', '3', '4', '5'],
        ];
    }

    public function array(): array
    {
        $rows = [];

        // Assets
        foreach (['A', 'B'] as $sec) {
            if (isset($this->reportData[$sec])) {
                $rows[] = [
                    $sec . ' - ' . strtoupper($this->reportData[$sec]['name']),
                    $this->reportData[$sec]['code'],
                    '',
                    $this->reportData[$sec]['end'],
                    $this->reportData[$sec]['start']
                ];

                foreach ($this->reportData[$sec]['sub'] as $subKey => $sub) {
                    $rows[] = [
                        '  ' . $subKey . '. ' . $sub['name'],
                        $sub['code'],
                        '',
                        $sub['end'],
                        $sub['start']
                    ];

                    foreach ($sub['items'] as $item) {
                        $rows[] = [
                            '    ' . $item['name'],
                            $item['code'],
                            $item['note'] ?? '',
                            $item['end'],
                            $item['start']
                        ];
                    }
                }
            }
        }

        // Total Assets
        $rows[] = [
            strtoupper($this->reportData['TOTAL_ASSETS']['name'] ?? 'TỔNG CỘNG TÀI SẢN'),
            $this->reportData['TOTAL_ASSETS']['code'] ?? '270',
            '',
            $this->reportData['TOTAL_ASSETS']['end'] ?? 0,
            $this->reportData['TOTAL_ASSETS']['start'] ?? 0
        ];

        $rows[] = [];
        $rows[] = ['NGUỒN VỐN', '', '', '', ''];

        // Resources
        foreach (['C', 'D'] as $sec) {
            if (isset($this->reportData[$sec])) {
                $rows[] = [
                    $sec . ' - ' . strtoupper($this->reportData[$sec]['name']),
                    $this->reportData[$sec]['code'],
                    '',
                    $this->reportData[$sec]['end'],
                    $this->reportData[$sec]['start']
                ];

                foreach ($this->reportData[$sec]['sub'] as $subKey => $sub) {
                    $rows[] = [
                        '  ' . $subKey . '. ' . $sub['name'],
                        $sub['code'],
                        '',
                        $sub['end'],
                        $sub['start']
                    ];

                    foreach ($sub['items'] as $item) {
                        $rows[] = [
                            '    ' . $item['name'],
                            $item['code'],
                            $item['note'] ?? '',
                            $item['end'],
                            $item['start']
                        ];
                    }
                }
            }
        }

        // Total Resources
        $rows[] = [
            strtoupper($this->reportData['TOTAL_RESOURCES']['name'] ?? 'TỔNG CỘNG NGUỒN VỐN'),
            $this->reportData['TOTAL_RESOURCES']['code'] ?? '440',
            '',
            $this->reportData['TOTAL_RESOURCES']['end'] ?? 0,
            $this->reportData['TOTAL_RESOURCES']['start'] ?? 0
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('A2:E2');
        $sheet->mergeCells('A3:E3');

        $styleArray = [
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1')->applyFromArray($styleArray);
        $sheet->getStyle('A2')->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
        $sheet->getStyle('A3')->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);

        // Table headers
        $sheet->getStyle('A5:E6')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E9ECEF'],
            ],
        ]);

        return [];
    }
}
