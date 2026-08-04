@extends('layouts.app')

@section('title', 'Theo dõi tiến độ đơn hàng')
@section('page-title', 'Theo dõi tiến độ đơn hàng')

@section('content')
<div class="space-y-6">
    <!-- Summary Stats (9-Step Timeline Process Pipeline) -->
    @php
        $timelineStats = [
            'pr' => ['label' => 'PR (Chờ đặt)', 'icon' => 'fas fa-file-alt', 'text' => 'text-gray-700', 'bg' => 'bg-gray-100 text-gray-700', 'border' => 'border-gray-300'],
            'po' => ['label' => 'PO (Đặt hàng)', 'icon' => 'fas fa-shopping-cart', 'text' => 'text-blue-700', 'bg' => 'bg-blue-100 text-blue-700', 'border' => 'border-blue-300'],
            'vendor_confirm' => ['label' => 'Hãng xác nhận', 'icon' => 'fas fa-user-check', 'text' => 'text-cyan-700', 'bg' => 'bg-cyan-100 text-cyan-700', 'border' => 'border-cyan-300'],
            'production' => ['label' => 'Đang sản xuất', 'icon' => 'fas fa-industry', 'text' => 'text-purple-700', 'bg' => 'bg-purple-100 text-purple-700', 'border' => 'border-purple-300'],
            'mfg_export' => ['label' => 'Xuất kho hãng', 'icon' => 'fas fa-sign-out-alt', 'text' => 'text-pink-700', 'bg' => 'bg-pink-100 text-pink-700', 'border' => 'border-pink-300'],
            'transit' => ['label' => 'Đang vận chuyển', 'icon' => 'fas fa-shipping-fast', 'text' => 'text-amber-700', 'bg' => 'bg-amber-100 text-amber-700', 'border' => 'border-amber-300'],
            'arrived_vn' => ['label' => 'Đã về VN', 'icon' => 'fas fa-plane-arrival', 'text' => 'text-teal-700', 'bg' => 'bg-teal-100 text-teal-700', 'border' => 'border-teal-300'],
            'warehouse_received' => ['label' => 'Đã nhập kho', 'icon' => 'fas fa-warehouse', 'text' => 'text-emerald-700', 'bg' => 'bg-emerald-100 text-emerald-700', 'border' => 'border-emerald-300'],
            'delivered_sales' => ['label' => 'Đã giao Sales', 'icon' => 'fas fa-check-circle', 'text' => 'text-green-700', 'bg' => 'bg-green-100 text-green-700', 'border' => 'border-green-300'],
        ];
    @endphp
    <div class="bg-white rounded-lg shadow p-3.5">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
            @foreach($timelineStats as $key => $cfg)
                @php
                    $count = $statsCounts[$key] ?? 0;
                @endphp
                <div class="flex items-center gap-3 p-2.5 rounded-lg bg-gray-50 border-l-4 {{ $cfg['border'] }} hover:bg-gray-100/80 transition">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm font-bold {{ $cfg['bg'] }} shrink-0 shadow-sm">
                        {{ $count }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-gray-800 leading-tight">
                            {{ $cfg['label'] }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4">
        <form action="{{ route('sales.order-tracking') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Mã Sale Order</label>
                <input type="text" name="sale_code" value="{{ request('sale_code') }}" 
                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500" placeholder="SO-...">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Part Number</label>
                <input type="text" name="part_number" value="{{ request('part_number') }}" 
                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500" placeholder="Nhập part number...">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Nhà cung cấp</label>
                <select name="vendor_id" class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Tất cả --</option>
                    @foreach($vendors as $v)
                        <option value="{{ $v->id }}" {{ request('vendor_id') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Trạng thái</label>
                <select name="status_filter" class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Tất cả --</option>
                    <option value="pr" {{ request('status_filter') == 'pr' ? 'selected' : '' }}>Chờ đặt hàng</option>
                    <option value="po" {{ request('status_filter') == 'po' ? 'selected' : '' }}>Đặt hàng (PO)</option>
                    <option value="vendor_confirm" {{ request('status_filter') == 'vendor_confirm' ? 'selected' : '' }}>Hãng xác nhận</option>
                    <option value="production" {{ request('status_filter') == 'production' ? 'selected' : '' }}>Đang sản xuất</option>
                    <option value="mfg_export" {{ request('status_filter') == 'mfg_export' ? 'selected' : '' }}>Xuất kho hãng</option>
                    <option value="transit" {{ request('status_filter') == 'transit' ? 'selected' : '' }}>Đang vận chuyển</option>
                    <option value="arrived_vn" {{ request('status_filter') == 'arrived_vn' ? 'selected' : '' }}>Đã về VN</option>
                    <option value="warehouse_received" {{ request('status_filter') == 'warehouse_received' ? 'selected' : '' }}>Đã nhập kho</option>
                    <option value="delivered_sales" {{ request('status_filter') == 'delivered_sales' ? 'selected' : '' }}>Đã giao Sales</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-3.5 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-search mr-1"></i> Lọc
                </button>
                <a href="{{ route('sales.order-tracking') }}" class="px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition">
                    Xóa
                </a>
            </div>
        </form>
    </div>

    <!-- Results Table (Consolidated 5 Columns Layout) -->
    <div class="bg-white rounded-lg shadow overflow-hidden" x-data="{ showPoModal: false, selectedPo: null }">
        <div class="px-5 py-3 border-b bg-gradient-to-r from-emerald-50 to-cyan-50 flex justify-between items-center">
            <div>
                <h3 class="font-semibold text-gray-800 text-sm flex items-center">
                    <i class="fas fa-boxes mr-2 text-emerald-600"></i>
                    Theo dõi hàng về — Group theo Sale Order + Sản phẩm
                </h3>
            </div>
            <span class="text-xs text-gray-500">Dữ liệu từ tất cả PR & PO</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3.5 py-2.5 text-left font-semibold text-gray-600 uppercase w-1/4">Sale Order & Sản phẩm</th>
                        <th class="px-3.5 py-2.5 text-center font-semibold text-gray-600 uppercase w-1/6">SL & Tiến độ</th>
                        <th class="px-3.5 py-2.5 text-left font-semibold text-gray-600 uppercase w-1/4">Trạng thái & Timeline</th>
                        <th class="px-3.5 py-2.5 text-left font-semibold text-gray-600 uppercase w-1/6">Mốc thời gian</th>
                        <th class="px-3.5 py-2.5 text-left font-semibold text-gray-600 uppercase w-1/4">Nguyên nhân & Đơn PO</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($rows as $row)
                        <tr class="hover:bg-gray-50 transition-colors {{ ($row['completion_percent'] ?? 0) >= 100 ? 'bg-green-50/20' : '' }}">
                            <!-- Col 1: Sale Order & Part Number -->
                            <td class="px-3.5 py-2.5 align-top">
                                <div class="font-bold text-blue-600 text-xs">
                                    <a href="{{ route('sales.show', $row['sale_id']) }}" class="hover:underline flex items-center gap-1">
                                        <i class="fas fa-file-invoice text-blue-500"></i>
                                        <span>{{ $row['sale_code'] }}</span>
                                    </a>
                                </div>
                                <div class="font-semibold text-gray-900 mt-1 text-xs">{{ $row['part_number'] }}</div>
                                <div class="text-[11px] text-gray-500 flex flex-wrap items-center gap-1.5 mt-0.5">
                                    <span>{{ $row['vendor_name'] }}</span>
                                    @foreach($row['pr_codes'] as $prCode)
                                        <span class="bg-gray-100 text-gray-600 px-1 py-0.5 rounded text-[10px]">PR: {{ $prCode }}</span>
                                    @endforeach
                                </div>
                            </td>

                            <!-- Col 2: Quantity & Progress -->
                            <td class="px-3.5 py-2.5 align-top text-center">
                                <div class="inline-flex items-center justify-center gap-1.5 bg-gray-50 border border-gray-200 px-2 py-1 rounded text-xs font-semibold">
                                    <span class="text-gray-700" title="Số lượng Sales/PR yêu cầu">Cần: <b class="text-gray-900">{{ $row['requested'] ?? 0 }}</b></span>
                                    <span class="text-gray-300">|</span>
                                    <span class="text-blue-700" title="Số lượng Purchasing đã lên PO">Đặt: <b>{{ $row['ordered'] ?? 0 }}</b></span>
                                    <span class="text-gray-300">|</span>
                                    <span class="text-green-700" title="Số lượng đã nhập kho thực tế">Về: <b>{{ $row['received'] ?? 0 }}</b></span>
                                </div>
                                <div class="mt-1 flex items-center justify-center gap-1.5">
                                    <span class="text-[10px] text-gray-500 font-semibold">% Về kho:</span>
                                    <span class="text-[11px] font-bold {{ ($row['completion_percent'] ?? 0) >= 100 ? 'text-green-700' : 'text-blue-700' }}">
                                        {{ $row['completion_percent'] ?? 0 }}%
                                    </span>
                                    <div class="w-10 bg-gray-200 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-green-500 h-1.5 rounded-full" style="width: {{ $row['completion_percent'] ?? 0 }}%"></div>
                                    </div>
                                </div>
                                @if(($row['remaining'] ?? 0) > 0)
                                    <div class="text-[10px] text-red-600 font-bold mt-0.5">Còn thiếu: {{ $row['remaining'] }} món</div>
                                @endif
                            </td>

                            <!-- Col 3: Status & Timeline 9 Dots -->
                            <td class="px-3.5 py-2.5 align-top">
                                <div class="mb-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold {{ $row['status_color'] ?? 'bg-gray-100' }}">
                                        <i class="{{ $row['status_icon'] ?? 'fas fa-info-circle' }} mr-1 text-[10px]"></i> {{ $row['status_label'] ?? 'N/A' }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-1 py-1">
                                    @foreach(['pr', 'po', 'vendor_confirm', 'production', 'mfg_export', 'transit', 'arrived_vn', 'warehouse_received', 'delivered_sales'] as $stepKey)
                                        @php
                                            $step = $row['timeline'][$stepKey] ?? null;
                                            $status = $step['status'] ?? 'pending';
                                            $dotColor = match($status) {
                                                'completed' => 'bg-green-500 hover:bg-green-600 ring-1 ring-green-200',
                                                'active' => 'bg-blue-500 hover:bg-blue-600 ring-2 ring-blue-200 animate-pulse',
                                                default => 'bg-gray-200 hover:bg-gray-300',
                                            };
                                        @endphp
                                        <div class="relative group cursor-pointer">
                                            <div class="w-2.5 h-2.5 rounded-full {{ $dotColor }} transition"></div>
                                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block z-50 w-48 bg-gray-900 text-white text-[11px] rounded-lg p-2 shadow-xl border border-gray-700 pointer-events-none">
                                                <div class="font-bold flex justify-between border-b border-gray-700 pb-1 mb-1">
                                                    <span>{{ $step['label'] }}</span>
                                                    <span class="text-[9px] uppercase font-bold {{ $status === 'completed' ? 'text-green-400' : ($status === 'active' ? 'text-blue-400' : 'text-gray-400') }}">
                                                        {{ $status === 'completed' ? 'Xong' : ($status === 'active' ? 'Hiện tại' : 'Chờ') }}
                                                    </span>
                                                </div>
                                                @if($step['date'])
                                                    <div class="text-[10px] text-gray-300"><i class="far fa-calendar-alt mr-1"></i>{{ $step['date'] }}</div>
                                                @endif
                                                <div class="text-[10px] text-gray-400 leading-tight mt-0.5">{{ $step['details'] }}</div>
                                            </div>
                                        </div>
                                        @if(!$loop->last)
                                            <div class="w-1 h-0.5 {{ $status === 'completed' ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                                        @endif
                                    @endforeach
                                </div>
                            </td>

                            <!-- Col 4: Milestone Dates (Explicit Labels) -->
                            <td class="px-3.5 py-2.5 align-top whitespace-nowrap text-[11px] space-y-0.5">
                                <div class="flex items-center gap-1 text-purple-700 font-medium" title="ETD: Ngày xuất kho hãng / xong sản xuất">
                                    <span class="text-gray-500 font-semibold text-[10px] w-20 inline-block">Hãng xuất:</span>
                                    <span class="font-bold">{{ $row['etd'] ?? '--' }}</span>
                                </div>
                                <div class="flex items-center gap-1 text-blue-700 font-medium" title="ETA: Ngày dự kiến hàng về đến Việt Nam">
                                    <span class="text-gray-500 font-semibold text-[10px] w-20 inline-block">Dự kiến về:</span>
                                    <span class="font-bold">{{ $row['eta'] ?? '--' }}</span>
                                </div>
                                <div class="flex items-center gap-1 text-green-700 font-medium" title="Actual Arrival: Ngày nhập kho thực tế">
                                    <span class="text-gray-500 font-semibold text-[10px] w-20 inline-block">Về kho thực:</span>
                                    <span class="font-bold">{{ $row['actual_arrival'] ?? '--' }}</span>
                                </div>
                            </td>

                            <!-- Col 5: Shortage Reason & PO Badges -->
                            <td class="px-3.5 py-2.5 align-top">
                                @if(!empty($row['shortage_reason']))
                                    <div class="text-[11px] text-amber-800 bg-amber-50 p-1.5 rounded border border-amber-200 mb-1.5 flex items-start gap-1">
                                        <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5 shrink-0 text-[10px]"></i>
                                        <span class="leading-tight">{{ $row['shortage_reason'] }}</span>
                                    </div>
                                @endif
                                <div class="flex flex-wrap gap-1">
                                    @foreach($row['po_links'] as $po)
                                        <button type="button" 
                                            @click="selectedPo = {{ json_encode($po) }}; showPoModal = true"
                                            class="inline-flex items-center px-1.5 py-0.5 bg-indigo-50 text-indigo-700 rounded text-[11px] font-bold hover:bg-indigo-100 border border-indigo-200 transition gap-1"
                                            title="Click để xem chi tiết PO">
                                            <i class="fas fa-search-plus text-[9px]"></i>
                                            <span>{{ $po['code'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-gray-500">
                                <i class="fas fa-search text-4xl mb-3"></i>
                                <p>Không tìm thấy dữ liệu yêu cầu đặt hàng nào.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())
            <div class="px-4 py-3 border-t">
                {{ $rows->links() }}
            </div>
        @endif

        <!-- PO Quick View Modal -->
        <div x-show="showPoModal" 
             class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             style="display: none;">
            <div class="bg-white rounded-xl shadow-2xl max-w-xl w-full overflow-hidden border border-gray-100" @click.away="showPoModal = false">
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-700 text-white flex justify-between items-center">
                    <div>
                        <h3 class="text-base font-bold flex items-center gap-2">
                            <i class="fas fa-file-invoice"></i>
                            <span>Chi tiết PO: <span x-text="selectedPo?.code"></span></span>
                        </h3>
                        <p class="text-xs text-blue-100 mt-0.5" x-text="'Trạng thái: ' + (selectedPo?.status_label || 'N/A')"></p>
                    </div>
                    <button type="button" @click="showPoModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <!-- Modal Body -->
                <div class="p-6 space-y-4 text-sm text-gray-700">
                    <div class="grid grid-cols-2 gap-3 bg-gray-50 p-3.5 rounded-lg border border-gray-200">
                        <div>
                            <span class="text-[10px] font-bold text-gray-500 uppercase block">Nhà cung cấp (Vendor)</span>
                            <span class="font-bold text-gray-900" x-text="selectedPo?.supplier_name || 'N/A'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-gray-500 uppercase block">Ngày đặt hàng</span>
                            <span class="font-bold text-gray-900" x-text="selectedPo?.order_date || '--'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-gray-500 uppercase block">Số CPQ / Invoice</span>
                            <span class="font-bold text-indigo-600" x-text="selectedPo?.cpq_number || '--'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-gray-500 uppercase block">Tổng tiền USD</span>
                            <span class="font-bold text-green-700" x-text="'$' + (selectedPo?.total_usd || '0.00')"></span>
                        </div>
                    </div>

                    <!-- Tracking & Timeline Dates -->
                    <div class="border border-indigo-100 bg-indigo-50/40 p-3.5 rounded-lg">
                        <h4 class="text-xs font-bold text-indigo-900 uppercase mb-2.5 flex items-center gap-1.5">
                            <i class="fas fa-shipping-fast text-indigo-600"></i> Theo dõi vận chuyển & Mốc thời gian
                        </h4>
                        <div class="grid grid-cols-3 gap-2.5 text-center">
                            <div class="bg-white p-2 rounded border border-gray-200 shadow-sm">
                                <div class="text-[9px] text-gray-500 font-bold uppercase">ETD (Xuất kho hãng)</div>
                                <div class="text-xs font-bold text-purple-700 mt-0.5" x-text="selectedPo?.etd || '--'"></div>
                            </div>
                            <div class="bg-white p-2 rounded border border-gray-200 shadow-sm">
                                <div class="text-[9px] text-gray-500 font-bold uppercase">ETA (Dự kiến về VN)</div>
                                <div class="text-xs font-bold text-blue-700 mt-0.5" x-text="selectedPo?.eta || '--'"></div>
                            </div>
                            <div class="bg-white p-2 rounded border border-gray-200 shadow-sm">
                                <div class="text-[9px] text-gray-500 font-bold uppercase">Về kho thực tế</div>
                                <div class="text-xs font-bold text-green-700 mt-0.5" x-text="selectedPo?.actual_delivery || '--'"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes & Hold Reason if any -->
                    <template x-if="selectedPo?.hold_reason">
                        <div class="bg-red-50 text-red-800 p-2.5 rounded-lg border border-red-200 text-xs">
                            <span class="font-bold"><i class="fas fa-exclamation-triangle mr-1"></i>Lý do hoãn (Hold):</span>
                            <span x-text="selectedPo?.hold_reason"></span>
                        </div>
                    </template>
                    <template x-if="selectedPo?.note">
                        <div class="bg-gray-50 text-gray-700 p-2.5 rounded-lg border border-gray-200 text-xs">
                            <span class="font-bold block mb-0.5">Ghi chú PO:</span>
                            <span x-text="selectedPo?.note"></span>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
                    <button type="button" @click="showPoModal = false" class="px-4 py-1.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-xs font-bold transition">
                        Đóng
                    </button>
                    <template x-if="selectedPo?.id">
                        <a :href="'/purchase-orders/' + selectedPo.id" target="_blank" class="px-4 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-xs font-bold transition flex items-center gap-1.5">
                            <span>Mở trang PO đầy đủ</span>
                            <i class="fas fa-external-link-alt text-[10px]"></i>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
