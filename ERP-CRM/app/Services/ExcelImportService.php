<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\ProductItem;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;

class ExcelImportService
{
    protected $productItemService;
    protected $transactionService;

    public function __construct(ProductItemService $productItemService, TransactionService $transactionService)
    {
        $this->productItemService = $productItemService;
        $this->transactionService = $transactionService;
    }

    /**
     * Generate Product Excel template
     * Requirements: 6.1, 6.3, 6.5
     * Updated: New format with warehouse column - import directly to warehouse from Excel
     * Columns: STT | Part Number / FRU | Tổng Slg kho vật lý | Slg. Chi tiết | Số Serial | Ngày nhập kho | Kho | Nhà cung cấp | Tên sản phẩm | Danh mục | Đơn vị | Bảo hành (tháng) | Ghi chú
     */
    public function generateProductTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sản Phẩm');

        $headers = ['STT', 'Mã PO', 'Part Number / FRU', 'Tổng Slg kho vật lý', 'Slg. Chi tiết', 'Số Serial', 'Ngày nhập kho', 'Kho', 'Nhà cung cấp', 'Tên sản phẩm', 'Danh mục', 'Đơn vị', 'Bảo hành (tháng)', 'Giá nhập', 'Phí vận chuyển', 'Phí bốc dỡ', 'Phí kiểm định', 'Phí khác', 'Ghi chú'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:S1')->getFont()->setBold(true);
        $sheet->getStyle('A1:S1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:S1')->getFont()->getColor()->setRGB('FFFFFF');

        $examples = [
            [1, 'PO2026-0001', 'ST4000VN006', 2, 1, 'WW67EWKA', '12/3/2025', 'WH0001', 'Công ty Maxlink 2', 'Seagate IronWolf 4TB', 'A', 'Cái', 36, 1200000, 50000, 0, 0, 0, 'Nhập bên Maxlink 2'],
            [2, 'PO2026-0001', 'ST4000VN006', '', 1, 'WW67H60T', '12/3/2025', 'WH0001', 'Công ty Maxlink 2', '', '', '', '', 1200000, 0, 0, 0, 0, ''],
            [3, '', 'XGS2220-30F-US0101F', 4, 1, 'S242L02014561', '12/4/2025', 'Kho Hà Nội', 'Zyxel Vietnam', 'Zyxel XGS2220-30F Switch', 'A', 'Cái', 24, 5000000, 100000, 20000, 0, 0, ''],
            [4, '', 'XGS2220-30F-US0101F', '', 1, 'S242L02014573', '12/4/2025', 'Kho Hà Nội', 'Zyxel Vietnam', '', '', '', '', 5000000, 0, 0, 0, 0, ''],
            [5, 'PO2026-0002', 'XGS2220-30F-US0101F', '', 1, 'S242L02014518', '12/4/2025', 'WH0002', 'Zyxel Vietnam', '', '', '', '', 5000000, 0, 0, 0, 0, ''],
            [6, 'PO2026-0002', 'XGS2220-30F-US0101F', '', 1, 'S242L02014515', '12/4/2025', 'WH0002', 'Zyxel Vietnam', '', '', '', '', 5000000, 0, 0, 0, 0, ''],
            [7, '', 'WAX510D-EU0101F', 28, 1, 'S252L14101325', '12/4/2025', 'WH0001', 'Zyxel Vietnam', 'Zyxel WAX510D Access Point', 'A', 'Cái', 12, 3500000, 0, 0, 0, 0, ''],
            [8, '', 'WAX510D-EU0101F', '', 1, 'S252L14100502', '12/4/2025', 'WH0001', 'Zyxel Vietnam', '', '', '', '', 3500000, 0, 0, 0, 0, ''],
            [9, '', 'WAX510D-EU0101F', '', 1, 'S252L14101273', '12/4/2025', 'Kho Đà Nẵng', 'Zyxel Vietnam', '', '', '', '', 3500000, 0, 0, 0, 0, ''],
            [10, '', 'WAX510D-EU0101F', '', 1, 'S252L14101019', '12/4/2025', 'Kho Đà Nẵng', 'Zyxel Vietnam', '', '', '', '', 3500000, 0, 0, 0, 0, ''],
            [11, '', 'WAX510D-EU0101F', '', 1, 'S252L14101012', '12/4/2025', 'WH0003', 'Zyxel Vietnam', '', '', '', '', 3500000, 0, 0, 0, 0, ''],
            [12, '', 'WAX510D-EU0101F', '', 1, 'S252L14100702', '12/4/2025', 'WH0003', 'Zyxel Vietnam', '', '', '', '', 3500000, 0, 0, 0, 0, ''],
        ];

        $row = 2;
        foreach ($examples as $example) {
            $sheet->fromArray($example, null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'S') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $lastRow = $row - 1;
        $sheet->getStyle("A1:S{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $tempFile = tempnam(sys_get_temp_dir(), 'product_template_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }

    /**
     * Generate Update Serial Excel template
     * Columns: STT | Part Number / FRU | Kho | Số Serial mới | Ghi chú
     */
    public function generateUpdateSerialTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cập nhật Serial');

        $headers = ['STT', 'Part Number / FRU', 'Kho', 'Số Serial mới (S/N)', 'Ghi chú'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('107C41');
        $sheet->getStyle('A1:E1')->getFont()->getColor()->setRGB('FFFFFF');

        $examples = [
            [1, 'ST4000VN006', 'WH0001', "WW67EWKA\nWW67H60T", 'Cập nhật 2 serial cho lô không serial (xuống dòng hoặc phẩy)'],
            [2, 'XGS2220-30F-US0101F', 'Kho Hà Nội', 'S242L02014561', 'Hoặc mỗi dòng 1 serial'],
            [3, 'WAX510D-EU0101F', 'WH0002', 'S252L14101325, S252L14100502', ''],
        ];

        $row = 2;
        foreach ($examples as $example) {
            $sheet->fromArray($example, null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $lastRow = $row - 1;
        $sheet->getStyle("A1:E{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $tempFile = tempnam(sys_get_temp_dir(), 'update_serial_template_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }

    /**
     * Generate PO Serial Import template
     */
    public function generatePoSerialTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Mẫu Nhập Serial PO');

        // Headers expected by findExcelHeaderIndexes: PO Code, Part Number, Serial Number
        $headers = ['Mã PO', 'Part Number', 'Số Serial (S/N)'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->getStyle('A1:C1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:C1')->getFont()->getColor()->setRGB('FFFFFF');

        $examples = [
            ['PO2026-0001', 'ST4000VN006', 'WW67EWKA'],
            ['PO2026-0001', 'ST4000VN006', 'WW67H60T'],
            ['PO2026-0002', 'XGS2220-30F-US0101F', 'S242L02014561'],
        ];

        $row = 2;
        foreach ($examples as $example) {
            $sheet->fromArray($example, null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $lastRow = $row - 1;
        $sheet->getStyle("A1:C{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $tempFile = tempnam(sys_get_temp_dir(), 'po_serial_template_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }

    /**
     * Generate Inventory Excel template
     * Requirements: 6.2, 6.4, 6.5
     * Updated: New format per customer request
     */
    public function generateInventoryTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Nhập Kho');

        $headers = ['stt', 'ma_po', 'part_number_fru', 'tong_slg_kho_vat_ly', 'slg_chi_tiet', 'so_serial', 'ngay_nhap_kho', 'kho', 'gia_von_usd', 'bang_gia_json', 'ghi_chu'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A1:K1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:K1')->getFont()->getColor()->setRGB('FFFFFF');

        $examples = [
            [1, 'PO2026-0001', 'ST4000VN006', 2, 1, 'WW67EWKA', '12/3/2025', 'WH01', 80.00, '', 'Nhập bên Maxlink 2'],
            [2, 'PO2026-0001', 'ST4000VN006', '', 1, 'WW67H60T', '12/3/2025', 'WH01', 80.00, '', ''],
            [3, '', 'XGS2220-30F-US0101F', 4, 1, 'S242L02014561', '12/4/2025', 'WH01', 250.00, '[{"name":"1yr","price":300}]', ''],
            [4, '', 'XGS2220-30F-US0101F', '', 1, 'S242L02014573', '12/4/2025', 'WH01', 250.00, '[{"name":"1yr","price":300}]', ''],
            [5, '', 'XGS2220-30F-US0101F', '', 1, 'S242L02014518', '12/4/2025', 'WH01', 250.00, '', ''],
            [6, '', 'XGS2220-30F-US0101F', '', 1, 'S242L02014515', '12/4/2025', 'WH01', 250.00, '', ''],
            [7, '', 'WAX510D-EU0101F', 28, 1, 'S252L14101325', '12/4/2025', 'WH01', 150.00, '', ''],
            [8, '', 'WAX510D-EU0101F', '', 1, 'S252L14100502', '12/4/2025', 'WH01', 150.00, '', ''],
            [9, '', 'WAX510D-EU0101F', '', 1, 'S252L14101273', '12/4/2025', 'WH01', 150.00, '', ''],
        ];

        $row = 2;
        foreach ($examples as $example) {
            $sheet->fromArray($example, null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $lastRow = $row - 1;
        $sheet->getStyle("A1:K{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $tempFile = tempnam(sys_get_temp_dir(), 'inventory_template_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }

    /**
     * Import products from Excel file and create inventory
     * Updated: Import sản phẩm + nhập kho với cột Kho trong Excel (mã kho hoặc tên kho)
     * Columns: STT | Part Number / FRU | Tổng Slg kho vật lý | Slg. Chi tiết | Số Serial | Ngày nhập kho | Kho | Tên sản phẩm | Danh mục | Đơn vị | Bảo hành (tháng) | Ghi chú
     */
    /**
     * Detect column index mapping from headers for Product Import
     */
    protected function detectProductImportColumns(array $headers): array
    {
        $map = [
            'stt' => null,
            'po_code' => null,
            'product_code' => null,
            'total_qty' => null,
            'detail_qty' => null,
            'serial' => null,
            'date' => null,
            'warehouse' => null,
            'supplier' => null,
            'product_name' => null,
            'category' => null,
            'unit' => null,
            'warranty_months' => null,
            'cost' => null,
            'shipping_cost' => null,
            'loading_cost' => null,
            'inspection_cost' => null,
            'other_cost' => null,
            'note' => null,
        ];

        foreach ($headers as $idx => $header) {
            $h = mb_strtolower(trim((string)$header), 'UTF-8');
            $norm = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $h);
            $norm = trim($norm);

            if (in_array($norm, ['stt', 'no', 'index'])) {
                $map['stt'] = $idx;
            } elseif (in_array($norm, ['ma po', 'mã po', 'po', 'ma don hang po', 'mã đơn hàng po', 'purchase order', 'don mua hang'])) {
                $map['po_code'] = $idx;
            } elseif (str_contains($norm, 'part number') || str_contains($norm, 'fru') || $norm === 'ma sp' || $norm === 'mã sp' || $norm === 'ma san pham' || $norm === 'mã sản phẩm' || $norm === 'code') {
                $map['product_code'] = $idx;
            } elseif (str_contains($norm, 'tong slg') || str_contains($norm, 'tổng slg') || str_contains($norm, 'tong so luong') || str_contains($norm, 'tổng số lượng')) {
                $map['total_qty'] = $idx;
            } elseif (str_contains($norm, 'chi tiet') || str_contains($norm, 'chi tiết') || $norm === 'slg' || $norm === 'so luong' || $norm === 'số lượng' || $norm === 'quantity' || $norm === 'qty') {
                $map['detail_qty'] = $idx;
            } elseif (str_contains($norm, 'serial') || str_contains($norm, 's n') || $norm === 'sn' || $norm === 'sku') {
                $map['serial'] = $idx;
            } elseif (str_contains($norm, 'ngay') || str_contains($norm, 'ngày') || $norm === 'date') {
                $map['date'] = $idx;
            } elseif ($norm === 'kho' || str_contains($norm, 'kho nhap') || str_contains($norm, 'kho nhập') || $norm === 'warehouse') {
                $map['warehouse'] = $idx;
            } elseif (str_contains($norm, 'nha cung cap') || str_contains($norm, 'nhà cung cấp') || $norm === 'ncc' || $norm === 'supplier' || str_contains($norm, 'hang') || str_contains($norm, 'hãng')) {
                $map['supplier'] = $idx;
            } elseif (str_contains($norm, 'ten san pham') || str_contains($norm, 'tên sản phẩm') || str_contains($norm, 'ten sp') || str_contains($norm, 'tên sp') || $norm === 'name') {
                $map['product_name'] = $idx;
            } elseif (str_contains($norm, 'danh muc') || str_contains($norm, 'danh mục') || $norm === 'category') {
                $map['category'] = $idx;
            } elseif (str_contains($norm, 'don vi') || str_contains($norm, 'đơn vị') || $norm === 'unit') {
                $map['unit'] = $idx;
            } elseif (str_contains($norm, 'bao hanh') || str_contains($norm, 'bảo hành') || str_contains($norm, 'warranty')) {
                $map['warranty_months'] = $idx;
            } elseif (str_contains($norm, 'gia nhap') || str_contains($norm, 'giá nhập') || str_contains($norm, 'gia von') || str_contains($norm, 'giá vốn') || $norm === 'cost' || $norm === 'price') {
                $map['cost'] = $idx;
            } elseif (str_contains($norm, 'van chuyen') || str_contains($norm, 'vận chuyển') || str_contains($norm, 'shipping')) {
                $map['shipping_cost'] = $idx;
            } elseif (str_contains($norm, 'boc do') || str_contains($norm, 'bốc dỡ') || str_contains($norm, 'boc xep') || str_contains($norm, 'bốc xếp') || str_contains($norm, 'loading')) {
                $map['loading_cost'] = $idx;
            } elseif (str_contains($norm, 'kiem dinh') || str_contains($norm, 'kiểm định') || str_contains($norm, 'inspection')) {
                $map['inspection_cost'] = $idx;
            } elseif (str_contains($norm, 'phi khac') || str_contains($norm, 'phí khác') || str_contains($norm, 'other')) {
                $map['other_cost'] = $idx;
            } elseif (str_contains($norm, 'ghi chu') || str_contains($norm, 'ghi chú') || $norm === 'note' || $norm === 'comment') {
                $map['note'] = $idx;
            }
        }

        // Fallbacks if not detected from header text
        $hasPoHeader = $map['po_code'] !== null;
        $offset = $hasPoHeader ? 1 : 0;

        if ($map['product_code'] === null) $map['product_code'] = 1 + $offset;
        if ($map['total_qty'] === null) $map['total_qty'] = 2 + $offset;
        if ($map['detail_qty'] === null) $map['detail_qty'] = 3 + $offset;
        if ($map['serial'] === null) $map['serial'] = 4 + $offset;
        if ($map['date'] === null) $map['date'] = 5 + $offset;
        if ($map['warehouse'] === null) $map['warehouse'] = 6 + $offset;
        if ($map['supplier'] === null) $map['supplier'] = 7 + $offset;
        if ($map['product_name'] === null) $map['product_name'] = 8 + $offset;
        if ($map['category'] === null) $map['category'] = 9 + $offset;
        if ($map['unit'] === null) $map['unit'] = 10 + $offset;
        if ($map['warranty_months'] === null) $map['warranty_months'] = 11 + $offset;
        if ($map['cost'] === null) $map['cost'] = 12 + $offset;
        if ($map['shipping_cost'] === null) $map['shipping_cost'] = 13 + $offset;
        if ($map['loading_cost'] === null) $map['loading_cost'] = 14 + $offset;
        if ($map['inspection_cost'] === null) $map['inspection_cost'] = 15 + $offset;
        if ($map['other_cost'] === null) $map['other_cost'] = 16 + $offset;
        if ($map['note'] === null) $map['note'] = 17 + $offset;

        return $map;
    }

    /**
     * Detect column index mapping from headers for Inventory Import
     */
    protected function detectInventoryImportColumns(array $headers): array
    {
        $map = [
            'stt' => null,
            'po_code' => null,
            'product_code' => null,
            'total_qty' => null,
            'detail_qty' => null,
            'serial' => null,
            'date' => null,
            'warehouse' => null,
            'cost_usd' => null,
            'price_tiers' => null,
            'comments' => null,
        ];

        foreach ($headers as $idx => $header) {
            $h = mb_strtolower(trim((string)$header), 'UTF-8');
            $norm = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $h);
            $norm = trim($norm);

            if (in_array($norm, ['stt', 'no', 'index'])) {
                $map['stt'] = $idx;
            } elseif (in_array($norm, ['ma po', 'mã po', 'po', 'ma don hang po', 'mã đơn hàng po', 'purchase order'])) {
                $map['po_code'] = $idx;
            } elseif (str_contains($norm, 'part number') || str_contains($norm, 'fru') || $norm === 'ma sp' || $norm === 'code') {
                $map['product_code'] = $idx;
            } elseif (str_contains($norm, 'tong slg') || str_contains($norm, 'tổng slg')) {
                $map['total_qty'] = $idx;
            } elseif (str_contains($norm, 'chi tiet') || str_contains($norm, 'chi tiết') || $norm === 'slg' || $norm === 'quantity') {
                $map['detail_qty'] = $idx;
            } elseif (str_contains($norm, 'serial') || str_contains($norm, 'sku')) {
                $map['serial'] = $idx;
            } elseif (str_contains($norm, 'ngay') || str_contains($norm, 'date')) {
                $map['date'] = $idx;
            } elseif ($norm === 'kho' || str_contains($norm, 'warehouse')) {
                $map['warehouse'] = $idx;
            } elseif (str_contains($norm, 'gia von') || str_contains($norm, 'giá vốn') || str_contains($norm, 'cost')) {
                $map['cost_usd'] = $idx;
            } elseif (str_contains($norm, 'bang gia') || str_contains($norm, 'bảng giá') || str_contains($norm, 'price tiers')) {
                $map['price_tiers'] = $idx;
            } elseif (str_contains($norm, 'ghi chu') || str_contains($norm, 'ghi chú') || str_contains($norm, 'comment')) {
                $map['comments'] = $idx;
            }
        }

        $hasPoHeader = $map['po_code'] !== null;
        $offset = $hasPoHeader ? 1 : 0;

        if ($map['product_code'] === null) $map['product_code'] = 1 + $offset;
        if ($map['total_qty'] === null) $map['total_qty'] = 2 + $offset;
        if ($map['detail_qty'] === null) $map['detail_qty'] = 3 + $offset;
        if ($map['serial'] === null) $map['serial'] = 4 + $offset;
        if ($map['date'] === null) $map['date'] = 5 + $offset;
        if ($map['warehouse'] === null) $map['warehouse'] = 6 + $offset;
        if ($map['cost_usd'] === null) $map['cost_usd'] = 7 + $offset;
        if ($map['price_tiers'] === null) $map['price_tiers'] = 8 + $offset;
        if ($map['comments'] === null) $map['comments'] = 9 + $offset;

        return $map;
    }

    /**
     * Import products from Excel file and create inventory
     * Updated: Import sản phẩm + nhập kho với cột Kho trong Excel (mã kho hoặc tên kho) và cột Mã PO tùy chọn
     * Columns: STT | Mã PO (tùy chọn) | Part Number / FRU | Tổng Slg kho vật lý | Slg. Chi tiết | Số Serial | Ngày nhập kho | Kho | Nhà cung cấp | Tên sản phẩm | Danh mục | Đơn vị | Bảo hành (tháng) | Giá nhập | Phí vận chuyển | Phí bốc dỡ | Phí kiểm định | Phí khác | Ghi chú
     */
    public function importProducts($filePath, $warehouseId = null): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, false, false);
        
        // Remove header row
        $headers = array_shift($rows);
        $cols = $this->detectProductImportColumns($headers ?? []);
        
        $productsCreated = 0;
        $suppliersCreated = 0;
        $itemsImported = 0;
        $errors = [];
        $productCache = []; // Cache products to avoid repeated queries
        $warehouseCache = []; // Cache warehouses to avoid repeated queries
        $supplierCache = []; // Cache suppliers to avoid repeated queries
        $poCache = []; // Cache purchase orders to avoid repeated queries
        
        // Pre-load all active warehouses for lookup
        $allWarehouses = Warehouse::active()->get();
        foreach ($allWarehouses as $wh) {
            $warehouseCache[strtolower($wh->code)] = $wh;
            $warehouseCache[strtolower($wh->name)] = $wh;
        }
        
        // Pre-load all suppliers for lookup
        $allSuppliers = \App\Models\Supplier::all();
        foreach ($allSuppliers as $sup) {
            $supplierCache[strtolower($sup->code ?? '')] = $sup;
            $supplierCache[strtolower($sup->name)] = $sup;
        }

        // Pre-load all purchase orders for lookup
        $allPurchaseOrders = \App\Models\PurchaseOrder::with('supplier')->get();
        foreach ($allPurchaseOrders as $po) {
            $poCache[strtolower(trim($po->code))] = $po;
        }
        
        // Fallback warehouse if provided (for backward compatibility)
        $fallbackWarehouse = null;
        if ($warehouseId) {
            $fallbackWarehouse = Warehouse::find($warehouseId);
        }
        
        DB::beginTransaction();
        try {
            // Group items by warehouse, supplier, and PO for creating import transactions
            $groupedItems = [];
            
            $lastPoCode = '';
            $lastWarehouseInput = '';
            $lastSupplierInput = '';
            $lastDateRaw = date('Y-m-d');
            
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                $poCode = $cols['po_code'] !== null ? trim((string)($row[$cols['po_code']] ?? '')) : '';
                if (empty($poCode) && !empty($lastPoCode)) {
                    $poCode = $lastPoCode;
                } elseif (!empty($poCode)) {
                    $lastPoCode = $poCode;
                }

                $productCode = trim((string)($row[$cols['product_code']] ?? ''));
                $quantity = $cols['detail_qty'] !== null ? ($row[$cols['detail_qty']] ?? 1) : 1;
                $serialRaw = $cols['serial'] !== null ? trim((string)($row[$cols['serial']] ?? '')) : '';
                
                $dateRaw = $cols['date'] !== null ? ($row[$cols['date']] ?? null) : null;
                if (empty($dateRaw) && !empty($lastDateRaw)) {
                    $dateRaw = $lastDateRaw;
                } elseif (!empty($dateRaw)) {
                    $lastDateRaw = $dateRaw;
                } else {
                    $dateRaw = date('Y-m-d');
                }

                $warehouseInput = $cols['warehouse'] !== null ? trim((string)($row[$cols['warehouse']] ?? '')) : '';
                if (empty($warehouseInput) && !empty($lastWarehouseInput)) {
                    $warehouseInput = $lastWarehouseInput;
                } elseif (!empty($warehouseInput)) {
                    $lastWarehouseInput = $warehouseInput;
                }

                $supplierInput = $cols['supplier'] !== null ? trim((string)($row[$cols['supplier']] ?? '')) : '';
                if (empty($supplierInput) && !empty($lastSupplierInput)) {
                    $supplierInput = $lastSupplierInput;
                } elseif (!empty($supplierInput)) {
                    $lastSupplierInput = $supplierInput;
                }

                $productName = $cols['product_name'] !== null ? trim((string)($row[$cols['product_name']] ?? '')) : '';
                $category = $cols['category'] !== null ? strtoupper(trim((string)($row[$cols['category']] ?? 'A'))) : 'A';
                $unit = $cols['unit'] !== null ? trim((string)($row[$cols['unit']] ?? 'Cái')) : 'Cái';
                $warrantyMonths = $cols['warranty_months'] !== null ? ($row[$cols['warranty_months']] ?? null) : null;
                $cost = $cols['cost'] !== null && isset($row[$cols['cost']]) && is_numeric($row[$cols['cost']]) ? (float)$row[$cols['cost']] : 0;
                $shippingCost = $cols['shipping_cost'] !== null && isset($row[$cols['shipping_cost']]) && is_numeric($row[$cols['shipping_cost']]) ? (float)$row[$cols['shipping_cost']] : 0;
                $loadingCost = $cols['loading_cost'] !== null && isset($row[$cols['loading_cost']]) && is_numeric($row[$cols['loading_cost']]) ? (float)$row[$cols['loading_cost']] : 0;
                $inspectionCost = $cols['inspection_cost'] !== null && isset($row[$cols['inspection_cost']]) && is_numeric($row[$cols['inspection_cost']]) ? (float)$row[$cols['inspection_cost']] : 0;
                $otherCost = $cols['other_cost'] !== null && isset($row[$cols['other_cost']]) && is_numeric($row[$cols['other_cost']]) ? (float)$row[$cols['other_cost']] : 0;
                $note = $cols['note'] !== null ? trim((string)($row[$cols['note']] ?? '')) : '';
                
                // Skip if no product code
                if (empty($productCode)) {
                    continue;
                }
                
                // Parse serials (optional: single serial, multiple serials separated by newline/comma/semicolon, or empty)
                $serialsInRow = [];
                if (!empty($serialRaw)) {
                    $serialsInRow = array_values(array_filter(
                        array_map('trim', preg_split('/[\r\n,;]+/', $serialRaw)),
                        fn($s) => $s !== ''
                    ));
                }
                
                // Parse date
                $importDate = $this->parseDate($dateRaw);
                if (!$importDate) {
                    $errors[] = "Dòng {$rowNumber}: Ngày nhập kho không hợp lệ '{$dateRaw}'";
                    continue;
                }
                
                // Validate warehouse (from Excel column or fallback)
                $warehouse = null;
                if (!empty($warehouseInput)) {
                    $warehouseKey = strtolower($warehouseInput);
                    if (isset($warehouseCache[$warehouseKey])) {
                        $warehouse = $warehouseCache[$warehouseKey];
                    } else {
                        $errors[] = "Dòng {$rowNumber}: Kho '{$warehouseInput}' không tồn tại (nhập mã kho hoặc tên kho)";
                        continue;
                    }
                } elseif ($fallbackWarehouse) {
                    $warehouse = $fallbackWarehouse;
                } else {
                    $errors[] = "Dòng {$rowNumber}: Thiếu thông tin kho";
                    continue;
                }

                // Resolve PO (optional)
                $po = null;
                if (!empty($poCode)) {
                    $poKey = strtolower($poCode);
                    if (isset($poCache[$poKey])) {
                        $po = $poCache[$poKey];
                    } else {
                        $foundPo = \App\Models\PurchaseOrder::with('supplier')->whereRaw('LOWER(code) = ?', [$poKey])->first();
                        $poCache[$poKey] = $foundPo;
                        $po = $foundPo;
                    }
                }
                
                // Validate supplier (optional, auto-create if not exists, or default from PO)
                $supplier = null;
                if (!empty($supplierInput)) {
                    $supplierKey = strtolower($supplierInput);
                    if (isset($supplierCache[$supplierKey])) {
                        $supplier = $supplierCache[$supplierKey];
                    } else {
                        // Auto-create supplier if not exists
                        $supplier = \App\Models\Supplier::create([
                            'code' => 'SUP' . str_pad(\App\Models\Supplier::count() + 1, 4, '0', STR_PAD_LEFT),
                            'name' => $supplierInput,
                            'email' => '',
                            'phone' => '',
                        ]);
                        $supplierCache[$supplierKey] = $supplier;
                        $supplierCache[strtolower($supplier->code)] = $supplier;
                        $suppliersCreated++;
                    }
                } elseif ($po && $po->supplier) {
                    $supplier = $po->supplier;
                }
                
                // Parse warranty months (optional)
                $warrantyMonthsValue = null;
                if (!empty($warrantyMonths) && is_numeric($warrantyMonths)) {
                    $warrantyMonthsValue = (int) $warrantyMonths;
                    if ($warrantyMonthsValue < 0 || $warrantyMonthsValue > 120) {
                        $warrantyMonthsValue = null;
                    }
                }

                // Get or create product
                if (!isset($productCache[$productCode])) {
                    $product = Product::where('code', $productCode)->first();
                    
                    if (!$product) {
                        // Create new product
                        if (empty($productName)) {
                            $productName = $productCode;
                        }
                        if (!preg_match('/^[A-Z]$/', $category)) {
                            $category = 'A';
                        }
                        if (empty($unit)) {
                            $unit = 'Cái';
                        }
                        
                        $product = Product::create([
                            'code' => $productCode,
                            'name' => $productName,
                            'brand' => $supplier ? $supplier->name : null,
                            'category' => $category,
                            'unit' => $unit,
                            'warranty_months' => $warrantyMonthsValue,
                            'description' => null,
                            'note' => null,
                        ]);
                        $productsCreated++;
                    } else {
                        // Update brand if product doesn't have one
                        if ($supplier && empty($product->brand)) {
                            $product->update(['brand' => $supplier->name]);
                        }
                        // Update warranty_months if provided and product doesn't have one
                        if ($warrantyMonthsValue !== null && empty($product->warranty_months)) {
                            $product->update(['warranty_months' => $warrantyMonthsValue]);
                        }
                    }
                    
                    $productCache[$productCode] = $product;
                }
                
                $product = $productCache[$productCode];
                
                // Group by warehouse, supplier, and PO (do not split by date or product)
                $poIdentifier = $po ? 'po_' . $po->id : (!empty($poCode) ? 'pocode_' . strtolower($poCode) : 'no_po');
                $supplierIdentifier = $supplier ? 'sup_' . $supplier->id : (!empty($supplierInput) ? 'supname_' . strtolower($supplierInput) : 'no_sup');
                $warehouseIdentifier = 'wh_' . $warehouse->id;

                $groupKey = $warehouseIdentifier . '__' . $supplierIdentifier . '__' . $poIdentifier;
                if (!isset($groupedItems[$groupKey])) {
                    $groupedItems[$groupKey] = [
                        'date' => $importDate,
                        'warehouse_id' => $warehouse->id,
                        'supplier_id' => $supplier ? $supplier->id : null,
                        'purchase_order' => $po,
                        'po_code_text' => $poCode,
                        'shipping_cost' => 0,
                        'loading_cost' => 0,
                        'inspection_cost' => 0,
                        'other_cost' => 0,
                        'notes' => [],
                        'items' => [],
                    ];
                }
                
                if (!empty($note)) {
                    $groupedItems[$groupKey]['notes'][] = $note;
                }
                
                // Aggregate max cost for fees per group (assuming user might enter them on one row or repeat them)
                $groupedItems[$groupKey]['shipping_cost'] = max($groupedItems[$groupKey]['shipping_cost'], $shippingCost);
                $groupedItems[$groupKey]['loading_cost'] = max($groupedItems[$groupKey]['loading_cost'], $loadingCost);
                $groupedItems[$groupKey]['inspection_cost'] = max($groupedItems[$groupKey]['inspection_cost'], $inspectionCost);
                $groupedItems[$groupKey]['other_cost'] = max($groupedItems[$groupKey]['other_cost'], $otherCost);
                
                $qtyInput = null;
                $detailQtyVal = $cols['detail_qty'] !== null ? ($row[$cols['detail_qty']] ?? null) : null;
                $totalQtyVal = $cols['total_qty'] !== null ? ($row[$cols['total_qty']] ?? null) : null;
                if ($detailQtyVal !== null && is_numeric($detailQtyVal) && (int)$detailQtyVal > 0) {
                    $qtyInput = (int)$detailQtyVal;
                } elseif ($totalQtyVal !== null && is_numeric($totalQtyVal) && (int)$totalQtyVal > 0) {
                    $qtyInput = (int)$totalQtyVal;
                }

                $totalQty = max($qtyInput ?? count($serialsInRow), count($serialsInRow));
                if ($totalQty <= 0) {
                    $totalQty = 1;
                }

                if (!empty($serialsInRow)) {
                    foreach ($serialsInRow as $sn) {
                        $groupedItems[$groupKey]['items'][] = [
                            'product_id' => $product->id,
                            'product_code' => $productCode,
                            'quantity' => 1,
                            'cost' => $cost,
                            'serial' => $sn,
                            'note' => $note,
                            'row_number' => $rowNumber,
                        ];
                    }

                    $remainingQty = $totalQty - count($serialsInRow);
                    if ($remainingQty > 0) {
                        $groupedItems[$groupKey]['items'][] = [
                            'product_id' => $product->id,
                            'product_code' => $productCode,
                            'quantity' => $remainingQty,
                            'cost' => $cost,
                            'serial' => null,
                            'note' => $note,
                            'row_number' => $rowNumber,
                        ];
                    }
                } else {
                    // No serial provided - use specified quantity
                    $groupedItems[$groupKey]['items'][] = [
                        'product_id' => $product->id,
                        'product_code' => $productCode,
                        'quantity' => $totalQty,
                        'cost' => $cost,
                        'serial' => null,
                        'note' => $note,
                        'row_number' => $rowNumber,
                    ];
                }
            }
            
            if (!empty($errors)) {
                DB::rollBack();
                return ['success' => false, 'imported' => 0, 'errors' => $errors];
            }

            // Check for duplicate serials in database and within the file
            $allSerials = [];
            $duplicatesInFile = [];
            $duplicatesInDb = [];

            foreach ($groupedItems as $groupKey => $group) {
                foreach ($group['items'] as $item) {
                    if (empty($item['serial'])) {
                        continue;
                    }
                    $key = "{$item['product_id']}:{$item['serial']}";
                    
                    // Check duplicate within file
                    if (isset($allSerials[$key])) {
                        $duplicatesInFile[] = "Dòng {$item['row_number']}: Serial '{$item['serial']}' bị trùng với dòng {$allSerials[$key]}";
                    } else {
                        $allSerials[$key] = $item['row_number'];
                    }
                }
            }

            // Check duplicates in database (batch check for performance)
            $serialsByProduct = [];
            foreach ($groupedItems as $groupKey => $group) {
                foreach ($group['items'] as $item) {
                    if (empty($item['serial'])) {
                        continue;
                    }
                    if (!isset($serialsByProduct[$item['product_id']])) {
                        $serialsByProduct[$item['product_id']] = [
                            'serials' => [],
                            'product_code' => $item['product_code'],
                        ];
                    }
                    $serialsByProduct[$item['product_id']]['serials'][] = $item['serial'];
                }
            }

            foreach ($serialsByProduct as $productId => $data) {
                $existingSerials = ProductItem::where('product_id', $productId)
                    ->whereIn('sku', $data['serials'])
                    ->pluck('sku')
                    ->toArray();
                
                if (!empty($existingSerials)) {
                    foreach ($existingSerials as $existingSerial) {
                        $duplicatesInDb[] = "Serial '{$existingSerial}' đã tồn tại trong hệ thống cho sản phẩm '{$data['product_code']}'";
                    }
                }
            }

            if (!empty($duplicatesInFile) || !empty($duplicatesInDb)) {
                DB::rollBack();
                $allDuplicateErrors = array_merge($duplicatesInFile, $duplicatesInDb);
                return ['success' => false, 'imported' => 0, 'errors' => $allDuplicateErrors];
            }
            
            // Create import transactions for each group
            foreach ($groupedItems as $groupKey => $group) {
                $distinctNotes = array_values(array_filter(array_unique($group['notes'] ?? [])));
                $groupNote = !empty($distinctNotes) ? implode('; ', $distinctNotes) : null;

                $transactionData = [
                    'warehouse_id' => $group['warehouse_id'],
                    'supplier_id' => $group['supplier_id'],
                    'date' => $group['date'],
                    'shipping_cost' => $group['shipping_cost'],
                    'loading_cost' => $group['loading_cost'],
                    'inspection_cost' => $group['inspection_cost'],
                    'other_cost' => $group['other_cost'],
                    'total_service_cost' => $group['shipping_cost'] + $group['loading_cost'] + $group['inspection_cost'] + $group['other_cost'],
                    'note' => $groupNote ?: 'Import từ Excel',
                    'po_code' => $group['po_code_text'] ?: ($group['purchase_order'] ? $group['purchase_order']->code : null),
                    'reference_type' => $group['purchase_order'] ? 'purchase_order' : null,
                    'reference_id' => $group['purchase_order'] ? $group['purchase_order']->id : null,
                    'items' => [],
                ];
                
                // Group items by product_id to merge serials
                $productItems = [];
                foreach ($group['items'] as $item) {
                    $productId = $item['product_id'];
                    if (!isset($productItems[$productId])) {
                        $productItems[$productId] = [
                            'product_id' => $productId,
                            'quantity' => 0,
                            'cost' => $item['cost'],
                            'skus' => [],
                            'comments' => [],
                        ];
                    }
                    $productItems[$productId]['quantity'] += $item['quantity'];
                    if (!empty($item['serial'])) {
                        $productItems[$productId]['skus'][] = $item['serial'];
                    }
                    if (!empty($item['note'])) {
                        $productItems[$productId]['comments'][] = $item['note'];
                    }
                    $itemsImported += $item['quantity'];
                }
                
                // Build transaction items from grouped products
                foreach ($productItems as $productItem) {
                    $transactionData['items'][] = [
                        'product_id' => $productItem['product_id'],
                        'warehouse_id' => $group['warehouse_id'],
                        'quantity' => $productItem['quantity'],
                        'serials' => $productItem['skus'],
                        'cost' => $productItem['cost'],
                        'comments' => !empty($productItem['comments']) ? implode('; ', array_unique($productItem['comments'])) : null,
                    ];
                }
                
                $this->transactionService->processImport($transactionData);
            }
            
            DB::commit();
            
            $message = "Đã import thành công: {$itemsImported} sản phẩm vào kho";
            if ($productsCreated > 0) {
                $message .= " (tạo mới {$productsCreated} mã sản phẩm)";
            }
            if ($suppliersCreated > 0) {
                $message .= " (tạo mới {$suppliersCreated} nhà cung cấp)";
            }
            
            return [
                'success' => true, 
                'imported' => $itemsImported,
                'products_created' => $productsCreated,
                'suppliers_created' => $suppliersCreated,
                'errors' => [],
                'message' => $message
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            return ['success' => false, 'imported' => 0, 'errors' => ['Exception: ' . $e->getMessage()]];
        }
    }

    /**
     * Import inventory from Excel file
     * Requirements: 3.3, 3.4, 3.5, 3.7, 7.3, 7.4, 7.5
     * Updated: New format per customer request with optional PO column
     * Columns: STT | Mã PO (tùy chọn) | Part Number / FRU | Tổng Slg kho vật lý | Slg. Chi tiết | Số Serial | Ngày nhập kho | Kho | Giá vốn (USD) | Bảng giá JSON | Ghi chú
     */
    public function importInventory($filePath, $warehouseId = null): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, false, false);
        
        // Remove header row
        $headers = array_shift($rows);
        $cols = $this->detectInventoryImportColumns($headers ?? []);
        
        $imported = 0;
        $errors = [];
        $poCache = [];
        $warehouseCache = [];
        
        // Pre-load all warehouses for lookup
        $allWarehouses = Warehouse::active()->get();
        foreach ($allWarehouses as $wh) {
            $warehouseCache[strtolower($wh->code)] = $wh;
            $warehouseCache[strtolower($wh->name)] = $wh;
        }

        // Fallback warehouse if provided
        $fallbackWarehouse = null;
        if ($warehouseId) {
            $fallbackWarehouse = Warehouse::find($warehouseId);
        }

        // Pre-load all purchase orders for lookup
        $allPurchaseOrders = \App\Models\PurchaseOrder::with('supplier')->get();
        foreach ($allPurchaseOrders as $po) {
            $poCache[strtolower(trim($po->code))] = $po;
        }
        
        DB::beginTransaction();
        try {
            // Group rows by warehouse and PO
            $groupedRows = [];
            $lastPoCode = '';
            $lastWarehouseCode = '';
            $lastDateRaw = date('Y-m-d');

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                $poCode = $cols['po_code'] !== null ? trim((string)($row[$cols['po_code']] ?? '')) : '';
                if (empty($poCode) && !empty($lastPoCode)) {
                    $poCode = $lastPoCode;
                } elseif (!empty($poCode)) {
                    $lastPoCode = $poCode;
                }

                $productCode = trim((string)($row[$cols['product_code']] ?? ''));
                $quantity = $cols['detail_qty'] !== null ? ($row[$cols['detail_qty']] ?? 1) : 1;
                $serial = $cols['serial'] !== null ? trim((string)($row[$cols['serial']] ?? '')) : '';
                
                $transactionDateRaw = $cols['date'] !== null ? ($row[$cols['date']] ?? null) : null;
                if (empty($transactionDateRaw) && !empty($lastDateRaw)) {
                    $transactionDateRaw = $lastDateRaw;
                } elseif (!empty($transactionDateRaw)) {
                    $lastDateRaw = $transactionDateRaw;
                } else {
                    $transactionDateRaw = date('Y-m-d');
                }

                $warehouseCode = $cols['warehouse'] !== null ? trim((string)($row[$cols['warehouse']] ?? '')) : '';
                if (empty($warehouseCode) && !empty($lastWarehouseCode)) {
                    $warehouseCode = $lastWarehouseCode;
                } elseif (!empty($warehouseCode)) {
                    $lastWarehouseCode = $warehouseCode;
                }

                $costUsd = $cols['cost_usd'] !== null && isset($row[$cols['cost_usd']]) ? $row[$cols['cost_usd']] : 0;
                $priceTiersJson = $cols['price_tiers'] !== null && isset($row[$cols['price_tiers']]) ? $row[$cols['price_tiers']] : '[]';
                $comments = $cols['comments'] !== null ? trim((string)($row[$cols['comments']] ?? '')) : '';
                
                // Skip if no product code
                if (empty($productCode)) {
                    continue;
                }
                
                // Parse date (support DD/MM/YYYY and YYYY-MM-DD)
                $transactionDate = $this->parseDate($transactionDateRaw);
                if (!$transactionDate) {
                    $errors[] = "Dòng {$rowNumber}: Ngày nhập kho không hợp lệ '{$transactionDateRaw}'";
                    continue;
                }
                
                // Validate product exists
                $product = Product::where('code', $productCode)->first();
                if (!$product) {
                    $errors[] = "Dòng {$rowNumber}: Mã sản phẩm '{$productCode}' không tồn tại";
                    continue;
                }
                
                // Validate warehouse exists
                $warehouse = null;
                if (!empty($warehouseCode)) {
                    $whKey = strtolower($warehouseCode);
                    if (isset($warehouseCache[$whKey])) {
                        $warehouse = $warehouseCache[$whKey];
                    } else {
                        $errors[] = "Dòng {$rowNumber}: Mã kho '{$warehouseCode}' không tồn tại";
                        continue;
                    }
                } elseif ($fallbackWarehouse) {
                    $warehouse = $fallbackWarehouse;
                } else {
                    $errors[] = "Dòng {$rowNumber}: Thiếu mã kho";
                    continue;
                }

                // Resolve PO (optional)
                $po = null;
                if (!empty($poCode)) {
                    $poKey = strtolower($poCode);
                    if (isset($poCache[$poKey])) {
                        $po = $poCache[$poKey];
                    } else {
                        $foundPo = \App\Models\PurchaseOrder::with('supplier')->whereRaw('LOWER(code) = ?', [$poKey])->first();
                        $poCache[$poKey] = $foundPo;
                        $po = $foundPo;
                    }
                }
                
                // Validate quantity (default to 1)
                $quantity = is_numeric($quantity) && $quantity > 0 ? (int)$quantity : 1;
                
                // Validate serial (optional: single or multiple serials separated by newline, comma, semicolon)
                $serialsInRow = [];
                if (!empty($serial)) {
                    $serialsInRow = array_values(array_filter(
                        array_map('trim', preg_split('/[\r\n,;]+/', $serial)),
                        fn($s) => $s !== ''
                    ));
                }
                
                // Validate and parse price_tiers JSON
                $priceTiers = $this->parsePriceTiers($priceTiersJson);
                if ($priceTiers === false) {
                    $errors[] = "Dòng {$rowNumber}: JSON bảng giá không hợp lệ";
                    continue;
                }
                
                // Group by warehouse and PO (not splitting by part number or row date)
                $poIdentifier = $po ? 'po_' . $po->id : (!empty($poCode) ? 'pocode_' . strtolower($poCode) : 'no_po');
                $key = 'wh_' . $warehouse->id . '__' . $poIdentifier;

                if (!isset($groupedRows[$key])) {
                    $groupedRows[$key] = [
                        'warehouse_id' => $warehouse->id,
                        'supplier_id' => $po && $po->supplier_id ? $po->supplier_id : null,
                        'date' => $transactionDate,
                        'purchase_order' => $po,
                        'po_code_text' => $poCode,
                        'notes' => [],
                        'items' => [],
                    ];
                }
                
                if (!empty($comments)) {
                    $groupedRows[$key]['notes'][] = $comments;
                }

                $qtyInput = null;
                $detailQtyVal = $cols['detail_qty'] !== null ? ($row[$cols['detail_qty']] ?? null) : null;
                $totalQtyVal = $cols['total_qty'] !== null ? ($row[$cols['total_qty']] ?? null) : null;
                if ($detailQtyVal !== null && is_numeric($detailQtyVal) && (int)$detailQtyVal > 0) {
                    $qtyInput = (int)$detailQtyVal;
                } elseif ($totalQtyVal !== null && is_numeric($totalQtyVal) && (int)$totalQtyVal > 0) {
                    $qtyInput = (int)$totalQtyVal;
                }

                $totalQty = max($qtyInput ?? count($serialsInRow), count($serialsInRow));
                if ($totalQty <= 0) {
                    $totalQty = 1;
                }

                if (!empty($serialsInRow)) {
                    foreach ($serialsInRow as $sn) {
                        $groupedRows[$key]['items'][] = [
                            'product_id' => $product->id,
                            'product_code' => $productCode,
                            'quantity' => 1,
                            'sku' => $sn,
                            'cost_usd' => (float)$costUsd,
                            'price_tiers' => $priceTiers,
                            'description' => null,
                            'comments' => $comments,
                            'row_number' => $rowNumber,
                        ];
                    }

                    $remainingQty = $totalQty - count($serialsInRow);
                    if ($remainingQty > 0) {
                        $groupedRows[$key]['items'][] = [
                            'product_id' => $product->id,
                            'product_code' => $productCode,
                            'quantity' => $remainingQty,
                            'sku' => null,
                            'cost_usd' => (float)$costUsd,
                            'price_tiers' => $priceTiers,
                            'description' => null,
                            'comments' => $comments,
                            'row_number' => $rowNumber,
                        ];
                    }
                } else {
                    $groupedRows[$key]['items'][] = [
                        'product_id' => $product->id,
                        'product_code' => $productCode,
                        'quantity' => $totalQty,
                        'sku' => null,
                        'cost_usd' => (float)$costUsd,
                        'price_tiers' => $priceTiers,
                        'description' => null,
                        'comments' => $comments,
                        'row_number' => $rowNumber,
                    ];
                }
            }
            
            if (!empty($errors)) {
                DB::rollBack();
                return ['success' => false, 'imported' => 0, 'errors' => $errors];
            }

            // Check for duplicate serials in database and within the file (only for items with serial)
            $allSerials = [];
            $duplicatesInFile = [];
            $duplicatesInDb = [];

            foreach ($groupedRows as $key => $group) {
                foreach ($group['items'] as $item) {
                    if (empty($item['sku'])) {
                        continue;
                    }
                    $serialKey = "{$item['product_id']}:{$item['sku']}";
                    
                    // Check duplicate within file
                    if (isset($allSerials[$serialKey])) {
                        $duplicatesInFile[] = "Dòng {$item['row_number']}: Serial '{$item['sku']}' bị trùng với dòng {$allSerials[$serialKey]}";
                    } else {
                        $allSerials[$serialKey] = $item['row_number'];
                    }
                }
            }

            // Check duplicates in database (batch check for performance)
            $serialsByProduct = [];
            foreach ($groupedRows as $key => $group) {
                foreach ($group['items'] as $item) {
                    if (empty($item['sku'])) {
                        continue;
                    }
                    if (!isset($serialsByProduct[$item['product_id']])) {
                        $serialsByProduct[$item['product_id']] = [
                            'serials' => [],
                            'product_code' => $item['product_code'],
                        ];
                    }
                    $serialsByProduct[$item['product_id']]['serials'][] = $item['sku'];
                }
            }

            foreach ($serialsByProduct as $productId => $data) {
                $existingSerials = ProductItem::where('product_id', $productId)
                    ->whereIn('sku', $data['serials'])
                    ->pluck('sku')
                    ->toArray();
                
                if (!empty($existingSerials)) {
                    foreach ($existingSerials as $existingSerial) {
                        $duplicatesInDb[] = "Serial '{$existingSerial}' đã tồn tại trong hệ thống cho sản phẩm '{$data['product_code']}'";
                    }
                }
            }

            if (!empty($duplicatesInFile) || !empty($duplicatesInDb)) {
                DB::rollBack();
                $allDuplicateErrors = array_merge($duplicatesInFile, $duplicatesInDb);
                return ['success' => false, 'imported' => 0, 'errors' => $allDuplicateErrors];
            }
            
            // Create transactions for each group
            foreach ($groupedRows as $group) {
                $distinctNotes = array_values(array_filter(array_unique($group['notes'] ?? [])));
                $groupNote = !empty($distinctNotes) ? implode('; ', $distinctNotes) : null;

                $transactionData = [
                    'type' => 'import',
                    'warehouse_id' => $group['warehouse_id'],
                    'supplier_id' => $group['supplier_id'],
                    'date' => $group['date'],
                    'note' => $groupNote ?: 'Import từ Excel',
                    'po_code' => $group['po_code_text'] ?: ($group['purchase_order'] ? $group['purchase_order']->code : null),
                    'reference_type' => $group['purchase_order'] ? 'purchase_order' : null,
                    'reference_id' => $group['purchase_order'] ? $group['purchase_order']->id : null,
                    'items' => [],
                ];
                
                // Group items by product_id to merge serials
                $productItems = [];
                foreach ($group['items'] as $item) {
                    $productId = $item['product_id'];
                    if (!isset($productItems[$productId])) {
                        $productItems[$productId] = [
                            'product_id' => $productId,
                            'quantity' => 0,
                            'cost_usd' => $item['cost_usd'],
                            'price_tiers' => $item['price_tiers'],
                            'description' => $item['description'],
                            'skus' => [],
                            'comments' => [],
                        ];
                    }
                    $productItems[$productId]['quantity'] += $item['quantity'];
                    if (!empty($item['sku'])) {
                        $productItems[$productId]['skus'][] = $item['sku'];
                    }
                    if (!empty($item['comments'])) {
                        $productItems[$productId]['comments'][] = $item['comments'];
                    }
                    $imported += $item['quantity'];
                }

                foreach ($productItems as $productItem) {
                    $transactionData['items'][] = [
                        'product_id' => $productItem['product_id'],
                        'warehouse_id' => $group['warehouse_id'],
                        'quantity' => $productItem['quantity'],
                        'serials' => $productItem['skus'],
                        'skus' => $productItem['skus'],
                        'cost' => $productItem['cost_usd'],
                        'cost_usd' => $productItem['cost_usd'],
                        'price_tiers' => $productItem['price_tiers'],
                        'description' => $productItem['description'],
                        'comments' => !empty($productItem['comments']) ? implode('; ', array_unique($productItem['comments'])) : null,
                        'create_product_items' => true,
                    ];
                }
                
                $this->transactionService->processImport($transactionData);
            }
            
            DB::commit();
            return [
                'success' => true, 
                'imported' => $imported, 
                'errors' => [],
                'message' => "Đã import thành công: {$imported} sản phẩm vào kho"
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            return ['success' => false, 'imported' => 0, 'errors' => ['Exception: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Parse date from various formats
     * Supports: Excel numeric timestamps, DD/MM/YYYY, D/M/YYYY, YYYY-MM-DD, DD-MM-YYYY, DD.MM.YYYY
     */
    protected function parseDate($dateString): ?string
    {
        if (empty($dateString)) {
            return date('Y-m-d');
        }

        if ($dateString instanceof \DateTimeInterface) {
            return $dateString->format('Y-m-d');
        }

        // Numeric Excel timestamp (days since 1900, e.g. 45000, 46290)
        if (is_numeric($dateString) && (float)$dateString > 1000 && (float)$dateString < 200000) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$dateString)->format('Y-m-d');
            } catch (\Exception $e) {
                // fallback
            }
        }

        $dateString = trim((string)$dateString);

        // Year-Month-Day with any separators (e.g. 2026-09-25, 2026/09/25, 2026年9月25日, 1212122026年9月25日)
        if (preg_match('/(\d{4})[^\d]+(\d{1,2})[^\d]+(\d{1,2})/u', $dateString, $matches)) {
            return sprintf('%04d-%02d-%02d', (int)$matches[1], (int)$matches[2], (int)$matches[3]);
        }

        // Already in YYYY-MM-DD or YYYY/MM/DD or YYYY.MM.DD format
        if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $dateString, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]);
        }

        // DD/MM/YYYY, D/M/YYYY, DD-MM-YYYY, DD.MM.YYYY format
        if (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/', $dateString, $matches)) {
            $p1 = (int)$matches[1];
            $p2 = (int)$matches[2];
            $year = (int)$matches[3];
            if ($p1 > 12 && $p2 <= 12) {
                // p1 is day, p2 is month
                return sprintf('%04d-%02d-%02d', $year, $p2, $p1);
            } elseif ($p2 > 12 && $p1 <= 12) {
                // p2 is day, p1 is month
                return sprintf('%04d-%02d-%02d', $year, $p1, $p2);
            } else {
                // Standard Vietnamese format DD/MM/YYYY
                return sprintf('%04d-%02d-%02d', $year, $p2, $p1);
            }
        }

        // DD/MM/YY format (2-digit year)
        if (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{2})$/', $dateString, $matches)) {
            $year = 2000 + (int)$matches[3];
            $day = (int)$matches[1];
            $month = (int)$matches[2];
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        // Try to parse with Carbon / strtotime
        try {
            $carbon = \Carbon\Carbon::parse($dateString);
            if ($carbon) {
                return $carbon->format('Y-m-d');
            }
        } catch (\Exception $e) {
            // fallback
        }

        $timestamp = strtotime($dateString);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    /**
     * Parse price_tiers JSON string
     * Requirements: 3.4, 7.5
     */
    public function parsePriceTiers($jsonString)
    {
        if (empty($jsonString) || $jsonString === '[]') {
            return [];
        }
        
        $decoded = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        // Validate structure
        if (!is_array($decoded)) {
            return false;
        }
        
        foreach ($decoded as $tier) {
            if (!isset($tier['name']) || !isset($tier['price'])) {
                return false;
            }
        }
        
        return $decoded;
    }

    /**
     * Import Serial updates for existing in-stock products without serial (NOSERIAL / NOSKU)
     * Does NOT increase stock, only assigns new serials to existing NOSERIAL items.
     */
    public function importUpdateSerials(string $filePath, ?int $warehouseId = null): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, false, false);

        if (empty($rows) || count($rows) < 2) {
            return [
                'success' => false,
                'message' => 'File Excel không có dữ liệu',
                'errors' => ['File trống hoặc chỉ có dòng tiêu đề']
            ];
        }

        // Header detection or standard indices
        $headerRow = array_shift($rows);
        
        $codeIdx = 1;
        $whIdx = 2;
        $serialIdx = 3;
        $noteIdx = 4;

        foreach ($headerRow as $i => $h) {
            $hLower = mb_strtolower(trim((string)$h), 'UTF-8');
            if (str_contains($hLower, 'part') || str_contains($hLower, 'mã') || str_contains($hLower, 'fru') || str_contains($hLower, 'code')) {
                $codeIdx = $i;
            } elseif (str_contains($hLower, 'kho') || str_contains($hLower, 'warehouse')) {
                $whIdx = $i;
            } elseif (str_contains($hLower, 'serial') || str_contains($hLower, 'sn') || str_contains($hLower, 's/n')) {
                $serialIdx = $i;
            } elseif (str_contains($hLower, 'ghi chú') || str_contains($hLower, 'note') || str_contains($hLower, 'comment')) {
                $noteIdx = $i;
            }
        }

        // Pre-load warehouses and products
        $warehouseCache = [];
        foreach (Warehouse::all() as $wh) {
            $warehouseCache[mb_strtolower(trim($wh->code), 'UTF-8')] = $wh;
            $warehouseCache[mb_strtolower(trim($wh->name), 'UTF-8')] = $wh;
        }
        $fallbackWarehouse = $warehouseId ? Warehouse::find($warehouseId) : null;

        $productCache = [];
        foreach (Product::all() as $p) {
            $productCache[mb_strtolower(trim($p->code), 'UTF-8')] = $p;
        }

        $errors = [];
        $seenSerials = [];
        // Group serial requests by product_id and warehouse_id
        $groupUpdates = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if (empty(array_filter($row, fn($v) => $v !== null && $v !== ''))) {
                continue;
            }

            $productCode = trim((string)($row[$codeIdx] ?? ''));
            $warehouseInput = trim((string)($row[$whIdx] ?? ''));
            $serialRaw = trim((string)($row[$serialIdx] ?? ''));
            $note = trim((string)($row[$noteIdx] ?? ''));

            if (empty($productCode)) {
                $errors[] = "Dòng {$rowNumber}: Thiếu mã sản phẩm / Part Number.";
                continue;
            }

            $productKey = mb_strtolower($productCode, 'UTF-8');
            if (!isset($productCache[$productKey])) {
                $errors[] = "Dòng {$rowNumber}: Không tìm thấy sản phẩm với mã '{$productCode}' trong hệ thống.";
                continue;
            }
            $product = $productCache[$productKey];

            // Resolve warehouse
            $warehouse = null;
            if (!empty($warehouseInput)) {
                $whKey = mb_strtolower($warehouseInput, 'UTF-8');
                $warehouse = $warehouseCache[$whKey] ?? null;
                if (!$warehouse) {
                    $errors[] = "Dòng {$rowNumber}: Không tìm thấy kho '{$warehouseInput}'.";
                    continue;
                }
            } else {
                $warehouse = $fallbackWarehouse;
            }

            if (!$warehouse) {
                $errors[] = "Dòng {$rowNumber}: Chưa chỉ định kho cho sản phẩm '{$productCode}'.";
                continue;
            }

            if (empty($serialRaw)) {
                $errors[] = "Dòng {$rowNumber}: Chưa nhập số serial cho sản phẩm '{$productCode}'.";
                continue;
            }

            // Split serials
            $parts = preg_split('/[\r\n,;]+/', $serialRaw);
            $rowSerials = [];
            foreach ($parts as $p) {
                $sn = trim($p);
                if ($sn !== '') {
                    $rowSerials[] = $sn;
                }
            }

            if (empty($rowSerials)) {
                $errors[] = "Dòng {$rowNumber}: Số serial không hợp lệ.";
                continue;
            }

            // Check duplicate in file
            foreach ($rowSerials as $sn) {
                $snUpper = strtoupper($sn);
                if (isset($seenSerials[$snUpper])) {
                    $errors[] = "Dòng {$rowNumber}: Số serial '{$sn}' bị trùng lặp với dòng {$seenSerials[$snUpper]} trong file import.";
                } else {
                    $seenSerials[$snUpper] = $rowNumber;
                }
            }

            $groupKey = $product->id . '_' . $warehouse->id;
            if (!isset($groupUpdates[$groupKey])) {
                $groupUpdates[$groupKey] = [
                    'product' => $product,
                    'warehouse' => $warehouse,
                    'serials' => [],
                    'notes' => [],
                ];
            }

            foreach ($rowSerials as $sn) {
                $groupUpdates[$groupKey]['serials'][] = $sn;
                $groupUpdates[$groupKey]['notes'][] = $note;
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Dữ liệu trong file Excel chưa hợp lệ',
                'errors' => $errors
            ];
        }

        if (empty($groupUpdates)) {
            return [
                'success' => false,
                'message' => 'Không có dữ liệu serial nào để cập nhật',
                'errors' => ['File không chứa dữ liệu serial hợp lệ']
            ];
        }

        // Check duplicate serials in database and verify sufficient NOSERIAL items in stock
        $plan = [];
        foreach ($groupUpdates as $key => $group) {
            $product = $group['product'];
            $warehouse = $group['warehouse'];
            $serials = $group['serials'];
            $notes = $group['notes'];
            $neededCount = count($serials);

            // Check if any serial already exists in ProductItem
            $existingSerials = ProductItem::whereIn('sku', $serials)->pluck('sku')->toArray();
            if (!empty($existingSerials)) {
                $errors[] = "Sản phẩm '{$product->code}': Các serial sau đã tồn tại trong hệ thống: " . implode(', ', $existingSerials);
                continue;
            }

            // Query in-stock NOSERIAL items for this product and warehouse
            $availableItems = ProductItem::where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->where('status', ProductItem::STATUS_IN_STOCK)
                ->noSerial()
                ->orderBy('id', 'asc')
                ->limit($neededCount)
                ->get();

            if ($availableItems->count() < $neededCount) {
                $errors[] = "Sản phẩm '{$product->code}' tại kho '{$warehouse->name}': Yêu cầu cập nhật {$neededCount} serial nhưng hiện tại chỉ có {$availableItems->count()} sản phẩm chưa có serial trong kho.";
                continue;
            }

            $plan[] = [
                'items' => $availableItems,
                'serials' => $serials,
                'notes' => $notes,
            ];
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Không thể cập nhật serial do không đủ tồn kho hoặc serial đã tồn tại',
                'errors' => $errors
            ];
        }

        // Execute update in transaction
        DB::beginTransaction();
        try {
            $updatedCount = 0;
            foreach ($plan as $p) {
                $items = $p['items'];
                $serials = $p['serials'];
                $notes = $p['notes'];

                foreach ($serials as $i => $sn) {
                    $item = $items[$i];
                    $item->sku = $sn;
                    if (!empty($notes[$i])) {
                        $item->comments = !empty($item->comments) ? ($item->comments . ' | ' . $notes[$i]) : $notes[$i];
                    }
                    $item->save();
                    $updatedCount++;
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Cập nhật thành công {$updatedCount} serial cho các sản phẩm tồn kho.",
                'errors' => []
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Lỗi khi cập nhật serial: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ];
        }
    }
}
