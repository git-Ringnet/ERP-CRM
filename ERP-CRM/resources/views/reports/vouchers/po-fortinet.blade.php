<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @if(isset($isPreview) && $isPreview)
        <title>{{ str_replace(['\\', '/', '?', '*', ':', '[', ']'], '-', 'PO_' . $po->code) }}</title>
    @endif
</head>
<body style="font-family: Arial; font-size: 9pt;">
@php
    $currentRow = 1;

    // Fetch information from the first item with saleOrderRequestItem
    $firstItem = $po->items->first(function($item) {
        return $item->saleOrderRequestItem !== null;
    });

    $siName = '';
    $euName = '';
    $euMst = '';
    $euAddress = '';
    $posId = '';

    if ($firstItem && $firstItem->saleOrderRequestItem) {
        $siName = $firstItem->saleOrderRequestItem->si_name;
        $euAddress = $firstItem->saleOrderRequestItem->address;
        $posId = $firstItem->saleOrderRequestItem->pos_id;

        $parts = explode(' - ', $firstItem->saleOrderRequestItem->eu_name_mst, 2);
        $euName = $parts[0] ?? '';
        $euMst = $parts[1] ?? '';
    }
@endphp

<table style="font-family: Arial; font-size: 9pt;">
    {{-- Row 1: PURCHASE ORDER --}}
    <tr>
        <td colspan="10" style="font-weight: bold; font-size: 16pt; font-family: 'Times New Roman';">PURCHASE ORDER</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Row 2: Order No and Date --}}
    <tr>
        <td colspan="5" style="font-weight: normal; color: #000000; font-family: Arial; font-size: 9pt;">(Order No : {{ $po->code }})</td>
        <td colspan="5" style="text-align: right; font-family: 'Times New Roman'; font-size: 9pt;">PO date: {{ date('m-d-Y') }}</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Row 3: Blank --}}
    <tr><td colspan="10"></td></tr>
    @php $currentRow++; @endphp

    {{-- Cards: THE BUYER & THE SELLER --}}
    {{-- Row 4 --}}
    <tr>
        <td colspan="5" style="font-weight: bold; font-family: Arial; border-top: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000;">THE BUYER: {{ $company->buyer_name }}</td>
        <td colspan="5" style="font-weight: bold; font-family: Arial; border-top: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000;">THE SELLER: {{ $config->seller_name }}</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Row 5 --}}
    <tr>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">-Address: {{ $company->buyer_address_line1 }}</td>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">-Address : {{ $config->seller_address_line1 }}</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Row 6 --}}
    <tr>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">{{ $company->buyer_address_line2 }}</td>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">{{ $config->seller_address_line2 }}</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Row 7 --}}
    <tr>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">-Tel: {{ $company->buyer_tel }} Fax: {{ $company->buyer_fax }}</td>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">-Tel: {{ $config->seller_tel }}     Fax : {{ $config->seller_fax }}</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Row 8 --}}
    <tr>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">-Contact: {{ $company->buyer_contact }}</td>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">Contact : {{ $config->seller_contact }}</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Row 9 --}}
    <tr>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">-Bank Account USD: {{ $company->buyer_bank_account }}</td>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">Beneficary: {{ $config->seller_beneficiary }}</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Row 10 --}}
    <tr>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">-Bank name: {{ $company->buyer_bank_name }}</td>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">-Address: {{ $config->seller_beneficiary_address }}</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Row 11 --}}
    <tr>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">-Bank Address: {{ $company->buyer_bank_address_line1 }}</td>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">-Bank account: {{ $config->seller_bank_account }} at {{ $config->seller_bank_name }}</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Row 12 --}}
    <tr>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">{{ $company->buyer_bank_address_line2 }}</td>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">-Bank address: {{ $config->seller_bank_address_line1 }}</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Row 13 --}}
    <tr>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">-Swift code: {{ $company->buyer_swift_code }}</td>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">{{ $config->seller_bank_address_line2 }}</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Row 14 --}}
    <tr>
        <td colspan="5" style="font-family: Arial; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000;"></td>
        <td colspan="5" style="font-family: Arial; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000;">-Bank ABA: {{ $config->seller_bank_aba }};  BANK SWIFT CODE: {{ $config->seller_swift_code }}</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Cards: SHIP TO & INVOICE TO --}}
    {{-- Row 15 --}}
    <tr>
        <td colspan="5" style="font-weight: bold; font-family: Arial; border-top: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000;">SHIP TO: {{ $company->ship_to_name }}</td>
        <td colspan="5" style="font-weight: bold; font-family: Arial; border-top: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000;">INVOICE TO: {{ $company->invoice_to_name }}</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Row 16 --}}
    <tr>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">-Address: {{ $company->ship_to_address_line1 }}</td>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">-Address: {{ $company->invoice_to_address_line1 }}</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Row 17 --}}
    <tr>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">{{ $company->ship_to_address_line2 }}</td>
        <td colspan="5" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000;">{{ $company->invoice_to_address_line2 }}</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Row 18 --}}
    <tr>
        <td colspan="5" style="font-family: Arial; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000;">-Attn: {{ $company->ship_to_attn }}</td>
        <td colspan="5" style="font-family: Arial; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000;">-Attn: {{ $company->invoice_to_attn }}</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Row 19 --}}
    <tr>
        <td colspan="5" style="font-weight: bold; font-family: Arial; border: 1px solid #000000;">Port of loading: {{ $config->port_loading }}</td>
        <td colspan="5" style="font-weight: bold; font-family: Arial; border: 1px solid #000000;">Port of discharge: {{ $config->port_discharge }}</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Row 20 --}}
    <tr><td colspan="10"></td></tr>
    @php $currentRow++; @endphp

    {{-- Row 21 --}}
    <tr>
        <td colspan="10" style="font-weight: bold; text-decoration: underline; font-family: Arial;">Equipment description, Part number, Quantity, Prices:</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Table Header --}}
    {{-- Row 22 --}}
    <tr>
        <th style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000; font-family: Arial;">Item</th>
        <th style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000; font-family: Arial;">SKU</th>
        <th style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000; font-family: Arial;">Qty</th>
        <th style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000; font-family: Arial;">S/N</th>
        <th style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000; font-family: Arial;">Quote ID</th>
        <th style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000; font-family: Arial;">Unit PriceList<br>(US$)</th>
        <th style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000; font-family: Arial;">Discount<br>Approved (%)</th>
        <th style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000; font-family: Arial;">Unit Price</th>
        <th style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000; font-family: Arial;">Total Net Price<br>(US$)</th>
        <th style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000; font-family: Arial;">Note</th>
    </tr>
    @php $currentRow++; @endphp

    {{-- Loop Items --}}
    @php
        $startItemRow = $currentRow;
    @endphp

    @foreach($po->items as $index => $item)
        @php
            $itemRow = $currentRow;
            $discountPctDecimal = ($item->discount_percent ?? 0) / 100;
            // Back-calculate list price if there's a discount, otherwise use unit_price
            $listPrice = ($item->discount_percent > 0 && $item->discount_percent < 100)
                ? ($item->unit_price / (1 - $discountPctDecimal))
                : $item->unit_price;

            $snVal = $item->serial_number ?: ($item->saleOrderRequestItem->serial_number ?? '');
            if (!$snVal) {
                $snVal = 'New Buy';
            }
        @endphp
        <tr>
            <td style="text-align: center; border: 1px solid #000000; font-family: Arial;">{{ sprintf('%02d', $index + 1) }}</td>
            <td style="text-align: left; border: 1px solid #000000; font-family: Arial;">{{ $item->product_name ?: ($item->product->code ?? '') }}</td>
            <td style="text-align: center; border: 1px solid #000000; font-family: Arial;">{{ (int)$item->quantity }}</td>
            <td style="text-align: center; border: 1px solid #000000; font-family: Arial; {{ $snVal === 'New Buy' ? 'color: #FF0000;' : '' }}">{{ $snVal }}</td>
            <td style="text-align: center; border: 1px solid #000000; font-family: Arial;">New Buy</td>
            <td style="text-align: right; border: 1px solid #000000; font-family: Arial;">{{ number_format($listPrice, 2, '.', '') }}</td>
            <td style="text-align: right; border: 1px solid #000000; font-family: Arial;">{{ number_format($item->discount_percent ?? 0, 2, '.', '') }}%</td>
            <td style="text-align: right; border: 1px solid #000000; font-family: Arial;">=ROUND(F{{ $itemRow }}*(1-G{{ $itemRow }}), 2)</td>
            <td style="text-align: right; border: 1px solid #000000; font-family: Arial;">=ROUND(H{{ $itemRow }}*C{{ $itemRow }}, 2)</td>
            <td style="text-align: left; border: 1px solid #000000; font-family: Arial;">{{ $item->note }}</td>
        </tr>
        @php $currentRow++; @endphp
    @endforeach

    @php
        $endItemRow = $currentRow - 1;
    @endphp

    {{-- Grand Total --}}
    <tr>
        <td colspan="7" style="text-align: right; font-weight: bold; border: 1px solid #000000; font-family: Arial;">GRAND TOTAL (US$):</td>
        <td style="text-align: right; font-weight: bold; border: 1px solid #000000; font-family: Arial;"></td>
        <td style="text-align: right; font-weight: bold; border: 1px solid #000000; background-color: #FFF2CC; font-family: Arial;">=SUM(I{{ $startItemRow }}:I{{ $endItemRow }})</td>
        <td style="border: 1px solid #000000;"></td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Say in words --}}
    <tr>
        <td colspan="10" style="font-weight: bold; font-style: italic; border: 1px solid #000000; background-color: #FFFF00; padding: 6px 10px; font-family: Arial;">
            Say in words: {{ \App\Helpers\NumberHelper::currencyToEnglishWords($po->total_foreign ?? $po->total) }}
        </td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Notes Section --}}
    <tr><td colspan="10"></td></tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="10" style="font-weight: bold; font-size: 9pt; text-decoration: underline; font-family: Arial;">Note:</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td style="font-weight: bold; font-family: Arial;">1</td>
        <td colspan="2" style="font-weight: bold; font-family: Arial;">CPQ:</td>
        <td colspan="7" style="background-color: #FFFF00; font-family: Arial; text-align: left;">{{ $po->cpq_number }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td style="font-weight: bold; font-family: Arial;">2</td>
        <td colspan="9" style="font-weight: bold; font-family: Arial;">End-User</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="2" style="font-weight: bold; color: #FF0000; font-family: Arial;">End-User Name:</td>
        <td colspan="7" style="background-color: #FFFF00; font-family: Arial; text-align: left;">{{ $euName }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="2" style="font-weight: bold; color: #FF0000; font-family: Arial;">Website:</td>
        <td colspan="7" style="background-color: #FFFF00; font-family: Arial; text-align: left;">{{ $euMst }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="2" style="font-weight: bold; color: #FF0000; font-family: Arial;">Address:</td>
        <td colspan="7" style="background-color: #FFFF00; font-family: Arial; text-align: left;">{{ $euAddress }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="2" style="font-weight: bold; color: #FF0000; font-family: Arial;">Line of Business:</td>
        <td colspan="7" style="font-family: Arial; text-align: left;"></td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td style="font-weight: bold; font-family: Arial;">3</td>
        <td colspan="9" style="font-weight: bold; font-family: Arial;">Reseller</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="2" style="font-weight: bold; color: #FF0000; font-family: Arial;">Reseller Name:</td>
        <td colspan="7" style="background-color: #FFFF00; font-family: Arial; text-align: left;">{{ $siName }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="2" style="font-weight: bold; color: #FF0000; font-family: Arial;">Reseller POS ID:</td>
        <td colspan="7" style="background-color: #FFFF00; font-family: Arial; text-align: left;">{{ $posId }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td style="font-weight: bold; font-family: Arial;">4</td>
        <td colspan="9" style="font-weight: bold; font-family: Arial;">Order Detail:</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="2" style="font-weight: bold; font-family: Arial;">Stocking Order:</td>
        <td colspan="7" style="font-family: Arial;">NO - For Internal Use</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="2" style="font-weight: bold; font-family: Arial;">Partial Shipment Allowed:</td>
        <td colspan="7" style="font-family: Arial;">NO</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="9" style="font-style: italic; font-family: Arial;">Shipment Complete &amp; Invoice Complete required (both Hardware &amp; License).</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td style="font-weight: bold; font-family: Arial;">5</td>
        <td colspan="9" style="font-weight: bold; font-family: Arial;">Other request:</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="2" style="font-weight: bold; font-family: Arial;">COO request:</td>
        <td colspan="7" style="font-family: Arial;">COO Taiwan, only for main equipment.</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="2" style="font-weight: bold; font-family: Arial;">Year of manufacturing:</td>
        <td colspan="7" style="font-family: Arial;">FY2025 / FY2026</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="2" style="font-weight: bold; font-family: Arial;">Show information in C.Q:</td>
        <td colspan="7" style="font-family: Arial;">EU name, Year of manufacturing, Place of manufacturing. Original copy required</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td style="font-weight: bold; font-family: Arial;">6</td>
        <td colspan="9" style="font-weight: bold; font-family: Arial;">Commercial Terms</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="9" style="font-family: Arial;">Delivery Term: Ex-Works, ASAP. All above-prices are in accordance with INCOTERMS 2000.</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="9" style="font-family: Arial;">Payment term: by T/T, NET 45 DAYS</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="9" style="font-family: Arial;">Currency: All prices are in USD</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="9" style="font-family: Arial;">Warranty time: in accordance with manufacturer's standard</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="9" style="font-family: Arial;">Documents required: Invoice, Packing list, Cert of Origin, Cert of Quantity</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="9" style="font-weight: bold; font-family: Arial;">Forwarder:</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="9" style="font-weight: bold; text-decoration: underline; font-family: Arial;">Hardware Shipments:</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="9" style="font-weight: bold; text-decoration: underline; font-family: Arial;">Freight Carrier Name and Contact Information in Taipei, Taiwan</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="9" style="font-family: Arial;">Forwarder name: AOF CARGO LOGISTICS CO.,LTD.</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="9" style="font-family: Arial;">Forwarder Address: Rm. A2, 17F., No. 497, Zhongming S. Rd., West Dist., Taichung City 40347 Taiwan (R.O.C.)</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="9" style="font-family: Arial;">Phone number: 886-04-2375-1189#685</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="9" style="font-family: Arial;">Fax number: +886-4-2375-1369</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="9" style="font-family: Arial;">Contact name: DORA CHANG (TXG CS)</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td></td>
        <td colspan="9" style="font-family: Arial;">Contact email: tpecs226@aif.com.tw</td>
    </tr>

    <tr><td colspan="10"></td></tr>
    @php $currentRow++; @endphp

    {{-- Signatures --}}
    <tr>
        <td colspan="5" style="font-weight: bold; font-family: Arial;">Confirmed by {{ $config->seller_name }}</td>
        <td colspan="5" style="text-align: right; font-weight: bold; font-family: Arial;">{{ $company->company_full_name }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="5"></td>
        <td colspan="5" style="text-align: right; font-weight: bold; font-family: Arial;">{{ $company->signer_title }}</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- signature spacer --}}
    <tr><td colspan="10" style="height: 60px;"></td></tr>
    @php $currentRow += 3; @endphp

    <tr>
        <td colspan="5"></td>
        <td colspan="5" style="text-align: right; font-weight: bold; font-family: Arial;">{{ $company->signer_name }}</td>
    </tr>
    @php $currentRow++; @endphp
</table>
</body>
</html>
