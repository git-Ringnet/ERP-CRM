<?php

function findHeaderRow($rowValues) {
    $headerKeywords = [
        'sku', 'part number', 'part#', 'part #', 'partnumber', 'part_no', 'part no',
        'model', 'code', 'product', 'description', 'desc', 'price', 'usd', 'msrp',
        'amount', 'cost', 'item', 'unit', 'contract', 'forticare', 'list', 'p/n'
    ];

    echo "Debugging Header Search Logic:\n";
    $keywordMatches = 0;
    
    foreach ($rowValues as $value) {
        $val = strtolower(trim((string) $value));
        $val = preg_replace('/_x000d_|\r\n|\r|\n/i', ' ', $val);
        $val = preg_replace('/\s+/', ' ', $val);
        $val = trim($val);
        
        // Clean aggressive
        $cleanValue = preg_replace('/[^a-z0-9\p{L}]/u', '', mb_strtolower($value));
        echo "Original: '$value' -> Clean: '$cleanValue'\n";

        foreach ($headerKeywords as $keyword) {
            $cleanKeyword = preg_replace('/[^a-z0-9\p{L}]/u', '', mb_strtolower($keyword));
            if (str_contains($cleanValue, $cleanKeyword)) {
                echo "  Match! Keyword: '$keyword' ($cleanKeyword) found in '$cleanValue'\n";
                $keywordMatches++;
                break;
            }
        }
    }
    
    echo "Total Matches: $keywordMatches\n";
    return $keywordMatches >= 2;
}

$headers = [
    "Segment",
    "P/N",
    "Purchase Price\n(Ex-work TW)\nUSD",
    "Suggested Dealer Price\nUSD",
    "MSRP\nwithout VAT\nUSD",
    "Availability",
    "HDD Bay",
    "HDD Type"
];

findHeaderRow($headers);
