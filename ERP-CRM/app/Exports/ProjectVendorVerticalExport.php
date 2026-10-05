<?php

namespace App\Exports;

use App\Models\Project;
use App\Http\Controllers\ProjectController;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Contracts\View\View;

class ProjectVendorVerticalExport implements FromView, WithColumnWidths, WithStyles, WithTitle
{
    protected Project $project;
    protected array $bomItems;

    public function __construct(Project $project, array $bomItems = [])
    {
        $this->project = $project;
        $this->bomItems = $bomItems;
    }

    public function title(): string
    {
        return 'DKDA_' . $this->project->code;
    }

    public function view(): View
    {
        return view('projects.export_vendor_vertical', [
            'project' => $this->project,
            'bomItems' => $this->bomItems,
            'industries' => ProjectController::INDUSTRIES,
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 32,
            'B' => 35,
            'C' => 20,
            'D' => 25,
            'E' => 15,
            'F' => 25,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setShowGridLines(true);
        $sheet->getStyle('A1:F100')->getFont()->setName('Arial');
        $sheet->getStyle('A1:F100')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        
        return [];
    }
}
