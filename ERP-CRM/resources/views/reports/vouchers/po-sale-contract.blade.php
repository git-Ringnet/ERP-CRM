<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @if(isset($isPreview) && $isPreview)
        <title>Sale Contract - {{ $po->code }}</title>
    @endif
</head>
<body style="font-family: Arial; font-size: 9pt;">
@php
    $currentRow = 1;
    $bannerPath = null;
    if ($company->header_banner_path && file_exists(public_path($company->header_banner_path))) {
        $bannerPath = $company->header_banner_path;
    } elseif (file_exists(public_path('images/default-banner.png'))) {
        $bannerPath = 'images/default-banner.png';
    } elseif (file_exists(public_path('uploads/po-logos/default-banner.png'))) {
        $bannerPath = 'uploads/po-logos/default-banner.png';
    }
@endphp

<table style="font-family: Arial; font-size: 9pt;">
    {{-- Rows 1-6: Header Info --}}
    @if($bannerPath)
        {{-- Banner image mode: single image spans full header --}}
        <tr>
            @if(isset($isPreview) && $isPreview)
                <td colspan="7" rowspan="6" style="text-align: center; vertical-align: middle; padding: 0;">
                    <img src="{{ asset($bannerPath) }}" style="max-height: 120px; max-width: 100%; height: auto; object-fit: contain;">
                </td>
            @else
                {{-- Excel mode: empty cells; Drawing overlay will be applied by the Export class --}}
                <td colspan="7" style="height: 20px;"></td>
            @endif
        </tr>
        @if(isset($isPreview) && $isPreview)
            {{-- Rows already spanned by rowspan="6" in preview --}}
        @else
            <tr><td colspan="7" style="height: 20px;"></td></tr>
            @php $currentRow++; @endphp
            <tr><td colspan="7" style="height: 20px;"></td></tr>
            @php $currentRow++; @endphp
            <tr><td colspan="7" style="height: 20px;"></td></tr>
            @php $currentRow++; @endphp
            <tr><td colspan="7" style="height: 20px;"></td></tr>
            @php $currentRow++; @endphp
            <tr><td colspan="7" style="height: 20px;"></td></tr>
            @php $currentRow++; @endphp
        @endif
        @php $currentRow++; @endphp
    @else
        {{-- Text-based header fallback (no banner uploaded) --}}
        <tr>
            @if(isset($isPreview) && $isPreview)
                <td colspan="3" rowspan="6" style="text-align: center; vertical-align: middle;">
                    @if($company->header_logo_path && file_exists(public_path($company->header_logo_path)))
                        <img src="{{ asset($company->header_logo_path) }}" style="max-height: 80px; max-width: 180px;">
                    @endif
                </td>
            @else
                <td></td>
                <td></td>
                <td></td>
            @endif
            <td colspan="4" style="font-weight: bold; font-size: 14pt; color: #153E7E; font-family: Arial; text-align: left;">
                {{ $company->company_full_name ?: 'TECH HORIZON CORP' }}
            </td>
        </tr>
        @php $currentRow++; @endphp

        <tr>
            @if(!isset($isPreview) || !$isPreview)
                <td></td>
                <td></td>
                <td></td>
            @endif
            <td colspan="4" style="font-size: 8pt; font-weight: bold; font-family: Arial; text-align: left;">HCMC OFFICE</td>
        </tr>
        @php $currentRow++; @endphp

        <tr>
            @if(!isset($isPreview) || !$isPreview)
                <td></td>
                <td></td>
                <td></td>
            @endif
            <td colspan="4" style="font-size: 8pt; font-family: Arial; text-align: left;">{{ $company->hcmc_address }}</td>
        </tr>
        @php $currentRow++; @endphp

        <tr>
            @if(!isset($isPreview) || !$isPreview)
                <td></td>
                <td></td>
                <td></td>
            @endif
            <td colspan="4" style="font-size: 8pt; font-weight: bold; font-family: Arial; text-align: left;">HANOI BRANCH</td>
        </tr>
        @php $currentRow++; @endphp

        <tr>
            @if(!isset($isPreview) || !$isPreview)
                <td></td>
                <td></td>
                <td></td>
            @endif
            <td colspan="4" style="font-size: 8pt; font-family: Arial; text-align: left;">{{ $company->hanoi_address }}</td>
        </tr>
        @php $currentRow++; @endphp

        <tr>
            @if(!isset($isPreview) || !$isPreview)
                <td></td>
                <td></td>
                <td></td>
            @endif
            <td colspan="4" style="font-size: 8pt; font-family: Arial; text-align: left;">Website: {{ $company->website }} | Email: {{ $company->email }} | Tel: {{ $company->phone }}</td>
        </tr>
        @php $currentRow++; @endphp
    @endif

    {{-- Separation line --}}
    <tr>
        <td colspan="7" style="border-bottom: 2px solid #1E3A8A; height: 5px;"></td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Title: SALE CONTRACT --}}
    <tr>
        <td colspan="7" style="text-align: center; font-weight: bold; font-size: 16pt; font-family: 'Times New Roman'; height: 35px; vertical-align: middle;">
            SALE CONTRACT
        </td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="4" style="text-align: left; font-family: Arial; font-size: 9pt; font-weight: bold;">
            (Order No.: {{ $po->code }})
        </td>
        <td colspan="3" style="text-align: right; font-family: Arial; font-size: 9pt; font-weight: bold;">
            PO date: {{ date('m-d-Y') }}
        </td>
    </tr>
    @php $currentRow++; @endphp

    <tr><td colspan="7"></td></tr>
    @php $currentRow++; @endphp

    {{-- Cards: THE BUYER & THE SELLER --}}
    <tr>
        <td colspan="4" style="font-weight: bold; font-family: Arial; border-top: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">THE BUYER: {{ $company->buyer_name }}</td>
        <td colspan="3" style="font-weight: bold; font-family: Arial; border-top: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">THE SELLER: {{ $config->seller_name }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="4" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">-Address: {{ $company->buyer_address_line1 }}</td>
        <td colspan="3" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">-Address: {{ $config->seller_address_line1 }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="4" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">{{ $company->buyer_address_line2 }}</td>
        <td colspan="3" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">{{ $config->seller_address_line2 }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="4" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">-Tel: {{ $company->buyer_tel }} Fax: {{ $company->buyer_fax }}</td>
        <td colspan="3" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">-Tel: {{ $config->seller_tel }} Fax: {{ $config->seller_fax }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="4" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">-Contact: {{ $company->buyer_contact }}</td>
        <td colspan="3" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">-Contact: {{ $config->seller_contact }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="4" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">-Bank Account: USD: {{ $company->buyer_bank_account }}</td>
        <td colspan="3" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">-Beneficiary: {{ $config->seller_beneficiary }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="4" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">-Bank name: {{ $company->buyer_bank_name }}</td>
        <td colspan="3" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">-Address: {{ $config->seller_beneficiary_address }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="4" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">-Bank Address: {{ $company->buyer_bank_address_line1 }}</td>
        <td colspan="3" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">-Bank: {{ $config->seller_bank_name }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="4" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">{{ $company->buyer_bank_address_line2 }}</td>
        <td colspan="3" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">-Bank account: {{ $config->seller_bank_account }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="4" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">-Swift code: {{ $company->buyer_swift_code }}</td>
        <td colspan="3" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">-Bank address: {{ $config->seller_bank_address_line1 }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="4" style="font-family: Arial; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;"></td>
        <td colspan="3" style="font-family: Arial; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">
            -BANK SWIFT CODE: {{ $config->seller_swift_code }}
            @if($config->seller_bank_address_line2)
                <br>{!! $config->seller_bank_address_line2 !!}
            @endif
        </td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Cards: SHIP TO & INVOICE TO --}}
    <tr>
        <td colspan="4" style="font-weight: bold; font-family: Arial; border-top: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">SHIP TO: {{ $company->ship_to_name }}</td>
        <td colspan="3" style="font-weight: bold; font-family: Arial; border-top: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">INVOICE TO: {{ $company->invoice_to_name }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="4" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">-Address: {{ $company->ship_to_address_line1 }}</td>
        <td colspan="3" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">-Address: {{ $company->invoice_to_address_line1 }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="4" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">{{ $company->ship_to_address_line2 }}</td>
        <td colspan="3" style="font-family: Arial; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">{{ $company->invoice_to_address_line2 }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="4" style="font-family: Arial; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">-Attn: {{ $company->ship_to_attn }}</td>
        <td colspan="3" style="font-family: Arial; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; vertical-align: middle;">-Attn: {{ $company->invoice_to_attn }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="4" style="font-weight: bold; font-family: Arial; border: 1px solid #000000; vertical-align: middle;">Port of loading: {{ $config->port_loading }}</td>
        <td colspan="3" style="font-weight: bold; font-family: Arial; border: 1px solid #000000; vertical-align: middle;">Port of discharge: {{ $config->port_discharge }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr><td colspan="7"></td></tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="7" style="font-family: Arial; font-size: 9.5pt; vertical-align: middle;">
            It's mutually agreed on that the Buyer wishes to buy and the Seller agrees to sell the following commodities under the terms and conditions hereunder stipulated:
        </td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="7" style="font-weight: bold; text-decoration: underline; font-family: Arial; vertical-align: middle; text-align: left;">
            Equipment description, Part number, Quantity, Prices:
        </td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="7" style="height: 15px;"></td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Items Table Header --}}
    <tr>
        <th style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000; font-family: Arial;">ITEM</th>
        <th style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000; font-family: Arial;">Goods Description</th>
        <th style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000; font-family: Arial;">Model/Part</th>
        <th style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000; font-family: Arial;">Project ID</th>
        <th style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000; font-family: Arial;">Unit Price<br>(US$)</th>
        <th style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000; font-family: Arial;">QTY</th>
        <th style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000; font-family: Arial;">Total Net Price<br>(US$)</th>
    </tr>
    @php $currentRow++; @endphp

    {{-- Items loop --}}
    @php
        $startItemRow = $currentRow;
    @endphp

    @foreach($po->items as $index => $item)
        @php
            $itemRow = $currentRow;
        @endphp
        <tr>
            <td style="text-align: center; border: 1px solid #000000; font-family: Arial; vertical-align: middle;">{{ sprintf('%02d', $index + 1) }}</td>
            <td style="text-align: left; border: 1px solid #000000; font-family: Arial; vertical-align: middle;">{{ $item->product_name ?: ($item->product->code ?? '') }}</td>
            <td style="text-align: center; border: 1px solid #000000; font-family: Arial; vertical-align: middle;">{{ $item->product->code ?? '' }}</td>
            <td style="text-align: center; border: 1px solid #000000; font-family: Arial; vertical-align: middle;"></td>
            <td style="text-align: right; border: 1px solid #000000; font-family: Arial; vertical-align: middle;">{{ number_format($item->unit_price, 2, '.', '') }}</td>
            <td style="text-align: center; border: 1px solid #000000; font-family: Arial; vertical-align: middle;">{{ (int)$item->quantity }}</td>
            <td style="text-align: right; border: 1px solid #000000; font-family: Arial; vertical-align: middle;">
                @if(isset($isPreview) && $isPreview)
                    {{ number_format($item->unit_price * $item->quantity, 2, '.', ',') }}
                @else
                    =E{{ $itemRow }}*F{{ $itemRow }}
                @endif
            </td>
        </tr>
        @php $currentRow++; @endphp
    @endforeach

    @php
        $endItemRow = $currentRow - 1;
    @endphp

    {{-- Grand Total --}}
    <tr>
        <td colspan="6" style="text-align: right; font-weight: bold; border: 1px solid #000000; font-family: Arial; vertical-align: middle;">TOTAL (EX-WORKS) (US$):</td>
        <td style="text-align: right; font-weight: bold; border: 1px solid #000000; background-color: #FFF2CC; font-family: Arial; vertical-align: middle;">
            @if(isset($isPreview) && $isPreview)
                {{ number_format($po->items->sum(function($item) { return $item->unit_price * $item->quantity; }), 2, '.', ',') }}
            @else
                =SUM(G{{ $startItemRow }}:G{{ $endItemRow }})
            @endif
        </td>
    </tr>
    @php $currentRow++; @endphp

    {{-- Say in words --}}
    <tr>
        <td colspan="7" style="font-weight: bold; font-style: italic; border: 1px solid #000000; background-color: #FFFF00; padding: 6px 10px; font-family: Arial; vertical-align: middle;">
            Say in words: {{ \App\Helpers\NumberHelper::currencyToEnglishWords($po->total_foreign ?? $po->total) }}
        </td>
    </tr>
    @php $currentRow++; @endphp

    <tr><td colspan="7"></td></tr>
    @php $currentRow++; @endphp

    {{-- Terms --}}
    <tr>
        <td colspan="7" style="font-family: Arial; vertical-align: middle;">-All above-prices are in accordance with INCOTERMS 2000.</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="7" style="font-family: Arial; vertical-align: middle;">1. <u>Prices</u>: in US Dollars, FOB, USA.</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="4" style="font-family: Arial; vertical-align: middle;">2. <u>Delivery time</u>: ASAP</td>
        <td colspan="3" style="font-family: Arial; vertical-align: middle;">3. <u>Payment term</u>: by T/T, NET 30 DAYS.</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="4" style="font-family: Arial; vertical-align: middle;">4. <u>Warranty time</u>: in accordance with manufacturer's standard.</td>
        <td colspan="3" style="font-family: Arial; vertical-align: middle;">5. <u>Partial Shipment</u>: Allowed</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="7" style="font-family: Arial; vertical-align: middle;">6. <u>Documents required</u>: Invoice, Packing list, CO ( third-party's documents is acceptable ).</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="7" style="font-family: Arial; vertical-align: middle;">7. <u>Forwarder</u>: AOF CARGO LOGISTICS CO.,LTD.</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="7" style="font-family: Arial; vertical-align: middle;">8. <u>Force Majeure</u>: Seller is not liable for any penalty of delay delivery of all or any of this contract caused by any contingency beyond its control or beyond the control of, or covered by its contract to furnish this commodity. Such contingencies shall include, but not limited to governmental or other restraints affecting shipment or credit, strikes, lockouts, floods, droughts, short or reduced supply of fuel or raw materials declared or undeclared wars revolutions, fires cyclones or hurricanes, epidemics or any other acts of good or force majeure.</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="7" style="font-family: Arial; vertical-align: middle;">9. <u>Arbitration</u>: In case of disputes and it contracting parties can not reach an amicable settlement of the claim within 30 days from its occurrence the case will be transferred to the arbitration chamber of Hochiminh City Chamber of Commerce for final settlement. A panel of 3 Arbitration will be formed, each party appointing one arbitrator and both shall appointing a third one as president of panel. The decision taken by the arbitration panel shall be final and binding.</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="7" style="font-family: Arial; vertical-align: middle;">10. <u>Other terms</u>: Any amendment of the terms and conditions of this contract must be agreed by both sides in writing. This contract is made into 04 originals in English language. Each party keeps 02 originals.</td>
    </tr>
    @php $currentRow++; @endphp

    <tr><td colspan="7"></td></tr>
    @php $currentRow++; @endphp

    {{-- Signatures --}}
    <tr>
        <td colspan="4" style="font-weight: bold; font-family: Arial; text-align: center; vertical-align: middle;">confirm by {{ $config->seller_name }}</td>
        <td colspan="3" style="font-weight: bold; font-family: Arial; text-align: center; vertical-align: middle;">{{ $company->company_full_name }}</td>
    </tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="4" style="font-weight: bold; font-family: Arial; text-align: center; vertical-align: middle;">Manager</td>
        <td colspan="3" style="font-weight: bold; font-family: Arial; text-align: center; vertical-align: middle;">{{ $company->signer_title }}</td>
    </tr>
    @php $currentRow++; @endphp

    {{-- signature spacer --}}
    <tr><td colspan="7" style="height: 50px;"></td></tr>
    @php $currentRow++; @endphp

    <tr>
        <td colspan="4"></td>
        <td colspan="3" style="font-weight: bold; font-family: Arial; text-align: center; vertical-align: middle;">{{ $company->signer_name }}</td>
    </tr>
    @php $currentRow++; @endphp
</table>
</body>
</html>
