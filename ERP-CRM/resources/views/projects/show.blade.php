@extends('layouts.app')

@section('title', 'Chi tiết dự án')
@section('page-title', "Chi tiết dự án: {$project->code}")

@section('content')
<div class="space-y-4">
    <!-- Actions -->
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('projects.index') }}" 
           class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại
        </a>
        <a href="{{ route('projects.edit', $project->id) }}" 
           class="inline-flex items-center px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors">
            <i class="fas fa-edit mr-2"></i> Sửa
        </a>
        <a href="{{ route('sales.create', ['project_id' => $project->id]) }}" 
           class="inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
            <i class="fas fa-plus mr-2"></i> Tạo đơn hàng
        </a>
    </div>

    <!-- Project Info -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Basic Info -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Thông tin dự án</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm text-gray-500">Mã dự án</dt>
                    <dd class="font-medium text-gray-900">{{ $project->code }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Trạng thái</dt>
                    <dd>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $project->status_color }}">
                            {{ $project->status_label }}
                        </span>
                    </dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-sm text-gray-500">Tên dự án</dt>
                    <dd class="font-medium text-gray-900">{{ $project->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Khách hàng / Chủ đầu tư</dt>
                    <dd class="text-gray-900">{{ $project->customer_name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Người quản lý</dt>
                    <dd class="text-gray-900">{{ $project->manager?->name ?? '-' }}</dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-sm text-gray-500">Địa chỉ</dt>
                    <dd class="text-gray-900">{{ $project->address ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Ngày bắt đầu</dt>
                    <dd class="text-gray-900">{{ $project->start_date?->format('d/m/Y') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Ngày kết thúc dự kiến</dt>
                    <dd class="text-gray-900">{{ $project->end_date?->format('d/m/Y') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Dự toán / Ngân sách</dt>
                    <dd class="font-medium text-gray-900">{{ number_format($project->budget) }} đ</dd>
                </div>
                @if($project->description)
                <div class="md:col-span-2">
                    <dt class="text-sm text-gray-500">Mô tả</dt>
                    <dd class="text-gray-900">{{ $project->description }}</dd>
                </div>
                @endif
                @if($project->note)
                <div class="md:col-span-2">
                    <dt class="text-sm text-gray-500">Ghi chú</dt>
                    <dd class="text-gray-900">{{ $project->note }}</dd>
                </div>
                @endif
            </div>
        </div>

        <!-- Statistics -->
        <div class="space-y-4">
            <!-- Revenue Card -->
            <div class="bg-blue-50 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-blue-600">Doanh thu</p>
                        <p class="text-2xl font-bold text-blue-700">{{ number_format($salesStats['total_revenue']) }} đ</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-coins text-blue-600 text-xl"></i>
                    </div>
                </div>
                <p class="text-xs text-blue-600 mt-2">{{ $salesStats['total_orders'] }} đơn hàng</p>
            </div>

            <!-- Cost Card -->
            <div class="bg-orange-50 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-orange-600">Giá vốn</p>
                        <p class="text-2xl font-bold text-orange-700">{{ number_format($salesStats['total_cost']) }} đ</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-box text-orange-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Profit Card -->
            <div class="{{ $salesStats['profit'] >= 0 ? 'bg-green-50' : 'bg-red-50' }} rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm {{ $salesStats['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">Lợi nhuận</p>
                        <p class="text-2xl font-bold {{ $salesStats['profit'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                            {{ number_format($salesStats['profit']) }} đ
                        </p>
                    </div>
                    <div class="w-12 h-12 {{ $salesStats['profit'] >= 0 ? 'bg-green-100' : 'bg-red-100' }} rounded-full flex items-center justify-center">
                        <i class="fas fa-chart-line {{ $salesStats['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }} text-xl"></i>
                    </div>
                </div>
                <p class="text-xs {{ $salesStats['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-2">
                    {{ number_format($salesStats['profit_percent'], 2) }}% margin
                </p>
            </div>

            <!-- Debt Card -->
            @if($salesStats['total_debt'] > 0)
            <div class="bg-red-50 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-red-600">Công nợ</p>
                        <p class="text-2xl font-bold text-red-700">{{ number_format($salesStats['total_debt']) }} đ</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-file-invoice-dollar text-red-600 text-xl"></i>
                    </div>
                </div>
            </div>
            @endif

            <!-- Budget vs Actual -->
            @if($project->budget > 0)
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 mb-2">Dự toán vs Thực tế</p>
                @php
                    $budgetPercent = min(($salesStats['total_revenue'] / $project->budget) * 100, 100);
                @endphp
                <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                    <div class="h-3 rounded-full {{ $budgetPercent >= 100 ? 'bg-green-500' : 'bg-blue-500' }}" 
                         style="width: {{ $budgetPercent }}%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500">
                    <span>{{ number_format($salesStats['total_revenue']) }} đ</span>
                    <span>{{ number_format($budgetPercent, 1) }}%</span>
                    <span>{{ number_format($project->budget) }} đ</span>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Export Materials Section -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Vật tư đã xuất cho dự án</h3>
                <p class="text-sm text-gray-500 mt-1">
                    Tổng giá trị: <span class="font-semibold text-orange-600">{{ number_format($exportStats['total_export_value']) }} đ</span>
                    <span class="text-gray-400 mx-2">|</span>
                    {{ $exportStats['total_exports'] }} phiếu xuất
                </p>
            </div>
            <a href="{{ route('exports.index', ['project_id' => $project->id]) }}" class="text-sm text-primary hover:underline">
                Xem tất cả <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-8"></th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã phiếu</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày xuất</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho xuất</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentExports as $index => $export)
                    <!-- Export Row -->
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <button type="button" onclick="toggleExportDetails({{ $index }})" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-chevron-right transition-transform" id="icon-{{ $index }}"></i>
                            </button>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('exports.show', $export->id) }}" class="font-medium text-orange-600 hover:text-orange-800 hover:underline">
                                {{ $export->code }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $export->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $export->warehouse->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 text-sm font-semibold bg-orange-100 text-orange-800 rounded">
                                {{ number_format($export->total_qty) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($export->status === 'pending')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Chờ xử lý</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Hoàn thành</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('exports.show', $export->id) }}" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <!-- Product Details Row (Hidden by default) -->
                    <tr id="details-{{ $index }}" class="hidden bg-gray-50">
                        <td colspan="7" class="px-4 py-4">
                            <div class="bg-white rounded-lg border border-gray-200 p-4">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                                    <i class="fas fa-boxes text-orange-500 mr-2"></i>
                                    Chi tiết sản phẩm
                                </h4>
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Mã SP</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tên sản phẩm</th>
                                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">SL</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Serial</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ghi chú</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach($export->items as $item)
                                            @php
                                                // Get serials based on status
                                                $serialsWithSku = collect();
                                                $noSkuCount = 0;
                                                
                                                if ($export->status === 'completed') {
                                                    $exportedSerials = \App\Models\ProductItem::where('export_id', $export->id)
                                                        ->where('product_id', $item->product_id)
                                                        ->get();
                                                    $serialsWithSku = $exportedSerials->filter(fn($pi) => !str_starts_with($pi->sku, 'NOSKU'));
                                                    $noSkuCount = $exportedSerials->filter(fn($pi) => str_starts_with($pi->sku, 'NOSKU'))->count();
                                                } else {
                                                    if (!empty($item->serial_number)) {
                                                        $productItemIds = json_decode($item->serial_number, true);
                                                        if (is_array($productItemIds) && !empty($productItemIds)) {
                                                            $serialsWithSku = \App\Models\ProductItem::whereIn('id', $productItemIds)->get();
                                                        }
                                                    }
                                                    $noSkuCount = $item->quantity - $serialsWithSku->count();
                                                }
                                                
                                                // Tách từng serial thành dòng riêng
                                                $totalRows = max($serialsWithSku->count(), 1);
                                                if ($noSkuCount > 0) {
                                                    $totalRows += $noSkuCount;
                                                }
                                            @endphp
                                            
                                            @if($serialsWithSku->count() > 0)
                                                @foreach($serialsWithSku as $serialIndex => $serial)
                                                <tr class="hover:bg-gray-50">
                                                    @if($serialIndex === 0)
                                                    <td class="px-3 py-2 align-top" rowspan="{{ $totalRows }}">
                                                        <span class="font-mono text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">
                                                            {{ $item->product->code }}
                                                        </span>
                                                    </td>
                                                    <td class="px-3 py-2 text-sm text-gray-900 align-top" rowspan="{{ $totalRows }}">
                                                        {{ $item->product->name }}
                                                    </td>
                                                    <td class="px-3 py-2 text-center align-top" rowspan="{{ $totalRows }}">
                                                        <span class="px-2 py-1 text-xs font-bold bg-orange-100 text-orange-800 rounded-full">
                                                            {{ number_format($item->quantity) }}
                                                        </span>
                                                    </td>
                                                    @endif
                                                    <td class="px-3 py-2">
                                                        <span class="px-2 py-0.5 text-xs font-mono bg-blue-100 text-blue-700 rounded">
                                                            {{ $serial->sku }}
                                                        </span>
                                                    </td>
                                                    @if($serialIndex === 0)
                                                    <td class="px-3 py-2 text-xs text-gray-500 align-top" rowspan="{{ $totalRows }}">
                                                        {{ $item->comments ?: '-' }}
                                                    </td>
                                                    @endif
                                                </tr>
                                                @endforeach
                                                
                                                @if($noSkuCount > 0)
                                                    @for($i = 0; $i < $noSkuCount; $i++)
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-3 py-2">
                                                            <span class="text-xs text-gray-400 italic">Không serial</span>
                                                        </td>
                                                    </tr>
                                                    @endfor
                                                @endif
                                            @else
                                                {{-- Không có serial nào --}}
                                                @if($noSkuCount > 0)
                                                    @for($i = 0; $i < $noSkuCount; $i++)
                                                    <tr class="hover:bg-gray-50">
                                                        @if($i === 0)
                                                        <td class="px-3 py-2 align-top" rowspan="{{ $noSkuCount }}">
                                                            <span class="font-mono text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">
                                                                {{ $item->product->code }}
                                                            </span>
                                                        </td>
                                                        <td class="px-3 py-2 text-sm text-gray-900 align-top" rowspan="{{ $noSkuCount }}">
                                                            {{ $item->product->name }}
                                                        </td>
                                                        <td class="px-3 py-2 text-center align-top" rowspan="{{ $noSkuCount }}">
                                                            <span class="px-2 py-1 text-xs font-bold bg-orange-100 text-orange-800 rounded-full">
                                                                {{ number_format($item->quantity) }}
                                                            </span>
                                                        </td>
                                                        @endif
                                                        <td class="px-3 py-2">
                                                            <span class="text-xs text-gray-400 italic">Không serial</span>
                                                        </td>
                                                        @if($i === 0)
                                                        <td class="px-3 py-2 text-xs text-gray-500 align-top" rowspan="{{ $noSkuCount }}">
                                                            {{ $item->comments ?: '-' }}
                                                        </td>
                                                        @endif
                                                    </tr>
                                                    @endfor
                                                @else
                                                    {{-- Không có gì cả --}}
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-3 py-2">
                                                            <span class="font-mono text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">
                                                                {{ $item->product->code }}
                                                            </span>
                                                        </td>
                                                        <td class="px-3 py-2 text-sm text-gray-900">{{ $item->product->name }}</td>
                                                        <td class="px-3 py-2 text-center">
                                                            <span class="px-2 py-1 text-xs font-bold bg-orange-100 text-orange-800 rounded-full">
                                                                {{ number_format($item->quantity) }}
                                                            </span>
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <span class="text-xs text-gray-400">-</span>
                                                        </td>
                                                        <td class="px-3 py-2 text-xs text-gray-500">
                                                            {{ $item->comments ?: '-' }}
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>Chưa có phiếu xuất vật tư nào cho dự án này</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function toggleExportDetails(index) {
        const detailsRow = document.getElementById('details-' + index);
        const icon = document.getElementById('icon-' + index);
        
        if (detailsRow.classList.contains('hidden')) {
            detailsRow.classList.remove('hidden');
            icon.classList.add('rotate-90');
        } else {
            detailsRow.classList.add('hidden');
            icon.classList.remove('rotate-90');
        }
    }
    </script>

    <!-- Recent Sales -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900">Đơn hàng của dự án</h3>
            <a href="{{ route('sales.index', ['project_id' => $project->id]) }}" class="text-sm text-primary hover:underline">
                Xem tất cả <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã đơn</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng tiền</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Công nợ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentSales as $sale)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('sales.show', $sale->id) }}" class="font-medium text-primary hover:underline">
                                {{ $sale->code }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $sale->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium">{{ number_format($sale->total) }} đ</td>
                        <td class="px-4 py-3 text-sm text-right {{ $sale->debt_amount > 0 ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                            {{ number_format($sale->debt_amount) }} đ
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $sale->status_color }}">
                                {{ $sale->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('sales.show', $sale->id) }}" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            Chưa có đơn hàng nào cho dự án này
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
