<?php

namespace App\Exports;

use App\Models\FinancialTransaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;

class MisaReceiptsExport implements FromView, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function view(): View
    {
        $query = FinancialTransaction::with('category')
            ->orderBy('date', 'asc');

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('date', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('date', '<=', $this->filters['date_to']);
        }
        if (!empty($this->filters['type'])) {
            $query->where('type', $this->filters['type']);
        }

        return view('exports.misa-receipts', [
            'transactions' => $query->get()
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }
}
