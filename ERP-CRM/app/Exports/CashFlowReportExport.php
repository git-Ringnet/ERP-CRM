<?php

namespace App\Exports;

use App\Models\FinancialTransaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CashFlowReportExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnFormatting
{
    protected $year;

    public function __construct($year)
    {
        $this->year = $year;
    }

    public function collection()
    {
        $rows = new Collection();
        
        $controller = new \App\Http\Controllers\CashFlowReportController();
        $reportData = $controller->prepareMonthlyReportData($this->year);
        
        // 1. Opening Balance Row
        $row = ['Tiền mặt Hiện có (đầu tháng)'];
        foreach ($reportData['months'] as $m) $row[] = $reportData['opening_balances'][$m];
        $row[] = $reportData['opening_balances'][1]; // Year total opening
        $rows->push($row);

        // 2. Income Header
        $rows->push(['Dòng tiền Thu vào']);

        // 3. Income Items
        foreach ($reportData['income_items'] as $itemName) {
            $row = [$itemName];
            $itemTotal = 0;
            foreach ($reportData['months'] as $m) {
                $amt = $reportData['income_data'][$itemName][$m] ?? 0;
                $row[] = $amt;
                $itemTotal += $amt;
            }
            $row[] = $itemTotal;
            $rows->push($row);
        }

        // 4. Total Income Row
        $row = ['Tổng cộng Thu vào'];
        $yearInc = 0;
        foreach ($reportData['months'] as $m) {
            $row[] = $reportData['total_income'][$m];
            $yearInc += $reportData['total_income'][$m];
        }
        $row[] = $yearInc;
        $rows->push($row);

        // 5. Expense Header
        $rows->push(['Dòng tiền Chi ra']);

        // 6. Expense Items
        foreach ($reportData['expense_items'] as $itemName) {
            $row = [$itemName];
            $itemTotal = 0;
            foreach ($reportData['months'] as $m) {
                $amt = $reportData['expense_data'][$itemName][$m] ?? 0;
                $row[] = $amt;
                $itemTotal += $amt;
            }
            $row[] = $itemTotal;
            $rows->push($row);
        }

        // 7. Total Expense Row
        $row = ['Tổng cộng Chi ra'];
        $yearExp = 0;
        foreach ($reportData['months'] as $m) {
            $row[] = $reportData['total_expense'][$m];
            $yearExp += $reportData['total_expense'][$m];
        }
        $row[] = $yearExp;
        $rows->push($row);

        // 8. Final Position Row
        $row = ['Tình hình Tiền mặt Hiện tại (Sau chi)'];
        foreach ($reportData['months'] as $m) $row[] = $reportData['closing_balances'][$m];
        $row[] = $reportData['closing_balances'][12]; // Year final closing
        $rows->push($row);

        return $rows;
    }

    public function headings(): array
    {
        $headings = ['Nội dung'];
        for ($i = 1; $i <= 12; $i++) {
            $headings[] = 'THÁNG ' . $i;
        }
        $headings[] = 'TỔNG CỘNG';
        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        // Styling based on row content
        $sheet->getStyle('A1:N1')->getFont()->setBold(true);
        $sheet->getStyle('A1:N1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEEBC8'); // Orange-100ish

        $rowCount = $sheet->getHighestRow();
        for ($i = 2; $i <= $rowCount; $i++) {
            $content = $sheet->getCell('A' . $i)->getValue();
            if (in_array($content, ['Dòng tiền Thu vào', 'Dòng tiền Chi ra'])) {
                $sheet->getStyle('A' . $i . ':N' . $i)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
                $sheet->getStyle('A' . $i . ':N' . $i)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF6AD55'); // Orange-500
            }
            if (in_array($content, ['Tổng cộng Thu vào', 'Tổng cộng Chi ra', 'Tiền mặt Hiện có (đầu tháng)'])) {
                $sheet->getStyle('A' . $i . ':N' . $i)->getFont()->setBold(true);
            }
            if ($content == 'Tình hình Tiền mặt Hiện tại (Sau chi)') {
                $sheet->getStyle('A' . $i . ':N' . $i)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
                $sheet->getStyle('A' . $i . ':N' . $i)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF2B6CB0'); // Blue-600
            }
        }

        return [];
    }

    public function title(): string
    {
        return 'Báo cáo dòng tiền ' . $this->year;
    }

    public function columnFormats(): array
    {
        $formats = [];
        foreach (range('B', 'N') as $col) {
            $formats[$col] = NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;
        }
        return $formats;
    }
}
