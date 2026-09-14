<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class BomParserService
{
    /**
     * Parse raw BOM text (from Project bom_data or pasted Excel/text) into structured items.
     * Optionally match against Product database.
     *
     * @param string|null $bomText
     * @param int|null $projectId
     * @return array
     */
    public function parse(?string $bomText, ?int $projectId = null): array
    {
        if (empty($bomText) || !trim($bomText)) {
            return [];
        }

        $rawItems = $this->extractRawLines($bomText);
        $parsedItems = [];

        foreach ($rawItems as $raw) {
            $matchedProduct = $this->findMatchingProduct($raw['pn'], $raw['model']);

            if ($matchedProduct) {
                $price = $raw['price'] > 0 
                    ? $raw['price'] 
                    : (float)($matchedProduct->calculated_selling_price ?? 0);

                $parsedItems[] = [
                    'product_id' => $matchedProduct->id,
                    'is_new' => false,
                    'is_matched' => true,
                    'code' => $matchedProduct->code,
                    'name' => $matchedProduct->name,
                    'display_text' => '[' . $matchedProduct->code . '] ' . $matchedProduct->name,
                    'quantity' => $raw['qty'],
                    'price' => $price,
                    'vat' => 8,
                    'warranty_months' => $raw['warranty'] ?? ($matchedProduct->warranty_months ?? 12),
                    'unit' => $raw['unit'] ?? ($matchedProduct->unit ?? 'Cái'),
                    'project_id' => $projectId,
                    'raw_text' => $raw['original_line'] ?? '',
                ];
            } else {
                $code = !empty($raw['pn']) ? $raw['pn'] : (!empty($raw['model']) ? $raw['model'] : 'SP-MOI');
                $name = !empty($raw['model']) ? $raw['model'] : (!empty($raw['pn']) ? $raw['pn'] : 'Sản phẩm mới');

                $parsedItems[] = [
                    'product_id' => 'new',
                    'is_new' => true,
                    'is_matched' => false,
                    'new_code' => $code,
                    'new_name' => $name,
                    'code' => $code,
                    'name' => $name,
                    'display_text' => '[SP Mới] ' . $name,
                    'quantity' => $raw['qty'],
                    'price' => $raw['price'] ?? 0,
                    'vat' => 8,
                    'warranty_months' => $raw['warranty'] ?? 12,
                    'new_unit' => $raw['unit'] ?? 'Cái',
                    'unit' => $raw['unit'] ?? 'Cái',
                    'project_id' => $projectId,
                    'raw_text' => $raw['original_line'] ?? '',
                ];
            }
        }

        return $parsedItems;
    }

    /**
     * Parse BOM text for multiple projects
     *
     * @param Collection|array $projects
     * @return array
     */
    public function parseFromProjects($projects): array
    {
        $allItems = [];
        foreach ($projects as $project) {
            if (!empty($project->bom_data)) {
                $items = $this->parse($project->bom_data, $project->id);
                foreach ($items as $item) {
                    $allItems[] = $item;
                }
            }
        }
        return $allItems;
    }

    /**
     * Extract raw items from text
     */
    public function extractRawLines(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text));
        $items = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Check if header row (skip header)
            if ($this->isHeaderLine($line)) {
                continue;
            }

            // Check if tab-delimited or structured delimiter (CSV / TSV)
            if (str_contains($line, "\t") || (str_contains($line, ';') && substr_count($line, ';') >= 2) || (str_contains($line, '|') && substr_count($line, '|') >= 2)) {
                $item = $this->parseDelimitedLine($line);
            } else {
                $item = $this->parseFreeformLine($line);
            }

            if ($item && (!empty($item['pn']) || !empty($item['model']))) {
                $item['original_line'] = $line;
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Check if a line appears to be a table header
     */
    protected function isHeaderLine(string $line): bool
    {
        $normalized = mb_strtolower($line, 'UTF-8');
        $headerKeywords = ['stt', 'p/n', 'part number', 'part number / model', 'model / description', 'mô tả', 'số lượng', 'quantity', 'đơn giá', 'unit price', 'thành tiền', 'total price', 'bảo hành', 'warranty'];
        
        $matchCount = 0;
        foreach ($headerKeywords as $kw) {
            if (str_contains($normalized, $kw)) {
                $matchCount++;
            }
        }

        return $matchCount >= 2;
    }

    /**
     * Parse tab-separated or column-delimited lines
     */
    protected function parseDelimitedLine(string $line): array
    {
        $delimiter = "\t";
        if (!str_contains($line, "\t")) {
            if (str_contains($line, ';')) $delimiter = ';';
            elseif (str_contains($line, '|')) $delimiter = '|';
            elseif (str_contains($line, ',')) $delimiter = ',';
        }

        $cols = array_map('trim', explode($delimiter, $line));
        $cols = array_values(array_filter($cols, fn($c) => $c !== ''));

        if (empty($cols)) {
            return $this->parseFreeformLine($line);
        }

        $qty = 1;
        $price = 0;
        $pn = '';
        $model = '';
        $unit = 'Cái';
        $warranty = 12;

        // Strip STT (e.g. "1", "2", "3") if first col is pure numeric <= 1000 and count > 2
        if (count($cols) >= 3 && is_numeric($cols[0]) && intval($cols[0]) <= 1000 && !preg_match('/[a-zA-Z]/', $cols[0])) {
            array_shift($cols);
        }

        if (count($cols) === 1) {
            return $this->parseFreeformLine($cols[0]);
        }

        if (count($cols) >= 2) {
            // Check last columns for Price & Qty
            $numericIndices = [];
            foreach ($cols as $idx => $val) {
                $cleanNum = $this->cleanNumber($val);
                if ($cleanNum !== null) {
                    $numericIndices[$idx] = $cleanNum;
                }
            }

            // Standard layout: PN, Model, Qty, Unit Price, Total Price
            // Or: PN, Qty, Unit Price
            // Or: Model, Qty, Unit Price
            
            // Look for quantity: usually integer between 1 and 99999
            // Look for price: usually larger number or contains currency symbols
            
            if (count($cols) >= 4) {
                // Col 0: PN
                $pn = $cols[0];
                // Col 1: Model
                $model = $cols[1];
                // Col 2: Qty
                $qty = $this->cleanNumber($cols[2]) ?: 1;
                // Col 3: Unit Price
                $price = $this->cleanNumber($cols[3]) ?: 0;
                // Col 4: Total price (optional)
            } elseif (count($cols) === 3) {
                // Could be: PN, Model, Qty OR PN, Qty, Price
                $isCol1Num = $this->cleanNumber($cols[1]) !== null;
                $isCol2Num = $this->cleanNumber($cols[2]) !== null;

                if ($isCol1Num && $isCol2Num) {
                    $pn = $cols[0];
                    $model = $cols[0];
                    $qty = (int)$this->cleanNumber($cols[1]) ?: 1;
                    $price = $this->cleanNumber($cols[2]) ?: 0;
                } elseif ($isCol2Num) {
                    $pn = $cols[0];
                    $model = $cols[1];
                    $qty = (int)$this->cleanNumber($cols[2]) ?: 1;
                } else {
                    $pn = $cols[0];
                    $model = $cols[1] . ' ' . $cols[2];
                }
            } elseif (count($cols) === 2) {
                // PN/Model, Qty OR PN, Model
                $isCol1Num = $this->cleanNumber($cols[1]) !== null;
                if ($isCol1Num) {
                    $pn = $cols[0];
                    $model = $cols[0];
                    $qty = (int)$this->cleanNumber($cols[1]) ?: 1;
                } else {
                    $pn = $cols[0];
                    $model = $cols[1];
                }
            }
        }

        // Clean up PN and Model
        if (empty($pn) && !empty($model)) {
            $pn = $model;
        }
        if (empty($model) && !empty($pn)) {
            $model = $pn;
        }

        return [
            'pn' => trim($pn),
            'model' => trim($model),
            'qty' => max(1, (int)$qty),
            'price' => max(0, (float)$price),
            'unit' => $unit,
            'warranty' => $warranty,
        ];
    }

    /**
     * Parse freeform text line (e.g., "2x FG-60F-BDL", "Switch Cisco 24 port Qty: 2 Price: 15.000.000")
     */
    protected function parseFreeformLine(string $line): array
    {
        $qty = 1;
        $price = 0;
        $pn = '';
        $model = $line;
        $unit = 'Cái';
        $warranty = 12;

        // Remove bullet markers at start (e.g., "1.", "1)", "-", "*", "+")
        $model = preg_replace('/^\s*(?:\d+[\.\)]|[-*+•])\s+/', '', $model);

        // 1. Attempt to parse Price
        if (preg_match('/(?:price|đơn giá|giá|unit price|@|usd|vnd|đ)[:\-\s]*([0-9.,\s]+)(?:vnd|usd|đ)?/iu', $model, $matches)) {
            $cleanedPrice = $this->cleanNumber($matches[1]);
            if ($cleanedPrice !== null && $cleanedPrice > 0) {
                $price = $cleanedPrice;
                $model = str_replace($matches[0], '', $model);
            }
        }

        // 2. Attempt to parse Qty anywhere in the string
        // Case A: "(Qty: 2)" or "SL: 2" or "Số lượng: 2"
        if (preg_match('/(?:qty|quantity|số lượng|sl)[:\-\s]+(\d+)/iu', $model, $matches)) {
            $qty = (int)$matches[1];
            $model = preg_replace('/\(?\s*(?:qty|quantity|số lượng|sl)[:\-\s]+\d+\s*(?:pcs|pc|cái|chiếc|bộ)?\s*\)?/iu', '', $model);
        }
        // Case B: "2 x FG-60F" or "2x FG-60F" at start
        elseif (preg_match('/^(\d+)\s*(?:x|pcs|pc|cái|chiếc|bộ|license|lic|con|gói)\s+(.+)$/iu', $model, $matches)) {
            $qty = (int)$matches[1];
            $model = trim($matches[2]);
        }
        // Case C: "5 cái" / "5 pcs" / "5 chiếc" anywhere (surrounded by bounds or delimiters)
        elseif (preg_match('/(?:^|[\s\-,:\(\[])(\d+)\s*(?:pcs|pc|cái|chiếc|bộ|license|lic|con|gói)(?:[\s\-,:\)\]]|$)/iu', $model, $matches)) {
            $qty = (int)$matches[1];
            $model = preg_replace('/(?:^|[\s\-,:\(\[])' . preg_quote($matches[1], '/') . '\s*(?:pcs|pc|cái|chiếc|bộ|license|lic|con|gói)(?:[\s\-,:\)\]]|$)/iu', ' ', $model);
        }
        // Case D: "FG-60F 2 pcs" or "FG-60F 2x" at the end
        elseif (preg_match('/^(.+?)\s+(\d+)\s*(?:pcs|pc|cái|chiếc|bộ|license|lic|con|gói|x)$/iu', $model, $matches)) {
            $model = trim($matches[1]);
            $qty = (int)$matches[2];
        }

        // Clean trailing/leading delimiters
        $model = trim($model, " \t\n\r\0\x0B-:|(),");

        // 3. Separate Part Number and Model if possible
        if (preg_match('/^([A-Z0-9\-_.\/]{3,30})\s*[:\-\|]\s*(.+)$/i', $model, $pnMatches)) {
            $pn = trim($pnMatches[1]);
            $model = trim($pnMatches[2]);
        } elseif (preg_match('/^([A-Z0-9\-_.\/]{4,30})\s+(.+)$/', $model, $pnMatches)) {
            // Check if first token looks like a Part Number (has both letters and digits or dashes)
            $firstToken = $pnMatches[1];
            if ((preg_match('/[A-Za-z]/', $firstToken) && preg_match('/[0-9]/', $firstToken)) || str_contains($firstToken, '-') || str_contains($firstToken, '_')) {
                $pn = $firstToken;
                $model = trim($pnMatches[2]);
            } else {
                $pn = $model;
            }
        } else {
            $pn = $model;
        }

        return [
            'pn' => trim($pn),
            'model' => trim($model),
            'qty' => max(1, (int)$qty),
            'price' => max(0, (float)$price),
            'unit' => $unit,
            'warranty' => $warranty,
        ];
    }

    /**
     * Clean and parse a number string (handles Vietnamese/US number formats: 15,000,000 or 15.000.000)
     */
    protected function cleanNumber(?string $str): ?float
    {
        if ($str === null || trim($str) === '') {
            return null;
        }

        $str = trim($str);
        // Remove currency symbols & spaces
        $str = preg_replace('/[^\d.,]/', '', $str);

        if (empty($str)) {
            return null;
        }

        // If contains both . and ,
        // E.g. "15.000.000,50" -> thousand is '.', decimal is ','
        // E.g. "15,000,000.50" -> thousand is ',', decimal is '.'
        if (str_contains($str, '.') && str_contains($str, ',')) {
            $lastDot = strrpos($str, '.');
            $lastComma = strrpos($str, ',');
            if ($lastDot > $lastComma) {
                // US style 1,000.50
                $str = str_replace(',', '', $str);
            } else {
                // EU/VN style 1.000,50
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            }
        } elseif (str_contains($str, '.')) {
            // E.g. "15.000.000" or "15.5"
            $parts = explode('.', $str);
            if (count($parts) > 2) {
                // Thousands separator: 15.000.000
                $str = str_replace('.', '', $str);
            } elseif (count($parts) === 2 && strlen($parts[1]) === 3 && (int)$parts[0] > 0) {
                // Likely thousand separator: 15.000
                $str = str_replace('.', '', $str);
            }
        } elseif (str_contains($str, ',')) {
            // E.g. "15,000,000" or "15,5"
            $parts = explode(',', $str);
            if (count($parts) > 2) {
                $str = str_replace(',', '', $str);
            } elseif (count($parts) === 2 && strlen($parts[1]) === 3 && (int)$parts[0] > 0) {
                $str = str_replace(',', '', $str);
            } else {
                $str = str_replace(',', '.', $str);
            }
        }

        return is_numeric($str) ? floatval($str) : null;
    }

    /**
     * Search Product table for a match by PN or Model
     */
    public function findMatchingProduct(?string $pn, ?string $model): ?Product
    {
        $queries = array_unique(array_filter([
            trim($pn ?? ''),
            trim($model ?? ''),
        ]));

        foreach ($queries as $q) {
            if (empty($q)) continue;

            // 1. Exact match on code
            $product = Product::where('code', $q)
                ->orWhere('code', strtoupper($q))
                ->first();
            if ($product) return $product;

            // 2. Exact match on supplier price list SKU
            $product = Product::whereHas('supplierPriceListItems', function ($sq) use ($q) {
                $sq->where('sku', $q)->orWhere('sku', strtoupper($q));
            })->first();
            if ($product) return $product;

            // 3. Exact match on name
            $product = Product::where('name', $q)->first();
            if ($product) return $product;
        }

        // 4. Try matching partial / prefix code (if length >= 4)
        foreach ($queries as $q) {
            if (strlen($q) >= 4) {
                $product = Product::where('code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->first();
                if ($product) return $product;
            }
        }

        return null;
    }
}
