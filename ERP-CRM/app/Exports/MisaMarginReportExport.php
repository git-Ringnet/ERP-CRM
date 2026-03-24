<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Contracts\View\View;

class MisaMarginReportExport implements FromView, WithStyles
{
    protected $rows;
    protected $dateFrom;
    protected $dateTo;

    public function __construct($rows, $dateFrom, $dateTo)
    {
        $this->rows = $rows;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function view(): View
    {
        return view('exports.misa-margin-report', [
            'rows' => $this->rows,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Centering for header row and some columns
            2 => ['font' => ['bold' => true]],
        ];
    }
}
