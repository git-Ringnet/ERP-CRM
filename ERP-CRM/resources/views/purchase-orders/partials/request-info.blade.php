{{--
    Thông tin Sales khai lúc gửi Yêu cầu đặt hàng (S/N + hạn, SI, EU) cho 1 dòng PO.
    Hiện cho PO Team ngay trên dòng sản phẩm, trước khi hãng giao hàng / nhập S/N.
    Tham số: $reqItem (SaleOrderRequestItem|null)
--}}
@if($reqItem)
    @php
        $reqSerials = array_values(array_filter(array_map('trim', preg_split('/[,;\n\r]+/', (string) $reqItem->serial_number)), 'strlen'));
        $reqExpiry = $reqItem->serial_expiry_list;
        $reqSharedExp = $reqItem->exp_date ? $reqItem->exp_date->format('d/m/Y') : null;
        $reqHasInfo = $reqSerials || $reqSharedExp || $reqExpiry || filled($reqItem->si_name) || filled($reqItem->eu_name_mst);
    @endphp
    @if($reqHasInfo)
        <div class="mt-1.5 p-2 rounded border border-teal-200 bg-teal-50 text-[10px] text-teal-900 space-y-0.5">
            <div class="font-bold text-teal-700">
                <i class="fas fa-clipboard-list mr-1"></i>Yêu cầu của Sales{{ $reqItem->saleOrderRequest?->code ? ' (' . $reqItem->saleOrderRequest->code . ')' : '' }}
            </div>
            @if($reqSerials)
                <div>
                    <span class="font-semibold">S/N yêu cầu ({{ count($reqSerials) }}):</span>
                    @foreach($reqSerials as $reqSn)
                        <div class="font-mono pl-2">
                            {{ $reqSn }}@if($reqExpiry[$reqSn] ?? $reqSharedExp)<span class="font-sans text-teal-700"> — Exp: {{ $reqExpiry[$reqSn] ?? $reqSharedExp }}</span>@endif
                        </div>
                    @endforeach
                </div>
            @elseif($reqSharedExp)
                <div><span class="font-semibold">Exp:</span> {{ $reqSharedExp }}</div>
            @endif
            @if(filled($reqItem->si_name))
                <div><span class="font-semibold">SI:</span> {{ $reqItem->si_name }}{{ $reqItem->pos_id ? ' · POS ID: ' . $reqItem->pos_id : '' }}</div>
            @endif
            @if(filled($reqItem->eu_name_mst))
                <div><span class="font-semibold">EU:</span> {{ $reqItem->eu_name_mst }}</div>
            @endif
            @if(filled($reqItem->address))
                <div><span class="font-semibold">Địa chỉ:</span> {{ $reqItem->address }}</div>
            @endif
        </div>
    @endif
@endif
