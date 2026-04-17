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
                <div class="col-span-2">
                    <span class="text-gray-500">Tiêu đề:</span>
                    <span class="font-medium ml-2">{{ $quotation->title }}</span>
                </div>
            </div>
        </div>

        <!-- Products -->
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Chi tiết sản phẩm</h3>
            
            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">SL</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Đơn giá</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($quotation->items as $index => $item)
                        <tr>
                            <td class="px-4 py-3 text-center text-sm">{{ $index + 1 }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $item->product_name }}</div>
                                @if($item->product_code)
                                    <div class="text-xs text-gray-500">{{ $item->product_code }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($quotation->currency && !$quotation->currency->is_base)
                                    <div class="font-medium text-gray-900">{{ $quotation->currency->symbol ?? $quotation->currency->code }} {{ number_format($item->price, $quotation->currency->decimal_places ?? 2) }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ number_format($item->price * ($quotation->exchange_rate ?: 1), 0, ',', '.') }} đ</div>
                                @else
                                    {{ number_format($item->price, 0, ',', '.') }} đ
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-medium">
                                @if($quotation->currency && !$quotation->currency->is_base)
                                    <div class="font-medium text-gray-900">{{ $quotation->currency->symbol ?? $quotation->currency->code }} {{ number_format($item->total, $quotation->currency->decimal_places ?? 2) }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ number_format($item->total * ($quotation->exchange_rate ?: 1), 0, ',', '.') }} đ</div>
                                @else
                                    {{ number_format($item->total, 0, ',', '.') }} đ
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden space-y-3">
                @foreach($quotation->items as $index => $item)
                <div class="bg-gray-50 p-3 rounded-lg">
                    <div class="font-medium text-gray-900">{{ $item->product_name }}</div>
                    <div class="text-sm text-gray-500 mt-1">
                        @if($quotation->currency && !$quotation->currency->is_base)
                            SL: {{ $item->quantity }} x {{ $quotation->currency->symbol ?? $quotation->currency->code }} {{ number_format($item->price, $quotation->currency->decimal_places ?? 2) }} <span class="text-xs">({{ number_format($item->price * ($quotation->exchange_rate ?: 1), 0, ',', '.') }} đ)</span>
                        @else
                            SL: {{ $item->quantity }} x {{ number_format($item->price, 0, ',', '.') }} đ
                        @endif
                    </div>
                    <div class="text-sm font-medium text-right mt-2">
                        @if($quotation->currency && !$quotation->currency->is_base)
                            = {{ $quotation->currency->symbol ?? $quotation->currency->code }} {{ number_format($item->total, $quotation->currency->decimal_places ?? 2) }} <div class="text-xs text-gray-500">({{ number_format($item->total * ($quotation->exchange_rate ?: 1), 0, ',', '.') }} đ)</div>
                        @else
                            = {{ number_format($item->total, 0, ',', '.') }} đ
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

                $discountForeign = round($subtotalForeign * ($quotation->discount / 100), $decimals);
                $discountVnd = $isForeign ? round($discountForeign * $rate) : round($subtotalVnd * ($quotation->discount / 100));

                $afterDiscountForeign = $subtotalForeign - $discountForeign;
                $vatForeign = round($afterDiscountForeign * ($quotation->vat / 100), $decimals);
                $vatVnd = $isForeign ? round($vatForeign * $rate) : round(($subtotalVnd - $discountVnd) * ($quotation->vat / 100));

                $totalForeign = $quotation->total_foreign ?? ($isForeign ? round($afterDiscountForeign + $vatForeign, $decimals) : $quotation->total);
                $totalVnd = $quotation->total;
            @endphp
            <div class="mt-4 border-t pt-4">
                <div class="flex justify-end">
                    <div class="w-full md:w-64 space-y-2 text-sm">
                        <div class="flex justify-between items-start">
                            <span class="text-gray-500">Tổng tiền hàng:</span>
                            <div class="text-right">
                                @if($isForeign)
                                    <div>{{ $symbol }} {{ number_format($subtotalForeign, $decimals) }}</div>
                                    <div class="text-xs text-gray-400">{{ number_format($subtotalVnd, 0, ',', '.') }} đ</div>
                                @else
                                    <span>{{ number_format($subtotalVnd, 0, ',', '.') }} đ</span>
                                @endif
                            </div>
                        </div>
                        @if($quotation->discount > 0)
                        <div class="flex justify-between items-start">
                            <span class="text-gray-500">Chiết khấu ({{ $quotation->discount }}%):</span>
                            <div class="text-right text-red-600">
                                @if($isForeign)
                                    <div>-{{ $symbol }} {{ number_format($discountForeign, $decimals) }}</div>
                                    <div class="text-xs text-red-400">-{{ number_format($discountVnd, 0, ',', '.') }} đ</div>
                                @else
                                    <span>-{{ number_format($discountVnd, 0, ',', '.') }} đ</span>
                                @endif
                            </div>
                        </div>
                        @endif
                        <div class="flex justify-between items-start">
                            <span class="text-gray-500">VAT ({{ $quotation->vat }}%):</span>
                            <div class="text-right">
                                @if($isForeign)
                                    <div>{{ $symbol }} {{ number_format($vatForeign, $decimals) }}</div>
                                    <div class="text-xs text-gray-400">{{ number_format($vatVnd, 0, ',', '.') }} đ</div>
                                @else
                                    <span>{{ number_format($vatVnd, 0, ',', '.') }} đ</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex justify-between items-start font-bold text-lg border-t pt-2">
                            <span>Tổng cộng:</span>
                            <div class="text-right text-primary">
                                @if($isForeign)
                                    <div>{{ $symbol }} {{ number_format($totalForeign, $decimals) }}</div>
                                    <div class="text-sm font-normal text-blue-500">≈ {{ number_format($totalVnd, 0, ',', '.') }} đ</div>
                                @else
                                    <span>{{ number_format($totalVnd, 0, ',', '.') }} đ</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Terms -->
        @if($quotation->payment_terms || $quotation->delivery_time || $quotation->note)
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Điều khoản & Ghi chú</h3>
            <div class="space-y-3 text-sm">
                @if($quotation->payment_terms)
                <div>
                    <span class="text-gray-500 font-medium">Điều khoản thanh toán:</span>
                    <p class="mt-1">{{ $quotation->payment_terms }}</p>
                </div>
                @endif
                @if($quotation->delivery_time)
                <div>
                    <span class="text-gray-500 font-medium">Thời gian giao hàng:</span>
                    <p class="mt-1">{{ $quotation->delivery_time }}</p>
                </div>
                @endif
                @if($quotation->note)
                <div>
                    <span class="text-gray-500 font-medium">Ghi chú:</span>
                    <p class="mt-1">{{ $quotation->note }}</p>
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

                @if(!$quotation->converted_to_sale_id && !$quotation->isExpired())
                    <form action="{{ route('quotations.convert', $quotation) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-colors"
                                onclick="return confirm('KH đã chốt giá? Chuyển thành đơn hàng / XNĐH?')">
                            <i class="fas fa-file-contract mr-2"></i> KH chốt giá → Lập HĐMB/XNĐH
                        </button>
                    </form>
                @endif

                @if(!$quotation->converted_to_sale_id)
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
                @endif
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
@endsection
