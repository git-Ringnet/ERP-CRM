@extends('layouts.app')

@section('title', 'Chi tiết đơn hàng')
@section('page-title', 'Chi tiết đơn hàng: ' . $sale->code)

@section('content')
<div class="space-y-4">
    <!-- Actions -->
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('sales.index') }}" 
           class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại
        </a>
        <a href="{{ route('sales.edit', $sale->id) }}" 
           class="inline-flex items-center px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors">
            <i class="fas fa-edit mr-2"></i> Sửa
        </a>
        <a href="{{ route('sales.pdf', $sale->id) }}" target="_blank"
           class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
            <i class="fas fa-file-pdf mr-2"></i> Xuất hóa đơn
        </a>
        <form action="{{ route('sales.email', $sale->id) }}" method="POST" class="inline">
            @csrf
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                <i class="fas fa-envelope mr-2"></i> Gửi Email
            </button>
        </form>
        @if($sale->debt_amount > 0)
        <button onclick="openPaymentModal()" 
                class="inline-flex items-center px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors">
            <i class="fas fa-money-bill mr-2"></i> Ghi nhận thanh toán
        </button>
        @endif
    </div>

    <!-- Status Actions -->
    @if($sale->status !== 'cancelled')
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm font-medium text-gray-700">Cập nhật trạng thái:</span>
            
            @if($sale->status === 'pending')
            <form action="{{ route('sales.updateStatus', $sale->id) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="approved">
                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-blue-500 text-white text-sm rounded-lg hover:bg-blue-600 transition-colors">
                    <i class="fas fa-check mr-1"></i> Duyệt đơn
                </button>
            </form>
            <form action="{{ route('sales.updateStatus', $sale->id) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="cancelled">
                <button type="submit" onclick="return confirm('Bạn có chắc muốn hủy đơn hàng này?')" class="inline-flex items-center px-3 py-1.5 bg-red-500 text-white text-sm rounded-lg hover:bg-red-600 transition-colors">
                    <i class="fas fa-times mr-1"></i> Hủy đơn
                </button>
            </form>
            @endif

            @if($sale->status === 'approved')
            <form action="{{ route('sales.updateStatus', $sale->id) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="shipping">
                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-purple-500 text-white text-sm rounded-lg hover:bg-purple-600 transition-colors">
                    <i class="fas fa-truck mr-1"></i> Giao hàng
                </button>
            </form>
            <form action="{{ route('sales.updateStatus', $sale->id) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="cancelled">
                <button type="submit" onclick="return confirm('Bạn có chắc muốn hủy đơn hàng này?')" class="inline-flex items-center px-3 py-1.5 bg-red-500 text-white text-sm rounded-lg hover:bg-red-600 transition-colors">
                    <i class="fas fa-times mr-1"></i> Hủy đơn
                </button>
            </form>
            @endif

            @if($sale->status === 'shipping')
            <form action="{{ route('sales.updateStatus', $sale->id) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="completed">
                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-green-500 text-white text-sm rounded-lg hover:bg-green-600 transition-colors">
                    <i class="fas fa-check-double mr-1"></i> Hoàn thành
                </button>
            </form>
            @endif

            @if($sale->status === 'completed')
            <span class="text-sm text-green-600 font-medium">
                <i class="fas fa-check-circle mr-1"></i> Đơn hàng đã hoàn thành
            </span>
            @endif
        </div>
        
        <!-- Status Flow -->
        <div class="mt-4 flex items-center text-xs text-gray-500">
            <span class="px-2 py-1 rounded {{ $sale->status === 'pending' ? 'bg-yellow-100 text-yellow-800 font-medium' : 'bg-gray-100' }}">Chờ duyệt</span>
            <i class="fas fa-arrow-right mx-2"></i>
            <span class="px-2 py-1 rounded {{ $sale->status === 'approved' ? 'bg-blue-100 text-blue-800 font-medium' : 'bg-gray-100' }}">Đã duyệt</span>
            <i class="fas fa-arrow-right mx-2"></i>
            <span class="px-2 py-1 rounded {{ $sale->status === 'shipping' ? 'bg-purple-100 text-purple-800 font-medium' : 'bg-gray-100' }}">Đang giao</span>
            <i class="fas fa-arrow-right mx-2"></i>
            <span class="px-2 py-1 rounded {{ $sale->status === 'completed' ? 'bg-green-100 text-green-800 font-medium' : 'bg-gray-100' }}">Hoàn thành</span>
        </div>
    </div>
    @else
    <div class="bg-red-50 rounded-lg p-4">
        <span class="text-red-700 font-medium"><i class="fas fa-ban mr-2"></i>Đơn hàng đã bị hủy</span>
    </div>
    @endif

    <!-- Sale Info -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Thông tin đơn hàng</h3>
                <dl class="space-y-2">
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Mã đơn:</dt>
                        <dd class="font-medium text-gray-900">{{ $sale->code }}</dd>
                    </div>
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Loại:</dt>
                        <dd>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $sale->type == 'project' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $sale->type_label }}
                            </span>
                        </dd>
                    </div>
                    @if($sale->project)
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Dự án:</dt>
                        <dd>
                            <a href="{{ route('projects.show', $sale->project_id) }}" class="text-purple-600 hover:underline font-medium">
                                <i class="fas fa-project-diagram mr-1"></i>
                                {{ $sale->project->code }} - {{ $sale->project->name }}
                            </a>
                        </dd>
                    </div>
                    @endif
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Ngày tạo:</dt>
                        <dd class="text-gray-900">{{ $sale->date->format('d/m/Y') }}</dd>
                    </div>
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Trạng thái:</dt>
                        <dd>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $sale->status_color }}">
                                {{ $sale->status_label }}
                            </span>
                        </dd>
                    </div>
                    @if($sale->currency && !$sale->currency->is_base)
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Tiền tệ:</dt>
                        <dd>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-money-bill-wave mr-1"></i>{{ $sale->currency->code }} - {{ $sale->currency->name_vi }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Tỷ giá:</dt>
                        <dd class="font-medium text-gray-900">1 {{ $sale->currency->code }} = {{ rtrim(rtrim(number_format($sale->exchange_rate, 2, ',', '.'), '0'), ',') }} VND</dd>
                    </div>
                    @endif
                </dl>
            </div>
            
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Thông tin khách hàng</h3>
                <dl class="space-y-2">
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Khách hàng:</dt>
                        <dd class="font-medium text-gray-900">{{ $sale->customer_name }}</dd>
                    </div>
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Nhân viên kinh doanh:</dt>
                        <dd class="font-medium text-primary">{{ $sale->user->name ?? 'N/A' }}</dd>
                    </div>
                    @if($sale->customer)
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Email:</dt>
                        <dd class="text-gray-900">{{ $sale->customer->email }}</dd>
                    </div>
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Điện thoại:</dt>
                        <dd class="text-gray-900">{{ $sale->customer->phone }}</dd>
                    </div>
                    @endif
                    @if($sale->delivery_address)
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Địa chỉ giao:</dt>
                        <dd class="text-gray-900">{{ $sale->delivery_address }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <!-- Products -->
    @php
        $isForeign = $sale->currency && !$sale->currency->is_base;
        $rate = $sale->exchange_rate ?: 1;
        $decimals = $sale->currency->decimal_places ?? 2;
        $symbol = $sale->currency->symbol ?? $sale->currency->code ?? '';

        // Subtotal
        $subtotalVnd = $sale->subtotal;
        $subtotalForeign = $isForeign ? round($sale->subtotal / $rate, $decimals) : $subtotalVnd;

        // Discount Amount
        $discountForeign = round($subtotalForeign * ($sale->discount / 100), $decimals);
        $discountVnd = $isForeign ? round($discountForeign * $rate) : round($subtotalVnd * ($sale->discount / 100));

        // VAT Amount
        $afterDiscountForeign = $subtotalForeign - $discountForeign;
        $vatForeign = round($afterDiscountForeign * ($sale->vat / 100), $decimals);
        $vatVnd = $isForeign ? round($vatForeign * $rate) : round(($subtotalVnd - $discountVnd) * ($sale->vat / 100));

        // Total
        $totalForeign = $sale->total_foreign ?? ($isForeign ? round($afterDiscountForeign + $vatForeign, $decimals) : $sale->total);
        $totalVnd = $sale->total;
    @endphp
    
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900">Chi tiết sản phẩm</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">SL</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Giá bán</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Giá vốn</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Bảo hành</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thành tiền</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng vốn</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Lợi nhuận</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($sale->items as $index => $item)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $index + 1 }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $item->product_name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($item->quantity) }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($sale->currency && !$sale->currency->is_base)
                                <div class="text-sm font-medium text-gray-900">{{ $sale->currency->symbol ?? $sale->currency->code }} {{ number_format($item->price, $sale->currency->decimal_places ?? 2) }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ number_format($item->price * ($sale->exchange_rate ?: 1)) }} đ</div>
                            @else
                                <div class="text-sm font-medium text-gray-900">{{ number_format($item->price) }} đ</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-orange-600 text-right">{{ number_format($item->cost_price) }} đ</td>
                        <td class="px-4 py-3 text-sm text-center">
                            @if($item->warranty_months)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    <i class="fas fa-shield-alt mr-1"></i>{{ $item->warranty_months }} tháng
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($sale->currency && !$sale->currency->is_base)
                                <div class="text-sm font-medium text-gray-900">{{ $sale->currency->symbol ?? $sale->currency->code }} {{ number_format($item->total, $sale->currency->decimal_places ?? 2) }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ number_format($item->total * ($sale->exchange_rate ?: 1)) }} đ</div>
                            @else
                                <div class="text-sm font-medium text-gray-900">{{ number_format($item->total) }} đ</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-orange-600 text-right">{{ number_format($item->cost_total) }} đ</td>
                        <td class="px-4 py-3 text-sm text-right font-medium {{ $item->profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($item->profit) }} đ
                            <span class="text-xs">({{ number_format($item->profit_percent, 1) }}%)</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-white">
                    <tr>
                        <td colspan="6" class="px-4 py-3 text-sm font-medium text-gray-700 text-right">Tổng tiền hàng:</td>
                        <td colspan="3" class="px-4 py-3 text-right">
                            @if($isForeign)
                                <div class="text-sm font-semibold text-gray-900">{{ $symbol }} {{ number_format($subtotalForeign, $decimals) }}</div>
                                <div class="text-xs text-gray-500">{{ number_format($subtotalVnd) }} đ</div>
                            @else
                                <div class="text-sm font-semibold text-gray-900">{{ number_format($subtotalVnd) }} đ</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6" class="px-4 py-3 text-sm font-medium text-gray-700 text-right">Chiết khấu ({{ $sale->discount }}%):</td>
                        <td colspan="3" class="px-4 py-3 text-right">
                            @if($isForeign)
                                <div class="text-sm font-semibold text-red-600">-{{ $symbol }} {{ number_format($discountForeign, $decimals) }}</div>
                                <div class="text-xs text-red-400 mt-0.5">-{{ number_format($discountVnd) }} đ</div>
                            @else
                                <div class="text-sm font-semibold text-red-600">-{{ number_format($discountVnd) }} đ</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6" class="px-4 py-3 text-sm font-medium text-gray-700 text-right">VAT ({{ $sale->vat }}%):</td>
                        <td colspan="3" class="px-4 py-3 text-right">
                            @if($isForeign)
                                <div class="text-sm font-semibold text-gray-900">{{ $symbol }} {{ number_format($vatForeign, $decimals) }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ number_format($vatVnd) }} đ</div>
                            @else
                                <div class="text-sm font-semibold text-gray-900">{{ number_format($vatVnd) }} đ</div>
                            @endif
                        </td>
                    </tr>
                    <tr class="border-t-2 border-gray-300 bg-blue-50">
                        <td colspan="6" class="px-4 py-3 text-base font-bold text-gray-900 text-right">Tổng cộng:</td>
                        <td colspan="3" class="px-4 py-3 text-right">
                            @if($isForeign)
                                <div class="text-base font-bold text-blue-700">{{ $symbol }} {{ number_format($totalForeign, $decimals) }}</div>
                                <div class="text-xs font-normal text-blue-500 mt-1">
                                    ≈ {{ number_format($totalVnd) }} đ (Tỷ giá: {{ number_format($rate, 0, '', ',') }})
                                </div>
                            @else
                                <div class="text-base font-bold text-blue-700">{{ number_format($totalVnd) }} đ</div>
                            @endif
                        </td>
                    </tr>
                    @if($sale->cost > 0)
                    <tr class="bg-orange-50">
                        <td colspan="6" class="px-4 py-3 text-sm font-medium text-gray-700 text-right">Chi phí bán hàng:</td>
                        <td colspan="3" class="px-4 py-3 text-right">
                            @if($isForeign)
                                <div class="text-sm font-semibold text-orange-700">{{ $symbol }} {{ number_format($sale->cost / $rate, $decimals) }}</div>
                                <div class="text-xs text-orange-600 mt-0.5">{{ number_format($sale->cost) }} đ</div>
                            @else
                                <div class="text-sm font-semibold text-orange-700">{{ number_format($sale->cost) }} đ</div>
                            @endif
                        </td>
                    </tr>
                    <tr class="bg-green-50">
                        <td colspan="6" class="px-4 py-3 text-sm font-bold text-gray-900 text-right">Lợi nhuận (Margin):</td>
                        <td colspan="3" class="px-4 py-3 text-right">
                            @if($isForeign)
                                <div class="text-sm font-bold text-green-700">{{ $sale->margin < 0 ? '-' : '' }}{{ $symbol }} {{ number_format(abs($sale->margin) / $rate, $decimals) }} <span class="text-xs font-normal">({{ number_format($sale->margin_percent, 2) }}%)</span></div>
                                <div class="text-xs text-green-600 mt-0.5">{{ number_format($sale->margin) }} đ</div>
                            @else
                                <div class="text-sm font-bold text-green-700">{{ number_format($sale->margin) }} đ ({{ number_format($sale->margin_percent, 2) }}%)</div>
                            @endif
                        </td>
                    </tr>
                    @endif
                    @if($sale->paid_amount > 0 || $sale->debt_amount > 0)
                    <tr class="border-t bg-white">
                        <td colspan="5" class="px-4 py-3 text-sm font-medium text-gray-700 text-right">Đã thanh toán:</td>
                        <td colspan="3" class="px-4 py-3 text-sm font-semibold text-green-600 text-right">{{ number_format($sale->paid_amount) }} đ</td>
                    </tr>
                    <tr class="bg-red-50">
                        <td colspan="5" class="px-4 py-3 text-sm font-bold text-gray-900 text-right">Công nợ còn lại:</td>
                        <td colspan="4" class="px-4 py-3 text-sm font-bold text-red-600 text-right">{{ number_format($sale->debt_amount) }} đ</td>
                    </tr>
                    @endif
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Margin Analysis -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b bg-gradient-to-r from-green-50 to-blue-50">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-chart-pie mr-2 text-green-600"></i>
                Phân tích Margin theo đơn hàng
            </h3>
            <p class="text-sm text-gray-500 mt-1">Thể hiện giá bán, giá vốn và các chi phí liên quan</p>
        </div>
        
        <div class="p-4 sm:p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Left: Revenue & Cost Summary -->
                <div class="space-y-4">
                    <h4 class="font-semibold text-gray-800 border-b pb-2">Tổng quan doanh thu & chi phí</h4>
                    
                    <!-- Revenue -->
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-blue-700">
                                <i class="fas fa-coins mr-1"></i> Doanh thu (Giá bán)
                            </span>
                            @if($isForeign)
                                <div class="text-right">
                                    <div class="text-lg font-bold text-blue-700">{{ $symbol }} {{ number_format($totalForeign, $decimals) }}</div>
                                    <div class="text-sm font-medium text-blue-600 mt-0.5">≈ {{ number_format($totalVnd) }} đ</div>
                                </div>
                            @else
                                <span class="text-lg font-bold text-blue-700">{{ number_format($totalVnd) }} đ</span>
                            @endif
                        </div>
                        <div class="text-xs text-blue-600 mt-2">
                            @if($isForeign)
                                Tổng tiền hàng: {{ $symbol }} {{ number_format($subtotalForeign, $decimals) }} | 
                                CK: -{{ $symbol }} {{ number_format($discountForeign, $decimals) }} | 
                                VAT: +{{ $symbol }} {{ number_format($vatForeign, $decimals) }}
                                <br><span class="opacity-75">(Quy đổi: Tiền hàng {{ number_format($subtotalVnd) }} đ | CK -{{ number_format($discountVnd) }} đ | VAT +{{ number_format($vatVnd) }} đ)</span>
                            @else
                                Tổng tiền hàng: {{ number_format($subtotalVnd) }} đ | 
                                CK: -{{ number_format($discountVnd) }} đ | 
                                VAT: +{{ number_format($vatVnd) }} đ
                            @endif
                        </div>
                    </div>
                    
                    <!-- Cost of Goods -->
                    @php
                        $totalCostOfGoods = $sale->items->sum('cost_total');
                    @endphp
                    <div class="bg-orange-50 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-orange-700">
                                <i class="fas fa-box mr-1"></i> Giá vốn hàng bán
                            </span>
                            @if($isForeign)
                                <div class="text-right">
                                    <div class="text-lg font-bold text-orange-700">{{ $symbol }} {{ number_format($totalCostOfGoods / $rate, $decimals) }}</div>
                                    <div class="text-sm font-medium text-orange-600 mt-0.5">≈ {{ number_format($totalCostOfGoods) }} đ</div>
                                </div>
                            @else
                                <span class="text-lg font-bold text-orange-700">{{ number_format($totalCostOfGoods) }} đ</span>
                            @endif
                        </div>
                        <div class="text-xs text-orange-600">
                            Tổng giá vốn của {{ $sale->items->count() }} sản phẩm
                        </div>
                    </div>
                    
                    <!-- Operating Expenses -->
                    <div class="bg-red-50 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-red-700">
                                <i class="fas fa-receipt mr-1"></i> Chi phí bán hàng
                            </span>
                            @if($isForeign)
                                <div class="text-right">
                                    <div class="text-lg font-bold text-red-700">{{ $symbol }} {{ number_format($sale->cost / $rate, $decimals) }}</div>
                                    <div class="text-sm font-medium text-red-600 mt-0.5">≈ {{ number_format($sale->cost) }} đ</div>
                                </div>
                            @else
                                <span class="text-lg font-bold text-red-700">{{ number_format($sale->cost) }} đ</span>
                            @endif
                        </div>
                        @if($sale->expenses->count() > 0)
                        <div class="mt-2 space-y-1">
                            @foreach($sale->expenses as $expense)
                            <div class="flex justify-between text-xs">
                                <span class="text-red-600">
                                    <i class="fas {{ $expense->type_icon }} mr-1"></i>
                                    {{ $expense->type_label }}: {{ $expense->description }}
                                </span>
                                <span class="text-red-700 font-medium">
                                    @if($isForeign)
                                        {{ $symbol }} {{ number_format($expense->amount / $rate, $decimals) }} <span class="text-[10px] text-red-500 ml-1">({{ number_format($expense->amount) }} đ)</span>
                                    @else
                                        {{ number_format($expense->amount) }} đ
                                    @endif
                                </span>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-xs text-red-600">Chưa có chi phí nào được ghi nhận</div>
                        @endif
                    </div>
                </div>
                
                <!-- Right: Margin Calculation -->
                <div class="space-y-4">
                    <h4 class="font-semibold text-gray-800 border-b pb-2">Tính toán Margin</h4>
                    
                    <!-- Gross Margin -->
                    @php
                        $grossMargin = $sale->total - $totalCostOfGoods;
                        $grossMarginPercent = $sale->total > 0 ? ($grossMargin / $sale->total) * 100 : 0;
                    @endphp
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-yellow-700">
                                <i class="fas fa-calculator mr-1"></i> Lợi nhuận gộp (Gross Margin)
                            </span>
                            <div class="text-right">
                                @if($isForeign)
                                    <div class="text-lg font-bold {{ $grossMargin >= 0 ? 'text-yellow-700' : 'text-red-700' }}">
                                        {{ $grossMargin < 0 ? '-' : '' }}{{ $symbol }} {{ number_format(abs($grossMargin) / $rate, $decimals) }}
                                    </div>
                                    <div class="text-sm font-medium {{ $grossMargin >= 0 ? 'text-yellow-600' : 'text-red-600' }} mt-0.5">
                                        ≈ {{ number_format($grossMargin) }} đ
                                    </div>
                                @else
                                    <span class="text-lg font-bold {{ $grossMargin >= 0 ? 'text-yellow-700' : 'text-red-700' }}">
                                        {{ number_format($grossMargin) }} đ
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="text-xs text-yellow-600">
                            @if($isForeign)
                                = {{ number_format($totalForeign, $decimals) }} - {{ number_format($totalCostOfGoods / $rate, $decimals) }}
                                <br><span class="opacity-75">(= Doanh thu - Giá vốn = {{ number_format($sale->total) }} - {{ number_format($totalCostOfGoods) }} đ)</span>
                            @else
                                = Doanh thu - Giá vốn = {{ number_format($sale->total) }} - {{ number_format($totalCostOfGoods) }}
                            @endif
                        </div>
                        <div class="mt-2">
                            <div class="flex justify-between text-xs mb-1">
                                <span>Tỷ lệ lợi nhuận gộp</span>
                                <span class="font-medium">{{ number_format($grossMarginPercent, 2) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full {{ $grossMarginPercent >= 0 ? 'bg-yellow-500' : 'bg-red-500' }}" 
                                     style="width: {{ min(abs($grossMarginPercent), 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Net Margin -->
                    @php
                        $netMargin = $grossMargin - $sale->cost;
                        $netMarginPercent = $sale->total > 0 ? ($netMargin / $sale->total) * 100 : 0;
                    @endphp
                    <div class="rounded-lg p-4 {{ $netMargin >= 0 ? 'bg-green-50' : 'bg-red-50' }}">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium {{ $netMargin >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                <i class="fas fa-chart-line mr-1"></i> Lợi nhuận ròng (Net Margin)
                            </span>
                            <div class="text-right">
                                @if($isForeign)
                                    <div class="text-xl font-bold {{ $netMargin >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                        {{ $netMargin < 0 ? '-' : '' }}{{ $symbol }} {{ number_format(abs($netMargin) / $rate, $decimals) }}
                                    </div>
                                    <div class="text-sm font-medium {{ $netMargin >= 0 ? 'text-green-600' : 'text-red-600' }} mt-0.5">
                                        ≈ {{ number_format($netMargin) }} đ
                                    </div>
                                @else
                                    <span class="text-xl font-bold {{ $netMargin >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                        {{ number_format($netMargin) }} đ
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="text-xs {{ $netMargin >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            @if($isForeign)
                                = {{ $grossMargin < 0 ? '-' : '' }}{{ number_format(abs($grossMargin) / $rate, $decimals) }} - {{ number_format($sale->cost / $rate, $decimals) }}
                                <br><span class="opacity-75">(= Lợi nhuận gộp - Chi phí = {{ number_format($grossMargin) }} - {{ number_format($sale->cost) }} đ)</span>
                            @else
                                = Lợi nhuận gộp - Chi phí = {{ number_format($grossMargin) }} - {{ number_format($sale->cost) }}
                            @endif
                        </div>
                        <div class="mt-2">
                            <div class="flex justify-between text-xs mb-1">
                                <span>Tỷ lệ lợi nhuận ròng</span>
                                <span class="font-medium">{{ number_format($netMarginPercent, 2) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full {{ $netMarginPercent >= 0 ? 'bg-green-500' : 'bg-red-500' }}" 
                                     style="width: {{ min(abs($netMarginPercent), 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Summary Box -->
                    <div class="border-2 {{ $netMargin >= 0 ? 'border-green-300 bg-green-100' : 'border-red-300 bg-red-100' }} rounded-lg p-4">
                        <div class="text-center">
                            <div class="text-sm font-medium {{ $netMargin >= 0 ? 'text-green-800' : 'text-red-800' }}">
                                {{ $netMargin >= 0 ? 'LỢI NHUẬN' : 'LỖ' }}
                            </div>
                            @if($isForeign)
                                <div class="text-2xl font-bold {{ $netMargin >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $symbol }} {{ number_format(abs($netMargin) / $rate, $decimals) }}
                                </div>
                                <div class="text-sm {{ $netMargin >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                                    ≈ {{ number_format(abs($netMargin)) }} đ ({{ number_format(abs($netMarginPercent), 2) }}% trên doanh thu)
                                </div>
                            @else
                                <div class="text-2xl font-bold {{ $netMargin >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                    {{ number_format(abs($netMargin)) }} đ
                                </div>
                                <div class="text-sm {{ $netMargin >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ({{ number_format(abs($netMarginPercent), 2) }}% trên doanh thu)
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Expense Breakdown by Type -->
            @if($sale->expenses->count() > 0)
            <div class="mt-6 pt-6 border-t">
                <h4 class="font-semibold text-gray-800 mb-4">Chi tiết chi phí theo loại</h4>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @php
                        $expenseTypes = ['shipping' => 'Vận chuyển', 'marketing' => 'Marketing', 'commission' => 'Hoa hồng', 'other' => 'Khác'];
                        $expenseIcons = ['shipping' => 'fa-truck', 'marketing' => 'fa-bullhorn', 'commission' => 'fa-percentage', 'other' => 'fa-receipt'];
                        $expenseColors = ['shipping' => 'blue', 'marketing' => 'orange', 'commission' => 'green', 'other' => 'gray'];
                    @endphp
                    @foreach($expenseTypes as $type => $label)
                    @php
                        $typeAmount = $sale->expenses->where('type', $type)->sum('amount');
                    @endphp
                    <div class="bg-{{ $expenseColors[$type] }}-50 rounded-lg p-3 text-center">
                        <i class="fas {{ $expenseIcons[$type] }} text-{{ $expenseColors[$type] }}-500 text-xl mb-1"></i>
                        <div class="text-xs text-{{ $expenseColors[$type] }}-600">{{ $label }}</div>
                        @if($isForeign)
                            <div class="text-sm font-bold text-{{ $expenseColors[$type] }}-700">{{ $symbol }} {{ number_format($typeAmount / $rate, $decimals) }}</div>
                            <div class="text-[10px] text-{{ $expenseColors[$type] }}-500 mt-0.5">{{ number_format($typeAmount) }} đ</div>
                        @else
                            <div class="text-sm font-bold text-{{ $expenseColors[$type] }}-700">{{ number_format($typeAmount) }} đ</div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Note -->
    @if($sale->note)
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Ghi chú</h3>
        <p class="text-gray-700">{{ $sale->note }}</p>
    </div>
    @endif

    <!-- Payment Modal -->
    <div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Ghi nhận thanh toán</h3>
                <form action="{{ route('sales.payment', $sale->id) }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tiền tệ <span class="text-red-500">*</span></label>
                                <select name="currency_id" id="payment_currency_id" required onchange="handlePaymentCurrencyChange()"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                    @foreach($currencies as $currency)
                                        <option value="{{ $currency->id }}" data-code="{{ $currency->code }}" {{ $currency->id == ($sale->currency_id ?? $baseCurrencyId) ? 'selected' : '' }}>
                                            {{ $currency->code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div id="payment_exchange_rate_container" class="{{ ($sale->currency_id ?? $baseCurrencyId) == $baseCurrencyId ? 'hidden' : '' }}">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tỷ giá</label>
                                <input type="number" name="exchange_rate" id="payment_exchange_rate" step="0.000001" value="{{ $sale->exchange_rate ?? 1 }}"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền thanh toán <span class="text-red-500">*</span></label>
                            <input type="number" name="amount" id="payment_amount" required min="0" step="0.01"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            <p class="text-xs text-gray-500 mt-1">Còn nợ: <span id="payment_debt_display">{{ number_format($sale->debt_amount) }}</span> đ</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ngày thanh toán <span class="text-red-500">*</span></label>
                            <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phương thức <span class="text-red-500">*</span></label>
                            <select name="payment_method" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="cash">Tiền mặt</option>
                                <option value="bank_transfer">Chuyển khoản</option>
                                <option value="credit_card">Thẻ tín dụng</option>
                                <option value="other">Khác</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                            <textarea name="note" rows="2"
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                        </div>
                    </div>
                    <div class="flex gap-2 mt-6">
                        <button type="button" onclick="closePaymentModal()"
                                class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                            Hủy
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                            Xác nhận
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openPaymentModal() {
    document.getElementById('paymentModal').classList.remove('hidden');
    handlePaymentCurrencyChange();
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

function handlePaymentCurrencyChange() {
    const select = document.getElementById('payment_currency_id');
    const currencyId = select.value;
    const baseCurrencyId = @json($baseCurrencyId);
    const container = document.getElementById('payment_exchange_rate_container');
    const rateInput = document.getElementById('payment_exchange_rate');
    
    if (currencyId == baseCurrencyId) {
        container.classList.add('hidden');
        rateInput.value = 1;
    } else {
        container.classList.remove('hidden');
        // If it matches the sale currency, use sale rate, otherwise fetch latest
        if (currencyId == @json($sale->currency_id ?? null)) {
            rateInput.value = @json($sale->exchange_rate ?? 1);
        } else {
            // Fetch current rate
            fetch(`/exchange-rates/latest/${currencyId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.rate) rateInput.value = data.rate;
                });
        }
    }
}

// Close modal when clicking outside
document.getElementById('paymentModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closePaymentModal();
    }
});
</script>
@endpush
@endsection
