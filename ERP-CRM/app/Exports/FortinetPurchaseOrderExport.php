<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FortinetPurchaseOrderExport implements FromView, WithStyles, WithTitle, WithColumnWidths
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
        $company = \App\Models\PoCompanyConfig::getConfig();
        $config = $this->po->supplier->poConfig ?: new \App\Models\SupplierPoConfig([
            'template_type' => 'fortinet',
            'seller_name' => 'FORTINET INC',
            'seller_address_line1' => 'US Headquarters, 909 Kifer Road, Sunnyvale, CA 94086 US',
            'seller_address_line2' => '',
            'seller_tel' => '(408) 486-4816',
            'seller_fax' => '(408) 235-7737',
            'seller_contact' => "ANSON HA - Order Coordinator",
            'seller_beneficiary' => 'Fortinet Inc., 909 Kifer Road',
            'seller_beneficiary_address' => 'Sunnyvale, CA 94086 United States',
            'seller_bank_name' => 'WELLS FARGO BANK, N.A',
            'seller_bank_account' => '4040006199',
            'seller_bank_address_line1' => 'Santa Clara Valley RCBO, 420 Montgomery St,',
            'seller_bank_address_line2' => 'San Francisco, CA 94104',
            'seller_bank_aba' => '121000248',
            'seller_swift_code' => 'WFBIUS6SSFO',
            'port_loading' => 'TAIWAN/ USA',
            'port_discharge' => 'HOCHIMINH CITY, VIETNAM',
        ]);

        return view('reports.vouchers.po-fortinet', [
            'po' => $this->po,
            'company' => $company,
            'config' => $config,
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // Item
            'B' => 25,  // SKU
            'C' => 8,   // Qty
            'D' => 18,  // S/N
            'E' => 15,  // Quote ID
            'F' => 18,  // List Price
            'G' => 18,  // Discount
            'H' => 18,  // Unit Price
            'I' => 20,  // Total Net Price
            'J' => 25,  // Note
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set default font for the entire workbook to Arial
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial');
        $sheet->getParent()->getDefaultStyle()->getFont()->setSize(9);

        // Apply Arial font to the entire expected active range
        $sheet->getStyle('A1:J250')->getFont()->setName('Arial');
        $sheet->getStyle('A1:J250')->getFont()->setSize(9);

        // Explicitly set PURCHASE ORDER (A1) and PO Date (F2) to Times New Roman
        $sheet->getStyle('A1')->getFont()->setName('Times New Roman')->setSize(16)->setBold(true);
        $sheet->getStyle('F2:J2')->getFont()->setName('Times New Roman')->setSize(9)->setBold(false);

        // Explicitly set Order No (A2) to Arial and not bold
        $sheet->getStyle('A2')->getFont()->setName('Arial')->setBold(false);

        // Enable gridlines visibility
        $sheet->setShowGridlines(true);

        // Enable wrap text and vertical center alignment for all cells in this range globally
        $sheet->getStyle('A1:J250')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:J250')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // Fix spacing between Tel and Fax in row 7 (Buyer & Seller cards)
        $cellA7 = $sheet->getCell('A7');
        $valA7 = $cellA7->getValue();
        if (is_string($valA7)) {
            $sheet->getCell('A7')->setValue(str_replace(' Fax:', '     Fax:', $valA7));
        }

        $cellF7 = $sheet->getCell('F7');
        $valF7 = $cellF7->getValue();
        if (is_string($valF7)) {
            $sheet->getCell('F7')->setValue(str_replace(' Fax', '     Fax', $valF7));
        }

        $cellF14 = $sheet->getCell('F14');
        $valF14 = $cellF14->getValue();
        if (is_string($valF14)) {
            $sheet->getCell('F14')->setValue(str_replace('; BANK', ';  BANK', $valF14));
        }

        $itemCount = $this->po->items->count();
        $lastItemRow = 22 + $itemCount;

        // Set row heights explicitly for all active rows to prevent Excel auto-fit when wrap text is enabled
        $lastRow = $lastItemRow + 50;
        for ($r = 1; $r <= $lastRow; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(17.5);
        }

        // Explicitly set specific taller rows
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getRowDimension(22)->setRowHeight(26);
        $sheet->getRowDimension($lastItemRow + 40)->setRowHeight(50);
        $grandTotalRow = $lastItemRow + 1;
        $sayInWordsRow = $lastItemRow + 2;

        // Thin border style definition
        $thinBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        // Outline border style definition
        $outlineBorder = [
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        // Apply outline borders to Buyer/Seller and Ship/Invoice cards
        $sheet->getStyle('A4:E14')->applyFromArray($outlineBorder); // THE BUYER
        $sheet->getStyle('F4:J14')->applyFromArray($outlineBorder); // THE SELLER
        $sheet->getStyle('A15:E18')->applyFromArray($outlineBorder); // SHIP TO
        $sheet->getStyle('F15:J18')->applyFromArray($outlineBorder); // INVOICE TO
        $sheet->getStyle('A19:E19')->applyFromArray($outlineBorder); // Port of loading
        $sheet->getStyle('F19:J19')->applyFromArray($outlineBorder); // Port of discharge

        // Apply programmatical RichText to Buyer/Seller/Ship/Invoice headers
        $this->makeRichTextColonCell($sheet, 'A4', true, true, true, false);
        $this->makeRichTextColonCell($sheet, 'F4', true, true, true, true);
        $this->makeRichTextColonCell($sheet, 'A15', true, true, true, true);
        $this->makeRichTextColonCell($sheet, 'F15', true, true, true, false);

        // Apply programmatical RichText to Ports
        $this->makeRichTextColonCell($sheet, 'A19', false, true, true, false);
        $this->makeRichTextColonCell($sheet, 'F19', false, false, true, false);

        // Apply full grid borders to items table including headers, total, and say-in-words rows
        $sheet->getStyle('A22:J' . $sayInWordsRow)->applyFromArray($thinBorder);

        // Header Alignments
        $sheet->getStyle('A22:J22')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A22:J22')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        
        // Items Alignments
        $sheet->getStyle('A23:A' . $lastItemRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C23:E' . $lastItemRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Right alignment for values
        $sheet->getStyle('F23:I' . $lastItemRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('I' . $grandTotalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        // Header font style
        $sheet->getStyle('A22:J22')->getFont()->setBold(true);
        
        // Grand total font style
        $sheet->getStyle('A' . $grandTotalRow . ':J' . $grandTotalRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $sayInWordsRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $sayInWordsRow)->getFont()->setItalic(true);

        // Apply programmatical RichText to Commercial Terms
        for ($r = $lastItemRow + 23; $r <= $lastItemRow + 27; $r++) {
            $this->makeRichTextColonCell($sheet, 'B' . $r, false, false, true, false);
        }

        // Apply programmatical RichText to Taipei Forwarder details
        for ($r = $lastItemRow + 31; $r <= $lastItemRow + 36; $r++) {
            $this->makeRichTextColonCell($sheet, 'B' . $r, false, false, true, false);
        }

        // Ensure CPQ, End-User name, Website/MST, Reseller name, Reseller POS ID (all starting in column D) are left-aligned
        $sheet->getStyle('D' . ($lastItemRow + 5) . ':D' . ($lastItemRow + 20))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

        return [];
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
