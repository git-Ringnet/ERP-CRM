@extends('layouts.app')

@section('title', 'Chi tiết báo giá - ' . $quotation->code)
@section('page-title', 'Chi tiết báo giá: ' . $quotation->code)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Quotation Info -->
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Thông tin báo giá</h3>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Mã báo giá:</span>
                    <span class="font-medium ml-2">{{ $quotation->code }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Ngày tạo:</span>
                    <span class="font-medium ml-2">{{ $quotation->date->format('d/m/Y') }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Trạng thái:</span>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ml-2 {{ $quotation->status_color }}">
                        {{ $quotation->status_label }}
                    </span>
                </div>
                <div>
                    <span class="text-gray-500">Khách hàng:</span>
                    <span class="font-medium ml-2">{{ $quotation->customer_name }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Hạn báo giá:</span>
                    <span class="font-medium ml-2 {{ $quotation->isExpired() ? 'text-red-600' : '' }}">
                        {{ $quotation->valid_until->format('d/m/Y') }}
                        @if($quotation->isExpired())
                            <i class="fas fa-exclamation-circle ml-1" title="Đã hết hạn"></i>
                        @endif
                    </span>
                </div>
                <div class="col-span-2 border-t border-gray-150 pt-2 mt-1">
                    <span class="text-gray-500">Người phụ trách (P.I.C):</span>
                    @if($quotation->contact)
                        <span class="font-medium ml-2">{{ $quotation->contact->name }}</span>
                        @if($quotation->contact->position)
                            <span class="text-xs text-gray-500 ml-1">({{ $quotation->contact->position }})</span>
                        @endif
                        <span class="text-xs text-gray-500 ml-4">
                            <i class="fas fa-envelope mr-1 text-gray-400"></i>{{ $quotation->contact->email ?: 'N/A' }}
                            <i class="fas fa-phone ml-3 mr-1 text-gray-400"></i>{{ $quotation->contact->phone ?: 'N/A' }}
                        </span>
                    @else
                        <span class="text-red-500 ml-2 font-medium">Chưa chọn P.I.C</span>
                    @endif
                </div>
                @if($quotation->project)
                <div class="col-span-2">
                    <span class="text-gray-500">Dự án liên kết:</span>
                    <a href="{{ route('projects.show', $quotation->project_id) }}" class="font-bold text-purple-700 hover:text-purple-900 hover:underline ml-2">
                        <i class="fas fa-project-diagram mr-1"></i>{{ $quotation->project->code }} - {{ $quotation->project->name }}
                    </a>
                </div>
                @endif
                <div class="col-span-2">
                    <span class="text-gray-500">Tiêu đề:</span>
                    <span class="font-medium ml-2">{{ $quotation->title }}</span>
                </div>
            </div>
        </div>

        <!-- Products -->
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Chi tiết sản phẩm</h3>
            
        @php
            $allColumns = $quotation->custom_columns ?? ['product_id', 'quantity', 'price', 'vat', 'row_total'];
            if (!is_array($allColumns)) {
                $allColumns = [];
            }
            $allColumns = array_values(array_filter($allColumns, fn($col) => strtolower(str_replace(['_', ' '], '', $col)) !== 'pricelist'));
            if (!in_array('product_id', $allColumns)) {
                $allColumns = array_merge(['product_id', 'quantity', 'price', 'vat', 'row_total'], $allColumns);
            } else {
                if (!in_array('row_total', $allColumns)) {
                    $allColumns[] = 'row_total';
                }
            }
            $customColumns = array_values(array_filter($allColumns, fn($col) => !in_array($col, ['product_id', 'quantity', 'price', 'vat', 'row_total'])));
        @endphp
        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full min-w-[800px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">#</th>
                        @foreach($allColumns as $colName)
                            @if($colName === 'product_id')
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                            @elseif($colName === 'quantity')
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">SL</th>
                            @elseif($colName === 'price')
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Đơn giá</th>
                            @elseif(strtolower(str_replace(['_', ' '], '', $colName)) === 'pricelist')
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Pricelist ($)</th>
                            @elseif($colName === 'vat')
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">VAT (%)</th>
                            @elseif($colName === 'row_total')
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thành tiền (gồm VAT)</th>
                            @else
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $colName }}</th>
                            @endif
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($quotation->items as $index => $item)
                    @php
                        $itemEffectiveVat = $item->vat < 0 ? 0 : $item->vat;
                        $itemTotalWithVat = $item->total * (1 + $itemEffectiveVat / 100);
                    @endphp
                    <tr>
                        <td class="px-4 py-3 text-center text-sm">{{ $index + 1 }}</td>
                        @foreach($allColumns as $colName)
                            @if($colName === 'product_id')
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $item->product_code ?: $item->product_name }}</div>
                                    @if($item->product_code)
                                        <div class="text-xs text-gray-500" style="white-space: pre-line;">{{ $item->description ?: $item->product_name }}</div>
                                    @else
                                        @if($item->description)
                                            <div class="text-xs text-gray-500" style="white-space: pre-line;">{{ $item->description }}</div>
                                        @endif
                                    @endif
                                </td>
                            @elseif($colName === 'quantity')
                                <td class="px-4 py-3 text-center">{{ $item->quantity }}</td>
                            @elseif($colName === 'price')
                                <td class="px-4 py-3 text-right">
                                    @if($quotation->currency && !$quotation->currency->is_base)
                                        <div class="font-medium text-gray-900">{{ $quotation->currency->symbol ?? $quotation->currency->code }} {{ number_format($item->price, $quotation->currency->decimal_places ?? 2) }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">{{ number_format($item->price * ($quotation->exchange_rate ?: 1)) }} đ</div>
                                    @else
                                        {{ number_format($item->price) }} đ
                                    @endif
                                </td>
                            @elseif(strtolower(str_replace(['_', ' '], '', $colName)) === 'pricelist')
                                <td class="px-4 py-3 text-right text-sm">
                                    <span class="font-semibold text-blue-600">${{ number_format($item->pricelist_price ?? 0, 2) }}</span>
                                </td>
                            @elseif($colName === 'vat')
                                <td class="px-4 py-3 text-center">{{ $item->vat == -1 ? 'KCT' : (float)$item->vat . '%' }}</td>
                            @elseif($colName === 'row_total')
                                <td class="px-4 py-3 text-right font-medium">
                                    @if($quotation->currency && !$quotation->currency->is_base)
                                        <div class="font-medium text-gray-900">{{ $quotation->currency->symbol ?? $quotation->currency->code }} {{ number_format($itemTotalWithVat, $quotation->currency->decimal_places ?? 2) }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">{{ number_format($itemTotalWithVat * ($quotation->exchange_rate ?: 1)) }} đ</div>
                                    @else
                                        {{ number_format($itemTotalWithVat) }} đ
                                    @endif
                                </td>
                            @else
                                <td class="px-4 py-3 text-left text-sm text-gray-600">
                                    {{ $item->custom_fields[$colName] ?? '' }}
                                </td>
                            @endif
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="md:hidden space-y-3">
            @foreach($quotation->items as $index => $item)
            @php
                $itemEffectiveVat = $item->vat < 0 ? 0 : $item->vat;
                $itemTotalWithVat = $item->total * (1 + $itemEffectiveVat / 100);
            @endphp
            <div class="bg-gray-50 p-3 rounded-lg">
                <div class="font-medium text-gray-900">{{ $item->product_code ?: $item->product_name }}</div>
                @if($item->product_code)
                    <div class="text-xs text-gray-500 mt-0.5" style="white-space: pre-line;">{{ $item->description ?: $item->product_name }}</div>
                @else
                    @if($item->description)
                        <div class="text-xs text-gray-500 mt-0.5" style="white-space: pre-line;">{{ $item->description }}</div>
                    @endif
                @endif
                @if(!empty($customColumns))
                    <div class="text-xs text-gray-600 mt-1.5 space-y-0.5 bg-white p-2 rounded border border-gray-100">
                        @foreach($customColumns as $colName)
                            @if(!empty($item->custom_fields[$colName]))
                                <div><span class="font-medium text-gray-500">{{ $colName }}:</span> {{ $item->custom_fields[$colName] }}</div>
                            @endif
                        @endforeach
                    </div>
                @endif
                <div class="text-sm text-gray-500 mt-1 flex justify-between">
                    <span>
                        @if($quotation->currency && !$quotation->currency->is_base)
                            SL: {{ $item->quantity }} x {{ $quotation->currency->symbol ?? $quotation->currency->code }} {{ number_format($item->price, $quotation->currency->decimal_places ?? 2) }}
                        @else
                            SL: {{ $item->quantity }} x {{ number_format($item->price) }} đ
                        @endif
                    </span>
                    <span class="text-blue-600">VAT: {{ $item->vat == -1 ? 'KCT' : (float)$item->vat . '%' }}</span>
                </div>
                <div class="text-sm font-medium text-right mt-2 border-t pt-1">
                    @if($quotation->currency && !$quotation->currency->is_base)
                        = {{ $quotation->currency->symbol ?? $quotation->currency->code }} {{ number_format($itemTotalWithVat, $quotation->currency->decimal_places ?? 2) }}
                    @else
                        = {{ number_format($itemTotalWithVat) }} đ
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <!-- Totals -->
        @php
                $isForeign = $quotation->currency && !$quotation->currency->is_base;
                $rate = $quotation->exchange_rate ?: 1;
                $decimals = $quotation->currency->decimal_places ?? 2;
                $symbol = $quotation->currency->symbol ?? $quotation->currency->code ?? '';

                $subtotalForeign = $isForeign ? $quotation->items->sum('total') : $quotation->subtotal;
                $subtotalVnd = $isForeign ? round($subtotalForeign * $rate) : $quotation->subtotal;

                $subtotalWithVatForeign = 0;
                foreach ($quotation->items as $item) {
                    $itemEffectiveVat = $item->vat < 0 ? 0 : $item->vat;
                    $subtotalWithVatForeign += $item->total * (1 + $itemEffectiveVat / 100);
                }
                $subtotalWithVatVnd = $isForeign ? round($subtotalWithVatForeign * $rate) : $subtotalWithVatForeign;

                $discountForeign = round($subtotalForeign * ($quotation->discount / 100), $decimals);
                $discountVnd = $isForeign ? round($discountForeign * $rate) : round($subtotalVnd * ($quotation->discount / 100));

                $vatForeign = $quotation->items->sum('vat_amount');
                $vatVnd = $quotation->vat_amount ?: round($vatForeign * $rate);

                $totalForeign = $quotation->total_foreign ?: round($subtotalForeign - $discountForeign + $vatForeign, $decimals);
                $totalVnd = $quotation->total;
            @endphp
            <div class="mt-4 border-t pt-4">
                <div class="flex justify-end">
                    <div class="w-full md:w-[420px] space-y-2 text-sm">
                        <div class="flex justify-between items-start">
                            <span class="text-gray-500">Tổng tiền hàng (chưa VAT):</span>
                            <div class="text-right font-medium">
                                @if($isForeign)
                                    <div>{{ $symbol }} {{ number_format($subtotalForeign, $decimals) }}</div>
                                    <div class="text-xs text-gray-400 font-normal">{{ number_format($subtotalVnd) }} đ</div>
                                @else
                                    <span>{{ number_format($subtotalVnd) }} đ</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex justify-between items-start">
                            <span class="text-gray-500">Tổng tiền hàng (đã gồm VAT):</span>
                            <div class="text-right font-medium">
                                @if($isForeign)
                                    <div>{{ $symbol }} {{ number_format($subtotalWithVatForeign, $decimals) }}</div>
                                    <div class="text-xs text-gray-400 font-normal">{{ number_format($subtotalWithVatVnd) }} đ</div>
                                @else
                                    <span>{{ number_format($subtotalWithVatVnd) }} đ</span>
                                @endif
                            </div>
                        </div>
                        @if($quotation->discount > 0)
                        <div class="flex justify-between items-start text-red-600">
                            <span class="text-gray-500">Chiết khấu ({{ (float)$quotation->discount }}%):</span>
                            <div class="text-right">
                                @if($isForeign)
                                    <div>-{{ $symbol }} {{ number_format($discountForeign, $decimals) }}</div>
                                    <div class="text-xs text-red-400 font-normal">-{{ number_format($discountVnd) }} đ</div>
                                @else
                                    <span>-{{ number_format($discountVnd) }} đ</span>
                                @endif
                            </div>
                        </div>
                        @endif
                        <div class="flex justify-between items-start">
                            <span class="text-gray-500">Thuế VAT:</span>
                            <div class="text-right font-medium text-blue-600">
                                @if($isForeign)
                                    <div>{{ $symbol }} {{ number_format($vatForeign, $decimals) }}</div>
                                    <div class="text-xs text-gray-400 font-normal">{{ number_format($vatVnd) }} đ</div>
                                @else
                                    <span>{{ number_format($vatVnd) }} đ</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex justify-between items-start font-bold text-lg border-t pt-2">
                            <span>Tổng cộng (gồm VAT & CK):</span>
                            <div class="text-right text-primary">
                                @if($isForeign)
                                    <div>{{ $symbol }} {{ number_format($totalForeign, $decimals) }}</div>
                                    <div class="text-sm font-normal text-blue-500">≈ {{ number_format($totalVnd) }} đ</div>
                                @else
                                    <span>{{ number_format($totalVnd) }} đ</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Terms -->
        @if($quotation->delivery_time || $quotation->warranty_terms || !empty($quotation->note_array) || !empty($quotation->disclaimer_array))
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Điều khoản & Ghi chú</h3>
            <div class="space-y-4 text-sm">
                @if($quotation->delivery_time)
                <div>
                    <span class="text-gray-500 font-medium">Thời gian giao hàng:</span>
                    <p class="mt-1 text-gray-800 leading-relaxed">{{ $quotation->delivery_time }}</p>
                </div>
                @endif
                @if(!empty($quotation->warranty_terms_array))
                <div>
                    <span class="text-gray-500 font-medium">Bảo hành:</span>
                    <div class="mt-1.5 space-y-1.5">
                        @foreach($quotation->warranty_terms_array as $i => $item)
                            <div class="flex items-start gap-1">
                                <span class="font-semibold text-gray-500 flex-shrink-0 w-6">({{ $i + 1 }})</span>
                                <span class="text-gray-800 leading-relaxed">{{ $item }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
                @if(!empty($quotation->note_array))
                <div>
                    <span class="text-gray-500 font-medium">Ghi chú:</span>
                    <div class="mt-1.5 space-y-1.5">
                        @foreach($quotation->note_array as $i => $item)
                            <div class="flex items-start gap-1">
                                <span class="font-semibold text-gray-500 flex-shrink-0 w-6">({{ $i + 1 }})</span>
                                <span class="text-gray-800 leading-relaxed">{{ $item }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
                @if(!empty($quotation->disclaimer_array))
                <div class="pt-3 border-t border-dashed border-gray-200">
                    <span class="text-amber-700 font-medium"><i class="fas fa-exclamation-triangle mr-1"></i> Cảnh báo / Lưu ý:</span>
                    <div class="mt-1.5 space-y-1.5">
                        @foreach($quotation->disclaimer_array as $i => $item)
                            <div class="flex items-start gap-1">
                                <span class="font-semibold text-amber-600 flex-shrink-0 w-6">({{ $i + 1 }})</span>
                                <span class="text-gray-800 leading-relaxed">{{ $item }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Workflow Guide -->
        <div class="bg-gradient-to-b from-blue-50 to-white rounded-lg shadow-sm p-4 sm:p-6 border border-blue-100">
            <h3 class="text-sm font-semibold text-blue-800 mb-3"><i class="fas fa-route mr-1"></i> Quy trình bán hàng</h3>
            @php
                $isConverted = (bool) $quotation->converted_to_sale_id;
                $sale = $isConverted ? \App\Models\Sale::find($quotation->converted_to_sale_id) : null;
                $isPnlApproved = $sale && $sale->pl_status === 'approved';
                $isPnlPending = $sale && $sale->pl_status === 'pending';
                $isSaleApproved = $sale && $sale->status === 'approved';
            @endphp
            <div class="space-y-1.5 text-xs">
                {{-- Step 1: Quotation --}}
                <div class="flex items-start gap-2 {{ !$isConverted ? 'text-blue-700 font-semibold' : 'text-green-600' }}">
                    <span class="w-5 h-5 rounded-full {{ $isConverted ? 'bg-green-500' : 'bg-blue-600 animate-pulse' }} text-white flex items-center justify-center text-[10px] flex-shrink-0 mt-0.5">
                        @if($isConverted) <i class="fas fa-check text-[8px]"></i> @else 1 @endif
                    </span>
                    <span>Gửi báo giá, tư vấn thêm SP</span>
                </div>
                {{-- Step 2: BOM --}}
                <div class="flex items-start gap-2 {{ !$isConverted ? 'text-blue-700 font-semibold' : 'text-green-600' }}">
                    <span class="w-5 h-5 rounded-full {{ $isConverted ? 'bg-green-500' : 'bg-blue-600 animate-pulse' }} text-white flex items-center justify-center text-[10px] flex-shrink-0 mt-0.5">
                        @if($isConverted) <i class="fas fa-check text-[8px]"></i> @else 2 @endif
                    </span>
                    <span>KH chốt BOM, gửi báo giá chính thức</span>
                </div>
                {{-- Step 3: Convert to Sale --}}
                <div class="flex items-start gap-2 {{ $isConverted ? 'text-green-600' : 'text-gray-400' }}">
                    <span class="w-5 h-5 rounded-full {{ $isConverted ? 'bg-green-500' : 'bg-gray-300' }} text-white flex items-center justify-center text-[10px] flex-shrink-0 mt-0.5">
                        @if($isConverted) <i class="fas fa-check text-[8px]"></i> @else 3 @endif
                    </span>
                    <span>KH chốt giá → Lập HĐMB/XNĐH + PNL</span>
                </div>
                {{-- Step 4: Legal Review --}}
                <div class="flex items-start gap-2 {{ $isPnlApproved || $isSaleApproved ? 'text-green-600' : ($isPnlPending ? 'text-yellow-700 font-semibold' : 'text-gray-400') }}">
                    <span class="w-5 h-5 rounded-full {{ $isPnlApproved || $isSaleApproved ? 'bg-green-500' : ($isPnlPending ? 'bg-yellow-500 animate-pulse' : 'bg-gray-300') }} text-white flex items-center justify-center text-[10px] flex-shrink-0 mt-0.5">
                        @if($isPnlApproved || $isSaleApproved) <i class="fas fa-check text-[8px]"></i> @else 4 @endif
                    </span>
                    <span>Legal review hợp đồng & P&L</span>
                </div>
                {{-- Step 5: BOD Review --}}
                <div class="flex items-start gap-2 {{ $isPnlApproved || $isSaleApproved ? 'text-green-600' : ($isPnlPending ? 'text-yellow-700 font-semibold' : 'text-gray-400') }}">
                    <span class="w-5 h-5 rounded-full {{ $isPnlApproved || $isSaleApproved ? 'bg-green-500' : ($isPnlPending ? 'bg-yellow-500 animate-pulse' : 'bg-gray-300') }} text-white flex items-center justify-center text-[10px] flex-shrink-0 mt-0.5">
                        @if($isPnlApproved || $isSaleApproved) <i class="fas fa-check text-[8px]"></i> @else 5 @endif
                    </span>
                    <span>BOD phê duyệt</span>
                </div>
                {{-- Step 6: Purchase Order --}}
                <div class="flex items-start gap-2 {{ $isSaleApproved ? 'text-blue-700 font-semibold' : 'text-gray-400' }}">
                    <span class="w-5 h-5 rounded-full {{ $isSaleApproved ? 'bg-blue-600 animate-pulse' : 'bg-gray-300' }} text-white flex items-center justify-center text-[10px] flex-shrink-0 mt-0.5">6</span>
                    <span>Gửi yêu cầu đặt hàng</span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Thao tác</h3>
            <div class="space-y-3">
                @if(!$quotation->converted_to_sale_id)
                    @can('update', $quotation)
                    <a href="{{ route('quotations.edit', $quotation) }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors">
                        <i class="fas fa-edit mr-2"></i> Chỉnh sửa / Tư vấn thêm SP
                    </a>
                    @endcan
                @endif

                <a href="{{ route('quotations.print', $quotation) }}" target="_blank" 
                   class="w-full inline-flex items-center justify-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-print mr-2"></i> In & Gửi báo giá chính thức
                </a>

                <a href="{{ route('quotations.export-single', $quotation) }}" 
                   class="w-full inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-file-excel mr-2"></i> Xuất Excel
                </a>

                @can('update', $quotation)
                <button type="button" onclick="openSendEmailModal()"
                   class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium shadow-sm">
                    <i class="fas fa-paper-plane mr-2"></i> Gửi email báo giá cho KH
                </button>
                @endcan

                <form action="{{ route('quotations.duplicate', $quotation) }}" method="POST">
                    @csrf
                    <input type="hidden" name="redirect_to" value="show">
                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-copy mr-2"></i> Nhân bản báo giá
                    </button>
                </form>

                @if(!$quotation->converted_to_sale_id)
                    <form action="{{ route('quotations.convert', $quotation) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-colors"
                                onclick="return confirm('KH đã chốt giá? Chuyển thành đơn hàng / XNĐH?')">
                            <i class="fas fa-file-contract mr-2"></i> KH chốt giá → Lập HĐMB/XNĐH
                        </button>
                    </form>
                @endif

                @can('delete', $quotation)
                @if($quotation->canBeDeleted())
                    <form action="{{ route('quotations.destroy', $quotation) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa báo giá này?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                            <i class="fas fa-trash mr-2"></i> Xóa báo giá
                        </button>
                    </form>
                @endif
                @endcan
            </div>
        </div>

        <!-- Converted Sale -->
        @if($quotation->converted_to_sale_id)
        <div class="bg-green-50 rounded-lg shadow-sm p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-green-800 mb-2"><i class="fas fa-check-circle mr-1"></i> Đã lập đơn hàng</h3>
            <p class="text-sm text-green-600 mb-2">Báo giá đã được chuyển thành đơn hàng / XNĐH.</p>
            <a href="{{ route('sales.show', $quotation->converted_to_sale_id) }}" class="inline-flex items-center text-green-700 hover:text-green-900 font-medium text-sm">
                <i class="fas fa-external-link-alt mr-1"></i> Xem đơn hàng / XNĐH
            </a>
        </div>
        @endif

        <!-- Back Button -->
        <a href="{{ route('quotations.index') }}" 
           class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại danh sách
        </a>
    </div>
</div>

<!-- Send Quotation Email Modal -->
@can('update', $quotation)
@php
    $defaultRecipient = old('to', $quotation->contact?->email ?? $quotation->customer?->email ?? '');
    $defaultSubject = old('subject', 'Báo giá ' . $quotation->code . ($quotation->title ? ' - ' . $quotation->title : ''));
    $salesUser = auth()->user();
    
    // Mailto link for direct Outlook / native client opening
    $mailtoRecipient = $defaultRecipient;
    $mailtoSubject = rawurlencode($defaultSubject);
    $mailtoBody = rawurlencode("Kính gửi " . ($quotation->contact?->name ?: ($quotation->customer?->name ?: 'Quý khách hàng')) . ",\n\nTôi xin gửi Quý khách bảng báo giá chi tiết:\n- Mã báo giá: " . $quotation->code . "\n- Nội dung: " . $quotation->title . "\n- Tổng giá trị: " . number_format($quotation->total) . " đ\n- Hiệu lực đến: " . ($quotation->valid_until ? $quotation->valid_until->format('d/m/Y') : 'Thỏa thuận') . "\n\n(Tệp báo giá chi tiết đã được xuất kèm theo)\n\nTrân trọng,\n" . $salesUser->name . "\n" . ($salesUser->phone ? 'SĐT: ' . $salesUser->phone . "\n" : '') . "Email: " . $salesUser->email);
    $mailtoUrl = "mailto:{$mailtoRecipient}?subject={$mailtoSubject}&body={$mailtoBody}";
@endphp
<div id="sendEmailModal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-700 text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center text-lg">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <div>
                    <h3 class="font-bold text-base">Gửi email báo giá cho khách hàng</h3>
                    <p class="text-xs text-blue-100">Báo giá số: {{ $quotation->code }}</p>
                </div>
            </div>
            <button type="button" onclick="closeSendEmailModal()" class="text-white/80 hover:text-white text-lg p-1">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <form action="{{ route('quotations.send-email', $quotation) }}" method="POST" class="flex flex-col flex-1 overflow-y-auto">
            @csrf
            <div class="p-6 space-y-4">
                <!-- Info note about sales email and reply-to -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-3.5 flex items-start gap-3">
                    <i class="fas fa-info-circle text-blue-600 text-base mt-0.5 flex-shrink-0"></i>
                    <div class="text-xs text-blue-900 leading-relaxed">
                        <strong class="font-bold">Cơ chế gửi email tự động:</strong> Email được gửi đi qua cổng Mail của hệ thống. Phản hồi của khách hàng (<strong>Reply-To</strong>) sẽ tự động chuyển về hòm thư cá nhân của bạn: <span class="font-bold text-blue-700 underline">{{ $salesUser->email }}</span>.
                    </div>
                </div>

                <!-- Recipient Email -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                        Email người nhận <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="to" required value="{{ $defaultRecipient }}"
                        placeholder="VD: khachhang@gmail.com (hoặc nhiều email cách nhau dấu phẩy)"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    @if($quotation->contact && $quotation->contact->email)
                        <p class="text-[11px] text-gray-400 mt-1">Đã tự động điền email của P.I.C: {{ $quotation->contact->name }} ({{ $quotation->contact->email }})</p>
                    @endif
                </div>

                <!-- CC Me Checkbox -->
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="cc_me" id="inputCcMe" value="1" checked
                        class="rounded text-blue-600 focus:ring-blue-500">
                    <label for="inputCcMe" class="text-xs text-gray-700 cursor-pointer select-none">
                        Gửi một bản sao (CC) về email của tôi (<strong>{{ $salesUser->email }}</strong>) để lưu trữ
                    </label>
                </div>

                <!-- Subject -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                        Tiêu đề email <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="subject" required value="{{ $defaultSubject }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <!-- Custom Message -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                        Lời nhắn gửi khách hàng (Tùy chọn)
                    </label>
                    <textarea name="message" rows="3" placeholder="Ví dụ: Dạ em chào Anh/Chị, em xin gửi bảng báo giá các thiết bị như đã trao đổi. Anh/Chị xem qua và phản hồi giúp em nhé ạ..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">{{ old('message') }}</textarea>
                </div>

                <!-- Attach Excel Checkbox -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="attach_excel" value="1" checked class="rounded text-blue-600 focus:ring-blue-500">
                        <span class="text-xs font-semibold text-gray-800">
                            <i class="fas fa-file-excel text-green-600 mr-1"></i> Tự động đính kèm file Excel báo giá chi tiết (<code>bao-gia-{{ $quotation->code }}.xlsx</code>)
                        </span>
                    </label>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
                <!-- Option to open local Outlook -->
                <a href="{{ $mailtoUrl }}" onclick="downloadQuoteAndOpenClient(event, '{{ route('quotations.export-single', $quotation) }}', '{{ $mailtoUrl }}')"
                    class="text-xs text-blue-700 hover:text-blue-900 font-semibold flex items-center gap-1.5" title="Mở trực tiếp phần mềm Outlook trên máy của bạn">
                    <i class="fas fa-envelope-open-text text-base"></i> Hoặc mở Outlook trên máy để gửi
                </a>

                <div class="flex items-center gap-2 ml-auto">
                    <button type="button" onclick="closeSendEmailModal()"
                        class="px-4 py-2 border border-gray-300 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-100 transition-colors">
                        Hủy
                    </button>
                    <button type="submit"
                        class="inline-flex items-center px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition-colors shadow-sm">
                        <i class="fas fa-paper-plane mr-1.5"></i> Gửi email ngay
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function openSendEmailModal() {
        const modal = document.getElementById('sendEmailModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeSendEmailModal() {
        const modal = document.getElementById('sendEmailModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function downloadQuoteAndOpenClient(event, downloadUrl, mailtoUrl) {
        event.preventDefault();
        // Trigger download of the excel file first
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.setAttribute('download', '');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Open local mail client after short delay
        setTimeout(function() {
            window.location.href = mailtoUrl;
        }, 500);
    }
</script>
@endcan
@endsection
