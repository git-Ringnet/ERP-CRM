<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use App\Models\PoCompanyConfig;
use App\Models\SupplierPoConfig;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class SaleContractPurchaseOrderExport implements FromView, WithStyles, WithTitle, WithColumnWidths, WithDrawings
{
    protected PurchaseOrder $po;

    public function __construct(PurchaseOrder $po)
    {
        $this->po = $po;
        $this->po->load(['supplier', 'items.product', 'items.saleOrderRequestItem.saleItem', 'currency']);
    }

    public function title(): string
    {
        $title = str_replace(['\\', '/', '?', '*', ':', '[', ']'], '-', $this->po->code);
        return substr($title, 0, 31);
    }

    public function view(): View
    {
        $company = PoCompanyConfig::getConfig();
        $config = $this->po->supplier->poConfig ?: new SupplierPoConfig([
            'template_type' => 'sale_contract',
            'seller_name' => $this->po->supplier->name,
            'seller_address_line1' => $this->po->supplier->address,
            'seller_tel' => $this->po->supplier->phone,
            'seller_contact' => $this->po->supplier->contact_person,
            'port_loading' => 'TAIWAN/ USA',
            'port_discharge' => 'HOCHIMINH CITY, VIETNAM',
        ]);

        return view('reports.vouchers.po-sale-contract', [
            'po' => $this->po,
            'company' => $company,
            'config' => $config,
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ITEM
            'B' => 45,  // Goods Description
            'C' => 25,  // Model/Part
            'D' => 20,  // Project ID
            'E' => 20,  // Unit Price
            'F' => 10,  // QTY
            'G' => 25,  // Total Net Price
        ];
    }

    public function drawings()
    {
        $drawings = [];
        $company = PoCompanyConfig::getConfig();
        
        $bannerPath = null;
        if ($company->header_banner_path && file_exists(public_path($company->header_banner_path))) {
            $bannerPath = $company->header_banner_path;
        } elseif (file_exists(public_path('images/default-banner.png'))) {
            $bannerPath = 'images/default-banner.png';
        } elseif (file_exists(public_path('uploads/po-logos/default-banner.png'))) {
            $bannerPath = 'uploads/po-logos/default-banner.png';
        }

        if ($bannerPath) {
            // Full-width banner image spanning all header rows
            $drawing = new Drawing();
            $drawing->setName('Header Banner');
            $drawing->setDescription('Company Header Banner');
            $drawing->setPath(public_path($bannerPath));
            $drawing->setHeight(115);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(2);
            $drawing->setOffsetY(2);
            $drawings[] = $drawing;
        } elseif ($company->header_logo_path && file_exists(public_path($company->header_logo_path))) {
            // Small logo fallback
            $drawing = new Drawing();
            $drawing->setName('Company Logo');
            $drawing->setDescription('Logo');
            $drawing->setPath(public_path($company->header_logo_path));
            $drawing->setHeight(50);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(5);
            $drawings[] = $drawing;
        }
        
        return $drawings;
    }

    public function styles(Worksheet $sheet)
    {
        // Set default font for the entire workbook to Arial
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial');
        $sheet->getParent()->getDefaultStyle()->getFont()->setSize(9);

        // Apply Arial font to the entire expected active range
        $sheet->getStyle('A1:G250')->getFont()->setName('Arial');
        $sheet->getStyle('A1:G250')->getFont()->setSize(9);

        // Header section styling
        $sheet->getStyle('D1:G1')->getFont()->setName('Arial')->setSize(14)->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('153E7E'));
        $sheet->getStyle('D2:G6')->getFont()->setName('Arial')->setSize(8);
        $sheet->getStyle('D2:D2')->getFont()->setBold(true);
        $sheet->getStyle('D4:D4')->getFont()->setBold(true);

        // Title styling: SALE CONTRACT
        $sheet->getStyle('A8')->getFont()->setName('Times New Roman')->setSize(16)->setBold(true);
        $sheet->getStyle('A9:G9')->getFont()->setName('Arial')->setSize(9)->setBold(false);

        // Enable gridlines visibility
        $sheet->setShowGridlines(true);

        // Alignment globally
        $sheet->getStyle('A1:G250')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:G250')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $itemCount = $this->po->items->count();
        $startItemRow = 32; // Table starts at row 32 in view (row 31 is the header)
        $lastItemRow = $startItemRow + $itemCount - 1;
        $grandTotalRow = $lastItemRow + 1;
        $sayInWordsRow = $lastItemRow + 2;

        // Apply row heights globally for active rows
        $lastRow = $sayInWordsRow + 60;
        for ($r = 1; $r <= $lastRow; $r++) {
            if ($r >= 11 && $r <= 26) {
                // Card rows: auto height to prevent cutoff (rows 11-21 and 22-26)
                $sheet->getRowDimension($r)->setRowHeight(-1);
            } elseif ($r >= 27 && $r <= 29) {
                // Paragraphs and description title outside table: auto height
                $sheet->getRowDimension($r)->setRowHeight(-1);
            } elseif ($r == 30) {
                // Spacer row above table: 15px height, borderless
                $sheet->getRowDimension($r)->setRowHeight(15);
            } elseif ($r == 31) {
                // Table Header row: 26px height
                $sheet->getRowDimension($r)->setRowHeight(26);
            } elseif ($r >= 32 && $r <= $lastItemRow) {
                // Item rows: auto height
                $sheet->getRowDimension($r)->setRowHeight(-1);
            } elseif ($r >= $lastItemRow + 4) {
                // Terms, spacer, and signatures: auto height (excluding signature spacer)
                if ($r == $lastItemRow + 16) {
                    $sheet->getRowDimension($r)->setRowHeight(50);
                } else {
                    $sheet->getRowDimension($r)->setRowHeight(-1);
                }
            } else {
                $sheet->getRowDimension($r)->setRowHeight(17.5);
            }
        }

        // Taller row heights for static header
        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(20);
        $sheet->getRowDimension(4)->setRowHeight(20);
        $sheet->getRowDimension(5)->setRowHeight(20);
        $sheet->getRowDimension(6)->setRowHeight(20);
        $sheet->getRowDimension(7)->setRowHeight(10); // Separation line row
        $sheet->getRowDimension(8)->setRowHeight(28); // Title row (SALE CONTRACT)
        $sheet->getRowDimension(9)->setRowHeight(20); // Order No & Date

        // Borders
        $thinBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        $outlineBorder = [
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        // Apply outline border to Buyer and Seller boxes
        $sheet->getStyle('A11:D20')->applyFromArray($outlineBorder); // THE BUYER
        $sheet->getStyle('E11:G20')->applyFromArray($outlineBorder); // THE SELLER
        $sheet->getStyle('A21:D24')->applyFromArray($outlineBorder); // SHIP TO
        $sheet->getStyle('E21:G24')->applyFromArray($outlineBorder); // INVOICE TO
        $sheet->getStyle('A25:D25')->applyFromArray($outlineBorder); // Port of loading
        $sheet->getStyle('E25:G25')->applyFromArray($outlineBorder); // Port of discharge

        // Apply RichText parsing to Card Details (rows 11 to 26)
        for ($r = 11; $r <= 26; $r++) {
            $this->makeRichTextColonCell($sheet, 'A' . $r, false, false, true, false);
            $this->makeRichTextColonCell($sheet, 'E' . $r, false, false, true, false);
        }

        // Apply thin borders to items table (starts at row 31 - Table Header)
        $sheet->getStyle('A31:G' . $sayInWordsRow)->applyFromArray($thinBorder);

        // Alignments inside items table
        $sheet->getStyle('A31:G31')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A32:A' . $lastItemRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C32:D' . $lastItemRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F32:F' . $lastItemRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Right alignment for values
        $sheet->getStyle('E32:E' . $lastItemRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('G32:G' . $lastItemRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('G' . $grandTotalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        // Font formatting inside items table
        $sheet->getStyle('A31:G31')->getFont()->setBold(true);
        $sheet->getStyle('A' . $grandTotalRow . ':G' . $grandTotalRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $sayInWordsRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $sayInWordsRow)->getFont()->setItalic(true);

        // Parse Order No and Date labels
        $sheet->getStyle('A9')->getFont()->setBold(true);
        $sheet->getStyle('E9')->getFont()->setBold(true);
        $sheet->getStyle('E9')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        // Parse Signatures styling (rows lastItemRow+14 to lastItemRow+17)
        $sheet->getStyle('A' . ($lastItemRow + 14) . ':G' . ($lastItemRow + 17))->getFont()->setBold(true);
        $sheet->getStyle('A' . ($lastItemRow + 14) . ':G' . ($lastItemRow + 17))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Apply RichText parsing to Terms 1-10 (underline only the title, regular text)
        for ($r = $lastItemRow + 5; $r <= $lastItemRow + 12; $r++) {
            $this->makeTermRichTextCell($sheet, 'A' . $r);
            $this->makeTermRichTextCell($sheet, 'E' . $r);
        }

        return [];
    }

    private function makeTermRichTextCell(Worksheet $sheet, string $cellCoordinate)
    {
        $cell = $sheet->getCell($cellCoordinate);
        $text = $cell->getValue();
        if ($text instanceof RichText) {
            $text = $text->getPlainText();
        }
        
        if (is_string($text) && preg_match('/^(\d+)\.\s*([^:]+):(.*)$/s', $text, $matches)) {
            $number = $matches[1] . '. ';
            $title = $matches[2];
            $suffix = ':' . $matches[3];
            
            $richText = new RichText();
            
            // Run 1: Number (e.g. "1. ") - Regular
            $run1 = $richText->createTextRun($number);
            $run1->getFont()->setBold(false);
            $run1->getFont()->setUnderline(false);
            $run1->getFont()->setName('Arial');
            $run1->getFont()->setSize(9);
            
            // Run 2: Title (e.g. "Prices") - Underlined
            $run2 = $richText->createTextRun($title);
            $run2->getFont()->setBold(false);
            $run2->getFont()->setUnderline(true);
            $run2->getFont()->setName('Arial');
            $run2->getFont()->setSize(9);
            
            // Run 3: Suffix (e.g. ": in US Dollars, FOB, USA.") - Regular
            $run3 = $richText->createTextRun($suffix);
            $run3->getFont()->setBold(false);
            $run3->getFont()->setUnderline(false);
            $run3->getFont()->setName('Arial');
            $run3->getFont()->setSize(9);
            
            $cell->setValue($richText);
        }
    }

    private function makeRichTextColonCell(Worksheet $sheet, string $cellCoordinate, bool $underlinePrefix = false, bool $boldSuffix = false, bool $boldPrefix = true, bool $underlineSuffix = false)
    {
        $cell = $sheet->getCell($cellCoordinate);
        $text = $cell->getValue();
        if (is_string($text) && strpos($text, ':') !== false) {
            $parts = explode(':', $text, 2);
            $prefix = $parts[0] . ':';
            $suffix = $parts[1];
            
            $richText = new RichText();
            
            $run1 = $richText->createTextRun($prefix);
            $run1->getFont()->setBold($boldPrefix);
            if ($underlinePrefix) {
                $run1->getFont()->setUnderline(true);
            }
            $run1->getFont()->setName('Arial');
            $run1->getFont()->setSize(9);
            
            $run2 = $richText->createTextRun($suffix);
            $run2->getFont()->setBold($boldSuffix);
            if ($underlineSuffix) {
                $run2->getFont()->setUnderline(true);
            }
            $run2->getFont()->setName('Arial');
            $run2->getFont()->setSize(9);
            
            $cell->setValue($richText);
        }
    }
}
