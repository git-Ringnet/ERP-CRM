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

class MarketingItemTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    /**
     * Dữ liệu mẫu (mẫu chuẩn sạch UTF-8)
     */
    public function array(): array
    {
        return [
            [
                'MKT-0001',
                'Bình giữ nhiệt Lock&Lock 500ml in logo Fortinet',
                'gift',
                'Cái',
                100,
                20,
                180000,
                'Quà tặng hội thảo khách hàng VIP',
            ],
            [
                'MKT-0002',
                'Áo Polo đồng phục sự kiện Ringnet - Cisco',
                'clothing',
                'Cái',
                50,
                10,
                150000,
                'Size L, XL màu xanh navy',
            ],
            [
                'MKT-0003',
                'Brochure Giải pháp Trung tâm dữ liệu HPE Q3/2026',
                'publication',
                'Cuốn',
                200,
                50,
                25000,
                'Giấy Couche 250gsm cán mờ',
            ],
            [
                'MKT-0004',
                'Standee cuốn nhôm cao cấp 0.8x2.0m Sự kiện TechDay',
                'equipment',
                'Bộ',
                10,
                2,
                350000,
                'Chân đế hợp kim nhôm, in PP ngoài trời',
            ],
            [
                'MKT-0005',
                'Túi vải Canvas in logo Ringnet quà tặng hội thảo',
                'gift',
                'Túi',
                150,
                30,
                45000,
                'Quà tặng kèm tài liệu hội thảo',
            ],
        ];
    }

    /**
     * Tiêu đề các cột
     */
    public function headings(): array
    {
        return [
            'Mã vật phẩm',
            'Tên vật phẩm (*)',
            'Phân loại',
            'Đơn vị tính',
            'Số lượng nhập (*)',
            'Cảnh báo tồn tối thiểu',
            'Đơn giá ước tính (VNĐ)',
            'Mô tả / Ghi chú',
        ];
    }

    /**
     * Độ rộng các cột
     */
    public function columnWidths(): array
    {
        return [
            'A' => 16,
            'B' => 48,
            'C' => 18,
            'D' => 14,
            'E' => 18,
            'F' => 24,
            'G' => 24,
            'H' => 40,
        ];
    }

    /**
     * Định dạng giao diện file Excel
     */
    public function styles(Worksheet $sheet)
    {
        // Header styling (màu tím thương hiệu module MKT)
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '7C3AED'], // Purple 600
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(30);

        // Kẻ viền cho toàn bộ bảng mẫu (5 dòng dữ liệu mẫu)
        $lastRow = 6;
        $sheet->getStyle("A1:H{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E5E7EB'],
                ],
            ],
        ]);

        // Căn chỉnh các cột
        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B2:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("C2:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("G2:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("H2:H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Định dạng tiền tệ cho cột Đơn giá
        $sheet->getStyle("G2:G{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

        return [];
    }
}
