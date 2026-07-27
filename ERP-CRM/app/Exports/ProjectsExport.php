<?php

namespace App\Exports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjectsExport implements FromArray, WithHeadings, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function array(): array
    {
        $query = Project::with(['customer', 'manager', 'vendor', 'initialProcessedBy', 'notes', 'saleItems.product'])->orderBy('created_at', 'desc');

        if (!empty($this->filters['project_id'])) {
            $query->where('id', $this->filters['project_id']);
        }
        if (!empty($this->filters['search'])) {
            $query->search($this->filters['search']);
        }
        if (!empty($this->filters['status'])) {
            $query->filterByStatus($this->filters['status']);
        }
        if (!empty($this->filters['customer_id'])) {
            $query->where('customer_id', $this->filters['customer_id']);
        }
        if (!empty($this->filters['vendor_id'])) {
            $query->where('vendor_id', $this->filters['vendor_id']);
        }
        if (!empty($this->filters['manager_id'])) {
            $query->where('manager_id', $this->filters['manager_id']);
        }
        if (!empty($this->filters['initial_processed_by'])) {
            $query->where('initial_processed_by', $this->filters['initial_processed_by']);
        }
        if (!empty($this->filters['registration_status'])) {
            $query->where('registration_status', $this->filters['registration_status']);
        }
        if (!empty($this->filters['quarter'])) {
            $quarter = $this->filters['quarter'];
            $year = $this->filters['year'] ?? date('Y');
            $quarterMonths = [
                'Q1' => [1, 3],
                'Q2' => [4, 6],
                'Q3' => [7, 9],
                'Q4' => [10, 12],
            ];
            if (isset($quarterMonths[$quarter])) {
                $query->whereYear('created_at', $year)
                      ->whereMonth('created_at', '>=', $quarterMonths[$quarter][0])
                      ->whereMonth('created_at', '<=', $quarterMonths[$quarter][1]);
            }
        }
        if (!empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }
        if (!empty($this->filters['expiry_start_date'])) {
            $query->whereDate('end_date', '>=', $this->filters['expiry_start_date']);
        }
        if (!empty($this->filters['expiry_end_date'])) {
            $query->whereDate('end_date', '<=', $this->filters['expiry_end_date']);
        }
        if (!empty($this->filters['is_overdue_sla'])) {
            $query->where(function($q) {
                $q->where(function($q1) {
                    $q1->where('intake_status', 'pending')
                       ->whereNotNull('initial_sla_due_at')
                       ->where('initial_sla_due_at', '<', now());
                })->orWhere(function($q2) {
                    $q2->whereIn('registration_status', ['vendor_processing', 'vendor_reminded', 'processing'])
                       ->whereNotNull('vendor_due_at')
                       ->where('vendor_due_at', '<', now());
                });
            });
        }

        $projects = $query->get();
        $rows = [];
        $no = 1;

        foreach ($projects as $project) {
            // Get BOM items
            $bomItems = [];
            
            // 1. If project has saleItems (orders)
            if ($project->saleItems && $project->saleItems->count() > 0) {
                foreach ($project->saleItems as $item) {
                    $bomItems[] = [
                        'pn' => $item->product->sku ?? $item->product->code ?? '',
                        'model' => $item->product_name ?? $item->product->name ?? '',
                        'qty' => $item->quantity,
                        'unit_price' => $item->price,
                        'total_price' => $item->total,
                    ];
                }
            }
            // 2. Otherwise parse from bom_data textarea
            elseif (!empty($project->bom_data)) {
                $bomItems = self::parseBomData($project->bom_data);
            }

            // If no BOM items found, create one empty item so the project itself is still exported
            if (empty($bomItems)) {
                $bomItems[] = [
                    'pn' => '',
                    'model' => '',
                    'qty' => '',
                    'unit_price' => '',
                    'total_price' => '',
                ];
            }

            // Add rows for this project
            foreach ($bomItems as $index => $bomItem) {
                $isFirst = ($index === 0);
                
                $rows[] = [
                    'no' => $isFirst ? $no : '',
                    'salesman' => $isFirst ? ($project->manager->name ?? '') : '',
                    'si' => $isFirst ? ($project->collaborate_company ?? 'Làm việc trực tiếp End-User') : '',
                    'eu' => $isFirst ? (($project->eu_tax_code ? $project->eu_tax_code . ' - ' : '') . $project->eu_name_vi) : '',
                    'project_name' => $isFirst ? $project->name : '',
                    'pn' => $bomItem['pn'],
                    'model' => $bomItem['model'],
                    'od' => '', // Empty column as shown in Excel template
                    'qty' => $bomItem['qty'],
                    'unit_price' => $bomItem['unit_price'] ? (is_numeric($bomItem['unit_price']) ? number_format($bomItem['unit_price'], 0, ',', '.') : $bomItem['unit_price']) : '',
                    'total_price' => $bomItem['total_price'] ? (is_numeric($bomItem['total_price']) ? number_format($bomItem['total_price'], 0, ',', '.') : $bomItem['total_price']) : '',
                    'date_deal_reg' => $isFirst ? ($project->created_at ? $project->created_at->format('Y-m-d') : '') : '',
                    'date_expect' => $isFirst ? ($project->end_date ? $project->end_date->format('Y-m-d') : '') : '',
                    'last_update' => $isFirst ? ($project->notes->last()?->content ?? $project->note ?? '') : '',
                    'note_by_pm' => $isFirst ? ($project->intake_note ?? $project->vendor_quote_note ?? '') : '',
                ];
            }
            
            $no++;
        }

        return $rows;
    }

    public static function parseBomData($bomData)
    {
        $lines = explode("\n", $bomData);
        $items = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $qty = 1;
            $pn = '';
            $model = $line;
            $unitPrice = null;
            $totalPrice = null;

            // Attempt to parse Qty
            if (preg_match('/^(\d+)\s*(?:x|pcs|pc|cái|chiếc)?\s+(.+)$/i', $line, $matches)) {
                $qty = (int)$matches[1];
                $model = trim($matches[2]);
            }
            elseif (preg_match('/^(.+?)\s+(\d+)\s*(?:pcs|pc|cái|chiếc|x)$/i', $line, $matches)) {
                $model = trim($matches[1]);
                $qty = (int)$matches[2];
            }
            elseif (preg_match('/(?:qty|quantity|số lượng|sl)[:\-\s]+(\d+)/i', $line, $matches)) {
                $qty = (int)$matches[1];
                $model = preg_replace('/\(?\s*(?:qty|quantity|số lượng|sl)[:\-\s]+\d+\s*\)?/i', '', $line);
            }

            // Attempt to parse Price
            if (preg_match('/(?:price|đơn giá|giá|@|usd|vnd)[:\-\s]*([0-9.,]+)/i', $line, $matches)) {
                $priceStr = str_replace([',', ' '], '', $matches[1]);
                $unitPrice = floatval($priceStr);
                $totalPrice = $unitPrice * $qty;
                $model = preg_replace('/\(?\s*(?:price|đơn giá|giá|@|usd|vnd)[:\-\s]*[0-9.,]+\s*\)?/i', '', $model);
            }

            $model = trim($model, " -:|()");

            $items[] = [
                'pn' => $pn,
                'model' => $model,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ];
        }

        return $items;
    }

    public function headings(): array
    {
        return [
            'No.',
            'Salesman',
            'SI',
            'EU',
            "Project's Name",
            'P/N',
            'Model / Description',
            'OD',
            "Q'ty",
            'Unit Price',
            'Total Price',
            'Date Deal Reg',
            'Date Expect (Close Deal)',
            'Last update',
            'Note by PM',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '1E3A8A'] // Dark navy text
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => 'DBEAFE'] // Soft, light blue background
                ]
            ],
        ];
    }
}
